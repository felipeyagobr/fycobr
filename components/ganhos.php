<?php
// Buscar prêmios do banco de dados
try {
    $stmt = $pdo->query("
        SELECT rp.nome, rp.icone, rp.valor 
        FROM raspadinha_premios rp 
        JOIN raspadinhas r ON rp.raspadinha_id = r.id 
        WHERE rp.valor > 0 
        ORDER BY RAND() 
        LIMIT 20
    ");
    $premios = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $premios = [];
}

// Lista de nomes brasileiros aleatórios
$nomes = [
    'Ana Souza',
    'João Silva',
    'Maria Oliveira',
    'Pedro Almeida',
    'Carla Ferreira',
    'Lucas Mendes',
    'Fernanda Costa',
    'Rafael Lima',
    'Juliana Araújo',
    'Bruno Martins',
    'Camila Pereira',
    'Diego Rocha',
    'Beatriz Cardoso',
    'Thiago Ribeiro',
    'Larissa Teixeira',
    'André Santos',
    'Patrícia Machado',
    'Gustavo Correia',
    'Isabela Pinto',
    'Felipe Barbosa',
    'Amanda Monteiro',
    'Rodrigo Nascimento',
    'Natália Dias',
    'Gabriel Fonseca',
    'Letícia Carvalho',
    'Mateus Xavier',
    'Carolina Rezende',
    'Leonardo Farias',
    'Vanessa Ramos',
    'Marcelo Miranda',
    'Priscila Duarte',
    'Daniel Batista',
    'Roberta Queiroz',
    'Vinícius Cunha',
    'Mônica Freitas',
    'Ricardo Assis',
    'Adriana Vieira',
    'Fábio Gomes',
    'Cristina Neves',
    'Alexandre Pacheco',
    'Silvia Barros',
    'Eduardo Moreira',
    'Renata Tavares',
    'Carlos Peixoto',
    'Tatiane Antunes',
    'Paulo Moura',
    'Débora Azevedo',
    'Antônio Magalhães',
    'Sandra Siqueira',
    'José Campos',
    'Márcia Torres',
    'Roberto Guimarães',
    'Luciana Peixoto',
    'Marcos Menezes',
    'Eliane Coelho',
    'Francisco Ferraz',
    'Regina Leal',
    'Fernando Cavalcanti',
    'Marta Nogueira',
    'Luiz Borges',
    'Denise Amaral',
    'Sérgio Sales',
    'Cláudia Esteves',
    'Jorge Castro',
    'Vera Lacerda',
    'Raimundo Macedo',
    'Solange Prado',
    'Manoel Barreto',
    'Rosana Moraes',
    'Edson Andrade',
    'Lúcia Camargo',
    'Wilson Aguiar',
    'Simone Bittencourt',
    'Sebastião Peixoto',
    'Teresa Porto',
    'Antônio Cordeiro',
    'Aparecida Lopes',
    'Valdir Bueno',
    'Fátima Godoy',
    'João Rezende',
    'Cleusa Meireles',
    'Benedito Gonçalves',
    'Rita Barcellos',
    'Nelson Quevedo',
    'Marlene Santos',
    'Davi Silveira',
    'Célia Campos',
    'Geraldo Soares',
    'Neusa Braga',
    'Ademir Vasconcelos',
    'Ivone Peixoto',
    'Miguel Brandão',
    'Irene Chaves',
    'Waldir Pimentel',
    'Sônia Serafim',
    'Benedita Pontes',
    'Valter Figueiredo',
    'Lourdes Alencar',
    'Reinaldo Parente',
    'Terezinha Cavalheiro',
    'Alcides Galvão'

];

// Gerar ganhadores com dados aleatórios
$ganhadores = [];
$valor_total_distribuido = 0;

foreach ($premios as $premio) {
    $nome_aleatorio = $nomes[array_rand($nomes)];
    $tempo_aleatorio = rand(1, 60); // Entre 1 e 60 minutos

    $ganhadores[] = [
        'nome' => $nome_aleatorio,
        'premio' => $premio['nome'],
        'icone' => $premio['icone'],
        'valor' => $premio['valor'],
        'tempo' => $tempo_aleatorio
    ];

    $valor_total_distribuido += $premio['valor'];
}

// Duplicar para efeito infinito
$ganhadores_duplicados = array_merge($ganhadores, $ganhadores);
?>

<section class="winners-section">
    <div class="winners-container">
        <div class="main-layout">
            <div class="winners-header">
                <svg viewBox="0 0 59 60" fill="none" xmlns="http://www.w3.org/2000/svg" class="live-icon">
                    <path d="M2.381 31.8854L0.250732 32.1093L5.76436 16.3468L8.04082 16.1075L13.5753 30.7088L11.4242 30.9349L10.0667 27.2976L3.71764 27.9649L2.381 31.8854ZM6.64153 19.5306L4.34418 26.114L9.461 25.5762L7.14277 19.4779C7.101 19.3283 7.05227 19.1794 6.99657 19.0313C6.94088 18.8691 6.90607 18.7328 6.89215 18.6222C6.8643 18.7372 6.82949 18.8808 6.78772 19.0532C6.74595 19.2116 6.69722 19.3707 6.64153 19.5306Z" fill="#7B869D"></path>
                    <path d="M28.5469 21.5332C28.5469 23.0732 28.2336 24.4711 27.6071 25.727C26.9945 26.9674 26.1382 27.9814 25.0382 28.769C23.9522 29.5411 22.6922 30.0026 21.2581 30.1533C19.8518 30.3011 18.5987 30.1038 17.4988 29.5614C16.4128 29.0036 15.5634 28.1688 14.9508 27.0572C14.3382 25.9456 14.0319 24.6128 14.0319 23.0588C14.0319 21.5188 14.3382 20.1286 14.9508 18.8882C15.5774 17.6464 16.4336 16.6324 17.5197 15.8462C18.6057 15.0601 19.8588 14.5924 21.2789 14.4431C22.7131 14.2924 23.9731 14.4959 25.0591 15.0538C26.1451 15.6117 26.9945 16.4464 27.6071 17.558C28.2336 18.6681 28.5469 19.9932 28.5469 21.5332ZM26.3958 21.7593C26.3958 20.5833 26.18 19.577 25.7483 18.7404C25.3306 17.9023 24.7389 17.2855 23.9731 16.8899C23.2073 16.4804 22.3093 16.3298 21.2789 16.4381C20.2625 16.5449 19.3715 16.8836 18.6057 17.4541C17.8399 18.0106 17.2412 18.7525 16.8096 19.6799C16.3919 20.6058 16.183 21.6567 16.183 22.8327C16.183 24.0087 16.3919 25.0158 16.8096 25.8539C17.2412 26.6905 17.8399 27.3136 18.6057 27.7231C19.3715 28.1326 20.2625 28.2839 21.2789 28.1771C22.3093 28.0688 23.2073 27.7294 23.9731 27.1589C24.7389 26.5745 25.3306 25.8193 25.7483 24.8934C26.18 23.966 26.3958 22.9213 26.3958 21.7593Z" fill="#7B869D"></path>
                    <path d="M5.74539 52.1851L0.200195 37.8724L3.66344 37.5084L6.46607 44.7421C6.63956 45.1801 6.79971 45.6397 6.94652 46.1208C7.09332 46.6018 7.2468 47.156 7.40695 47.7833C7.59379 47.0525 7.76061 46.4445 7.90742 45.9594C8.06757 45.4729 8.22772 44.9998 8.38787 44.5401L11.1505 36.7215L14.5336 36.3659L9.08853 51.8337L5.74539 52.1851Z" fill="#00E880"></path>
                    <path d="M19.3247 35.8623V50.7578L16.0816 51.0987V36.2032L19.3247 35.8623Z" fill="#00E880"></path>
                    <path d="M26.4195 50.0121L20.8743 35.6995L24.3375 35.3355L27.1401 42.5692C27.3136 43.0072 27.4738 43.4667 27.6206 43.9478C27.7674 44.4289 27.9209 44.9831 28.081 45.6104C28.2679 44.8795 28.4347 44.2716 28.5815 43.7864C28.7416 43.2999 28.9018 42.8268 29.0619 42.3672L31.8245 34.5486L35.2077 34.193L29.7626 49.6608L26.4195 50.0121Z" fill="#00E880"></path>
                    <path d="M49.647 40.1029C49.647 41.6193 49.3401 42.9935 48.7261 44.2255C48.1122 45.4441 47.2581 46.4397 46.1637 47.2123C45.0694 47.9714 43.8015 48.4268 42.3602 48.5782C40.9322 48.7283 39.671 48.5388 38.5766 48.0097C37.4956 47.4658 36.6482 46.6491 36.0343 45.5595C35.4337 44.4686 35.1334 43.1649 35.1334 41.6485C35.1334 40.1321 35.4404 38.7646 36.0543 37.5461C36.6682 36.314 37.5156 35.3192 38.5967 34.5614C39.691 33.7889 40.9522 33.3275 42.3802 33.1774C43.8216 33.0259 45.0827 33.2222 46.1637 33.7661C47.2581 34.2952 48.1122 35.1045 48.7261 36.1941C49.3401 37.2836 49.647 38.5866 49.647 40.1029ZM46.2238 40.4627C46.2238 39.51 46.0703 38.7142 45.7634 38.0755C45.4564 37.4234 45.016 36.9463 44.4421 36.6443C43.8816 36.3409 43.201 36.2313 42.4002 36.3155C41.5995 36.3996 40.9122 36.653 40.3383 37.0757C39.7644 37.4983 39.324 38.0679 39.017 38.7846C38.7101 39.4878 38.5566 40.3158 38.5566 41.2686C38.5566 42.2214 38.7101 43.0238 39.017 43.6759C39.324 44.3281 39.7644 44.8051 40.3383 45.1071C40.9122 45.4091 41.5995 45.5181 42.4002 45.4339C43.201 45.3497 43.8816 45.097 44.4421 44.6758C45.016 44.2398 45.4564 43.6634 45.7634 42.9467C46.0703 42.2301 46.2238 41.4021 46.2238 40.4627Z" fill="#00E880"></path>
                    <circle cx="39" cy="20" r="6" fill="#222733"></circle>
                    <g filter="url(#filter0_d_726_17235)">
                        <circle cx="39" cy="20" r="3.75" fill="#00E880"></circle>
                    </g>
                </svg>
            </div>

            <div class="winners-carousel">
                <div class="winners-track">
                    <?php foreach ($ganhadores_duplicados as $ganhador): ?>
                        <div class="winner-item">
                            <div class="winner-avatar">
                                <?php if (!empty($ganhador['icone']) && file_exists($_SERVER['DOCUMENT_ROOT'] . $ganhador['icone'])): ?>
                                    <img src="<?= htmlspecialchars($ganhador['icone']) ?>" alt="<?= htmlspecialchars($ganhador['premio']) ?>" class="winner-image">
                                <?php else: ?>
                                    <div class="winner-placeholder">
                                        <i class="bi bi-gift"></i>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="winner-info">
                                <div class="winner-name">
                                    <?php
                                    $nome_completo = $ganhador['nome'];
                                    $partes_nome = explode(' ', $nome_completo);
                                    $primeiro_nome = $partes_nome[0];
                                    $sobrenome = count($partes_nome) > 1 ? end($partes_nome) : '';

                                    if ($sobrenome) {
                                        $sobrenome_ofuscado = substr($sobrenome, 0, 2) . str_repeat('*', strlen($sobrenome) - 2);
                                        echo htmlspecialchars($primeiro_nome . ' ' . $sobrenome_ofuscado);
                                    } else {
                                        echo htmlspecialchars($primeiro_nome);
                                    }
                                    ?>
                                </div>
                                <div class="prize-name"><?= htmlspecialchars($ganhador['premio']) ?></div>
                                <div class="prize-value">
                                    <span class="currency-symbol">R$</span>
                                    <span class="currency-value"><?= number_format($ganhador['valor'], 0, ',', '.') ?></span>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</section>

<style>
    /* --- Estilos Finais --- */
    .winners-section {
        padding: 2rem 1rem;
        overflow: hidden;
    }

    .winners-container {
        max-width: 100%;
        margin: 0 auto;
    }

    /* NOVO LAYOUT PRINCIPAL */
    .main-layout {
        display: flex;
        align-items: center;
        /* Alinha o "AO VIVO" e a esteira verticalmente */
        gap: 0.5rem;
        /* Espaço entre o "AO VIVO" e a esteira */
    }

    /* CABEÇALHO (agora só o SVG) */
    .winners-header {
        flex-shrink: 0;
        /* Impede que o cabeçalho seja espremido */
    }

    .winners-header .live-icon {
        height: 2.2rem;
        /* Altura similar ao texto original */
        width: auto;
        /* Largura automática para manter a proporção */
    }

    /* CARROSSEL */
    .winners-carousel {
        flex-grow: 1;
        /* Faz a esteira ocupar todo o espaço restante */
        position: relative;
        overflow: hidden;
    }

    .winners-track {
        display: flex;
        gap: 1rem;
        width: fit-content;
        transition: transform 0.5s ease-in-out;
    }

    /* CARD */
    .winner-item {
        flex-shrink: 0;
        width: 210px;
        background: #161616;
        border: 1px solid rgba(255, 255, 255, 0.08);
        border-radius: 10px;
        display: flex;
        align-items: center;
        padding: 0.5rem;
        gap: 0.75rem;
        overflow: hidden;
    }

    .winner-avatar {
        width: 45px;
        height: 45px;
        flex-shrink: 0;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .winner-image {
        width: 100%;
        height: 100%;
        object-fit: contain;
    }

    .winner-info {
        flex-grow: 1;
        display: flex;
        flex-direction: column;
        justify-content: center;
        min-width: 0;
    }

    .winner-name,
    .prize-name,
    .prize-value {
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        line-height: 1.3;
    }

    .winner-name {
        font-size: 0.75rem;
        font-weight: 500;
        color: #f8e800;
        margin: 0;
    }

    .prize-name {
        font-size: 0.8rem;
        font-weight: 500;
        color: #A0A0A0;
        margin: 0;
    }

    .prize-value {
        font-size: 1rem;
        font-weight: 500;
    }

    .currency-symbol {
        color: #78d403;
    }

    .currency-value {
        color: #F9F9FA;
    }
</style>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Lazy load images
        const images = document.querySelectorAll('.winner-image');
        images.forEach(img => {
            img.onload = function() {
                this.classList.add('loaded');
            };

            // If image is already loaded
            if (img.complete) {
                img.classList.add('loaded');
            }
        });

        console.log('%c🏆 Últimos Ganhadores carregados!', 'color: #22c55e; font-size: 14px; font-weight: bold;');
    });

    document.addEventListener('DOMContentLoaded', function() {
        // Código existente de lazy load...
        const images = document.querySelectorAll('.winner-image');
        images.forEach(img => {
            img.onload = function() {
                this.classList.add('loaded');
            };
            if (img.complete) {
                img.classList.add('loaded');
            }
        });

        console.log('%c🏆 Últimos Ganhadores carregados!', 'color: #22c55e; font-size: 14px; font-weight: bold;');

        // --- NOVO CÓDIGO DA ANIMAÇÃO ---
        const track = document.querySelector('.winners-track');
        if (track) {
            let currentIndex = 0;
            const items = track.querySelectorAll('.winner-item');
            const totalItems = items.length / 2; // Metade, pois a lista está duplicada

            setInterval(() => {
                currentIndex++;

                // Pega a largura do card + o espaçamento (gap)
                const itemWidth = items[0].offsetWidth;
                const gap = parseFloat(getComputedStyle(track).gap);
                const moveDistance = itemWidth + gap;

                track.style.transform = `translateX(-${currentIndex * moveDistance}px)`;

                // Quando chegar ao final da primeira metade, reseta sem animação
                if (currentIndex >= totalItems) {
                    setTimeout(() => {
                        track.style.transition = 'none'; // Desliga a animação para o reset
                        track.style.transform = 'translateX(0)';
                        currentIndex = 0;

                        // Liga a animação novamente após o reset
                        setTimeout(() => {
                            track.style.transition = 'transform 0.5s ease-in-out';
                        }, 50);

                    }, 500); // Espera a última transição terminar
                }
            }, 1300);
        }
    });
</script>