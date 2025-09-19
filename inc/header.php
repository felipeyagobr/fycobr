<?php
@session_start();

$current_page = $_SERVER['REQUEST_URI'];

if (file_exists('./conexao.php')) {
    include('./conexao.php');
} elseif (file_exists('../inc/conexao.php')) {
    include('../inc/conexao.php');
} elseif (file_exists('../../inc/conexao.php')) {
    include('../../inc/conexao.php');
}

// Buscar configurações do site
try {
    $stmt = $pdo->prepare("SELECT * FROM config WHERE id = 1 LIMIT 1");
    $stmt->execute();
    $config = $stmt->fetch(PDO::FETCH_ASSOC);

    $nomeSite = $config['nome_site'] ?? 'Raspadinha';
    $logoSite = $config['logo'] ?? null;
    $depositoMin = $config['deposito_min'] ?? 0;
    $saqueMin = $config['saque_min'] ?? 0;
    $cpaPadrao = $config['cpa_padrao'] ?? 0;
    $revshare_padrao = $config['revshare_padrao'] ?? 0;
} catch (PDOException $e) {
    $nomeSite = 'Raspadinha';
    $logoSite = null;
    $depositoMin = 0;
    $saqueMin = 0;
    $cpaPadrao = 0;
    $revshare_padrao = 0;
}

if (isset($_SESSION['usuario_id'])) {
    $usuario_id = $_SESSION['usuario_id'];

    try {
        $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE id = :id LIMIT 1");
        $stmt->bindParam(':id', $usuario_id, PDO::PARAM_INT);
        $stmt->execute();

        $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$usuario) {
            $_SESSION['message'] = ['type' => 'failure', 'text' => 'Usuário Não existe!'];
        }

        if ($usuario['banido'] == 1) {
            $_SESSION = [];

            session_destroy();
            @session_start();
            $_SESSION['message'] = ['type' => 'failure', 'text' => 'Você está banido!'];
            sleep(2);
            if ($_SESSION['message']) {
                header("Location: /");
            }
        }
    } catch (PDOException $e) {
        $_SESSION['message'] = ['type' => 'failure', 'text' => 'Erro na consulta!'];
        echo "Erro na consulta: " . $e->getMessage();
    }
}
?>

<!-- Bootstrap Icons -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">

<!-- App Download Banner (optional - like BetSpy) -->
<div class="app-download-banner" id="appBanner" style="display: none;">
    <i class="bi bi-download"></i>
    <span>Baixe nosso app e ganhe muitos pontos!</span>
    <button class="download-btn" onclick="openInstallModal()">
        Baixar
    </button>
    <button class="close-banner" onclick="closeBanner()">
        <i class="bi bi-x"></i>
    </button>
</div>

