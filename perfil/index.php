<?php
@session_start();

if (file_exists('./conexao.php')) {
    include('./conexao.php');
} elseif (file_exists('../conexao.php')) {
    include('../conexao.php');
} elseif (file_exists('../../conexao.php')) {
    include('../../conexao.php');
}

if (!isset($_SESSION['usuario_id'])) {
    $_SESSION['message'] = ['type' => 'warning', 'text' => 'Você precisa estar logado para acessar esta página!'];
    header("Location: /login");
    exit;
}

$usuario_id = $_SESSION['usuario_id'];

try {
    $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE id = :id LIMIT 1");
    $stmt->bindParam(':id', $usuario_id, PDO::PARAM_INT);
    $stmt->execute();
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$usuario) {
        $_SESSION['message'] = ['type' => 'failure', 'text' => 'Usuário não encontrado!'];
        header("Location: /login");
        exit;
    }

    $stmt_depositos = $pdo->prepare("SELECT SUM(valor) as total_depositado FROM depositos WHERE user_id = :user_id AND status = 'PAID'");
    $stmt_depositos->bindParam(':user_id', $usuario_id, PDO::PARAM_INT);
    $stmt_depositos->execute();
    $total_depositado = $stmt_depositos->fetch(PDO::FETCH_ASSOC)['total_depositado'] ?? 0;

    $stmt_saques = $pdo->prepare("SELECT SUM(valor) as total_sacado FROM saques WHERE user_id = :user_id AND status = 'PAID'");
    $stmt_saques->bindParam(':user_id', $usuario_id, PDO::PARAM_INT);
    $stmt_saques->execute();
    $total_sacado = $stmt_saques->fetch(PDO::FETCH_ASSOC)['total_sacado'] ?? 0;
} catch (PDOException $e) {
    $_SESSION['message'] = ['type' => 'failure', 'text' => 'Erro ao carregar dados do usuário!'];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $senha_atual = $_POST['senha_atual'] ?? '';
    $nome = trim($_POST['nome'] ?? '');
    $telefone = trim($_POST['telefone'] ?? '');
    $cpf = trim($_POST['cpf'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $nova_senha = $_POST['nova_senha'] ?? '';
    $confirmar_senha = $_POST['confirmar_senha'] ?? '';

    if (!password_verify($senha_atual, $usuario['senha'])) {
        $_SESSION['message'] = ['type' => 'failure', 'text' => 'Senha atual incorreta!'];
        header("Location: /perfil");
        exit;
    } else {
        try {
            $dados = [
                'id' => $usuario_id,
                'nome' => $nome,
                'telefone' => $telefone,
                'email' => $email
            ];

            if (!empty($cpf) && empty($usuario['cpf'])) {
                $dados['cpf'] = $cpf;
            }

            if (!empty($nova_senha)) {
                if ($nova_senha === $confirmar_senha) {
                    $dados['senha'] = password_hash($nova_senha, PASSWORD_BCRYPT);
                } else {
                    $_SESSION['message'] = ['type' => 'failure', 'text' => 'As novas senhas não coincidem!'];
                    header("Location: /perfil");
                    exit;
                }
            }

            $setParts = [];
            foreach ($dados as $key => $value) {
                if ($key !== 'id') {
                    $setParts[] = "$key = :$key";
                }
            }

            $query = "UPDATE usuarios SET " . implode(', ', $setParts) . " WHERE id = :id";
            $stmt = $pdo->prepare($query);

            if ($stmt->execute($dados)) {
                $_SESSION['message'] = ['type' => 'success', 'text' => 'Perfil atualizado com sucesso!'];
                header("Location: /perfil");
                exit;
            } else {
                $_SESSION['message'] = ['type' => 'failure', 'text' => 'Erro ao atualizar perfil!'];
            }
        } catch (PDOException $e) {
            $_SESSION['message'] = ['type' => 'failure', 'text' => 'Erro ao atualizar perfil: ' . $e->getMessage()];
        }
    }
    header("Location: /perfil");
    exit;
}
?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $nomeSite; ?> - Meu Perfil</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">

    <link rel="stylesheet" href="/assets/style/globalStyles.css?id=<?= time(); ?>">

    <script src="https://cdn.jsdelivr.net/npm/notiflix@3.2.8/dist/notiflix-aio-3.2.8.min.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/notiflix@3.2.8/src/notiflix.min.css" rel="stylesheet">

    <style>
        /* Estilos da Página de Perfil (código original) */
        .perfil-section {
            margin-top: 40px;
            padding: 4rem 0;
            background: #0a0a0a;
            min-height: calc(100vh - 200px);
        }

        .perfil-container {
            max-width: 900px;
            /* Mantém a largura máxima para telas menores */
            margin: 0 auto;
            padding: 0 2rem;
        }

        /* Em telas maiores (desktops) */
        @media (min-width: 992px) {
            .perfil-container {
                max-width: none;
                /* Remove a limitação de largura */
            }
        }

        /* Em telas maiores (desktops) */
        @media (min-width: 992px) {
            .perfil-container {
                max-width: none;
                /* Remove a limitação de largura */
            }
        }

        .page-header {
            display: flex;
            align-items: center;
            gap: 1.2rem;
            /* Espaçamento entre o avatar e o título */
            text-align: left;
            /* Alinha o texto à esquerda */
            margin-bottom: 3rem;
        }

        .page-header .page-title {
            margin-bottom: 0;
            /* Remove a margem inferior do título para um melhor alinhamento */
        }

        .page-title {
            font-size: 1.6rem;
            font-weight: 900;
            color: white;
            margin-bottom: 1rem;
            background: linear-gradient(135deg, #ffffff, #9ca3af);
            background-clip: text;
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .page-subtitle {
            font-size: 1.1rem;
            color: #6b7280;
            max-width: 500px;
            margin: 0 auto;
        }

        .user-avatar {
            width: 60px;
            height: 60px;
            margin: 0;
            /* Remove a centralização e margens automáticas */
            flex-shrink: 0;
            /* O resto das regras do user-avatar permanecem as mesmas */
            background: linear-gradient(135deg, #22c55e, #16a34a);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 2.5rem;
            font-weight: 800;
            box-shadow: 0 8px 32px rgba(34, 197, 94, 0.3);
            position: relative;
        }

        .user-avatar::after {
            content: '';
            position: absolute;
            inset: -4px;
            background: linear-gradient(135deg, #22c55e, #16a34a);
            border-radius: 50%;
            z-index: -1;
            opacity: 0.3;
            animation: pulse 2s infinite;
        }

        @keyframes pulse {

            0%,
            100% {
                transform: scale(1);
                opacity: 0.3;
            }

            50% {
                transform: scale(1.1);
                opacity: 0.1;
            }
        }

        /* Stats Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 3rem;
        }

        /* ... outras regras ... */

        /* Melhorias no grid das estatísticas */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 1.5rem;
            margin-bottom: 3rem;
        }

        @media (max-width: 900px) {
            .stats-grid {
                grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
                gap: 1.25rem;
            }
        }

        @media (max-width: 768px) {
            .stats-grid {
                grid-template-columns: 1fr;
                gap: 1rem;
            }
        }

        .stat-card {
            background: rgba(255, 255, 255, 0.02);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 20px;
            padding: 2rem;
            position: relative;
            overflow: hidden;
            transition: all 0.3s ease;
            min-height: 140px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        .stat-card:hover {
            transform: translateY(-4px);
            border-color: rgba(34, 197, 94, 0.3);
            box-shadow: 0 10px 40px rgba(34, 197, 94, 0.1);
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 4px;
            height: 100%;
            background: var(--accent-color);
        }

        .stat-card.saldo::before {
            background: linear-gradient(180deg, #22c55e, #16a34a);
        }

        .stat-card.depositos::before {
            background: linear-gradient(180deg, #3b82f6, #2563eb);
        }

        .stat-card.saques::before {
            background: linear-gradient(180deg, #f59e0b, #d97706);
        }

        .stat-header {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            margin-bottom: 0.5rem;
            gap: 1.25rem;
        }

        .stat-info {
            flex: 1;
            min-width: 0;
        }

        .stat-info h3 {
            color: #9ca3af;
            font-size: 0.8rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.1em;
            margin-bottom: 1rem;
            line-height: 1.2;
        }

        .stat-value {
            font-size: 2rem;
            font-weight: 900;
            color: white;
            line-height: 1.1;
            margin-bottom: 0.5rem;
        }

        .stat-icon {
            width: 52px;
            height: 52px;
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.3rem;
            flex-shrink: 0;
            margin-top: 0.25rem;
        }

        .stat-icon.saldo {
            background: rgba(34, 197, 94, 0.15);
            color: #78d403;
            border: 1px solid rgba(34, 197, 94, 0.2);
        }

        .stat-icon.depositos {
            background: rgba(59, 130, 246, 0.15);
            color: #3b82f6;
            border: 1px solid rgba(59, 130, 246, 0.2);
        }

        .stat-icon.saques {
            background: rgba(245, 158, 11, 0.15);
            color: #f59e0b;
            border: 1px solid rgba(245, 158, 11, 0.2);
        }

        .stat-footer {
            color: #6b7280;
            font-size: 0.8rem;
            margin-top: auto;
            padding-top: 0.75rem;
        }

        .form-card {
            background: rgba(20, 20, 20, 0.8);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 24px;
            padding: 3rem;
            backdrop-filter: blur(20px);
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.5);
            position: relative;
            overflow: hidden;
        }

        .form-card::before {
            content: '';
            position: absolute;
            top: 0;
            right: 0;
            width: 150px;
            height: 150px;
            background: linear-gradient(135deg, rgba(34, 197, 94, 0.1), transparent);
            border-radius: 50%;
            transform: translate(50%, -50%);
        }

        .form-header {
            text-align: center;
            margin-bottom: 3rem;
            position: relative;
            z-index: 2;
        }

        .form-icon {
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, #22c55e, #16a34a);
            border-radius: 16px;
            margin: 0 auto 1.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.5rem;
            box-shadow: 0 8px 24px rgba(34, 197, 94, 0.3);
        }

        .form-title {
            font-size: 1.8rem;
            font-weight: 700;
            color: white;
            margin-bottom: 0.5rem;
        }

        .form-description {
            color: #9ca3af;
            font-size: 1rem;
        }

        .form-grid {
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
            position: relative;
            z-index: 2;
        }

        .form-group {
            position: relative;
        }

        .form-input {
            width: 100%;
            padding: 1rem 1rem 1rem 3rem;
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 12px;
            color: white;
            font-size: 1rem;
            transition: all 0.3s ease;
        }

        .form-input:focus {
            outline: none;
            border-color: #78d403;
            background: rgba(255, 255, 255, 0.08);
            box-shadow: 0 0 0 3px rgba(34, 197, 94, 0.1);
        }

        .form-input::placeholder {
            color: #6b7280;
        }

        .input-icon {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: #6b7280;
            font-size: 1rem;
            transition: color 0.3s ease;
        }

        .form-group:focus-within .input-icon {
            color: #78d403;
        }

        .password-toggle {
            background: none;
            border: none;
            color: #78d403;
            cursor: pointer;
            padding: 0.5rem 0;
            font-size: 0.9rem;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            transition: color 0.3s ease;
            margin: 1rem 0;
        }

        .password-toggle:hover {
            color: #16a34a;
        }

        .password-fields {
            display: none;
            flex-direction: column;
            gap: 1.5rem;
            margin: 1.5rem 0;
            padding: 1.5rem;
            background: rgba(34, 197, 94, 0.05);
            border: 1px solid rgba(34, 197, 94, 0.1);
            border-radius: 16px;
        }

        .password-fields.active {
            display: flex;
        }

        .password-fields-title {
            color: #78d403;
            font-weight: 600;
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .submit-btn {
            background: #78d403;
            color: white;
            border: none;
            padding: 1rem 2rem;
            border-radius: 12px;
            font-size: 1rem;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            box-shadow: 0 4px 20px rgba(34, 197, 94, 0.3);
            margin-top: 1rem;
        }

        .submit-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 30px rgba(34, 197, 94, 0.4);
        }

        .submit-btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
        }

        .security-tips {
            background: rgba(59, 130, 246, 0.05);
            border: 1px solid rgba(59, 130, 246, 0.1);
            border-radius: 16px;
            padding: 1.5rem;
            margin-top: 2rem;
        }

        .security-title {
            color: #3b82f6;
            font-weight: 600;
            font-size: 0.9rem;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .security-list {
            list-style: none;
            padding: 0;
        }

        .security-list li {
            color: #9ca3af;
            font-size: 0.85rem;
            margin-bottom: 0.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .security-list i {
            color: #3b82f6;
            font-size: 0.75rem;
        }

        .user-avatar {
            cursor: pointer;
        }

        .user-avatar .edit-avatar-icon {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            opacity: 1;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .user-avatar .edit-avatar-icon i {
            font-size: 36px;
            /* Tamanho do ícone aumentado */
            color: black;
            font-weight: bold;
            animation: blink-effect 2.5s infinite;
            /* Aplica a animação */
        }

        /* Animação de piscar suave */
        @keyframes blink-effect {
            50% {
                opacity: 0;
            }
        }

        #avatarModal {
            display: none;
            position: fixed;
            z-index: 1050;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0, 0, 0, 0.8);
            backdrop-filter: blur(5px);
        }

        #avatarModal .avatar-modal-content {
            background-color: #1a1a1a;
            margin: 15% auto;
            padding: 2rem;
            border: 1px solid rgba(255, 255, 255, 0.1);
            width: 90%;
            max-width: 500px;
            border-radius: 20px;
            text-align: center;
            position: relative;
            animation: fadeIn 0.3s ease-out;
        }

        #avatarModal .close-avatar-modal {
            color: #aaa;
            position: absolute;
            top: 1rem;
            right: 1.5rem;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
            transition: color 0.3s ease;
        }

        #avatarModal .close-avatar-modal:hover {
            color: white;
        }

        #avatar-options .avatar-option {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            cursor: pointer;
            border: 3px solid transparent;
            transition: border-color 0.3s ease, transform 0.2s ease;
        }

        #avatar-options .avatar-option:hover {
            transform: scale(1.1);
            border-color: #78d403;
        }


        @media (min-width: 992px) {
            .form-card {
                max-width: 900px;
                margin-left: auto;
                margin-right: auto;
            }
        }


        .custom-file-upload {
            background: #333;
            color: white;
            border: 1px solid #555;
            padding: 0.8rem 1.5rem;
            border-radius: 12px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }

        .custom-file-upload:hover {
            background: #444;
            border-color: #777;
        }

        /* Ajusta o botão de envio dentro do modal */
        #upload-avatar-form {
            margin-top: 25px;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 15px;
            /* Adiciona um espaço de 15px entre cada item */
        }

        #upload-avatar-form p {
            color: white;
            margin: 0;
            /* Remove margens desnecessárias */
        }

        #avatar-options {
            display: flex;
            flex-wrap: nowrap !important;
            /* Força a não quebra de linha */
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
            padding-bottom: 15px;
            scrollbar-width: none;
            /* Firefox */
            -ms-overflow-style: none;
            /* IE 10+ */
        }

        #upload-button-container {
            margin: 0;
            /* Remove margens desnecessárias */
            display: none;
        }

        #avatar-options {
            display: flex;
            flex-wrap: nowrap !important;
            /* Força a não quebra de linha */
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
            padding: 10px 0 15px 0;
            /* Adiciona padding no topo e na base */
            justify-content: flex-start;
            /* Alinha os itens no início */
            scrollbar-width: none;
            /* Firefox */
            -ms-overflow-style: none;
            /* IE 10+ */
        }

        /* Alinha no centro apenas quando não há scroll */
        @media (min-width: 530px) {

            /* Largura aproximada do modal */
            #avatar-options {
                justify-content: center;
            }
        }

        #avatar-options::-webkit-scrollbar {
            display: none;
            /* Chrome, Safari, Opera */
        }

        #avatar-options .avatar-option {
            flex: 0 0 auto;
            width: 80px;
            height: 80px;
            margin: 0 7.5px;
            /* Espaçamento horizontal entre os avatares */
            transition: border-color 0.3s ease, transform 0.2s ease;
            /* Adiciona a transição de volta */
        }

        /* Efeito hover que já existia */
        #avatar-options .avatar-option:hover {
            transform: scale(1.1);
            border-color: #78d403;
        }
    </style>
