<?php
ob_start(); // Garante que o redirecionamento após salvar funcione

include '../includes/session.php';
include '../conexao.php';
include '../includes/notiflix.php';

// --- Busca as configurações do site (nome e favicon) ---
try {
    $configStmt = $pdo->prepare("SELECT nome_site, favicon FROM config WHERE id = 1 LIMIT 1");
    $configStmt->execute();
    $config = $configStmt->fetch(PDO::FETCH_ASSOC);
    $nomeSite = $config['nome_site'] ?? 'Admin';
    $faviconSite = $config['favicon'] ?? null;
} catch (PDOException $e) {
    $nomeSite = 'Admin';
    $faviconSite = null;
}

$usuarioId = $_SESSION['usuario_id'];
$adminStmt = $pdo->prepare("SELECT admin FROM usuarios WHERE id = ?");
$adminStmt->execute([$usuarioId]);
$admin = $adminStmt->fetchColumn();

if ($admin != 1) {
    $_SESSION['message'] = ['type' => 'warning', 'text' => 'Você não é um administrador!'];
    header("Location: /");
    exit;
}

$nomeStmt = $pdo->prepare("SELECT nome FROM usuarios WHERE id = ?");
$nomeStmt->execute([$usuarioId]);
$nome = $nomeStmt->fetchColumn();
$nome = $nome ? explode(' ', $nome)[0] : 'Admin';

// --- LÓGICA DO BÔNUS DE PRIMEIRO DEPÓSITO ---
$stmt = $pdo->prepare("SELECT * FROM bonus_settings WHERE id = 1");
$stmt->execute();
$bonus_settings = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$bonus_settings) {
    $bonus_settings = ['is_active' => 0, 'bonus_amount' => 0, 'min_deposit' => 0, 'rollover_multiplier' => 0];
}

// --- LÓGICA DE AÇÕES (DESATIVAR/EXCLUIR CÓDIGO) ---
if (isset($_GET['action'])) {
    $action = $_GET['action'];
    $codigo_id = $_GET['id'] ?? null;

    if ($codigo_id) {
        if ($action === 'toggle_status') {
            $stmt = $pdo->prepare("UPDATE codigos_bonus SET status = IF(status='ativo', 'inativo', 'ativo') WHERE id = ?");
            $stmt->execute([$codigo_id]);
            $_SESSION['message'] = ['type' => 'success', 'text' => 'Status do código alterado com sucesso!'];
        } elseif ($action === 'delete') {
            $stmt = $pdo->prepare("DELETE FROM codigos_bonus WHERE id = ?");
            $stmt->execute([$codigo_id]);
            // Opcional: apagar também os registos de uso
            $stmt = $pdo->prepare("DELETE FROM codigos_bonus_usos WHERE codigo_id = ?");
            $stmt->execute([$codigo_id]);
            $_SESSION['message'] = ['type' => 'success', 'text' => 'Código excluído com sucesso!'];
        }
    }
    header('Location: bonus.php');
    exit;
}


