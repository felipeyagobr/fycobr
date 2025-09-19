<?php
@session_start();
require_once '../conexao.php';

// Etapa 1: Buscar TODAS as raspadinhas
try {
    $stmt = $pdo->prepare("SELECT * FROM raspadinhas ORDER BY valor DESC, created_at DESC");
    $stmt->execute();
    $raspadinhas = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $raspadinhas = [];
}
?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $nomeSite; ?> - Todas as Raspadinhas</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="/assets/style/globalStyles.css?id=<?= time(); ?>">

    <style>
        body {
            background-color: #0a0a0a;
        }

        .raspadinhas-page-section {
            padding-top: 90px;
            padding-bottom: 4rem;
        }

        .ganhos-container {
            margin-bottom: 1.5rem;
        }

        .grid-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 1rem;
        }

        .search-wrapper {
            max-width: 1400px;
            margin: 0 auto 3rem auto;
            padding: 0 1rem;
            position: relative;
        }

        #search-input {
            width: 100%;
            background-color: #1a1a1a;
            border: 1px solid #2a2a2a;
            border-radius: 8px;
            padding: 0.9rem 1rem 0.9rem 2.5rem;
            color: #ffffff;
            font-size: 1rem;
            font-family: 'Inter', sans-serif;
            transition: border-color 0.3s ease, box-shadow 0.3s ease;
        }

        #search-input:focus {
            outline: none;
            border-color: #78d403;
            box-shadow: 0 0 10px rgba(120, 212, 3, 0.3);
        }

        .search-wrapper .bi-search {
            position: absolute;
            left: 1.8rem;
            top: 50%;
            transform: translateY(-50%);
            color: #9ca3af;
            pointer-events: none;
        }

        #no-results {
            color: #9ca3af;
            text-align: center;
            padding: 2rem;
            display: none;
        }

        .raspadinhas-grid-new {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 2rem;
        }

        @keyframes pulse-glow {
            0% {
                box-shadow: 0 0 5px rgba(34, 197, 94, 0.2);
            }

            50% {
                box-shadow: 0 0 15px rgba(34, 197, 94, 0.6);
            }

            100% {
                box-shadow: 0 0 5px rgba(34, 197, 94, 0.2);
            }
        }

        .raspinha-card-new {
            background: #1a1a1a;
            border: 1px solid #2a2a2a;
            border-radius: 16px;
            overflow: hidden;
            /* <--- CORREÇÃO APLICADA AQUI */
            display: flex;
            flex-direction: column;
            transition: all 0.4s cubic-bezier(0.25, 0.8, 0.25, 1);
            position: relative;
            animation: pulse-glow 3s infinite ease-in-out;
            container-type: inline-size;
            container-name: raspinha-card;
        }

        .raspinha-card-new:hover {
            transform: translateY(-8px) perspective(1000px) rotateX(2deg) rotateY(-1deg);
            border-color: rgba(34, 197, 94, 0.5);
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.5), 0 0 20px rgba(34, 197, 94, 0.5);
            animation-play-state: paused;
        }

        .card-banner-new {
            width: 100%;
            background-color: #333;
            line-height: 0;
        }

        .banner-image-new {
            width: 100%;
            height: auto;
        }

        .raspinha-card-new:hover .banner-image-new {
            transform: scale(1.05);
        }

        .card-content-new {
            padding: 1.5rem;
            padding-top: 1rem;
            display: flex;
            flex-direction: column;
            flex-grow: 1;
        }

        .card-title-new {
            font-size: 1.25rem;
            font-weight: 700;
            color: #ffffff;
            margin: 0;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            width: 100%;
        }

        .card-prize-new {
            font-size: 0.8rem;
            font-weight: 600;
            color: #FFD700;
            margin: 0.25rem 0 0.75rem 0;
            text-align: left;
        }

        .card-description-new {
            color: #9ca3af;
            font-size: 0.9rem;
            line-height: 1.5;
            text-align: left;
            margin-bottom: 1.5rem;
            flex-grow: 1;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .card-footer-new {
            margin-top: auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 1rem;
        }

        .play-button-replicated {
            display: flex;
            justify-content: center;
            align-items: center;
            background: #78D403;
            color: #000;
            border: none;
            border-radius: 8px;
            font-weight: 700;
            text-transform: uppercase;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 0 10px rgba(120, 212, 3, 0.4);
            font-family: inherit;
            text-decoration: none;
            flex-grow: 1;
        }

        .play-button-replicated:hover {
            box-shadow: 0 0 20px rgba(120, 212, 3, 0.6);
            transform: translateY(-2px);
        }

        .play-button-content-replicated {
            display: flex;
            align-items: center;
            justify-content: center;
            white-space: nowrap;
            width: 100%;
            padding: 0.65rem 0.8rem;
            gap: 0.5rem;
            font-size: clamp(0.8rem, 0.7rem + 1.2cqi, 1.0rem);
        }

        .play-button-content-replicated span:first-child {
            display: flex;
            align-items: center;
            gap: 0.25rem;
            flex-shrink: 0;
        }

        .button-icon-replicated {
            height: 1em;
            width: auto;
        }

        .play-button-value-replicated {
            background: rgba(0, 0, 0, 0.15);
            padding: 0.15rem 0.35rem;
            border-radius: 5px;
            font-weight: 700;
            flex-shrink: 0;
            font-size: clamp(0.75rem, 0.65rem + 1.2cqi, 0.9rem);
        }

        .view-prizes-btn-new {
            color: #ffffff;
            text-decoration: none;
            font-size: 0.85rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 0.4rem;
            transition: color 0.3s ease;
            white-space: nowrap;
            flex-shrink: 0;
        }

        .view-prizes-btn-new i {
            color: #fae802;
        }

        .view-prizes-btn-new:hover {
            color: #fae802;
        }

        @media (max-width: 768px) {
            .raspadinhas-showcase {
                padding: 80px 1rem 2rem 1rem;
            }

            .raspadinhas-grid-new {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>

<body>
    <?php include('../inc/header.php'); ?>
    <?php include('../components/modals.php'); ?>

    <section class="raspadinhas-page-section">

        <div class="ganhos-container">
            <?php include('../components/ganhos.php'); ?>
        </div>

        <div class="grid-container">
            <div class="search-wrapper">
                <i class="bi bi-search"></i>
                <input type="search" id="search-input" placeholder="Pesquisar por uma raspadinha..." autocomplete="off">
            </div>

            <div class="raspadinhas-grid-new" id="raspadinhas-grid">
                <?php if (!empty($raspadinhas)): ?>
                    <?php foreach ($raspadinhas as $raspinha): ?>
                        <?php
                        $premioMaximo = 0;
                        try {
                            $stmtPremio = $pdo->prepare(
                                "SELECT MAX(valor) as max_premio FROM raspadinha_premios WHERE raspadinha_id = :id"
                            );
                            $stmtPremio->bindParam(':id', $raspinha['id'], PDO::PARAM_INT);
                            $stmtPremio->execute();
                            $resultadoPremio = $stmtPremio->fetch(PDO::FETCH_ASSOC);

                            if ($resultadoPremio && $resultadoPremio['max_premio'] !== null) {
                                $premioMaximo = $resultadoPremio['max_premio'];
                            }
                        } catch (PDOException $e) {
                            // Silencia o erro.
                        }
                        ?>
                        <div class="raspinha-card-new">
                            <div class="card-banner-new">
                                <img src="<?= htmlspecialchars($raspinha['banner']) ?>" alt="<?= htmlspecialchars($raspinha['nome']) ?>" class="banner-image-new">
                            </div>

                            <div class="card-content-new">
                                <h3 class="card-title-new"><?= htmlspecialchars($raspinha['nome']) ?></h3>
                                <p class="card-prize-new">PRÊMIOS DE ATÉ R$ <?= number_format($premioMaximo, 2, ',', '.') ?></p>
                                <p class="card-description-new">
                                    <?= htmlspecialchars($raspinha['descricao'] ?: 'Raspe e ganhe prêmios incríveis!') ?>
                                </p>
                                <div class="card-footer-new">
                                    <a href="/raspadinhas/show.php?id=<?= $raspinha['id'] ?>" class="play-button-replicated">
                                        <div class="play-button-content-replicated">
                                            <span>
                                                <img src="/assets/img/icons/coin-icon.svg" class="button-icon-replicated" alt="">
                                                Jogar
                                            </span>
                                            <span class="play-button-value-replicated">R$ <?= number_format($raspinha['valor'], 2, ',', '.'); ?></span>
                                        </div>
                                    </a>
                                    <a href="/raspadinhas/show.php?id=<?= $raspinha['id'] ?>" class="view-prizes-btn-new">
                                        <i class="bi bi-gift-fill"></i>
                                        Ver Prêmios
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p style="color: #9ca3af; text-align: center;">Nenhuma raspadinha disponível no momento.</p>
                <?php endif; ?>
            </div>

            <div id="no-results">
                <h3>Nenhum resultado encontrado</h3>
                <p>Tente pesquisar com outros termos.</p>
            </div>
        </div>
    </section>

    <?php include('../inc/footer.php'); ?>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const searchInput = document.getElementById('search-input');
            const raspadinhasGrid = document.getElementById('raspadinhas-grid');
            const cards = raspadinhasGrid.querySelectorAll('.raspinha-card-new');
            const noResultsMessage = document.getElementById('no-results');

            searchInput.addEventListener('input', function() {
                const searchTerm = this.value.toLowerCase().trim();
                let visibleCards = 0;

                cards.forEach(card => {
                    const title = card.querySelector('.card-title-new').textContent.toLowerCase();

                    if (title.includes(searchTerm)) {
                        card.style.display = 'flex';
                        visibleCards++;
                    } else {
                        card.style.display = 'none';
                    }
                });

                if (visibleCards === 0) {
                    noResultsMessage.style.display = 'block';
                } else {
                    noResultsMessage.style.display = 'none';
                }
            });
        });
    </script>
</body>

</html>