<header class="header">
    <div class="header-container">
        <!-- Logo -->
        <a href="/" class="logo">
            <?php if ($logoSite && file_exists($_SERVER['DOCUMENT_ROOT'] . $logoSite)): ?>
                <img src="<?= htmlspecialchars($logoSite) ?>" alt="<?= htmlspecialchars($nomeSite) ?>" class="logo-image">
            <?php else: ?>
                <div class="logo-icon">
                    <?= strtoupper(substr($nomeSite, 0, 1)) ?>
                </div>
            <?php endif; ?>
        </a>

        <!-- Mobile Menu Button -->
        <div class="mobile-menu-btn">
            <i id="menuBtn" class="bi bi-list"></i>
        </div>

        <!-- Navigation -->
        <nav>
            <ul class="nav-menu">
                <li><a href="/" class="nav-link"><svg width="1em" height="1em" viewBox="0 0 512 512" fill="currentColor" xmlns="http://www.w3.org/2000/svg" class="size-4">
                            <path d="M416 174.74V48h-80v58.45L256 32 0 272h64v208h144V320h96v160h144V272h64z"></path>
                        </svg> Início</a></li>
                <li><a href="/cartelas" class="nav-link"><svg fill="currentColor" viewBox="0 0 256 256" width="1em" height="1em" xmlns="http://www.w3.org/2000/svg" class="size-4">
                            <path d="M198.51 56.09C186.44 35.4 169.92 24 152 24h-48c-17.92 0-34.44 11.4-46.51 32.09C46.21 75.42 40 101 40 128s6.21 52.58 17.49 71.91C69.56 220.6 86.08 232 104 232h48c17.92 0 34.44-11.4 46.51-32.09C209.79 180.58 216 155 216 128s-6.21-52.58-17.49-71.91Zm1.28 63.91h-32a152.8 152.8 0 0 0-9.68-48h30.59c6.12 13.38 10.16 30 11.09 48Zm-20.6-64h-28.73a83 83 0 0 0-12-16H152c10 0 19.4 6 27.19 16ZM152 216h-13.51a83 83 0 0 0 12-16h28.73C171.4 210 162 216 152 216Zm36.7-32h-30.58a152.8 152.8 0 0 0 9.68-48h32c-.94 18-4.98 34.62-11.1 48Z"></path>
                        </svg> Raspadinhas</a></li>
                <li><a href="/afiliados" class="nav-link"><i class="bi bi-gift-fill"></i> Indique e Ganhe</a></li>
            </ul>
        </nav>

        <!-- Header Actions -->
        <div class="header-actions">
            <?php if (!isset($usuario)): ?>
                <!-- Not Logged In -->
                <a href="/login" class="btn-login">
                    <span class="login-text">Entrar</span>
                </a>
                <a href="/cadastro" class="btn-register">
                    <i class="bi bi-person-plus-fill"></i>
                    Registrar
                </a>
            <?php else: ?>
                <!-- Logged In -->
                <?php
                $partesNome = explode(' ', $usuario['nome']);
                $primeiroNome = $partesNome[0];
                if (isset($partesNome[1])) {
                    $sobrenome = substr($partesNome[1], 0, 2);
                    $nomeExibicao = $primeiroNome . ' ' . $sobrenome . '...';
                } else {
                    $nomeExibicao = $primeiroNome;
                }
                ?>

                <!-- Balance Display -->
                <div class="balance-display-v2" id="balance-container">
                    <?php
                    // Calcula os saldos aqui para usar em ambos os locais
                    $saldo_real = $usuario['saldo'] ?? 0;
                    $saldo_bonus = $usuario['saldo_bonus'] ?? 0;
                    $saldo_total = $saldo_real + $saldo_bonus;
                    ?>
                    <button class="balance-toggle-btn" id="balance-toggle-btn">
                        <span class="font-semibold">R$ <?= number_format($saldo_total, 2, ',', '.') ?></span> <i id="balance-arrow-icon" class="bi bi-chevron-down"></i>
                    </button>

                    <div class="balance-popup" id="balance-popup">
                        <div class="balance-row">
                            <span class="balance-label">Saldo</span>
                            <span class="balance-value"><span class="balance-currency">R$</span> <?= number_format($saldo_real, 2, ',', '.') ?></span>
                        </div>
                        <div class="balance-row">
                            <span class="balance-label">Bônus</span>
                            <span class="balance-value"><span class="balance-currency">R$</span> <?= number_format($saldo_bonus, 2, ',', '.') ?></span>
                        </div>
                        <div class="balance-divider"></div>
                        <div class="balance-row total">
                            <span class="balance-label-total">Total</span>
                            <span class="balance-value"> <span class="balance-currency">R$</span> <?= number_format($saldo_total, 2, ',', '.') ?>
                            </span>
                        </div>
                        <p class="balance-info">O saldo total é a soma do seu saldo e bônus.</p>

                        <button onclick="openWithdrawModal(<?php echo $usuario['saldo']; ?>)" class="balance-withdraw-btn">
                            <svg viewBox="0 0 24 24" fill="currentColor" width="1em" height="1em" xmlns="http://www.w3.org/2000/svg" class="size-4">
                                <path d="M22 2H2a1 1 0 0 0-1 1v8a1 1 0 0 0 1 1h3v9a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1v-9h3a1 1 0 0 0 1-1V3a1 1 0 0 0-1-1ZM7 20v-2a2 2 0 0 1 2 2Zm10 0h-2a2 2 0 0 1 2-2Zm0-4a4 4 0 0 0-4 4h-2a4 4 0 0 0-4-4V8h10Zm4-6h-2V7a1 1 0 0 0-1-1H6a1 1 0 0 0-1 1v3H3V4h18Zm-9 5a3 3 0 1 0-3-3 3 3 0 0 0 3 3Zm0-4a1 1 0 1 1-1 1 1 1 0 0 1 1-1Z"></path>
                            </svg>
                            Sacar
                        </button>
                    </div>
                </div>

                <!-- Deposit Button -->
                <button onclick="openDepositModal()" class="btn-deposit">
                    <svg fill="none" viewBox="0 0 24 24" width="1em" height="1em" xmlns="http://www.w3.org/2000/svg" class="size-[1.6rem]">
                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 15v3m0 3v-3m0 0h-3m3 0h3"></path>
                        <path fill="currentColor" fill-rule="evenodd" d="M5 5a3 3 0 0 0-3 3v8a3 3 0 0 0 3 3h7.083A6 6 0 0 1 12 18c0-1.148.322-2.22.881-3.131A3 3 0 0 1 9 12a3 3 0 1 1 5.869.881A5.97 5.97 0 0 1 18 12c1.537 0 2.939.578 4 1.528V8a3 3 0 0 0-3-3zm7 6a1 1 0 1 0 0 2 1 1 0 0 0 0-2z" clip-rule="evenodd"></path>
                    </svg>
                    <span class="deposit-text">Depositar</span>
                </button>

                <!-- Withdraw Button -->
                <button onclick="openWithdrawModal(<?php echo $usuario['saldo']; ?>)" class="btn-withdraw">
                    <svg viewBox="0 0 24 24" fill="currentColor" width="1em" height="1em" xmlns="http://www.w3.org/2000/svg" class="size-4">
                        <path d="M22 2H2a1 1 0 0 0-1 1v8a1 1 0 0 0 1 1h3v9a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1v-9h3a1 1 0 0 0 1-1V3a1 1 0 0 0-1-1ZM7 20v-2a2 2 0 0 1 2 2Zm10 0h-2a2 2 0 0 1 2-2Zm0-4a4 4 0 0 0-4 4h-2a4 4 0 0 0-4-4V8h10Zm4-6h-2V7a1 1 0 0 0-1-1H6a1 1 0 0 0-1 1v3H3V4h18Zm-9 5a3 3 0 1 0-3-3 3 3 0 0 0 3 3Zm0-4a1 1 0 1 1-1 1 1 1 0 0 1 1-1Z"></path>
                    </svg>
                    <span class="withdraw-text">Sacar</span>
                </button>

                <!-- User Dropdown -->
                <div class="user-dropdown">
                    <button id="userDropdownBtn" class="user-btn">
                        <?php
                        // Define a URL do avatar para o cabeçalho
                        $headerAvatarUrl = !empty($usuario['avatar']) ? '/' . htmlspecialchars($usuario['avatar']) : '/assets/avatar/default.png';
                        ?>
                        <img src="<?= $headerAvatarUrl ?>" alt="Avatar" style="width: 30px; height: 30px; border-radius: 50%; object-fit: cover; margin-right: 5px;">
                        <span class="user-name"><?php echo htmlspecialchars($nomeExibicao); ?></span>
                        <i class="bi bi-chevron-down dropdown-arrow"></i>
                    </button>

                    <div id="userDropdownMenu" class="dropdown-menu">
                        <?php if ($usuario['admin'] == 1): ?>
                            <a href="/admin" class="dropdown-item">
                                <i class="bi bi-gear"></i>
                                Administrador
                            </a>
                        <?php endif; ?>

                        <a href="/perfil" class="dropdown-item">
                            <i class="bi bi-person"></i>
                            Conta
                        </a>

                        <button onclick="openWithdrawModal(<?php echo $usuario['saldo']; ?>)" class="dropdown-item">
                            <svg viewBox="0 0 24 24" fill="currentColor" width="1em" height="1em" xmlns="http://www.w3.org/2000/svg" class="size-4">
                                <path d="M22 2H2a1 1 0 0 0-1 1v8a1 1 0 0 0 1 1h3v9a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1v-9h3a1 1 0 0 0 1-1V3a1 1 0 0 0-1-1ZM7 20v-2a2 2 0 0 1 2 2Zm10 0h-2a2 2 0 0 1 2-2Zm0-4a4 4 0 0 0-4 4h-2a4 4 0 0 0-4-4V8h10Zm4-6h-2V7a1 1 0 0 0-1-1H6a1 1 0 0 0-1 1v3H3V4h18Zm-9 5a3 3 0 1 0-3-3 3 3 0 0 0 3 3Zm0-4a1 1 0 1 1-1 1 1 1 0 0 1 1-1Z"></path>
                            </svg>
                            Sacar
                        </button>

                        <a href="/apostas" class="dropdown-item">
                            <i class="bi bi-controller"></i>
                            Histórico de Jogos
                        </a>

                        <a href="/transacoes" class="dropdown-item">
                            <i class="bi bi-arrow-left-right"></i>
                            Transações
                        </a>

                        <div class="dropdown-divider"></div>

                        <a href="/logout" class="dropdown-item logout">
                            <i class="bi bi-box-arrow-right"></i>
                            Sair
                        </a>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/notiflix@3.2.8/dist/notiflix-aio-3.2.8.min.js"></script>
        <link href="https://cdn.jsdelivr.net/npm/notiflix@3.2.8/src/notiflix.min.css" rel="stylesheet">

</header>

