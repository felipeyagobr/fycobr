<?php
@session_start();
include('./conexao.php');
?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $nomeSite; ?> | Casa de Aposta e Cassino</title>
    <meta name="description" content="Jogue, raspe e ganhe na raspadinhabr.bet!">

    <!-- Preload Critical Resources -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    
    <!-- Styles -->
    <link rel="stylesheet" href="assets/style/globalStyles.css?v=<?php echo time();?>"/>
    
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    
    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
    <script src="https://cdn.jsdelivr.net/npm/notiflix@3.2.8/dist/notiflix-aio-3.2.8.min.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/notiflix@3.2.8/src/notiflix.min.css" rel="stylesheet">
    
    <!-- Favicon -->
    <link rel="icon" type="image/png" href="/assets/images/favicon-96x96.png" sizes="96x96" />
    <link rel="icon" type="image/svg+xml" href="/assets/images/favicon.svg" />
    <link rel="shortcut icon" href="/assets/images/favicon.ico" />
    <link rel="apple-touch-icon" sizes="180x180" href="/assets/images/apple-touch-icon.png" />
    <meta name="apple-mobile-web-app-title" content="RASPADINHABR" />
    <link rel="manifest" href="/assets/images/site.webmanifest" />
    
    <!-- Open Graph -->
    <meta property="og:title" content="<?php echo $nomeSite;?> - Raspadinhas Online">
    <meta property="og:description" content="Raspe e ganhe prêmios incríveis! PIX na conta instantâneo.">
    <meta property="og:type" content="website">
    <meta property="og:url" content="<?php echo $urlSite;?>">

    <style>
        /* Loading Animation */
        .loading-screen {
            position: fixed;
            top: 0;
            left: 0;
            width: 100vw;
            height: 100vh;
            background: rgba(10, 10, 10, 0.0);
            z-index: 9999;
            transition: opacity 0.5s ease, backdrop-filter 0.5s ease;
            display: grid;
            place-items: center;
        }

        .loading-logo {
            width: 150px;
            height: auto;
            /* Efeito de brilho suave e profissional */
            filter: drop-shadow(0 0 15px rgba(120, 212, 3, 0.5));
            opacity: 0;
            /* Inicia invisível para o fade-in da animação */
            /* Animação de revelação */
            animation: reveal-up 1.2s cubic-bezier(0.76, 0, 0.24, 1) forwards;
        }

        @keyframes reveal-up {
            from {
                opacity: 0;
                /* Revela a imagem de baixo para cima */
                clip-path: inset(100% 0 0 0);
                transform: translateY(20px);
            }

            to {
                opacity: 1;
                clip-path: inset(0% 0 0 0);
                transform: translateY(0);
            }
        }

        .hidden {
            opacity: 0;
            pointer-events: none;
            backdrop-filter: blur(0px);
        }

        /* Smooth scroll */
        html {
            scroll-behavior: smooth;
        }

        /* Parallax effect */
        .parallax-element {
            transform: translateZ(0);
            will-change: transform;
        }

        /* Animations */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .animate-fade-in-up {
            animation: fadeInUp 0.6s ease-out forwards;
        }

        /* Floating elements animation */
        .floating {
            animation: floating 3s ease-in-out infinite;
        }

        @keyframes floating {

            0%,
            100% {
                transform: translateY(0);
            }

            50% {
                transform: translateY(-10px);
            }
        }

        /* Glowing effect */
        .glow {
            box-shadow: 0 0 20px rgba(120, 212, 3, 0.3);
        }

        .glow:hover {
            box-shadow: 0 0 30px rgba(120, 212, 3, 0.5);
        }
    </style>
</head>

