<?php
session_start();
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Método não permitido']);
    exit;
}

sleep(2);

$amount = isset($_POST['amount']) ? (float)str_replace(',', '.', str_replace('.', '', str_replace('R$ ', '', $_POST['amount']))) : 0;
$cpf = isset($_POST['cpf']) ? preg_replace('/\D/', '', $_POST['cpf']) : '';
$quer_bonus = isset($_POST['quer_bonus']) && $_POST['quer_bonus'] === 'true' ? 1 : 0;

if ($amount <= 0 || strlen($cpf) !== 11) {
    http_response_code(400);
    echo json_encode(['error' => 'Dados inválidos']);
    exit;
}

require_once __DIR__ . '/../conexao.php';
require_once __DIR__ . '/../classes/DigitoPay.php';
require_once __DIR__ . '/../classes/GatewayProprio.php';

try {
    // Verificar gateway ativo
    $stmt = $pdo->query("SELECT active FROM gateway LIMIT 1");
    $activeGateway = $stmt->fetchColumn();

    if (!in_array($activeGateway, ['pixup', 'digitopay', 'gatewayproprio'])) {
        throw new Exception('Gateway não configurado ou não suportado.');
    }

    // Verificar autenticação do usuário
    if (!isset($_SESSION['usuario_id'])) {
        throw new Exception('Usuário não autenticado.');
    }

    $usuario_id = $_SESSION['usuario_id'];

    // Buscar dados do usuário
    $stmt = $pdo->prepare("SELECT nome, email FROM usuarios WHERE id = :id LIMIT 1");
    $stmt->bindParam(':id', $usuario_id, PDO::PARAM_INT);
    $stmt->execute();
    $usuario = $stmt->fetch();

    if (!$usuario) {
        throw new Exception('Usuário não encontrado.');
    }

    // Configurar URLs base
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
    $host = $_SERVER['HTTP_HOST'];
    $base = $protocol . $host;

    $external_id = uniqid();
    $idempotencyKey = uniqid() . '-' . time();

    if ($activeGateway === 'pixup') {
        // ===== PROCESSAR COM PIXUP =====
        $stmt = $pdo->query("SELECT url, ci, cs FROM pixup LIMIT 1");
        $pixup = $stmt->fetch();

        if (!$pixup) {
            throw new Exception('Credenciais PIXUP não encontradas.');
        }

        $url = rtrim($pixup['url'], '/');
        $ci = $pixup['ci'];
        $cs = $pixup['cs'];

        // Autenticação PixUp
        $authHeader = base64_encode("$ci:$cs");
        $ch = curl_init("$url/v2/oauth/token");
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => [
                "Authorization: Basic $authHeader"
            ]
        ]);
        $response = curl_exec($ch);
        curl_close($ch);

        $authData = json_decode($response, true);
        if (!isset($authData['access_token'])) {
            throw new Exception('Falha ao obter access_token da PIXUP.');
        }

        $accessToken = $authData['access_token'];
        $postbackUrl = $base . '/callback/pixup.php';

        $payload = [
            'amount' => number_format($amount, 2, '.', ''),
            'external_id' => $external_id,
            'postbackUrl' => $postbackUrl,
            'payerQuestion' => 'Pagamento Raspadinha',
            'payer' => [
                'name' => $usuario['nome'],
                'document' => $cpf,
                'email' => $usuario['email']
            ]
        ];

        $ch = curl_init("$url/v2/pix/qrcode");
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($payload),
            CURLOPT_HTTPHEADER => [
                "Authorization: Bearer $accessToken",
                "Content-Type: application/json"
            ]
        ]);
        $response = curl_exec($ch);
        curl_close($ch);

        $pixData = json_decode($response, true);

        if (!isset($pixData['transactionId'], $pixData['qrcode'])) {
            throw new Exception('Falha ao gerar QR Code.');
        }

        // Salvar no banco
        $stmt = $pdo->prepare("
    INSERT INTO depositos (transactionId, user_id, nome, cpf, valor, solicitou_bonus, status, qrcode, gateway, idempotency_key)
    VALUES (:transactionId, :user_id, :nome, :cpf, :valor, :solicitou_bonus, 'PENDING', :qrcode, 'pixup', :idempotency_key)
");

        $stmt->execute([
            ':transactionId' => $pixData['transactionId'],
            ':user_id' => $usuario_id,
            ':nome' => $usuario['nome'],
            ':cpf' => $cpf,
            ':valor' => $amount,
            ':solicitou_bonus' => $quer_bonus,
            ':qrcode' => $pixData['qrcode'],
            ':idempotency_key' => $external_id
        ]);

        $_SESSION['transactionId'] = $pixData['transactionId'];

        echo json_encode([
            'qrcode' => $pixData['qrcode'],
            'gateway' => 'pixup'
        ]);
    } elseif ($activeGateway === 'digitopay') {
        // ===== PROCESSAR COM DIGITOPAY =====
        $digitoPay = new DigitoPay($pdo);

        $callbackUrl = $base . '/callback/digitopay.php';

        $depositData = $digitoPay->createDeposit(
            $amount,
            $cpf,
            $usuario['nome'],
            $usuario['email'],
            $callbackUrl,
            $idempotencyKey
        );

        // Salvar no banco
        $stmt = $pdo->prepare("
    INSERT INTO depositos (transactionId, user_id, nome, cpf, valor, solicitou_bonus, status, qrcode, gateway, idempotency_key)
    VALUES (:transactionId, :user_id, :nome, :cpf, :valor, :solicitou_bonus, 'PENDING', :qrcode, 'digitopay', :idempotency_key)
");

        $stmt->execute([
            ':transactionId' => $depositData['transactionId'],
            ':user_id' => $usuario_id,
            ':nome' => $usuario['nome'],
            ':cpf' => $cpf,
            ':valor' => $amount,
            ':solicitou_bonus' => $quer_bonus,
            ':qrcode' => $depositData['qrcode'],
            ':idempotency_key' => $depositData['idempotencyKey']
        ]);

        $_SESSION['transactionId'] = $depositData['transactionId'];

        echo json_encode([
            'qrcode' => $depositData['qrcode'],
            'gateway' => 'digitopay'
        ]);
    } elseif ($activeGateway === 'gatewayproprio') {
        // ===== PROCESSAR COM GATEWAY PRÓPRIO =====
        $gatewayProprio = new GatewayProprio($pdo);

        $callbackUrl = $base . '/callback/gatewayproprio.php';

        $depositData = $gatewayProprio->createDeposit(
            $amount,
            $cpf,
            $usuario['nome'],
            $usuario['email'],
            $callbackUrl,
            $idempotencyKey
        );

        // Salvar no banco
        $stmt = $pdo->prepare("
    INSERT INTO depositos (transactionId, user_id, nome, cpf, valor, solicitou_bonus, status, qrcode, gateway, idempotency_key)
    VALUES (:transactionId, :user_id, :nome, :cpf, :valor, :solicitou_bonus, 'PENDING', :qrcode, 'gatewayproprio', :idempotency_key)
");

        $stmt->execute([
            ':transactionId' => $depositData['transactionId'],
            ':user_id' => $usuario_id,
            ':nome' => $usuario['nome'],
            ':cpf' => $cpf,
            ':valor' => $amount,
            ':solicitou_bonus' => $quer_bonus,
            ':qrcode' => $depositData['qrcode'],
            ':idempotency_key' => $depositData['idempotencyKey']
        ]);

        $_SESSION['transactionId'] = $depositData['transactionId'];

        echo json_encode([
            'qrcode' => $depositData['qrcode'],
            'gateway' => 'gatewayproprio'
        ]);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
    exit;
}