// --- PROCESSAMENTO DOS FORMULÁRIOS ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Formulário de Bônus de Primeiro Depósito
    if (isset($_POST['salvar_bonus_deposito'])) {
        $is_active = isset($_POST['is_active']) ? 1 : 0;
        $min_deposit_raw = $_POST['min_deposit'] ?? '0';
        $sanitized_value = preg_replace('/[^\d,]/', '', $min_deposit_raw);
        $min_deposit = str_replace(',', '.', $sanitized_value);

        $update_data = [
            ':is_active' => $is_active,
            ':bonus_amount' => (int)($_POST['bonus_amount'] ?? 0),
            ':min_deposit' => (float)($min_deposit),
            ':rollover_multiplier' => (int)($_POST['rollover_multiplier'] ?? 0)
        ];

        $update_stmt = $pdo->prepare(
            "UPDATE bonus_settings SET
                is_active = :is_active,
                bonus_amount = :bonus_amount,
                min_deposit = :min_deposit,
                rollover_multiplier = :rollover_multiplier
             WHERE id = 1"
        );
        $update_stmt->execute($update_data);
        $_SESSION['message'] = ['type' => 'success', 'text' => 'Configurações de bônus salvas com sucesso!'];
        header('Location: bonus.php');
        exit;
    }

    // Formulário de Gerar Código de Bônus
    if (isset($_POST['gerar_codigo_bonus'])) {
        $codigo = strtoupper($_POST['codigo']);
        $tipo = $_POST['tipo_bonus'];
        $valor_raw = $_POST['valor_bonus'] ?? '0';
        $valor = str_replace(',', '.', preg_replace('/[^\d,]/', '', $valor_raw));
        $rollover = (int)($_POST['rollover_bonus'] ?? 0);
        $data_expiracao = !empty($_POST['data_expiracao']) ? $_POST['data_expiracao'] : null;

        // Validação básica
        if (empty($codigo) || empty($tipo) || empty($valor) || empty($rollover)) {
            $_SESSION['message'] = ['type' => 'warning', 'text' => 'Por favor, preencha todos os campos obrigatórios.'];
        } else {
            // Verifica se o código já existe
            $checkStmt = $pdo->prepare("SELECT id FROM codigos_bonus WHERE codigo = ?");
            $checkStmt->execute([$codigo]);
            if ($checkStmt->fetch()) {
                $_SESSION['message'] = ['type' => 'warning', 'text' => 'Este código já existe. Por favor, escolha outro.'];
            } else {
                $sql = "INSERT INTO codigos_bonus (codigo, tipo, valor, rollover, data_expiracao) VALUES (?, ?, ?, ?, ?)";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$codigo, $tipo, $valor, $rollover, $data_expiracao]);
                $_SESSION['message'] = ['type' => 'success', 'text' => 'Código de bônus gerado com sucesso!'];
            }
        }
        header('Location: bonus.php');
        exit;
    }
}

