<?php
@session_start();
require_once '../conexao.php';

if (!isset($_SESSION['usuario_id'])) {
    $_SESSION['message'] = ['type' => 'warning', 'text' => 'Você precisa estar logado para acessar esta página!'];
    header("Location: /login");
    exit;
}

$id = (int)($_GET['id'] ?? 0);
$stmt = $pdo->prepare("SELECT * FROM raspadinhas WHERE id = ?");
$stmt->execute([$id]);
$cartela = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$cartela) {
    $_SESSION['message'] = ['type' => 'failure', 'text' => 'Cartela não encontrada.'];
    header("Location: /raspadinhas");
    exit;
}

$premios = $pdo->prepare("SELECT * FROM raspadinha_premios WHERE raspadinha_id = ? ORDER BY valor DESC");
$premios->execute([$id]);
$premios = $premios->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $nomeSite; ?> - <?= htmlspecialchars($cartela['nome']); ?></title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="/assets/style/globalStyles.css?id=<?= time(); ?>">

    <script src="https://cdn.jsdelivr.net/npm/notiflix@3.2.8/dist/notiflix-aio-3.2.8.min.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/notiflix@3.2.8/src/notiflix.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/js-confetti@latest/dist/js-confetti.browser.js"></script>

    <style>
        :root {
            --neon-green: #00ff7f;
            --background-color: #0d0e12;
            --container-color: #161821;
            --container-secondary-color: #2c3040;
            --border-color: rgba(255, 255, 255, 0.1);
            --text-color: #e5e7eb;
            --text-dark-color: #7b8ca0;
        }

        body {
            background-color: var(--background-color);
            overflow-x: hidden;
        }

        .page-wrapper {
            display: flex;
            flex-direction: column;
            min-height: 100vh;
            overflow-x: hidden;
            position: relative;
        }

        .raspadinha-section {
            flex-grow: 1;
            padding-top: 90px;
            padding-bottom: 2rem;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: flex-start;
            gap: 1.5rem;
        }

        .raspadinha-wrapper {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 1.5rem;
            width: 100%;
            max-width: 500px;
            padding: 0 1rem;
        }

        .premios-carousel {
            width: 100%;
            position: relative;
            background: rgba(0, 0, 0, 0.2);
            border-radius: 0;
            padding: 1rem 0;
        }

        .premios-carousel::before,
        .premios-carousel::after {
            content: '';
            position: absolute;
            top: 0;
            bottom: 0;
            width: 60px;
            z-index: 2;
            pointer-events: none;
        }

        .premios-carousel::before {
            left: 0;
            background: linear-gradient(to right, var(--background-color), transparent);
        }

        .premios-carousel::after {
            right: 0;
            background: linear-gradient(to left, var(--background-color), transparent);
        }

        .premios-track {
            display: flex;
            gap: 1rem;
            animation: scroll-premios 40s linear infinite;
        }

        @keyframes scroll-premios {
            from {
                transform: translateX(0);
            }

            to {
                transform: translateX(-50%);
            }
        }

        .premio-card {
            background: var(--background-color);
            border-radius: 12px;
            padding: 1rem;
            min-width: 140px;
            text-align: center;
        }

        .premio-image {
            width: 50px;
            height: 50px;
            margin: 0 auto 0.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .premio-image img {
            max-width: 100%;
            height: auto;
        }

        .premio-valor {
            font-size: 1rem;
            font-weight: 700;
            color: var(--neon-green);
        }

        .game-container {
            background-color: var(--container-color);
            border-radius: 16px;
            width: 100%;
            padding: 1rem;
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        #scratch-container {
            position: relative;
            width: 100%;
            max-width: 400px;
            aspect-ratio: 1 / 1;
            user-select: none;
            border-radius: 16px;
            overflow: hidden;
            margin: 0 auto;
        }

        #prizes-grid {
            position: absolute;
            inset: 0;
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 8px;
            padding: 12px;
            background: linear-gradient(135deg, #101218, #161821);
            z-index: 1;
        }

        #prizes-grid>div {
            background: rgba(0, 0, 0, 0.8);
            border: 1px solid var(--border-color);
            border-radius: 12px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            font-weight: 700;
            font-size: 1rem;
            color: #fff;
            text-shadow: 0 0 5px var(--neon-green);
        }

        #prizes-grid img {
            width: 48px;
            height: 48px;
            object-fit: contain;
            margin-bottom: 6px;
            filter: drop-shadow(0 0 8px var(--neon-green));
        }

        #scratch-canvas {
            position: absolute;
            inset: 0;
            z-index: 10;
            cursor: pointer;
            touch-action: none;
        }

        #btn-overlay {
            position: absolute;
            inset: 0;
            background: rgba(0, 0, 0, 0.7);
            backdrop-filter: blur(4px);
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            z-index: 30;
            gap: 1rem;
            padding: 1rem;
            text-align: center;
        }

        .overlay-icon {
            width: 4rem;
            height: 4rem;
            filter: drop-shadow(0 0 15px rgba(255, 255, 255, 0.3));
        }

        .overlay-title {
            font-size: 1rem;
            font-weight: 500;
            color: var(--text-dark-color);
        }

        .overlay-instructions {
            font-size: 0.8rem;
            color: var(--text-dark-color);
            max-width: 250px;
        }

        .controls-wrapper {
            width: 100%;
            max-width: 400px;
            margin: 0 auto;
            position: relative;
        }

        .controls-area {
            display: flex;
            gap: 0.5rem;
            align-items: center;
            background-color: var(--background-color);
            border-radius: 8px;
            padding: 0.3rem;
        }

        .buy-button-container {
            flex-grow: 1;
            height: 36px;
        }

        .buy-button {
            display: flex;
            justify-content: center;
            align-items: center;
            background: #78d403;
            color: #000;
            border: none;
            padding: 0 0.6rem;
            border-radius: 6px;
            font-weight: 700;
            text-transform: uppercase;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 0 10px rgba(0, 255, 127, 0.4);
            height: 100%;
            width: 100%;
            white-space: nowrap;
            font-size: 0.85rem;
        }

        .buy-button .buy-button-content {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.4rem;
        }

        .buy-button .buy-button-value {
            background: rgba(0, 0, 0, 0.15);
            padding: 0.2rem 0.5rem;
            border-radius: 4px;
            font-weight: 700;
            font-size: 0.75rem;
        }

        .buy-button:hover:not(:disabled) {
            box-shadow: 0 0 20px rgba(0, 255, 127, 0.6);
        }

        .buy-button:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        .action-buttons-container {
            display: flex;
            gap: 0.5rem;
            flex-shrink: 0;
        }

        .control-btn {
            flex-shrink: 0;
            background-color: var(--container-secondary-color);
            border: none;
            color: var(--text-color);
            border-radius: 6px;
            padding: 0;
            width: 36px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            justify-content: center;
            height: 36px;
            font-size: 1rem;
        }

        .control-btn:hover {
            background-color: #3a4055;
        }

        .control-btn.active {
            color: var(--neon-green);
        }

        .button-icon {
            height: 1em;
            width: auto;
        }

        .auto-play-popup {
            position: absolute;
            bottom: 100%;
            right: 0;
            width: 220px;
            margin-bottom: 8px;
            background-color: #1c1f27;
            border-radius: 8px;
            padding: 0.75rem 1rem;
            box-shadow: 0 -4px 15px rgba(0, 0, 0, 0.3);
            z-index: 100;
            display: none;
            flex-direction: column;
            gap: 0.75rem;
        }

        .auto-play-popup.show {
            display: flex;
        }

        .auto-play-popup h4 {
            margin: 0;
            text-align: center;
            white-space: nowrap;
            color: var(--text-dark-color);
            font-size: 0.7rem;
            font-weight: 600;
            text-transform: uppercase;
        }

        .rounds-selector {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 0.5rem;
        }

        .round-btn {
            background: transparent;
            border: 1px solid var(--border-color);
            color: var(--text-color);
            padding: 0.5rem;
            border-radius: 6px;
            font-size: 0.85rem;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.2s;
            flex-grow: 1;
        }

        .round-btn:hover {
            border-color: #4a516e;
        }

        .round-btn.selected {
            border-color: var(--neon-green);
            background-color: rgba(0, 255, 127, 0.1);
            color: var(--neon-green);
        }

        #btn-start-auto {
            height: 36px;
            font-size: 0.8rem;
        }

        @keyframes flash-saldo {
            50% {
                color: var(--neon-green);
                transform: scale(1.1);
            }
        }

        .saldo-update-animation {
            animation: flash-saldo 0.8s ease-out;
        }
    </style>
