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

if (!isset($_FILES['avatar']) || $_FILES['avatar']['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['success' => false, 'message' => 'Erro no upload do arquivo.']);
    exit;
}

$file = $_FILES['avatar'];

// Validação do arquivo
$allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
if (!in_array($file['type'], $allowed_types)) {
    echo json_encode(['success' => false, 'message' => 'Tipo de arquivo inválido. Apenas JPG, PNG e GIF são permitidos.']);
    exit;
}

if ($file['size'] > 2 * 1024 * 1024) { // 2MB
    echo json_encode(['success' => false, 'message' => 'O arquivo é muito grande. O tamanho máximo é 2MB.']);
    exit;
}

// Move o arquivo para o diretório de avatars
$upload_dir = '../assets/avatar/';
$file_extension = pathinfo($file['name'], PATHINFO_EXTENSION);
$new_filename = uniqid('avatar_', true) . '.' . $file_extension;
$upload_path = $upload_dir . $new_filename;

if (!move_uploaded_file($file['tmp_name'], $upload_path)) {
    echo json_encode(['success' => false, 'message' => 'Erro ao mover o arquivo enviado.']);
    exit;
}

$relative_path = 'assets/avatar/' . $new_filename;
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