</head>

<body>
    <?php include('../inc/header.php'); ?>
    <?php include('../components/modals.php'); ?>

    <section class="perfil-section">
        <div class="perfil-container">
            <div class="page-header fade-in">
                <div class="user-avatar" id="avatar-container">
                    <?php
                    $avatarUrl = !empty($usuario['avatar']) ? '../' . htmlspecialchars($usuario['avatar']) : '../assets/avatar/default.png';
                    ?>
                    <img src="<?= $avatarUrl ?>" alt="Avatar do usuário" style="width: 100%; height: 100%; border-radius: 50%; object-fit: cover;">
                    <div class="edit-avatar-icon">
                        <i class="bi bi-pencil-square"></i>
                    </div>
                </div>
                <h1 class="page-title">Meu Perfil</h1>
            </div>

            <div class="stats-grid">
                <div class="stat-card saldo">
                    <div class="stat-header">
                        <div class="stat-info">
                            <h3>Saldo Atual</h3>
                            <div class="stat-value">R$ <?= number_format($usuario['saldo'] ?? 0, 2, ',', '.') ?></div>
                        </div>
                        <div class="stat-icon saldo"><i class="bi bi-wallet2"></i></div>
                    </div>
                </div>
                <div class="stat-card depositos">
                    <div class="stat-header">
                        <div class="stat-info">
                            <h3>Total Depositado</h3>
                            <div class="stat-value">R$ <?= number_format($total_depositado, 2, ',', '.') ?></div>
                        </div>
                        <div class="stat-icon depositos"><i class="bi bi-arrow-down-circle"></i></div>
                    </div>
                </div>
                <div class="stat-card saques">
                    <div class="stat-header">
                        <div class="stat-info">
                            <h3>Total Sacado</h3>
                            <div class="stat-value">R$ <?= number_format($total_sacado, 2, ',', '.') ?></div>
                        </div>
                        <div class="stat-icon saques"><i class="bi bi-arrow-up-circle"></i></div>
                    </div>
                </div>
            </div>

            <div class="form-card">
                <div class="form-header">
                    <div class="form-icon"><i class="bi bi-person-gear"></i></div>
                    <h2 class="form-title">Editar Perfil</h2>
                    <p class="form-description">Atualize suas informações pessoais com segurança</p>
                </div>
                <form method="POST" class="form-grid" id="perfilForm">
                    <div class="form-group">
                        <div class="input-icon"><i class="bi bi-person"></i></div><input type="text" name="nome" class="form-input" value="<?= htmlspecialchars($usuario['nome'] ?? '') ?>" placeholder="Nome completo" required>
                    </div>
                    <div class="form-group">
                        <div class="input-icon"><i class="bi bi-person-vcard"></i></div><input type="text" id="cpf" name="cpf" class="form-input" value="<?= htmlspecialchars($usuario['cpf'] ?? '') ?>" placeholder="CPF" maxlength="14" <?php if (!empty(trim($usuario['cpf'] ?? ''))): ?>readonly style="cursor: not-allowed;" <?php endif; ?>><?php if (!empty(trim($usuario['cpf'] ?? ''))): ?><div class="input-icon-right"><i class="bi bi-lock-fill"></i></div><?php endif; ?>
                    </div>
                    <div class="form-group">
                        <div class="input-icon"><i class="bi bi-telephone"></i></div><input type="text" id="telefone" name="telefone" class="form-input" value="<?= htmlspecialchars($usuario['telefone'] ?? '') ?>" placeholder="(11) 99999-9999" required>
                    </div>
                    <div class="form-group">
                        <div class="input-icon"><i class="bi bi-envelope"></i></div><input type="email" name="email" class="form-input" value="<?= htmlspecialchars($usuario['email'] ?? '') ?>" placeholder="seu@email.com" required>
                    </div>
                    <button type="button" class="password-toggle" id="toggleSenha"><i class="bi bi-key"></i> Alterar senha</button>
                    <div class="password-fields" id="camposSenha">
                        <div class="password-fields-title"><i class="bi bi-shield-lock"></i> Nova Senha</div>
                        <div class="form-group">
                            <div class="input-icon"><i class="bi bi-lock-fill"></i></div><input type="password" name="nova_senha" class="form-input" placeholder="Digite a nova senha">
                        </div>
                        <div class="form-group">
                            <div class="input-icon"><i class="bi bi-lock-fill"></i></div><input type="password" name="confirmar_senha" class="form-input" placeholder="Confirme a nova senha">
                        </div>
                    </div>
                    <div class="form-group">
                        <div class="input-icon"><i class="bi bi-shield-check"></i></div><input type="password" name="senha_atual" class="form-input" placeholder="Senha atual (para confirmar alterações)" required>
                    </div>
                    <button type="submit" class="submit-btn" id="submitBtn"><i class="bi bi-check-circle"></i> Atualizar Perfil</button>
                </form>
                <div class="security-tips">
                    <div class="security-title"><i class="bi bi-info-circle"></i> Dicas de Segurança</div>
                    <ul class="security-list">
                        <li><i class="bi bi-check"></i> Use uma senha forte com pelo menos 8 caracteres</li>
                        <li><i class="bi bi-check"></i> Nunca compartilhe sua senha com terceiros</li>
                        <li><i class="bi bi-check"></i> Mantenha seus dados sempre atualizados</li>
                        <li><i class="bi bi-check"></i> Use um e-mail válido para recuperação da conta</li>
                    </ul>
                </div>
            </div>
        </div>
    </section>

    <div id="avatarModal">
        <div class="avatar-modal-content">
            <span class="close-avatar-modal">&times;</span>
            <h2 style="color: white; margin-bottom: 20px;">Escolha seu Avatar</h2>

            <div id="avatar-options">
                <?php
                $avatar_directory = '../assets/avatar/';
                $avatar_files = glob($avatar_directory . '*.{jpg,png,gif}', GLOB_BRACE);
                foreach ($avatar_files as $file) {
                    $file_name = basename($file);
                    if ($file_name !== 'default.png') {
                        echo '<img src="' . htmlspecialchars($file) . '" alt="Opção de Avatar" class="avatar-option">';
                    }
                }
                ?>
            </div>

            <form id="upload-avatar-form">
                <p>Ou envie sua própria foto:</p>

                <label for="avatar-upload" class="custom-file-upload">
                    <i class="bi bi-upload"></i> <span id="file-chosen-name">Escolher arquivo</span>
                </label>
                <input type="file" name="avatar-upload" id="avatar-upload" accept="image/png, image/jpeg, image/gif" style="display: none;">

                <div id="upload-button-container">
                    <button type="submit" class="submit-btn">Enviar</button>
                </div>
            </form>
        </div>
    </div>
    </form>

    </div>
    </div>
    </div>

    <?php include('../inc/footer.php'); ?>

    <script>
        document.addEventListener('DOMContentLoaded', function() {

            // --- LÓGICA DO FORMULÁRIO DE PERFIL ---
            const telefoneInput = document.getElementById('telefone');
            if (telefoneInput) {
                telefoneInput.addEventListener('input', function(e) {
                    let value = e.target.value.replace(/\D/g, '');
                    if (value.length > 11) value = value.slice(0, 11);
                    let formatted = '';
                    if (value.length > 0) formatted += '(' + value.substring(0, 2);
                    if (value.length >= 3) formatted += ') ' + value.substring(2, 7);
                    if (value.length >= 8) formatted += '-' + value.substring(7);
                    e.target.value = formatted;
                });
            }

            // ... (resto do seu JS para o formulário principal) ...

            // --- LÓGICA CORRIGIDA PARA O MODAL DE AVATAR ---
            const avatarContainer = document.getElementById('avatar-container');
            const avatarModal = document.getElementById('avatarModal');
            const closeModal = document.querySelector('#avatarModal .close-avatar-modal');
            const avatarOptions = document.querySelectorAll('.avatar-option');
            const uploadAvatarForm = document.getElementById('upload-avatar-form');
            const uploadButtonContainer = document.getElementById('upload-button-container');
            const fileInput = document.getElementById('avatar-upload');
            const fileChosenName = document.getElementById('file-chosen-name');

            let selectedPredefinedAvatar = null;

            avatarContainer?.addEventListener('click', () => {
                avatarModal.style.display = 'block';
            });

            const closeAvatarModal = () => {
                avatarModal.style.display = 'none';
                uploadButtonContainer.style.display = 'none';
                fileInput.value = ''; // Limpa a seleção de arquivo
                fileChosenName.textContent = 'Escolher arquivo';
                avatarOptions.forEach(opt => opt.classList.remove('selected'));
                selectedPredefinedAvatar = null;
            };

            closeModal?.addEventListener('click', closeAvatarModal);
            window.addEventListener('click', (event) => {
                if (event.target == avatarModal) closeAvatarModal();
            });

            // Evento para avatares pré-definidos
            avatarOptions.forEach(option => {
                option.addEventListener('click', function() {
                    avatarOptions.forEach(opt => opt.classList.remove('selected'));
                    this.classList.add('selected');
                    selectedPredefinedAvatar = this.src;
                    fileInput.value = ''; // Limpa o campo de arquivo
                    fileChosenName.textContent = 'Escolher arquivo';
                    uploadButtonContainer.style.display = 'flex';
                    // Renomeia o botão para refletir a ação correta
                    uploadButtonContainer.querySelector('button').textContent = 'Salvar Avatar Selecionado';
                });
            });

            // Evento para quando um arquivo é selecionado do computador
            fileInput?.addEventListener('change', function() {
                if (this.files && this.files[0]) {
                    const fileName = this.files[0].name;
                    fileChosenName.textContent = fileName.length > 20 ? fileName.substring(0, 17) + '...' : fileName;
                    avatarOptions.forEach(opt => opt.classList.remove('selected'));
                    selectedPredefinedAvatar = null;
                    uploadButtonContainer.style.display = 'flex';
                    // Renomeia o botão para refletir a ação correta
                    uploadButtonContainer.querySelector('button').textContent = 'Enviar Imagem';
                } else {
                    uploadButtonContainer.style.display = 'none';
                    fileChosenName.textContent = 'Escolher arquivo';
                }
            });

            // Evento de envio do formulário
            uploadAvatarForm?.addEventListener('submit', function(e) {
                e.preventDefault();
                Notiflix.Loading.circle('Salvando...');

                const file = fileInput.files[0];

                if (file) { // Se um arquivo foi enviado
                    const formData = new FormData();
                    formData.append('avatar', file);

                    fetch('upload_avatar.php', {
                            method: 'POST',
                            body: formData
                        })
                        .then(response => response.json())
                        .then(handleResponse)
                        .catch(handleError);

                } else if (selectedPredefinedAvatar) { // Se um avatar pré-definido foi selecionado
                    fetch('update_avatar.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json'
                            },
                            body: JSON.stringify({
                                avatar: selectedPredefinedAvatar
                            })
                        })
                        .then(response => response.json())
                        .then(handleResponse)
                        .catch(handleError);
                } else {
                    Notiflix.Loading.remove();
                    Notiflix.Notify.failure('Nenhuma alteração para salvar.');
                }
            });

            function handleResponse(data) {
                Notiflix.Loading.remove();
                if (data.success) {
                    Notiflix.Notify.success('Avatar atualizado com sucesso!');
                    setTimeout(() => location.reload(), 1000);
                } else {
                    Notiflix.Notify.failure(data.message || 'Ocorreu um erro.');
                }
            }

            function handleError(error) {
                Notiflix.Loading.remove();
                Notiflix.Notify.failure('Erro de conexão. Tente novamente.');
                console.error('Erro:', error);
            }

        }); // Fim do 'DOMContentLoaded'

        function formatarCPF(valor) {
            valor = valor.replace(/\D/g, "").substring(0, 11);
            valor = valor.replace(/(\d{3})(\d)/, "$1.$2");
            valor = valor.replace(/(\d{3})(\d)/, "$1.$2");
            valor = valor.replace(/(\d{3})(\d{1,2})$/, "$1-$2");
            return valor;
        }

        // Configuração do Notiflix
        Notiflix.Notify.init({
            width: '300px',
            position: 'right-top',
            distance: '20px',
            opacity: 1,
            borderRadius: '12px',
            timeout: 4000,
            success: {
                background: '#78d403',
                textColor: '#fff'
            },
            failure: {
                background: '#ef4444',
                textColor: '#fff'
            }
        });

        // Mensagens da sessão PHP
        <?php if (isset($_SESSION['message'])): ?>
            Notiflix.Notify.<?php echo $_SESSION['message']['type']; ?>('<?php echo $_SESSION['message']['text']; ?>');
            <?php unset($_SESSION['message']); ?>
        <?php endif; ?>

        // Animação de Spin
        const style = document.createElement('style');
        style.textContent = `@keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }`;
        document.head.appendChild(style);

        console.log('%c👤 Perfil do usuário carregado!', 'color: #78d403; font-size: 16px; font-weight: bold;');
    </script>
</body>

</html>