// Busca os códigos de bônus da base de dados para a tabela de gestão
$codigos_bonus = $pdo->query("
    SELECT c.*, COUNT(u.id) as total_usos
    FROM codigos_bonus c
    LEFT JOIN codigos_bonus_usos u ON c.id = u.codigo_id
    GROUP BY c.id
    ORDER BY c.data_criacao DESC
")->fetchAll(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $nomeSite; ?> - Configurações de Bônus</title>

    <?php if ($faviconSite && file_exists($_SERVER['DOCUMENT_ROOT'] . $faviconSite)): ?>
        <link rel="icon" type="image/x-icon" href="<?= htmlspecialchars($faviconSite) ?>" />
    <?php else: ?>
        <link rel="icon" href="data:image/svg+xml,<?= urlencode('<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><rect width="100" height="100" fill="#22c55e"/><text x="50" y="50" text-anchor="middle" dominant-baseline="middle" fill="white" font-family="Arial" font-size="40" font-weight="bold">' . strtoupper(substr($nomeSite, 0, 1)) . '</text></svg>') ?>" />
    <?php endif; ?>

    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/notiflix@3.2.8/dist/notiflix-aio-3.2.8.min.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/notiflix@3.2.8/src/notiflix.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">

    <style>
        body {
            font-family: 'Inter', sans-serif;
            background: #000000;
            color: #ffffff;
        }

        .sidebar {
            position: fixed;
            top: 0;
            left: 0;
            width: 320px;
            height: 100vh;
            background: linear-gradient(145deg, #0a0a0a 0%, #141414 25%, #1a1a1a 50%, #0f0f0f 100%);
            backdrop-filter: blur(20px);
            border-right: 1px solid rgba(34, 197, 94, 0.2);
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            z-index: 1001;
            box-shadow: 0 0 50px rgba(34, 197, 94, 0.1), inset 1px 0 0 rgba(255, 255, 255, 0.05);
            overflow-y: auto;
        }

        .sidebar::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: radial-gradient(circle at 20% 20%, rgba(34, 197, 94, 0.15) 0%, transparent 50%), radial-gradient(circle at 80% 80%, rgba(16, 185, 129, 0.1) 0%, transparent 50%);
            opacity: 0.8;
            pointer-events: none;
        }

        .sidebar.hidden {
            transform: translateX(-100%);
        }

        .sidebar-header {
            position: relative;
            padding: 2.5rem 2rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            background: linear-gradient(135deg, rgba(34, 197, 94, 0.1) 0%, transparent 100%);
        }

        .logo {
            display: flex;
            align-items: center;
            gap: 1rem;
            text-decoration: none;
        }

        .logo-icon {
            width: 48px;
            height: 48px;
            background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%);
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            color: #ffffff;
        }

        .logo-title {
            font-size: 1.5rem;
            font-weight: 800;
            color: #ffffff;
        }

        .nav-menu {
            padding: 2rem 0;
        }

        .nav-section-title {
            padding: 0 2rem 0.75rem 2rem;
            font-size: 0.75rem;
            font-weight: 600;
            color: #6b7280;
            text-transform: uppercase;
        }

        .nav-item {
            display: flex;
            align-items: center;
            padding: 1rem 2rem;
            color: #a1a1aa;
            text-decoration: none;
            transition: all 0.3s;
            margin: 0.25rem 1rem;
            border-radius: 12px;
            font-weight: 500;
            position: relative;
            overflow: hidden;
        }

        .nav-item:hover,
        .nav-item.active {
            color: #ffffff;
            background-color: rgba(34, 197, 94, 0.1);
        }

        .nav-item::before {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            height: 100%;
            width: 4px;
            background-color: #22c55e;
            transform: translateX(-100%);
            transition: transform 0.3s ease-in-out;
        }

        .nav-item:hover::before,
        .nav-item.active::before {
            transform: translateX(0);
        }

        .nav-icon {
            width: 24px;
            height: 24px;
            margin-right: 1rem;
        }

        .main-content {
            margin-left: 320px;
            transition: margin-left 0.4s;
        }

        .header {
            position: sticky;
            top: 0;
            background: rgba(0, 0, 0, 0.95);
            backdrop-filter: blur(20px);
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            padding: 1.5rem 2.5rem;
            z-index: 100;
        }

        .dashboard-content {
            padding: 2.5rem;
        }

        .welcome-title {
            font-size: 3rem;
            font-weight: 800;
            margin-bottom: 0.75rem;
        }

        .welcome-subtitle {
            font-size: 1.25rem;
            color: #6b7280;
        }

        .activity-card {
            background: linear-gradient(135deg, rgba(20, 20, 20, 0.8) 0%, rgba(10, 10, 10, 0.9) 100%);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 20px;
            padding: 2.5rem;
        }

        .form-label {
            display: block;
            margin-bottom: 0.5rem;
            color: #ffffff;
            font-weight: 500;
        }

        @media (max-width: 768px) {
            .form-label {
                font-weight: 400;
            }
        }

        .input-wrapper {
            position: relative;
            display: flex;
            align-items: center;
        }

        .form-input {
            width: 100%;
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.2);
            color: #ffffff;
            padding: 0.75rem 1rem;
            border-radius: 12px;
            transition: all 0.3s ease;
        }

        @media (max-width: 768px) {
            .form-input {
                padding-top: 0.5rem;
                padding-bottom: 0.5rem;
            }
        }

        .form-input:focus {
            outline: none;
            border-color: #22c55e;
            box-shadow: 0 0 0 3px rgba(34, 197, 94, 0.3);
        }

        .input-adornment {
            position: absolute;
            color: #6b7280;
            pointer-events: none;
            font-weight: 500;
        }

        .adornment-right {
            right: 1rem;
        }

        .input-adorned-right {
            padding-right: 3rem !important;
        }

        .form-checkbox {
            width: 1.25rem;
            height: 1.25rem;
            border-radius: 4px;
            background-color: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .btn-primary {
            background: #22c55e;
            color: #ffffff;
            padding: 0.75rem 1.5rem;
            border-radius: 12px;
            font-weight: 600;
            transition: background 0.3s;
            border: none;
            cursor: pointer;
        }

        .btn-primary:hover {
            background: #16a34a;
        }

        .overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.7);
            z-index: 1000;
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s ease;
            backdrop-filter: blur(4px);
        }

        .overlay.active {
            opacity: 1;
            visibility: visible;
        }

        @media (max-width: 1024px) {
            .sidebar {
                transform: translateX(-100%);
            }

            .sidebar:not(.hidden) {
                transform: translateX(0);
            }

            .main-content {
                margin-left: 0;
            }
        }

        @media (max-width: 768px) {
            .dashboard-content {
                padding: 1.5rem;
            }

            .activity-card {
                padding: 1.5rem;
            }

            .welcome-title {
                font-size: 2.25rem;
            }
        }

        .preload * {
            transition: none !important;
        }
    </style>