<!-- Mobile Sidebar -->
<aside id="sidebar" class="mobile-sidebar">
    <div class="sidebar-header">
        <div class="sidebar-logo">
            <?php if ($logoSite && file_exists($_SERVER['DOCUMENT_ROOT'] . $logoSite)): ?>
                <img src="<?= htmlspecialchars($logoSite) ?>" alt="<?= htmlspecialchars($nomeSite) ?>" class="sidebar-logo-image">
            <?php else: ?>
                <div class="sidebar-logo-icon">
                    <?= strtoupper(substr($nomeSite, 0, 1)) ?>
                </div>
            <?php endif; ?>
        </div>
        <button id="closeSidebar" class="close-btn">
            <i class="bi bi-x"></i>
        </button>
    </div>

    <nav class="sidebar-nav">
        <a href="/" class="bottom-nav-item <?php echo ($current_page == '/') ? 'active' : ''; ?>">
            <svg width="1em" height="1em" viewBox="0 0 512 512" fill="currentColor" xmlns="http://www.w3.org/2000/svg" class="size-4">
                <path d="M416 174.74V48h-80v58.45L256 32 0 272h64v208h144V320h96v160h144V272h64z"></path>
            </svg>
            <span>Início</span>
        </a>

        <a href="/cartelas" class="sidebar-item">
            <i class="bi bi-ticket-perforated"></i>
            <span>Raspadinhas</span>
        </a>

        <!-- <a href="/bingo" class="sidebar-item">
            <i class="bi bi-grid"></i>
            <span>Bingo</span>
        </a> -->

        <?php if (isset($_SESSION['usuario_id'])): ?>
            <div class="sidebar-divider"></div>

            <a href="/apostas" class="sidebar-item">
                <i class="bi bi-ticket-perforated"></i>
                <span>Minhas Apostas</span>
            </a>

            <a href="/transacoes" class="sidebar-item">
                <i class="bi bi-arrow-left-right"></i>
                <span>Transações</span>
            </a>

            <button onclick="openDepositModal()" class="sidebar-item">
                <i class="bi bi-piggy-bank"></i>
                <span>Depositar</span>
            </button>

            <button onclick="openWithdrawModal(<?php echo $usuario['saldo']; ?>)" class="sidebar-item">
                <svg viewBox="0 0 24 24" fill="currentColor" width="1em" height="1em" xmlns="http://www.w3.org/2000/svg" class="size-4">
                    <path d="M22 2H2a1 1 0 0 0-1 1v8a1 1 0 0 0 1 1h3v9a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1v-9h3a1 1 0 0 0 1-1V3a1 1 0 0 0-1-1ZM7 20v-2a2 2 0 0 1 2 2Zm10 0h-2a2 2 0 0 1 2-2Zm0-4a4 4 0 0 0-4 4h-2a4 4 0 0 0-4-4V8h10Zm4-6h-2V7a1 1 0 0 0-1-1H6a1 1 0 0 0-1 1v3H3V4h18Zm-9 5a3 3 0 1 0-3-3 3 3 0 0 0 3 3Zm0-4a1 1 0 1 1-1 1 1 1 0 0 1 1-1Z"></path>
                </svg>
                <span>Sacar</span>
            </button>

            <div class="sidebar-divider"></div>

            <a href="/logout" class="sidebar-item logout">
                <i class="bi bi-box-arrow-right"></i>
                <span>Sair</span>
            </a>
        <?php endif; ?>

        <div class="sidebar-divider"></div>

        <a href="https://t.me/daanrox" target="_blank" class="sidebar-item">
            <i class="bi bi-telegram"></i>
            <span>Suporte</span>
        </a>
    </nav>
</aside>

<!-- Mobile Backdrop -->
<div id="backdrop" class="mobile-backdrop"></div>

<!-- Bottom Navigation (NEW) -->
<nav class="bottom-navigation">
    <div class="bottom-nav-container">
        <?php if (!isset($usuario)): ?>
            <a href="/" class="bottom-nav-item <?php echo ($current_page == '/') ? 'active' : ''; ?>">
                <svg width="1em" height="1em" viewBox="0 0 512 512" fill="currentColor" xmlns="http://www.w3.org/2000/svg" class="size-4">
                    <path d="M416 174.74V48h-80v58.45L256 32 0 272h64v208h144V320h96v160h144V272h64z"></path>
                </svg>
                <span>Início</span>
            </a>
            <a href="/cartelas" class="bottom-nav-item <?php echo (strpos($current_page, 'cartelas') !== false) ? 'active' : ''; ?>">
                <i class="bi bi-ticket-perforated"></i>
                <span>Raspadinhas</span>
            </a>
            <a href="/login" class="bottom-nav-item deposit-btn">
                <svg fill="none" viewBox="0 0 24 24" width="1em" height="1em" xmlns="http://www.w3.org/2000/svg" class="size-[1.6rem]">
                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 15v3m0 3v-3m0 0h-3m3 0h3"></path>
                    <path fill="currentColor" fill-rule="evenodd" d="M5 5a3 3 0 0 0-3 3v8a3 3 0 0 0 3 3h7.083A6 6 0 0 1 12 18c0-1.148.322-2.22.881-3.131A3 3 0 0 1 9 12a3 3 0 1 1 5.869.881A5.97 5.97 0 0 1 18 12c1.537 0 2.939.578 4 1.528V8a3 3 0 0 0-3-3zm7 6a1 1 0 1 0 0 2 1 1 0 0 0 0-2z" clip-rule="evenodd"></path>
                </svg>
                <span>Depositar</span>
                <a href="/afiliados" class="bottom-nav-item <?php echo (strpos($current_page, 'afiliados') !== false) ? 'active' : ''; ?>">
                    <i class="bi bi-gift-fill"></i>
                    <span>Prêmios</span>
                </a>
                <a href="/login" class="bottom-nav-item <?php echo (strpos($current_page, 'login') !== false) ? 'active' : ''; ?>">
                    <i class="bi bi-person-fill"></i>
                    <span>Perfil</span>
                </a>
            <?php else: ?>
                <a href="/" class="bottom-nav-item <?php echo ($current_page == '/') ? 'active' : ''; ?>">
                    <svg width="1em" height="1em" viewBox="0 0 512 512" fill="currentColor" xmlns="http://www.w3.org/2000/svg" class="size-4">
                        <path d="M416 174.74V48h-80v58.45L256 32 0 272h64v208h144V320h96v160h144V272h64z"></path>
                    </svg>
                    <span>Início</span>
                </a>
                <a href="/cartelas" class="bottom-nav-item <?php echo (strpos($current_page, 'cartelas') !== false) ? 'active' : ''; ?>">
                    <i class="bi bi-ticket-perforated"></i>
                    <span>Raspadinhas</span>
                </a>
                <button onclick="openDepositModal()" class="bottom-nav-item deposit-btn">
                    <svg fill="none" viewBox="0 0 24 24" width="1em" height="1em" xmlns="http://www.w3.org/2000/svg" class="size-[1.6rem]">
                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 15v3m0 3v-3m0 0h-3m3 0h3"></path>
                        <path fill="currentColor" fill-rule="evenodd" d="M5 5a3 3 0 0 0-3 3v8a3 3 0 0 0 3 3h7.083A6 6 0 0 1 12 18c0-1.148.322-2.22.881-3.131A3 3 0 0 1 9 12a3 3 0 1 1 5.869.881A5.97 5.97 0 0 1 18 12c1.537 0 2.939.578 4 1.528V8a3 3 0 0 0-3-3zm7 6a1 1 0 1 0 0 2 1 1 0 0 0 0-2z" clip-rule="evenodd"></path>
                    </svg>
                    <span>Depositar</span>
                </button>
                <a href="/afiliados" class="bottom-nav-item <?php echo (strpos($current_page, 'afiliados') !== false) ? 'active' : ''; ?>">
                    <i class="bi bi-person-plus-fill"></i>
                    <span>Indique</span>
                </a>
                <a href="/perfil" class="bottom-nav-item <?php echo (strpos($current_page, 'perfil') !== false) ? 'active' : ''; ?>">
                    <i class="bi bi-person-fill"></i>
                    <span>Perfil</span>
                </a>
            <?php endif; ?>
    </div>
</nav>

