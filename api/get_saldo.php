<?php
// /api/get_saldo.php
@session_start();
header('Content-Type: application/json');

// Inclui a conexão com o banco de dados de forma segura
if (file_exists('../conexao.php')) {
    require_once '../conexao.php';
} else {
    echo json_encode(['success' => false, 'error' => 'Configuração de banco de dados não encontrada.']);
    exit;
}

if (!isset($_SESSION['usuario_id'])) {
    echo json_encode(['success' => false, 'error' => 'Usuário não logado.']);
    exit;
}

$usuario_id = $_SESSION['usuario_id'];

try {
    $stmt = $pdo->prepare("SELECT saldo, saldo_bonus FROM usuarios WHERE id = :id");
    $stmt->bindParam(':id', $usuario_id, PDO::PARAM_INT);
    $stmt->execute();
    $saldos = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($saldos) {
        $saldo_real = (float)($saldos['saldo'] ?? 0);
        $saldo_bonus = (float)($saldos['saldo_bonus'] ?? 0);
        $saldo_total = $saldo_real + $saldo_bonus;

        echo json_encode([
            'success' => true,
            'saldo_real' => number_format($saldo_real, 2, ',', '.'),
            'saldo_bonus' => number_format($saldo_bonus, 2, ',', '.'),
            'saldo_total' => number_format($saldo_total, 2, ',', '.')
        ]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Usuário não encontrado.']);
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => 'Erro ao consultar o banco de dados.']);
}
