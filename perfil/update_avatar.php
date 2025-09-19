<?php
@session_start();

// Garante que a conexão com o banco de dados seja estabelecida
if (file_exists('../conexao.php')) {
    include('../conexao.php');
} elseif (file_exists('../../conexao.php')) {
    include('../../conexao.php');
}

// Verifica se o usuário está logado e se a requisição é do tipo POST
if (!isset($_SESSION['usuario_id']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(403); // Proibido
    echo json_encode(['success' => false, 'message' => 'Acesso negado.']);
    exit;
}

// Pega o caminho do avatar enviado via POST
$data = json_decode(file_get_contents('php://input'), true);
$new_avatar_path = $data['avatar'] ?? null;

if (empty($new_avatar_path)) {
    http_response_code(400); // Requisição inválida
    echo json_encode(['success' => false, 'message' => 'Nenhum avatar selecionado.']);
    exit;
}

// Extrai apenas o caminho relativo a partir de 'assets/'
$assets_pos = strpos($new_avatar_path, 'assets/avatar/');
if ($assets_pos !== false) {
    $relative_path = substr($new_avatar_path, $assets_pos);
} else {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Caminho do avatar inválido.']);
    exit;
}


$usuario_id = $_SESSION['usuario_id'];

try {
    // Prepara a query para atualizar o avatar do usuário
    $stmt = $pdo->prepare("UPDATE usuarios SET avatar = :avatar WHERE id = :id");
    $stmt->bindParam(':avatar', $relative_path, PDO::PARAM_STR);
    $stmt->bindParam(':id', $usuario_id, PDO::PARAM_INT);

    // Executa a query e verifica se foi bem-sucedida
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'new_avatar_url' => '../' . $relative_path]);
    } else {
        http_response_code(500); // Erro interno do servidor
        echo json_encode(['success' => false, 'message' => 'Erro ao atualizar o avatar no banco de dados.']);
    }
} catch (PDOException $e) {
    http_response_code(500);
    error_log("Erro ao atualizar avatar: " . $e->getMessage()); // Log do erro
    echo json_encode(['success' => false, 'message' => 'Ocorreu um erro no servidor.']);
}