<!-- Install App Modal -->
<div id="installModal" class="install-modal">
    <div class="install-modal-content">
        <div class="install-modal-header">
            <div class="install-modal-icon">
                <i class="bi bi-phone"></i>
            </div>
            <h2>Instale Nosso App</h2>
            <button class="install-modal-close" onclick="closeInstallModal()">
                <i class="bi bi-x"></i>
            </button>
        </div>

        <div class="install-benefits">
            <div class="benefits-title">
                <i class="bi bi-gift-fill"></i>
                <span>Vantagens do App</span>
            </div>

            <div class="benefits-list">
                <div class="benefit-item">
                    <i class="bi bi-check-circle"></i>
                    <span>Acesso rápido direto da tela inicial</span>
                </div>
                <div class="benefit-item">
                    <i class="bi bi-check-circle"></i>
                    <span>Notificações de promoções exclusivas</span>
                </div>
                <div class="benefit-item">
                    <i class="bi bi-check-circle"></i>
                    <span>Experiência mais fluida e rápida</span>
                </div>
                <div class="benefit-item">
                    <i class="bi bi-check-circle"></i>
                    <span>Funciona mesmo offline</span>
                </div>
                <div class="benefit-item">
                    <i class="bi bi-check-circle"></i>
                    <span>Design otimizado para mobile</span>
                </div>
            </div>
        </div>

        <div class="install-instructions">
            <div class="platform-tabs">
                <button class="platform-tab active" data-platform="ios">
                    <i class="bi bi-apple"></i>
                    iOS (iPhone/iPad)
                </button>
                <button class="platform-tab" data-platform="android">
                    <i class="bi bi-android2"></i>
                    Android
                </button>
            </div>

            <div class="platform-content active" data-platform="ios">
                <div class="instruction-step">
                    <div class="step-number">1</div>
                    <div class="step-content">
                        <h4>Abra no Safari</h4>
                        <p>Este site deve ser aberto no navegador Safari</p>
                    </div>
                </div>

                <div class="instruction-step">
                    <div class="step-number">2</div>
                    <div class="step-content">
                        <h4>Toque no botão Compartilhar</h4>
                        <p><i class="bi bi-share"></i> Na barra inferior do Safari</p>
                    </div>
                </div>

                <div class="instruction-step">
                    <div class="step-number">3</div>
                    <div class="step-content">
                        <h4>Selecione "Adicionar à Tela de Início"</h4>
                        <p><i class="bi bi-plus-square"></i> Role para baixo se necessário</p>
                    </div>
                </div>

                <div class="instruction-step">
                    <div class="step-number">4</div>
                    <div class="step-content">
                        <h4>Confirme tocando em "Adicionar"</h4>
                        <p>O app aparecerá na sua tela inicial</p>
                    </div>
                </div>
            </div>

            <div class="platform-content" data-platform="android">
                <div class="instruction-step">
                    <div class="step-number">1</div>
                    <div class="step-content">
                        <h4>Abra no Chrome</h4>
                        <p>Recomendamos usar o Google Chrome</p>
                    </div>
                </div>

                <div class="instruction-step">
                    <div class="step-number">2</div>
                    <div class="step-content">
                        <h4>Toque no menu (3 pontos)</h4>
                        <p><i class="bi bi-three-dots-vertical"></i> No canto superior direito</p>
                    </div>
                </div>

                <div class="instruction-step">
                    <div class="step-number">3</div>
                    <div class="step-content">
                        <h4>Selecione "Instalar app" ou "Adicionar à tela inicial"</h4>
                        <p><i class="bi bi-house-add"></i> Pode aparecer automaticamente uma notificação</p>
                    </div>
                </div>

                <div class="instruction-step">
                    <div class="step-number">4</div>
                    <div class="step-content">
                        <h4>Confirme a instalação</h4>
                        <p>O app será adicionado ao seu dispositivo</p>
                    </div>
                </div>
            </div>

            <div class="desktop-note">
                <div class="platform-tab">
                    <i class="bi bi-display"></i>
                    Desktop
                </div>
                <p>No desktop, procure pelo ícone de instalação na barra de endereço do seu navegador ou um banner de instalação aparecerá automaticamente.</p>
            </div>
        </div>

        <div class="install-modal-footer">
            <button class="install-understand-btn" onclick="closeInstallModal()">
                Entendi, obrigado!
            </button>
        </div>
    </div>
</div>