</head>

<body>
    <div class="overlay" id="overlay"></div>

    <aside class="sidebar" id="sidebar">
        <div class="sidebar-header"><a href="/admin" class="logo">
                <div class="logo-icon"><i class="fas fa-bolt"></i></div>
                <div class="logo-text">
                    <div class="logo-title">Dashboard</div>
                </div>
            </a></div>
        <nav class="nav-menu">
            <div class="nav-section">
                <div class="nav-section-title">Principal</div><a href="index.php" class="nav-item">
                    <div class="nav-icon"><i class="fas fa-chart-pie"></i></div>
                    <div class="nav-text">Dashboard</div>
                </a>
            </div>
            <div class="nav-section">
                <div class="nav-section-title">Gestão</div><a href="usuarios.php" class="nav-item">
                    <div class="nav-icon"><i class="fas fa-user"></i></div>
                    <div class="nav-text">Usuários</div>
                </a><a href="afiliados.php" class="nav-item">
                    <div class="nav-icon"><i class="fas fa-user-plus"></i></div>
                    <div class="nav-text">Afiliados</div>
                </a><a href="depositos.php" class="nav-item">
                    <div class="nav-icon"><i class="fas fa-credit-card"></i></div>
                    <div class="nav-text">Depósitos</div>
                </a><a href="saques.php" class="nav-item">
                    <div class="nav-icon"><i class="fas fa-money-bill-wave"></i></div>
                    <div class="nav-text">Saques</div>
                </a>
            </div>
            <div class="nav-section">
                <div class="nav-section-title">Sistema</div><a href="config.php" class="nav-item">
                    <div class="nav-icon"><i class="fas fa-cogs"></i></div>
                    <div class="nav-text">Configurações</div>
                </a><a href="bonus.php" class="nav-item active">
                    <div class="nav-icon"><i class="fas fa-gift"></i></div>
                    <div class="nav-text">Bônus</div>
                </a><a href="gateway.php" class="nav-item">
                    <div class="nav-icon"><i class="fas fa-usd"></i></div>
                    <div class="nav-text">Gateway</div>
                </a><a href="banners.php" class="nav-item">
                    <div class="nav-icon"><i class="fas fa-images"></i></div>
                    <div class="nav-text">Banners</div>
                </a><a href="cartelas.php" class="nav-item">
                    <div class="nav-icon"><i class="fas fa-diamond"></i></div>
                    <div class="nav-text">Raspadinhas</div>
                </a><a href="../logout" class="nav-item">
                    <div class="nav-icon"><i class="fas fa-sign-out-alt"></i></div>
                    <div class="nav-text">Sair</div>
                </a>
            </div>
        </nav>
    </aside>

    <main class="main-content" id="mainContent">
        <header class="header"><button id="menuToggle" class="text-white lg:hidden"><i class="fas fa-bars text-2xl"></i></button><span></span></header>

        <div class="dashboard-content">
            <section class="welcome-section mb-12">
                <h2 class="welcome-title">Bônus de Primeiro Depósito</h2>
                <p class="welcome-subtitle">Ative e gerencie a oferta de boas-vindas para novos jogadores.</p>
            </section>

            <div class="activity-card">
                <form method="POST">
                    <div class="flex items-center mb-8">
                        <label for="is_active" class="text-white cursor-pointer whitespace-nowrap">Ativar Bônus de Primeiro Depósito</label>
                        <input type="checkbox" name="is_active" id="is_active" class="form-checkbox ml-4 accent-green-500" <?php echo $bonus_settings['is_active'] ? 'checked' : ''; ?>>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-x-6 gap-y-6">
                        <div class="form-group">
                            <label for="bonus_amount" class="form-label">Valor do Bônus</label>
                            <div class="input-wrapper">
                                <input type="text" inputmode="numeric" oninput="this.value = this.value.replace(/[^0-9]/g, '').slice(0, 3)" class="form-input input-adorned-right" name="bonus_amount" id="bonus_amount" value="<?php echo (int)($bonus_settings['bonus_amount'] ?? 0); ?>" placeholder="100">
                                <span class="input-adornment adornment-right">%</span>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="min_deposit" class="form-label">Depósito Mínimo</label>
                            <div class="input-wrapper">
                                <input type="text" inputmode="decimal" name="min_deposit" id="min_deposit" class="form-input input-adorned-right" value="<?php echo number_format($bonus_settings['min_deposit'] ?? 0, 2, ',', '.'); ?>" placeholder="50,00">
                                <span class="input-adornment adornment-right">R$</span>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="rollover_multiplier" class="form-label">Rollover</label>
                            <div class="input-wrapper">
                                <input type="text" inputmode="numeric" oninput="this.value = this.value.replace(/[^0-9]/g, '').slice(0, 2)" class="form-input input-adorned-right" name="rollover_multiplier" id="rollover_multiplier" value="<?php echo htmlspecialchars($bonus_settings['rollover_multiplier'] ?? '0'); ?>" placeholder="30">
                                <span class="input-adornment adornment-right">x</span>
                            </div>
                        </div>
                    </div>

                    <div class="mt-4 text-center">
                        <small class="text-gray-400 mt-2 block">O usuário precisará apostar o (valor do depósito + valor do bônus) X (multiplicador de rollover).</small>
                    </div>

                    <div class="mt-6 flex justify-center">
                        <button type="submit" name="salvar_bonus_deposito" class="btn-primary">Salvar Alterações</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="dashboard-content pt-0">
            <section class="welcome-section mb-12">
                <h2 class="welcome-title">Gerador de Códigos de Bônus</h2>
                <p class="welcome-subtitle">Crie códigos promocionais para os jogadores usarem na página de depósito.</p>
            </section>

            <div class="activity-card">
                <form method="POST">
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-x-6 gap-y-6">
                        <div class="form-group">
                            <label for="codigo" class="form-label">Código</label>
                            <div class="input-wrapper">
                                <input type="text" name="codigo" id="codigo" class="form-input uppercase" placeholder="Ex: JOGADORVIP" required maxlength="10">
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="tipo_bonus" class="form-label">Tipo de Bônus</label>
                            <div class="input-wrapper">
                                <select name="tipo_bonus" id="tipo_bonus" class="form-input">
                                    <option value="percent">Porcentagem (%)</option>
                                    <option value="fixo">Valor Fixo (R$)</option>
                                </select>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="valor_bonus" class="form-label">Valor</label>
                            <div class="input-wrapper">
                                <input type="text" name="valor_bonus" id="valor_bonus" class="form-input input-adorned-right" placeholder="50">
                                <span id="valor_bonus_adornment" class="input-adornment adornment-right">%</span>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="rollover_bonus" class="form-label">Rollover</label>
                            <div class="input-wrapper">
                                <input type="text" inputmode="numeric" oninput="this.value = this.value.replace(/[^0-9]/g, '').slice(0, 2)" name="rollover_bonus" id="rollover_bonus" class="form-input input-adorned-right" placeholder="10" required>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="data_expiracao" class="form-label">Data de Expiração</label>
                            <div class="input-wrapper">
                                <input type="datetime-local" name="data_expiracao" id="data_expiracao" class="form-input" style="color-scheme: dark;">
                            </div>
                        </div>
                    </div>
                    <div class="mt-6 flex justify-center">
                        <button type="submit" name="gerar_codigo_bonus" class="btn-primary">Gerar Código de Bônus</button>
                    </div>
                </form>
            </div>
        </div>
        <div class="dashboard-content pt-0">
            <section class="welcome-section mb-12">
                <h2 class="welcome-title">Códigos de Bônus Ativos</h2>
                <p class="welcome-subtitle">Gerencie os códigos promocionais existentes na plataforma.</p>
            </section>

            <div class="activity-card overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-700">
                    <thead class="bg-gray-800/50">
                        <tr>
                            <th scope="col" class="py-3.5 px-4 text-left text-sm font-semibold text-white">Código</th>
                            <th scope="col" class="py-3.5 px-4 text-left text-sm font-semibold text-white">Valor</th>
                            <th scope="col" class="py-3.5 px-4 text-left text-sm font-semibold text-white">Usos</th>
                            <th scope="col" class="py-3.5 px-4 text-left text-sm font-semibold text-white">Expiração</th>
                            <th scope="col" class="py-3.5 px-4 text-left text-sm font-semibold text-white">Status</th>
                            <th scope="col" class="relative py-3.5 px-4"><span class="sr-only">Ações</span></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-800">
                        <?php if (empty($codigos_bonus)): ?>
                            <tr>
                                <td colspan="7" class="whitespace-nowrap py-4 px-4 text-sm text-center text-gray-400">Nenhum código de bônus encontrado.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($codigos_bonus as $codigo): ?>
                                <tr>
                                    <td class="whitespace-nowrap py-4 px-4 text-sm font-medium text-white"><?= htmlspecialchars($codigo['codigo']) ?></td>
                                    <td class="whitespace-nowrap py-4 px-4 text-sm text-gray-300">
                                        <?= htmlspecialchars($codigo['tipo'] == 'fixo' ? 'R$ ' . number_format($codigo['valor'], 2, ',', '.') : $codigo['valor'] . '%') ?>
                                    </td>
                                    <td class="whitespace-nowrap py-4 px-4 text-sm text-gray-300"><?= $codigo['total_usos'] ?></td>
                                    <td class="whitespace-nowrap py-4 px-4 text-sm text-gray-300">
                                        <?= $codigo['data_expiracao'] ? date('d/m/Y H:i', strtotime($codigo['data_expiracao'])) : 'Nunca' ?>
                                    </td>
                                    <td class="whitespace-nowrap py-4 px-4 text-sm text-gray-300">
                                        <?php if ($codigo['status'] == 'ativo'): ?>
                                            <span class="inline-flex items-center rounded-md bg-green-500/10 px-2 py-1 text-xs font-medium text-green-400 ring-1 ring-inset ring-green-500/20">Ativo</span>
                                        <?php else: ?>
                                            <span class="inline-flex items-center rounded-md bg-red-500/10 px-2 py-1 text-xs font-medium text-red-400 ring-1 ring-inset ring-red-500/20">Inativo</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="whitespace-nowrap py-4 px-4 text-right text-sm font-medium">
                                        <a href="?action=toggle_status&id=<?= $codigo['id'] ?>" class="text-yellow-500 hover:text-yellow-700 mr-4"><?= $codigo['status'] == 'ativo' ? 'Desativar' : 'Ativar' ?></a>
                                        <a href="?action=delete&id=<?= $codigo['id'] ?>" onclick="return confirm('Tem certeza que deseja excluir este código? Esta ação não pode ser desfeita.')" class="text-red-500 hover:text-red-700">Excluir</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

    <script>
        document.body.classList.add('preload');
        window.addEventListener('load', () => {
            document.body.classList.remove('preload');
        });

        const menuToggle = document.getElementById('menuToggle');
        const sidebar = document.getElementById('sidebar');
        const overlay = document.getElementById('overlay');
        const toggleMenu = () => {
            sidebar.classList.toggle('hidden');
            overlay.classList.toggle('active');
        };
        menuToggle.addEventListener('click', toggleMenu);
        overlay.addEventListener('click', toggleMenu);

        document.addEventListener('DOMContentLoaded', () => {
            if (window.innerWidth <= 1024) {
                sidebar.classList.add('hidden');
            }
        });
        window.addEventListener('resize', () => {
            if (window.innerWidth <= 1024) {
                sidebar.classList.add('hidden');
                overlay.classList.remove('active');
            } else {
                sidebar.classList.remove('hidden');
            }
        });

        function formatMoney(e, maxDigits = 6) {
            let value = e.target.value.replace(/\D/g, '');
            if (value.length > maxDigits) {
                value = value.slice(0, maxDigits);
            }
            if (value === '' || !parseInt(value)) {
                e.target.value = '';
                return;
            }
            e.target.value = (parseInt(value, 10) / 100).toLocaleString('pt-BR', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            });
        }

        const minDepositInput = document.getElementById('min_deposit');
        if (minDepositInput) {
            minDepositInput.addEventListener('input', (e) => formatMoney(e, 5));
        }

        const tipoBonusSelect = document.getElementById('tipo_bonus');
        const valorBonusInput = document.getElementById('valor_bonus');
        const valorBonusAdornment = document.getElementById('valor_bonus_adornment');

        const setupValorField = () => {
            const tipo = tipoBonusSelect.value;
            valorBonusInput.value = '';

            valorBonusInput.removeEventListener('input', valorBonusInput.moneyHandler);
            valorBonusInput.removeEventListener('input', formatPercent);

            valorBonusInput.moneyHandler = (e) => formatMoney(e, 6);

            if (tipo === 'percent') {
                valorBonusAdornment.textContent = '%';
                valorBonusInput.placeholder = '50';
                valorBonusInput.setAttribute('inputmode', 'numeric');
                valorBonusInput.addEventListener('input', formatPercent);
            } else {
                valorBonusAdornment.textContent = 'R$';
                valorBonusInput.placeholder = '20,00';
                valorBonusInput.setAttribute('inputmode', 'decimal');
                valorBonusInput.addEventListener('input', valorBonusInput.moneyHandler);
            }
        };

        const formatPercent = (e) => {
            e.target.value = e.target.value.replace(/[^0-9]/g, '').slice(0, 3);
        };

        tipoBonusSelect.addEventListener('change', setupValorField);
        document.addEventListener('DOMContentLoaded', setupValorField);

        document.addEventListener('DOMContentLoaded', () => {
            const elements = document.querySelectorAll('.welcome-section, .activity-card');
            elements.forEach((el, index) => {
                el.style.opacity = '0';
                el.style.transform = 'translateY(20px)';
                el.style.transition = `opacity 0.6s ease ${index * 0.1}s, transform 0.6s ease ${index * 0.1}s`;
                setTimeout(() => {
                    el.style.opacity = '1';
                    el.style.transform = 'translateY(0)';
                }, 100);
            });
        });
    </script>

    <script>
        <?php
        if (isset($_SESSION['message'])) {
            $type = $_SESSION['message']['type'];
            $text = addslashes($_SESSION['message']['text']);
            if ($type === 'success') {
                echo "Notiflix.Notify.success('$text');";
            } else if ($type === 'warning') {
                echo "Notiflix.Notify.warning('$text');";
            }
            unset($_SESSION['message']);
        }
        ?>
    </script>
</body>

</html>