<body>
    <div class="loading-screen" id="loadingScreen">
        <img src="assets/images/fav_raspadinhabr.png" class="loading-logo">
    </div>

    <?php include('./inc/header.php'); ?>

    <main>
        <?php include('./components/carrossel.php'); ?>

        <?php include('./components/ganhos.php'); ?>

        <?php include('./components/chamada.php'); ?>

        <?php include('./components/modals.php'); ?>

        <?php include('./components/testimonials.php'); ?>
    </main>

    <?php include('./inc/footer.php'); ?>

    <script>
        // Loading screen
        window.addEventListener('load', function() {
            const loadingScreen = document.getElementById('loadingScreen');
            // Aumentei o tempo para a animação terminar com calma
            setTimeout(() => {
                loadingScreen.classList.add('hidden');
            }, 1500);
        });

        // Smooth animations on scroll
        const observerOptions = {
            threshold: 0.1,
            rootMargin: '0px 0px -50px 0px'
        };

        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('animate-fade-in-up');
                }
            });
        }, observerOptions);

        // Observe elements for animation
        document.addEventListener('DOMContentLoaded', function() {
            const elementsToAnimate = document.querySelectorAll('.step-item, .game-category, .prize-item');
            elementsToAnimate.forEach(el => {
                observer.observe(el);
            });
        });

        // Parallax effect for hero section
        window.addEventListener('scroll', () => {
            const scrolled = window.pageYOffset;
            const heroElements = document.querySelectorAll('.parallax-element');

            heroElements.forEach(element => {
                const speed = element.dataset.speed || 0.5;
                element.style.transform = `translateY(${scrolled * speed}px)`;
            });
        });

        // Add floating animation to certain elements
        document.addEventListener('DOMContentLoaded', function() {
            const floatingElements = document.querySelectorAll('.hero-visuals .gaming-item');
            floatingElements.forEach((el, index) => {
                el.style.animationDelay = `${index * 0.5}s`;
                el.classList.add('floating');
            });
        });

        // Notiflix configuration
        Notiflix.Notify.init({
            width: '300px',
            position: 'right-top',
            distance: '20px',
            opacity: 1,
            borderRadius: '12px',
            rtl: false,
            timeout: 4000,
            messageMaxLength: 110,
            backOverlay: false,
            backOverlayColor: 'rgba(0,0,0,0.5)',
            plainText: true,
            showOnlyTheLastOne: false,
            clickToClose: true,
            pauseOnHover: true,
            ID: 'NotiflixNotify',
            className: 'notiflix-notify',
            zindex: 4001,
            fontFamily: 'Inter',
            fontSize: '14px',
            cssAnimation: true,
            cssAnimationDuration: 400,
            cssAnimationStyle: 'zoom',
            closeButton: false,
            useIcon: true,
            useFontAwesome: false,
            fontAwesomeIconStyle: 'basic',
            fontAwesomeIconSize: '16px',
            success: {
                background: '#22c55e',
                textColor: '#fff',
                childClassName: 'notiflix-notify-success',
                notiflixIconColor: 'rgba(0,0,0,0.2)',
                fontAwesomeClassName: 'fas fa-check-circle',
                fontAwesomeIconColor: 'rgba(0,0,0,0.2)',
                backOverlayColor: 'rgba(34,197,94,0.2)',
            }
        });

        // Dynamic copyright year
        document.addEventListener('DOMContentLoaded', function() {
            const currentYear = new Date().getFullYear();
            const copyrightElements = document.querySelectorAll('.footer-description');
            if (copyrightElements.length > 0) {
                copyrightElements[0].innerHTML = copyrightElements[0].innerHTML.replace('2025', currentYear);
            }
        });

        // Add glow effect to interactive elements
        document.addEventListener('DOMContentLoaded', function() {
            const glowElements = document.querySelectorAll('.btn-register, .hero-cta, .game-btn');
            glowElements.forEach(el => {
                el.classList.add('glow');
            });
        });

        // Mobile menu toggle (if needed)
        function toggleMobileMenu() {
            const mobileMenu = document.querySelector('.mobile-menu');
            if (mobileMenu) {
                mobileMenu.classList.toggle('active');
            }
        }

        // Console welcome message
        console.log('%c🎯 RASPADINHABR - Bem-vindo!', 'color: #78d403; font-size: 16px; font-weight: bold;');
        console.log('%cSistema carregado com sucesso!', 'color: #16a34a; font-size: 12px;');
    </script>

    <script>
        // Performance monitoring
        window.addEventListener('load', function() {
            if ('performance' in window) {
                const loadTime = performance.timing.loadEventEnd - performance.timing.navigationStart;
                console.log(`Página carregada em ${loadTime}ms`);
            }
        });

        // Error handling
        window.addEventListener('error', function(e) {
            console.error('Erro na página:', e.error);
        });

        // Lazy loading for images when implemented
        if ('IntersectionObserver' in window) {
            const imageObserver = new IntersectionObserver((entries, observer) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        const img = entry.target;
                        img.src = img.dataset.src;
                        img.classList.remove('lazy');
                        imageObserver.unobserve(img);
                    }
                });
            });

            document.querySelectorAll('img[data-src]').forEach(img => {
                imageObserver.observe(img);
            });
        }
    </script>
</body>

</html>