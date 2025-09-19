<?php
session_start();
header('Content-Type: application/json');
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

// CONFIGURAÇÃO DE LOGS - ALTERE AQUI PARA ATIVAR/DESATIVAR
define('DEBUG_MODE', false); // true = logs ativos | false = logs desativados
define('LOG_FILE', 'logs.txt');

// Função para gravar logs apenas se DEBUG_MODE estiver ativo
function writeLog($message)
{
    if (DEBUG_MODE) {
        file_put_contents(LOG_FILE, date('d/m/Y H:i:s') . " - " . $message . PHP_EOL, FILE_APPEND);
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Método não permitido']);
    exit;
}

$rawInput = file_get_contents('php://input');
$data = json_decode($rawInput, true);

writeLog("PAYLOAD GATEWAY PRÓPRIO: " . print_r($data, true));
writeLog("----------------------------------------------------------");

if (!$data) {
    http_response_code(400);
    echo json_encode(['error' => 'Payload inválido']);
    exit;
}

$transactionId = $data['id'] ?? '';
$status = $data['status'] ?? '';

if ($status !== 'PAID' || empty($transactionId)) {
    http_response_code(400);
    echo json_encode(['error' => 'Dados insuficientes ou transação não paga']);
    exit;
}

require_once __DIR__ . '/../conexao.php';

try {
    $pdo->beginTransaction();

    writeLog("INICIANDO PROCESSO PARA TXN: " . $transactionId);

    $stmt = $pdo->prepare("SELECT id, user_id, valor, status FROM depositos WHERE transactionId = :txid AND gateway = 'gatewayproprio' LIMIT 1 FOR UPDATE");
    $stmt->execute([':txid' => $transactionId]);
    $deposito = $stmt->fetch();

    if (!$deposito) {
        $pdo->commit();
        writeLog("ERRO: Depósito não encontrado para TXN: " . $transactionId);
        http_response_code(404);
        echo json_encode(['error' => 'Depósito não encontrado']);
        exit;
    }

    writeLog("DEPÓSITO ENCONTRADO: " . print_r($deposito, true));

    if ($deposito['status'] === 'PAID') {
        $pdo->commit();
        echo json_encode(['message' => 'Este pagamento já foi aprovado']);
        exit;
    }

    // Atualiza o status do depósito
    $stmt = $pdo->prepare("UPDATE depositos SET status = 'PAID', updated_at = NOW() WHERE id = :id");
    $stmt->execute([':id' => $deposito['id']]);
    writeLog("DEPÓSITO ATUALIZADO PARA PAID");

    // --- INÍCIO DA LÓGICA DE BÔNUS E ATUALIZAÇÃO DE SALDO ---

    $user_id = $deposito['user_id'];
    $valor_do_deposito = $deposito['valor'];

    // 1. Busca os dados do usuário para verificar se ele pode receber o bônus
    $user_query = $pdo->prepare("SELECT recebeu_bonus FROM usuarios WHERE id = ?");
    $user_query->execute([$user_id]);
    $user_data = $user_query->fetch(PDO::FETCH_ASSOC);

    // 2. Busca as configurações de bônus
    $bonus_query = $pdo->prepare("SELECT * FROM bonus_settings WHERE id = 1");
    $bonus_query->execute();
    $bonus_settings = $bonus_query->fetch(PDO::FETCH_ASSOC);

    // 3. Verifica as condições para dar o bônus
    if ($bonus_settings && $bonus_settings['is_active'] && $user_data['recebeu_bonus'] == 0 && $valor_do_deposito >= $bonus_settings['min_deposit'] && $deposito['solicitou_bonus'] == 1) {

        $bonus_value = $valor_do_deposito * ($bonus_settings['bonus_amount'] / 100);
        $rollover_total = ($valor_do_deposito + $bonus_value) * $bonus_settings['rollover_multiplier'];

        // 4. Dá o bônus e MARCA que o usuário já recebeu
        $update_user_stmt = $pdo->prepare(
            "UPDATE usuarios SET 
          saldo_bonus = saldo_bonus + ?, 
          rollover_pending = ?, 
          recebeu_bonus = 1 
       WHERE id = ?"
        );
        $update_user_stmt->execute([$bonus_value, $rollover_total, $user_id]);
        writeLog("BÔNUS APLICADO: R$ " . $bonus_value . " para usuário " . $user_id);
    }

    // 5. Adiciona o valor do depósito ao saldo REAL do usuário
    $update_saldo_stmt = $pdo->prepare("UPDATE usuarios SET saldo = saldo + ? WHERE id = ?");
    $update_saldo_stmt->execute([$valor_do_deposito, $user_id]);
    writeLog("SALDO REAL CREDITADO: R$ " . $valor_do_deposito . " para usuário " . $user_id);

    // --- FIM DA LÓGICA DE BÔNUS ---

    // VERIFICAÇÃO PARA CPA (APENAS PRIMEIRO DEPÓSITO)
    $stmt = $pdo->prepare("SELECT indicacao FROM usuarios WHERE id = :uid");
    $stmt->execute([':uid' => $deposito['user_id']]);
    $usuario = $stmt->fetch();

    writeLog("USUÁRIO DATA: " . print_r($usuario, true));

    if ($usuario && !empty($usuario['indicacao'])) {
        writeLog("USUÁRIO TEM INDICAÇÃO: " . $usuario['indicacao']);

        // Verifica se este usuário JÁ teve algum depósito aprovado anteriormente
        $stmt = $pdo->prepare("SELECT COUNT(*) as total_pagos FROM depositos WHERE user_id = :uid AND status = 'PAID' AND id != :current_id");
        $stmt->execute([
            ':uid' => $deposito['user_id'],
            ':current_id' => $deposito['id']
        ]);
        $depositosAnteriores = $stmt->fetch();

        writeLog("DEPÓSITOS ANTERIORES PAGOS: " . $depositosAnteriores['total_pagos']);

        // CPA só é pago se este for o PRIMEIRO depósito aprovado do usuário
        if ($depositosAnteriores['total_pagos'] == 0) {
            writeLog("É O PRIMEIRO DEPÓSITO, VERIFICANDO AFILIADO");

            $stmt = $pdo->prepare("SELECT id, comissao_cpa, banido FROM usuarios WHERE id = :afiliado_id");
            $stmt->execute([':afiliado_id' => $usuario['indicacao']]);
            $afiliado = $stmt->fetch();

            writeLog("AFILIADO DATA: " . print_r($afiliado, true));

            if ($afiliado && $afiliado['banido'] != 1 && !empty($afiliado['comissao_cpa'])) {
                $comissao = $afiliado['comissao_cpa'];

                // Credita a comissão CPA para o afiliado
                $stmt = $pdo->prepare("UPDATE usuarios SET saldo = saldo + :comissao WHERE id = :afiliado_id");
                $stmt->execute([
                    ':comissao' => $comissao,
                    ':afiliado_id' => $afiliado['id']
                ]);

                // Tenta inserir na tabela transacoes_afiliados (removendo o campo 'tipo' caso não exista)
                try {
                    $stmt = $pdo->prepare("INSERT INTO transacoes_afiliados
                                          (afiliado_id, usuario_id, deposito_id, valor, created_at)
                                          VALUES (:afiliado_id, :usuario_id, :deposito_id, :valor, NOW())");
                    $stmt->execute([
                        ':afiliado_id' => $afiliado['id'],
                        ':usuario_id' => $deposito['user_id'],
                        ':deposito_id' => $deposito['id'],
                        ':valor' => $comissao
                    ]);
                } catch (Exception $insertError) {
                    writeLog("ERRO AO INSERIR TRANSAÇÃO AFILIADO: " . $insertError->getMessage());
                }

                writeLog("CPA PAGO: Afiliado {$afiliado['id']} recebeu R$ {$comissao} pelo primeiro depósito do usuário {$deposito['user_id']}");
            } else {
                writeLog("CPA NÃO PAGO: Afiliado inválido ou sem comissão");
            }
        } else {
            writeLog("CPA NÃO PAGO: Usuário {$deposito['user_id']} já teve {$depositosAnteriores['total_pagos']} depósito(s) aprovado(s) anteriormente");
        }
    } else {
        writeLog("USUÁRIO SEM INDICAÇÃO");
    }

    $pdo->commit();
    writeLog("TRANSAÇÃO FINALIZADA COM SUCESSO");
    dispararWebhookDepositoAprovado($deposito);
    echo json_encode(['message' => 'OK']);
} catch (Exception $e) {
    $pdo->rollBack();
    writeLog("ERRO GERAL: " . $e->getMessage());
    writeLog("STACK TRACE: " . $e->getTraceAsString());
    http_response_code(500);
    echo json_encode(['error' => 'Erro interno']);
    exit;
}