<style>
    /* Header Styles */
    .header {
        background: rgba(0, 0, 0, 0.95);
        backdrop-filter: blur(20px);
        border-bottom: 1px solid rgba(255, 255, 255, 0.05);
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        z-index: 1000;
        padding: 1rem 0;
    }

    .header-container {
        max-width: 1400px;
        margin: 0 auto;
        padding: 0 1rem;
        /* Padding horizontal reduzido para 1rem */
        display: flex;
        align-items: center;
        justify-content: flex-start;
        /* Alterado */
    }

    /* Logo Styles - Apenas logo, sem texto */
    .logo {
        display: flex;
        align-items: center;
        text-decoration: none;
        transition: all 0.3s ease;
    }

    /*
    .logo:hover {
         transform: scale(1.05); 
    }
    */

    .logo-image {
        height: 35px;
        width: auto;
        max-width: 200px;
        object-fit: contain;
        transition: all 0.3s ease;
    }

    /*
    .logo-image:hover {
        transform: scale(1.02);
    }
     */

    .logo-icon {
        width: 45px;
        height: 45px;
        background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%);
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.4rem;
        color: #ffffff;
        font-weight: 800;
        box-shadow:
            0 8px 20px rgba(34, 197, 94, 0.3),
            0 4px 8px rgba(0, 0, 0, 0.2);
        transition: all 0.3s ease;
    }

    .logo-icon:hover {
        box-shadow:
            0 12px 30px rgba(34, 197, 94, 0.4),
            0 6px 12px rgba(0, 0, 0, 0.3);
        transform: translateY(-1px);
    }

    /* Sidebar Logo Styles - Apenas logo, sem texto */
    .sidebar-logo {
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .sidebar-logo-image {
        height: 40px;
        width: auto;
        max-width: 150px;
        object-fit: contain;
    }

    .sidebar-logo-icon {
        width: 40px;
        height: 40px;
        background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%);
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.2rem;
        color: #ffffff;
        font-weight: 800;
        box-shadow: 0 4px 12px rgba(34, 197, 94, 0.3);
    }

    /* App Download Banner */
    .app-download-banner {
        background-color: #78d403;
        color: black;
        padding: 0.6rem 1rem;
        font-size: 0.85rem;
        font-weight: 600;
        text-align: center;
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        z-index: 1001;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 0.75rem;
        transition: all 0.3s ease;
        border-bottom: 1px solid rgba(255, 255, 255, 0.2);
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
    }

    .app-download-banner i {
        font-size: 1rem;
    }

    .download-btn {
        background: black;
        border: 1px solid rgba(255, 255, 255, 0.3);
        color: white;
        padding: 0.4rem 1rem;
        border-radius: 20px;
        font-size: 0.8rem;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s ease;
        margin-left: 0.5rem;
    }

    .download-btn:hover {
        background: rgba(0, 0, 0, 0.3);
        transform: translateY(-1px);
    }

    .close-banner {
        position: absolute;
        right: 1rem;
        background: none;
        border: none;
        color: white;
        font-size: 1.2rem;
        cursor: pointer;
        opacity: 0.8;
        transition: opacity 0.3s ease;
        padding: 0.25rem;
        border-radius: 4px;
    }

    .close-banner:hover {
        opacity: 1;
        background: rgba(255, 255, 255, 0.1);
    }

    /* Header position adjustments */
    .header {
        top: 0;
        position: fixed;
        background: rgba(0, 0, 0, 0.95);
        backdrop-filter: blur(20px);
        border-bottom: 1px solid rgba(255, 255, 255, 0.05);
        left: 0;
        right: 0;
        z-index: 1000;
        padding: 1rem 0;
        transition: top 0.3s ease;
    }

    .header.with-banner {
        top: 45px;
    }

    /* Install Modal Styles */
    .install-modal {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.8);
        backdrop-filter: blur(10px);
        display: none;
        align-items: center;
        justify-content: center;
        z-index: 2000;
        padding: 1rem;
    }

    .install-modal.active {
        display: flex;
    }

    .install-modal-content {
        background: linear-gradient(145deg, #1a1a1a 0%, #2a2a2a 100%);
        border: 1px solid rgba(34, 197, 94, 0.3);
        border-radius: 20px;
        max-width: 500px;
        width: 100%;
        max-height: 90vh;
        overflow-y: auto;
        box-shadow: 0 20px 60px rgba(0, 0, 0, 0.5);
    }

    .install-modal-header {
        padding: 2rem 2rem 1rem;
        text-align: center;
        position: relative;
        border-bottom: 1px solid rgba(255, 255, 255, 0.1);
    }

    .install-modal-icon {
        width: 60px;
        height: 60px;
        background: linear-gradient(135deg, #22c55e, #16a34a);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 1rem;
        font-size: 1.8rem;
        color: white;
        box-shadow: 0 8px 20px rgba(34, 197, 94, 0.3);
    }

    .install-modal-header h2 {
        color: white;
        font-size: 1.5rem;
        font-weight: 700;
        margin: 0;
    }

    .install-modal-close {
        position: absolute;
        top: 1rem;
        right: 1rem;
        background: none;
        border: none;
        color: #9ca3af;
        font-size: 1.5rem;
        cursor: pointer;
        transition: color 0.3s ease;
        padding: 0.5rem;
        border-radius: 8px;
    }

    .install-modal-close:hover {
        color: white;
        background: rgba(255, 255, 255, 0.1);
    }

    .install-benefits {
        padding: 1.5rem 2rem;
        border-bottom: 1px solid rgba(255, 255, 255, 0.1);
    }

    .benefits-title {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        color: #78d403;
        font-weight: 600;
        margin-bottom: 1rem;
        font-size: 1.1rem;
    }

    .benefits-title i {
        font-size: 1.2rem;
    }

    .benefits-list {
        display: grid;
        gap: 0.75rem;
    }

    .benefit-item {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        color: #e5e7eb;
        font-size: 0.9rem;
    }

    .benefit-item i {
        color: #78d403;
        font-size: 1rem;
        flex-shrink: 0;
    }

    .install-instructions {
        padding: 1.5rem 2rem;
    }

    .platform-tabs {
        display: flex;
        gap: 0.5rem;
        margin-bottom: 1.5rem;
    }

    .platform-tab {
        flex: 1;
        background: rgba(255, 255, 255, 0.05);
        border: 1px solid rgba(255, 255, 255, 0.1);
        color: #9ca3af;
        padding: 0.75rem 1rem;
        border-radius: 12px;
        font-size: 0.85rem;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s ease;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 0.5rem;
    }

    .platform-tab.active {
        background: linear-gradient(135deg, #22c55e, #16a34a);
        border-color: rgba(34, 197, 94, 0.5);
        color: white;
    }

    .platform-tab:hover:not(.active) {
        background: rgba(255, 255, 255, 0.08);
        color: white;
    }

    .platform-content {
        display: none;
    }

    .platform-content.active {
        display: block;
    }

    .instruction-step {
        display: flex;
        gap: 1rem;
        margin-bottom: 1.5rem;
        align-items: flex-start;
    }

    .step-number {
        width: 32px;
        height: 32px;
        background: linear-gradient(135deg, #22c55e, #16a34a);
        color: white;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 700;
        font-size: 0.9rem;
        flex-shrink: 0;
    }

    .step-content h4 {
        color: white;
        font-size: 1rem;
        font-weight: 600;
        margin: 0 0 0.25rem 0;
    }

    .step-content p {
        color: #9ca3af;
        font-size: 0.9rem;
        margin: 0;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .step-content p i {
        color: #78d403;
    }

    .desktop-note {
        margin-top: 1.5rem;
        padding: 1rem;
        background: rgba(59, 130, 246, 0.1);
        border: 1px solid rgba(59, 130, 246, 0.2);
        border-radius: 12px;
    }

    .desktop-note .platform-tab {
        background: none;
        border: none;
        color: #60a5fa;
        padding: 0;
        margin-bottom: 0.5rem;
        justify-content: flex-start;
        cursor: default;
    }

    .desktop-note p {
        color: #e5e7eb;
        font-size: 0.9rem;
        margin: 0;
        line-height: 1.4;
    }

    .install-modal-footer {
        padding: 1.5rem 2rem 2rem;
        text-align: center;
    }

    .install-understand-btn {
        background: linear-gradient(135deg, #22c55e, #16a34a);
        color: white;
        border: none;
        padding: 0.875rem 2rem;
        border-radius: 25px;
        font-size: 1rem;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s ease;
        box-shadow: 0 4px 15px rgba(34, 197, 94, 0.3);
    }

    .install-understand-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(34, 197, 94, 0.4);
    }

    /* Bottom Navigation Styles - Ícone Grande Centralizado */
    .bottom-navigation {
        position: fixed;
        bottom: 1rem;
        left: 1rem;
        right: 1rem;
        background: rgba(30, 30, 30, 0.85);
        backdrop-filter: blur(15px);
        border: 1px solid rgba(255, 255, 255, 0.1);
        border-radius: 50px;
        /* Bordas bem arredondadas (estilo pílula) */
        z-index: 999;
        box-shadow: 0 8px 30px rgba(0, 0, 0, 0.4);
        max-width: 500px;
        margin: 0 auto;
    }

    .bottom-nav-container {
        height: 55px;
        /* Altura fixa para alinhar o botão central */
        display: flex;
        justify-content: space-around;
        align-items: center;
        padding: 0 1rem;
    }

    .bottom-nav-item {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        text-decoration: none;
        color: #ffffff;
        transition: all 0.3s ease;
        background: none;
        border: none;
        cursor: pointer;
        font-family: inherit;
        flex: 1;
        /* Para que todos ocupem o mesmo espaço */
    }

    .bottom-nav-item i {
        font-size: 1.2rem;
        margin-bottom: 0.2rem;
    }

    .bottom-nav-item span {
        font-size: 0.7rem;
        font-weight: 500;
    }

    .bottom-nav-item:hover {
        color: #78d403;
    }

    .bottom-nav-item.active {
        color: #78d403;
        /* Cor do item ativo */
    }

    /* Estilo especial para o botão de DEPÓSITO (central) */
    .bottom-nav-item.deposit-btn {
        background: #78d403;
        color: #ffffff;
        width: 65px;
        height: 65px;
        border-radius: 50%;
        box-shadow: 0 6px 20px rgba(34, 197, 94, 0.4);
        transform: translateY(-22px);
        /* Move o botão para cima */
        border: 4px solid #1a1a1a;
        /* Borda um pouco mais grossa */
        flex: 0 0 65px;
        display: flex;
        /* Garante o alinhamento do ícone */
        align-items: center;
        justify-content: center;
    }

    .bottom-nav-item.deposit-btn:hover {
        transform: translateY(-24px);
        box-shadow: 0 8px 25px rgba(34, 197, 94, 0.5);
    }

    .bottom-nav-item.deposit-btn svg {
        width: 1.8rem;
        /* Controle a largura aqui */
        height: 1.8rem;
        /* Controle a altura aqui */
        margin: 0;
        /* Remove margens para centralizar perfeitamente */
    }

    /* Esconde o texto "Depositar" */
    .bottom-nav-item.deposit-btn span {
        display: none;
    }

    /* Remove o estilo do botão de registro, caso ele apareça aqui */
    .bottom-nav-item.register-btn {
        background: none;
        color: #9ca3af;
        box-shadow: none;
    }

    .user-btn i.bi-person-circle {
        font-size: 1.8rem;
        /* Aumenta o tamanho do ícone de pessoa */
        vertical-align: middle;
        /* Alinha melhor o ícone com o texto */
    }

    .user-btn .user-name {
        vertical-align: middle;
        /* Alinha melhor o nome com o ícone */
    }

    /* Mobile banner adjustments */
    @media (max-width: 992px) {
        .app-download-banner {
            padding: 0.5rem 1rem;
            font-size: 0.8rem;
            gap: 0.5rem;
        }

        .download-btn {
            padding: 0.3rem 0.8rem;
            font-size: 0.75rem;
        }

        .close-banner {
            right: 0.75rem;
            font-size: 1.1rem;
        }

        .header.with-banner {
            top: 40px;
        }

        .install-modal-content {
            margin: 1rem;
            max-height: 85vh;
        }

        .install-modal-header {
            padding: 1.5rem 1.5rem 1rem;
        }

        .install-benefits,
        .install-instructions,
        .install-modal-footer {
            padding-left: 1.5rem;
            padding-right: 1.5rem;
        }

        .platform-tabs {
            flex-direction: column;
        }

        /* Adjust body padding for bottom nav */
        body {
            padding-bottom: 85px !important;
        }
    }

    @media (max-width: 480px) {
        .app-download-banner {
            padding: 0.4rem 0.75rem;
            font-size: 0.75rem;
        }

        .app-download-banner span {
            max-width: calc(100% - 120px);
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .download-btn {
            padding: 0.25rem 0.6rem;
            font-size: 0.7rem;
            margin-left: 0.25rem;
        }

        .close-banner {
            right: 0.5rem;
            font-size: 1rem;
        }

        .header.with-banner {
            top: 35px;
        }

        .install-modal {
            padding: 0.5rem;
        }

        .install-modal-header {
            padding: 1rem;
        }

        .install-modal-icon {
            width: 50px;
            height: 50px;
            font-size: 1.5rem;
        }

        .install-modal-header h2 {
            font-size: 1.3rem;
        }

        .install-benefits,
        .install-instructions,
        .install-modal-footer {
            padding: 1rem;
        }

        /* Bottom nav adjustments for very small screens */
        .bottom-nav-container {
            padding: 0 0.5rem;
        }

        .bottom-nav-item {
            min-width: 50px;
            padding: 0.4rem 0.2rem;
        }

        .bottom-nav-item i {
            font-size: 1.1rem;
        }

        .bottom-nav-item span {
            font-size: 0.65rem;
        }

        .bottom-nav-item.deposit-btn i,
        .bottom-nav-item.register-btn i {
            font-size: 1.3rem;
        }

        .bottom-nav-item.deposit-btn span,
        .bottom-nav-item.register-btn span {
            font-size: 0.7rem;
        }

        /* Adjust body padding for bottom nav */
        body {
            padding-bottom: 80px !important;
        }
    }

    .mobile-menu-btn {
        display: none;
        color: white;
        font-size: 1.5rem;
        cursor: pointer;
        padding: 0.5rem;
        border-radius: 8px;
        transition: background-color 0.3s ease;
    }

    .mobile-menu-btn:hover {
        background: rgba(255, 255, 255, 0.1);
    }

    .nav-menu {
        display: flex;
        align-items: center;
        gap: 1.5rem;
        list-style: none;
    }

    .nav-link {
        color: #9ca3af;
        text-decoration: none;
        font-weight: 500;
        transition: all 0.3s ease;
        position: relative;
        display: inline-flex;
        align-items: center;
        font-size: 1rem;
        /* Adicione esta linha */
    }

    .nav-link::after {
        content: none !important;
        /* Remove o risco de forma definitiva */
    }

    .nav-link i {
        margin-right: 12px;
    }

    .nav-link svg {
        margin-right: 12px;
    }

    .nav-link:hover {
        color: #ffffff;
    }

    .nav-link::after {
        content: '';
        position: absolute;
        bottom: -4px;
        left: 0;
        width: 0;
        height: 2px;
        background: #78d403;
        transition: width 0.3s ease;
    }

    .nav-link:hover::after {
        width: 100%;
    }

    .header-container nav {
        margin-left: 2rem;
    }

    .header-actions {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        margin-left: auto;
        /* Adicionado */
    }

    .btn-login {
        background: #141514;
        /* Nova cor de fundo (azul, por exemplo) */
        color: white;
        /* Cor do texto */
        text-decoration: none;
        padding: 0.75rem 1.5rem;
        /* Preenchimento interno */
        border-radius: 12px;
        /* Bordas arredondadas */
        font-weight: 400;
        display: flex;
        align-items: center;
        gap: 0.5rem;
        transition: all 0.3s ease;
    }

    .btn-login:hover {
        background: #181818;
        /* Cor ao passar o mouse */
        color: #ffffff;
    }

    .btn-register {
        background: #78d403;
        color: black;
        text-decoration: none;
        padding: 0.75rem 1.5rem;
        border-radius: 12px;
        font-weight: 400;
        display: flex;
        align-items: center;
        gap: 0.5rem;
        transition: all 0.3s ease;
        border: none;
        cursor: pointer;
    }

    /* Estilos Corrigidos e Finalizados para o Display de Saldo */
    /* Estilos Corrigidos e Finalizados para o Display de Saldo */
    .balance-display-v2 {
        position: relative;
    }

    .balance-toggle-btn {
        background: rgba(255, 255, 255, 0.05);
        border: 1px solid rgba(255, 255, 255, 0.1);
        border-radius: 8px;
        padding: 0.4rem 0.8rem;
        display: flex;
        align-items: center;
        gap: 0.5rem;
        color: #ffffff;
        font-weight: 600;
        font-size: 0.8rem;
        cursor: pointer;
        transition: all 0.2s ease;
    }

    .balance-toggle-btn i {
        color: #9ca3af;
        font-size: 0.75rem;
        transition: transform 0.3s ease;
    }

    .balance-popup {
        display: none;
        position: absolute;
        top: calc(100% + 10px);
        left: 50%;
        transform: translateX(-50%);
        width: 280px;
        background: #1f1f1f;
        border: 1px solid rgba(255, 255, 255, 0.1);
        border-radius: 12px;
        padding: 1rem;
        z-index: 1100;
        box-shadow: 0 10px 40px rgba(0, 0, 0, 0.5);
        opacity: 0;
        transition: opacity 0.2s ease, transform 0.2s ease;
    }

    .balance-popup.active {
        display: block;
        opacity: 1;
        transform: translateX(-50%) translateY(0);
    }

    /* --- CORREÇÃO DE ESTILOS DE COR E TEXTO --- */

    .balance-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 0.2rem;
        font-size: 0.8rem;
    }

    .balance-row .balance-label {
        color: #ffffff !important;
        /* Força a cor branca para "Saldo" e "Bônus" */
    }

    .balance-value {
        color: #ffffff;
        font-weight: 600;
    }

    .balance-currency {
        color: #4ade80;
        /* Verde para o R$ */
    }

    .balance-label-total {
        color: #9ca3af;
        /* Cinza para a palavra "Total" */
    }

    .balance-info {
        font-size: 0.73rem;
        /* Tamanho da fonte reduzido para caber em uma linha */
        color: #9ca3af;
        text-align: center;
        margin-top: 0.5rem;
        margin-bottom: 1rem;
        white-space: nowrap;
        /* Força o texto a ficar em uma única linha */
    }

    /* --- FIM DA CORREÇÃO --- */

    .balance-row.total {
        font-size: 1rem;
    }

    .balance-divider {
        height: 1px;
        background: rgba(255, 255, 255, 0.1);
        margin: 0.75rem 0;
    }

    .balance-withdraw-btn {
        background: #78d403;
        color: black;
        width: 100%;
        border: none;
        padding: 0.75rem;
        border-radius: 8px;
        font-weight: 700;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 0.5rem;
        transition: background 0.2s ease;
    }

    .balance-withdraw-btn:hover {
        background: #16a34a;
    }

    .btn-deposit {
        background: #78d403;
        color: black;
        border: none;
        padding: 0.4rem 0.8rem;
        /* Preenchimento bastante reduzido */
        border-radius: 8px;
        /* Borda mais suave */
        font-weight: 600;
        font-size: 0.8rem;
        /* Fonte menor */
        display: flex;
        align-items: center;
        gap: 0.4rem;
        cursor: pointer;
        transition: all 0.3s ease;
    }

    .btn-withdraw {
        background: #78d403;
        color: black;
        border: none;
        padding: 0.4rem 0.8rem;
        /* Preenchimento bastante reduzido */
        border-radius: 8px;
        /* Borda mais suave */
        font-weight: 600;
        font-size: 0.8rem;
        /* Fonte menor */
        display: flex;
        align-items: center;
        gap: 0.4rem;
        cursor: pointer;
        transition: all 0.3s ease;
    }

    .user-dropdown {
        position: relative;
    }

    .user-btn {
        background: none;
        border: none;
        border-radius: 10px;
        /* Bordas um pouco menores */
        padding: 0.5rem 0.8rem;
        /* Padding ainda menor */
        color: #d9d9d9;
        font-size: 0.85rem;
        /* Tamanho da fonte reduzido */
        display: flex;
        align-items: center;
        gap: 0.4rem;
        /* Espaçamento interno menor */
        cursor: pointer;
        transition: all 0.3s ease;
    }

    .user-btn:hover {
        background: none;
        color: #ffffff;
        /* Cor branca ao passar o rato */
    }

    .dropdown-arrow {
        transition: transform 0.3s ease;
    }

    .user-dropdown.active .dropdown-arrow {
        transform: rotate(180deg);
    }

    .dropdown-menu {
        position: absolute;
        top: calc(100% + 0.5rem);
        right: 0;
        min-width: 200px;
        background: rgba(20, 20, 20, 0.95);
        backdrop-filter: blur(20px);
        border: 1px solid rgba(255, 255, 255, 0.1);
        border-radius: 12px;
        box-shadow: 0 10px 40px rgba(0, 0, 0, 0.3);
        opacity: 0;
        visibility: hidden;
        transform: translateY(-10px);
        transition: all 0.3s ease;
        z-index: 100;
    }

    .user-dropdown.active .dropdown-menu {
        opacity: 1;
        visibility: visible;
        transform: translateY(0);
    }

    .dropdown-item {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        padding: 0.75rem 1rem;
        color: #e5e7eb;
        text-decoration: none;
        background: none;
        border: none;
        width: 100%;
        text-align: left;
        cursor: pointer;
        transition: all 0.3s ease;
        font-size: 0.9rem;
    }

    .dropdown-item:hover {
        background: rgba(34, 197, 94, 0.1);
        color: #78d403;
    }

    .dropdown-item.logout {
        color: #ef4444;
    }

    .dropdown-item.logout:hover {
        background: rgba(239, 68, 68, 0.1);
        color: #ef4444;
    }

    .dropdown-divider {
        height: 1px;
        background: rgba(255, 255, 255, 0.1);
        margin: 0.5rem 0;
    }

    /* Mobile Sidebar */
    .mobile-sidebar {
        position: fixed;
        top: 0;
        left: 0;
        width: 280px;
        height: 100vh;
        background: rgba(15, 15, 15, 0.98);
        backdrop-filter: blur(20px);
        border-right: 1px solid rgba(255, 255, 255, 0.1);
        transform: translateX(-100%);
        transition: transform 0.3s ease;
        z-index: 1100;
        overflow-y: auto;
    }

    .mobile-sidebar.active {
        transform: translateX(0);
    }

    .sidebar-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 1.5rem 1rem;
        border-bottom: 1px solid rgba(255, 255, 255, 0.1);
    }

    .close-btn {
        background: none;
        border: none;
        color: #9ca3af;
        font-size: 1.5rem;
        cursor: pointer;
    }

    .sidebar-nav {
        padding: 1rem 0;
    }

    .sidebar-item {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        padding: 0.75rem 1.5rem;
        color: #e5e7eb;
        text-decoration: none;
        background: none;
        border: none;
        width: 100%;
        text-align: left;
        cursor: pointer;
        transition: all 0.3s ease;
    }

    .sidebar-item:hover {
        background: rgba(34, 197, 94, 0.1);
        color: #78d403;
    }

    .sidebar-item.logout {
        color: #ef4444;
    }

    .sidebar-item.logout:hover {
        background: rgba(239, 68, 68, 0.1);
    }

    .sidebar-divider {
        height: 1px;
        background: rgba(255, 255, 255, 0.1);
        margin: 1rem 0;
    }

    .mobile-backdrop {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.5);
        backdrop-filter: blur(4px);
        opacity: 0;
        visibility: hidden;
        transition: all 0.3s ease;
        z-index: 1050;
    }

    .mobile-backdrop.active {
        opacity: 1;
        visibility: visible;
    }

    /* Hide bottom navigation on desktop */
    @media (min-width: 993px) {
        .bottom-navigation {
            display: none;
        }
    }

    /* Responsive */
    @media (max-width: 992px) {
        .header-container {
            padding: 0 1rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .btn-withdraw {
            display: none;

        }

        .logo {
            font-size: 1.1rem;
            flex-shrink: 0;
        }

        .logo-image {
            height: 35px;
        }

        .logo-icon {
            width: 35px;
            height: 35px;
            font-size: 1.1rem;
        }

        .mobile-menu-btn {
            display: none;
            align-items: center;
            justify-content: center;
            width: 40px;
            height: 40px;
            color: white;
            font-size: 1.3rem;
            cursor: pointer;
            border-radius: 8px;
            transition: background-color 0.3s ease;
            margin-right: 1rem;
            flex-shrink: 0;
        }

        .mobile-menu-btn:hover {
            background: rgba(255, 255, 255, 0.1);
        }

        .nav-menu {
            display: none;
        }

        .header-actions {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            flex-shrink: 0;
        }

        .balance-display {
            padding: 0.5rem 0.75rem;
            font-size: 0.85rem;
            min-width: auto;
            border-radius: 8px;
        }

        .btn-deposit {
            padding: 0.5rem 0.75rem;
            font-size: 0.85rem;
            border-radius: 8px;
            min-width: auto;
        }

        .deposit-text {
            display: none;
        }

        .user-btn {
            padding: 0.5rem 0.75rem;
            font-size: 0.85rem;
            border-radius: 8px;
            min-width: auto;
        }

        .user-name {
            display: none;
        }

        .dropdown-arrow {
            font-size: 0.8rem;
        }

        .btn-login {
            font-size: 0.85rem;
            padding: 0.5rem;
        }

        .login-text {
            display: flex;
        }

        .btn-register {
            padding: 0.5rem 1rem;
            font-size: 0.85rem;
            border-radius: 8px;
        }

        .dropdown-menu {
            right: 0;
            min-width: 180px;
            margin-top: 0.25rem;
        }

        .dropdown-item {
            padding: 0.6rem 0.8rem;
            font-size: 0.85rem;
        }
    }

    @media (max-width: 480px) {
        .header-container {
            padding: 0 0.75rem;
            gap: 0.5rem;
        }

        .logo-image {
            height: 32px;
        }

        .logo-icon {
            width: 32px;
            height: 32px;
            font-size: 1rem;
        }

        .mobile-menu-btn {
            width: 36px;
            height: 36px;
            font-size: 1.2rem;
            margin-right: 0.75rem;
        }

        .header-actions {
            gap: 0.4rem;
        }

        .balance-display {
            padding: 0.4rem 0.6rem;
            font-size: 0.8rem;
            min-width: auto;
        }

        .btn-deposit,
        .user-btn {
            padding: 0.4rem 0.6rem;
            font-size: 0.8rem;
            min-width: 36px;
        }

        .btn-register {
            padding: 0.4rem 0.8rem;
            font-size: 0.8rem;
        }

        .dropdown-arrow {
            font-size: 0.7rem;
        }

        .sidebar-logo-image {
            height: 35px;
        }

        .sidebar-logo-icon {
            width: 35px;
            height: 35px;
            font-size: 1.1rem;
        }
    }
</style>

<script>
    // Função global para atualizar o saldo do usuário na interface
    async function atualizarSaldoUsuario() {
        const balanceBtn = document.querySelector('#balance-toggle-btn span.font-semibold');
        const balancePopup = document.getElementById('balance-popup');

        if (!balanceBtn || !balancePopup) {
            return;
        }

        try {
            const response = await fetch('/api/get_saldo.php');
            const data = await response.json();

            if (data.success) {
                balanceBtn.textContent = 'R$ ' + data.saldo_total;

                const saldoRealEl = balancePopup.querySelector('.balance-row:nth-child(1) .balance-value');
                const saldoBonusEl = balancePopup.querySelector('.balance-row:nth-child(2) .balance-value');
                const saldoTotalEl = balancePopup.querySelector('.balance-row.total .balance-value');

                if (saldoRealEl) saldoRealEl.innerHTML = `<span class="balance-currency">R$</span> ${data.saldo_real}`;
                if (saldoBonusEl) saldoBonusEl.innerHTML = `<span class="balance-currency">R$</span> ${data.saldo_bonus}`;
                if (saldoTotalEl) saldoTotalEl.innerHTML = `<span class="balance-currency">R$</span> ${data.saldo_total}`;
            }
        } catch (error) {
            console.error('Erro ao atualizar o saldo:', error);
        }
    }

    document.addEventListener('DOMContentLoaded', function() {
        // Banner do App
        const bannerClosed = localStorage.getItem('appBannerClosed');
        const appBanner = document.getElementById('appBanner');
        const header = document.querySelector('.header');

        if (!bannerClosed && appBanner) {
            appBanner.style.display = 'flex';
            header.classList.add('with-banner');
        } else {
            header.style.top = '0';
        }

        window.closeBanner = function() {
            if (appBanner) {
                appBanner.style.display = 'none';
                header.classList.remove('with-banner');
                header.style.top = '0';
                localStorage.setItem('appBannerClosed', 'true');
            }
        };

        // Modal de Instalação
        const installModal = document.getElementById('installModal');
        window.openInstallModal = function() {
            if (installModal) {
                installModal.classList.add('active');
                document.body.style.overflow = 'hidden';
            }
        };

        window.closeInstallModal = function() {
            if (installModal) {
                installModal.classList.remove('active');
                document.body.style.overflow = '';
            }
        };

        installModal?.addEventListener('click', function(e) {
            if (e.target === this) {
                closeInstallModal();
            }
        });

        const platformTabs = document.querySelectorAll('.platform-tab[data-platform]');
        const platformContents = document.querySelectorAll('.platform-content[data-platform]');
        platformTabs.forEach(tab => {
            tab.addEventListener('click', () => {
                const platform = tab.dataset.platform;
                platformTabs.forEach(t => t.classList.remove('active'));
                platformContents.forEach(c => c.classList.remove('active'));
                tab.classList.add('active');
                document.querySelector(`.platform-content[data-platform="${platform}"]`).classList.add('active');
            });
        });

        // Menu Mobile (Sidebar)
        const menuBtn = document.getElementById('menuBtn');
        const sidebar = document.getElementById('sidebar');
        const backdrop = document.getElementById('backdrop');
        const closeSidebarBtn = document.getElementById('closeSidebar');

        function openSidebar() {
            sidebar?.classList.add('active');
            backdrop?.classList.add('active');
            document.body.style.overflow = 'hidden';
        }

        function closeSidebarFunc() {
            sidebar?.classList.remove('active');
            backdrop?.classList.remove('active');
            document.body.style.overflow = '';
        }

        menuBtn?.addEventListener('click', openSidebar);
        closeSidebarBtn?.addEventListener('click', closeSidebarFunc);
        backdrop?.addEventListener('click', closeSidebarFunc);

        // Dropdowns de Usuário e Saldo
        const userDropdownBtn = document.getElementById('userDropdownBtn');
        const userDropdown = userDropdownBtn?.closest('.user-dropdown');
        const balanceContainer = document.getElementById('balance-container');
        const balanceBtn = document.getElementById('balance-toggle-btn');
        const balancePopup = document.getElementById('balance-popup');
        const arrowIcon = document.getElementById('balance-arrow-icon');

        userDropdownBtn?.addEventListener('click', function(e) {
            e.stopPropagation();
            if (balancePopup?.classList.contains('active')) {
                balancePopup.classList.remove('active');
                arrowIcon?.classList.remove('bi-chevron-up');
                arrowIcon?.classList.add('bi-chevron-down');
            }
            userDropdown?.classList.toggle('active');
        });

        if (balanceBtn) {
            balanceBtn.addEventListener('click', function(e) {
                e.stopPropagation();
                userDropdown?.classList.remove('active');
                const isActive = balancePopup.classList.toggle('active');
                if (isActive) {
                    arrowIcon?.classList.remove('bi-chevron-down');
                    arrowIcon?.classList.add('bi-chevron-up');
                } else {
                    arrowIcon?.classList.remove('bi-chevron-up');
                    arrowIcon?.classList.add('bi-chevron-down');
                }
            });
        }

        // Fecha os popups se clicar fora
        document.addEventListener('click', function(e) {
            if (userDropdown && !userDropdown.contains(e.target)) {
                userDropdown.classList.remove('active');
            }
            if (balancePopup && balancePopup.classList.contains('active') && !balanceContainer.contains(e.target)) {
                balancePopup.classList.remove('active');
                if (arrowIcon) {
                    arrowIcon.classList.remove('bi-chevron-up');
                    arrowIcon.classList.add('bi-chevron-down');
                }
            }
        });
    });
</script>
<!-- Modais will be included separately -->