</head>

<body>
    <div class="page-wrapper">
        <?php include('../inc/header.php'); ?>
        <?php include('../components/modals.php'); ?>

        <section class="raspadinha-section">

            <?php include('../components/ganhos.php'); ?>

            <div class="raspadinha-wrapper">
                <div class="game-container">
                    <div id="scratch-container">
                        <div id="prizes-grid"></div>
                        <canvas id="scratch-canvas"></canvas>
                        <div id="btn-overlay">
                            <svg fill="white" viewBox="0 0 256 256" xmlns="http://www.w3.org/2000/svg" class="overlay-icon">
                                <path d="M198.51 56.09C186.44 35.4 169.92 24 152 24h-48c-17.92 0-34.44 11.4-46.51 32.09C46.21 75.42 40 101 40 128s6.21 52.58 17.49 71.91C69.56 220.6 86.08 232 104 232h48c17.92 0 34.44-11.4 46.51-32.09C209.79 180.58 216 155 216 128s-6.21-52.58-17.49-71.91Zm1.28 63.91h-32a152.8 152.8 0 0 0-9.68-48h30.59c6.12 13.38 10.16 30 11.09 48Zm-20.6-64h-28.73a83 83 0 0 0-12-16H152c10 0 19.4 6 27.19 16ZM152 216h-13.51a83 83 0 0 0 12-16h28.73C171.4 210 162 216 152 216Zm36.7-32h-30.58a152.8 152.8 0 0 0 9.68-48h32c-.94 18-4.98 34.62-11.1 48Z"></path>
                            </svg>
                            <div class="overlay-title">Comprar por R$ <?= number_format($cartela['valor'], 2, ',', '.'); ?></div>

                            <div style="max-width: 250px; width: 100%;">
                                <button id="btn-buy-center" class="buy-button" style="padding: 0.75rem 1.5rem; height: auto; font-size: 1rem;">
                                    <div class="buy-button-content">
                                        <span>
                                            <img src="/assets/img/icons/coin-icon.svg" class="button-icon" alt="">
                                            Comprar
                                        </span>
                                        <span class="buy-button-value">R$ <?= number_format($cartela['valor'], 2, ',', '.'); ?></span>
                                    </div>
                                </button>
                            </div>

                            <div class="overlay-instructions">
                                Raspe os 9 quadradinhos, encontre 3 símbolos iguais e ganhe o prêmio!
                            </div>
                        </div>
                    </div>
                    <div class="controls-wrapper">
                        <div class="controls-area">
                            <div class="buy-button-container">
                                <button id="btn-buy" class="buy-button">
                                    <div class="buy-button-content">
                                        <span>
                                            <img src="/assets/img/icons/coin-icon.svg" class="button-icon" alt="">
                                            Comprar
                                        </span>
                                        <span class="buy-button-value">R$ <?= number_format($cartela['valor'], 2, ',', '.'); ?></span>
                                    </div>
                                </button>
                            </div>
                            <div class="action-buttons-container">
                                <button class="control-btn" id="btn-turbo"><i class="bi bi-lightning-fill"></i></button>
                                <button class="control-btn" id="btn-auto"><i class="bi bi-arrow-repeat"></i></button>
                            </div>
                        </div>

                        <div class="auto-play-popup" id="auto-play-popup">
                            <h4>Quantidades de rodada</h4>
                            <div class="rounds-selector">
                                <button class="round-btn" data-rounds="5">5</button>
                                <button class="round-btn" data-rounds="10">10</button>
                                <button class="round-btn" data-rounds="15">15</button>
                                <button class="round-btn" data-rounds="20">20</button>
                            </div>
                            <button id="btn-start-auto" class="buy-button">Comprar R$ 0,00</button>
                        </div>
                    </div>
                </div>
            </div>
            <?php if (!empty($premios)): ?>
                <div class="premios-carousel">
                    <div class="premios-track">
                        <?php foreach (array_merge($premios, $premios) as $premio): ?>
                            <div class="premio-card">
                                <div class="premio-image">
                                    <img src="<?= htmlspecialchars($premio['icone']); ?>" alt="<?= htmlspecialchars($premio['nome']); ?>">
                                </div>
                                <div class="premio-valor">R$ <?= number_format($premio['valor'], 2, ',', '.'); ?></div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
        </section>

        <?php include('../inc/footer.php'); ?>
    </div>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const container = document.getElementById('scratch-container');
            let canvas = document.getElementById('scratch-canvas');
            let ctx = canvas.getContext('2d');
            const prizesGrid = document.getElementById('prizes-grid');
            const btnBuy = document.getElementById('btn-buy');
            const overlay = document.getElementById('btn-overlay');

            // CORRIGIDO: Referência para o botão duplicado
            const btnBuyCenter = document.getElementById('btn-buy-center');

            const btnTurbo = document.getElementById('btn-turbo'),
                btnAuto = document.getElementById('btn-auto');
            const autoPlayPopup = document.getElementById('auto-play-popup'),
                btnStartAuto = document.getElementById('btn-start-auto');
            const cartelaValor = <?= (float)$cartela['valor']; ?>;
            let quantidade = 1,
                isTurbo = false,
                isAutoPlaying = false;
            let orderId = null,
                brushRadius = 55,
                isDrawing = false,
                isScratchEnabled = false;

            btnTurbo.onclick = () => {
                isTurbo = !isTurbo;
                btnTurbo.classList.toggle('active');
            };
            btnAuto.onclick = (e) => {
                e.stopPropagation();
                autoPlayPopup.classList.toggle('show');
            };
            document.onclick = (e) => {
                if (!autoPlayPopup.contains(e.target) && e.target !== btnAuto && !btnAuto.contains(e.target)) {
                    autoPlayPopup.classList.remove('show');
                }
            };
            autoPlayPopup.onclick = (e) => e.stopPropagation();

            let valorTotalAuto = 0;
            const btnStartAutoText = btnStartAuto;
            btnStartAutoText.textContent = `Comprar R$ ${cartelaValor.toLocaleString('pt-BR', { minimumFractionDigits: 2 })}`;

            document.querySelectorAll('.round-btn').forEach(btn => {
                btn.onclick = () => {
                    document.querySelectorAll('.round-btn').forEach(b => b.classList.remove('selected'));
                    btn.classList.add('selected');
                    quantidade = parseInt(btn.dataset.rounds, 10);
                    valorTotalAuto = quantidade * cartelaValor;
                    btnStartAutoText.textContent = `Comprar R$ ${valorTotalAuto.toLocaleString('pt-BR', { minimumFractionDigits: 2 })}`;
                };
            });

            btnStartAuto.onclick = () => {
                const selectedBtn = document.querySelector('.round-btn.selected');
                if (!selectedBtn) {
                    Notiflix.Notify.warning('Por favor, selecione uma quantidade de rodadas.');
                    return;
                }

                quantidade = parseInt(selectedBtn.dataset.rounds, 10);

                autoPlayPopup.classList.remove('show');
                isAutoPlaying = true;
                iniciarCompra();
            };

            const ajustarCanvas = () => {
                if (container) {
                    const size = container.clientWidth;
                    canvas.width = size;
                    canvas.height = size;
                    if (!isScratchEnabled) drawScratchImage();
                }
            };
            const drawScratchImage = () => {
                ctx.clearRect(0, 0, canvas.width, canvas.height);
                ctx.globalCompositeOperation = 'source-over';
                ctx.fillStyle = '#111';
                ctx.fillRect(0, 0, canvas.width, canvas.height);
                ctx.font = 'bold 30px Inter';
                ctx.fillStyle = 'var(--neon-green)';
                ctx.textAlign = 'center';
                ctx.textBaseline = 'middle';
                ctx.fillText('RASPE AQUI', canvas.width / 2, canvas.height / 2);
            };
            const addCanvasListeners = () => {
                canvas.addEventListener('mousedown', handleStart);
                canvas.addEventListener('mousemove', handleMove);
                document.addEventListener('mouseup', handleEnd);
                canvas.addEventListener('touchstart', handleStart, {
                    passive: false
                });
                canvas.addEventListener('touchmove', handleMove, {
                    passive: false
                });
                document.addEventListener('touchend', handleEnd);
            };

            function resetGame() {
                isAutoPlaying = false;
                overlay.style.display = 'flex';
                isScratchEnabled = false;
                isDrawing = false;
                ajustarCanvas();

                // CORRIGIDO: Reabilita TODOS os botões de ação
                [btnBuy, btnBuyCenter, btnAuto, btnTurbo].forEach(btn => btn.disabled = false);

                const buyButtonContent = btnBuy.querySelector('.buy-button-content');
                if (buyButtonContent) {
                    const spanIcon = buyButtonContent.querySelector('span:first-child');
                    const spanValue = buyButtonContent.querySelector('.buy-button-value');
                    if (spanIcon) spanIcon.innerHTML = '<span><img src="/assets/img/icons/coin-icon.svg" class="button-icon" alt=""> Comprar</span>';
                    if (spanValue) spanValue.style.display = 'inline-flex';
                }
                quantidade = 1;
            }

            const scratchSound = new Audio('/assets/audio/scratch1.mp3');
            scratchSound.volume = 0.5;
            scratchSound.loop = true;

            const scratch = (x, y) => {
                if (!isScratchEnabled) return;
                ctx.globalCompositeOperation = 'destination-out';
                ctx.beginPath();
                ctx.arc(x, y, brushRadius, 0, Math.PI * 2, true);
                ctx.fill();
            };
            const getScratchedPercentage = () => {
                const d = ctx.getImageData(0, 0, canvas.width, canvas.height).data;
                let t = 0;
                for (let i = 3; i < d.length; i += 4)
                    if (d[i] < 128) t++;
                return (t / (d.length / 4)) * 100;
            };
            const getMousePos = e => {
                const r = canvas.getBoundingClientRect();
                const cX = e.touches ? e.touches[0].clientX : e.clientX;
                const cY = e.touches ? e.touches[0].clientY : e.clientY;
                return {
                    x: cX - r.left,
                    y: cY - r.top
                };
            };
            const handleStart = e => {
                if (isScratchEnabled) {
                    isDrawing = true;
                    scratchSound.play().catch(() => {});
                    scratch(getMousePos(e).x, getMousePos(e).y);
                }
            };
            const handleMove = e => {
                if (isDrawing && isScratchEnabled) {
                    e.preventDefault();
                    scratch(getMousePos(e).x, getMousePos(e).y);
                    if (getScratchedPercentage() > 70) autoFinishScratch();
                }
            };
            const handleEnd = () => {
                isDrawing = false;
                scratchSound.pause();
                scratchSound.currentTime = 0;
            };
            const buildCell = p => `<div><img src="${p.icone}" /><span>${p.valor > 0 ? 'R$ ' + p.valor.toLocaleString('pt-BR', { minimumFractionDigits: 2 }) : p.nome}</span></div>`;

            async function autoFinishScratch() {
                if (!isScratchEnabled) return;
                isScratchEnabled = false;
                ctx.clearRect(0, 0, canvas.width, canvas.height);
                await finishScratch();
            }

            async function finishScratch() {
                const fd = new FormData();
                fd.append('order_id', orderId);
                const response = await fetch('/raspadinhas/finish.php', {
                    method: 'POST',
                    body: fd
                });
                const json = await response.json();
                if (!json.success) {
                    Notiflix.Notify.failure('Erro ao finalizar.');
                    return;
                }
                if (json.valor > 0) {
                    Notiflix.Notify.success(`🎉 Você ganhou R$ ${json.valor.toLocaleString('pt-BR', { minimumFractionDigits: 2 })}!`);
                    (new JSConfetti()).addConfetti({
                        emojis: ['🎉', '✨', '💰', '🍀']
                    });
                } else {
                    if (isAutoPlaying) Notiflix.Notify.info(`Rodada restante: ${quantidade - 1}`);
                    else Notiflix.Notify.info('Não foi dessa vez. 😢');
                }
                await atualizarSaldoUsuario();
                if (quantidade > 1 && isAutoPlaying) {
                    quantidade--;
                    setTimeout(iniciarCompra, 1000);
                } else {
                    isAutoPlaying = false;
                    setTimeout(resetGame, 2000);
                }
            }

            async function iniciarCompra() {
                const buyButtonContent = btnBuy.querySelector('.buy-button-content');
                if (isAutoPlaying) {
                    if (buyButtonContent) {
                        buyButtonContent.querySelector('span:first-child').textContent = `Jogando... (${quantidade})`;
                        buyButtonContent.querySelector('.buy-button-value').style.display = 'none';
                    }
                }

                // CORRIGIDO: Desabilita TODOS os botões de ação
                [btnBuy, btnBuyCenter, btnAuto, btnTurbo].forEach(btn => btn.disabled = true);

                const fd = new FormData();
                fd.append('raspadinha_id', <?= $cartela['id']; ?>);
                const res = await fetch('/raspadinhas/buy.php', {
                    method: 'POST',
                    body: fd
                });
                const json = await res.json();
                if (!json.success) {
                    Notiflix.Notify.failure(json.error || 'Erro ao gerar cartela.');
                    resetGame();
                    return;
                }
                await atualizarSaldoUsuario();
                overlay.style.display = 'none';
                orderId = json.order_id;
                const premiosRes = await fetch('/raspadinhas/prizes.php?ids=' + json.grid.join(','));
                const premios = await premiosRes.json();
                prizesGrid.innerHTML = premios.map(buildCell).join('');
                drawScratchImage();
                isScratchEnabled = true;
                if (isTurbo || isAutoPlaying) setTimeout(autoFinishScratch, 200);
            }

            // CORRIGIDO: Função única para ambos os botões de compra
            function comprarUmaRodada() {
                quantidade = 1;
                isAutoPlaying = false;
                iniciarCompra();
            }

            btnBuy.addEventListener('click', comprarUmaRodada);
            btnBuyCenter.addEventListener('click', comprarUmaRodada);
            ajustarCanvas();
            addCanvasListeners();
            window.addEventListener('resize', ajustarCanvas);
        });
    </script>
</body>

</html>