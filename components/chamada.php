<?php
// Etapa 1: Buscar APENAS as raspadinhas marcadas como destaque
try {
    $stmt = $pdo->prepare("SELECT * FROM raspadinhas WHERE destaque = 1 ORDER BY valor DESC, created_at DESC LIMIT 8");
    $stmt->execute();
    $raspadinhas = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $raspadinhas = [];
}
?>

<section class="raspadinhas-showcase">
    <div class="showcase-container">
        <div class="showcase-header">
            <h2 class="showcase-title"><i class="bi bi-fire bouncing-icon"></i> <span>Destaques</span></h2>
            <a href="/cartelas" class="view-all-header-btn">
                Ver mais
                <i class="bi bi-chevron-right"></i>
            </a>
        </div>

        <div class="raspadinhas-grid-new">
            <?php if (!empty($raspadinhas)): ?>
                <?php foreach ($raspadinhas as $raspinha): ?>
                    <?php
                    // Etapa 2: Para cada raspadinha, buscar o seu prémio máximo.
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
                        // Silencia o erro para não quebrar a página.
                    }
                    ?>
                    <div class="raspinha-card-new">
                        <div class="card-banner-new">
                            <?php if (!empty($raspinha['banner']) && file_exists($_SERVER['DOCUMENT_ROOT'] . $raspinha['banner'])): ?>
                                <img src="<?= htmlspecialchars($raspinha['banner']) ?>" alt="<?= htmlspecialchars($raspinha['nome']) ?>" class="banner-image-new">
                            <?php else: ?>
                                <div class="banner-placeholder-new">
                                    <i class="bi bi-grid-3x3-gap-fill"></i>
                                </div>
                            <?php endif; ?>
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
                <p>Nenhuma raspadinha em destaque no momento.</p>
            <?php endif; ?>
        </div>
    </div>
</section>

<style>
    /* ESTILOS GERAIS DO SHOWCASE */
    .raspadinhas-showcase {
        padding: 4rem 2rem;
    }

    .showcase-container {
        max-width: 1400px;
        margin: 0 auto;
        padding: 0 1rem;
    }

    .showcase-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 3rem;
        flex-wrap: wrap;
        gap: 1rem;
    }

    .showcase-title {
        font-size: 1.5rem;
        font-weight: 700;
        color: #ffffff;
        margin: 0;
        display: flex;
        align-items: center;
        gap: 0.75rem;
    }

    .showcase-title i {
        color: #78d403;
    }

    .view-all-header-btn {
        color: #9ca3af;
        text-decoration: none;
        font-weight: 600;
        font-size: 0.9rem;
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        transition: all 0.3s ease;
    }

    .view-all-header-btn:hover {
        color: #22c55e;
    }

    .raspadinhas-grid-new {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
        gap: 2rem;
    }

    /* ANIMAÇÕES */
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

    @keyframes bounce {

        0%,
        100% {
            transform: translateY(0);
        }

        50% {
            transform: translateY(-5px);
        }
    }

    .bouncing-icon {
        animation: bounce 1.2s infinite;
    }

    /* ESTILO DO CARD */
    .raspinha-card-new {
        background: #1a1a1a;
        border: 1px solid #2a2a2a;
        border-radius: 16px;
        overflow: hidden;
        display: flex;
        flex-direction: column;
        transition: all 0.4s cubic-bezier(0.25, 0.8, 0.25, 1);
        position: relative;
        animation: pulse-glow 3s infinite ease-in-out;
        /* HABILITA CONTAINER QUERIES PARA AJUSTES INTERNOS */
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

    .banner-placeholder-new {
        width: 100%;
        height: 180px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 3rem;
        color: rgba(255, 255, 255, 0.1);
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

    /* BOTÃO PRINCIPAL "JOGAR" */
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
        box-shadow: 0 0 10px rgba(0, 255, 127, 0.4);
        font-family: inherit;
        text-decoration: none;
        flex-grow: 1;
    }

    .play-button-replicated:hover {
        box-shadow: 0 0 20px rgba(0, 255, 127, 0.6);
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
        /* FONTE FLUIDA: se ajusta ao tamanho do container */
        font-size: clamp(0.8rem, 0.7rem + 1.2cqi, 1.0rem);
    }

    .play-button-content-replicated span:first-child {
        display: flex;
        align-items: center;
        gap: 0.25rem;
        flex-shrink: 0;
        /* Impede que o texto "Jogar" seja esmagado */
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
        /* Impede que o valor seja esmagado */
        /* FONTE FLUIDA: se ajusta ao tamanho do container */
        font-size: clamp(0.75rem, 0.65rem + 1.2cqi, 0.9rem);
    }

    /* BOTÃO SECUNDÁRIO "VER PRÊMIOS" */
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
        color: #ffffff;
    }

    /* ========================================================== */
    /* REGRAS DE AJUSTE PARA CONTAINERS (CARDS) ESTREITOS       */
    /* ========================================================== */
    @container raspinha-card (max-width: 320px) {

        /* Ajusta apenas os espaçamentos, pois a fonte já é fluida */
        .play-button-content-replicated {
            padding: 0.6rem 0.5rem;
            gap: 0.4rem;
        }

        .play-button-value-replicated {
            padding: 0.15rem 0.3rem;
        }

        .view-prizes-btn-new {
            font-size: 0.75rem;
            gap: 0.3rem;
        }
    }

    /* MEDIA QUERY PARA LAYOUT MOBILE GERAL */
    @media (max-width: 768px) {
        .raspadinhas-showcase {
            padding: 2rem 1rem;
        }

        .raspadinhas-grid-new {
            grid-template-columns: 1fr;
        }
    }
</style>