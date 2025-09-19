<?php
// É importante buscar as configurações de bônus aqui para usá-las no PHP e no JavaScript
$bonus_settings_query = $pdo->prepare("SELECT * FROM bonus_settings WHERE id = 1");
$bonus_settings_query->execute();
$bonus_settings = $bonus_settings_query->fetch(PDO::FETCH_ASSOC);

$is_bonus_active = $bonus_settings['is_active'] ?? 0;
$min_deposit_for_bonus = $bonus_settings['min_deposit'] ?? 0;

$is_user_eligible_for_bonus = false;
if (isset($_SESSION['usuario_id'])) {
    $user_bonus_query = $pdo->prepare("SELECT recebeu_bonus FROM usuarios WHERE id = ?");
    $user_bonus_query->execute([$_SESSION['usuario_id']]);
    $user_bonus_fetched = $user_bonus_query->fetchColumn();
    if ($user_bonus_fetched !== false) {
        $is_user_eligible_for_bonus = ($user_bonus_fetched == 0);
    }
}
?>
<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-maskmoney/3.0.2/jquery.maskMoney.min.js"></script>

<div id="backdrop2" class="modal-backdrop"></div>

<section id="depositModal" class="modal-container">
    <div class="modal-wrapper">
        <div class="modal-card">
            <button id="backToDepositForm" class="modal-back" style="display: none;"><i class="bi bi-arrow-left"></i></button>
            <button id="closeDepositModal" class="modal-close">
                <i class="bi bi-x"></i>
            </button>

            <h2 class="modal-title">Depositar</h2>

            <form id="depositForm" class="modal-form">
                <div class="form-group-column">
                     <div class="label-wrapper">
                        <label for="amountInput" class="form-label">Valor a ser depositado:</label>
                        <span class="coupon-text" id="show-coupon-field">Possui cupom?</span>
                    </div>
                    <div class="form-group">
                        <div class="input-icon">
                           <i class="bi bi-currency-dollar"></i>
                        </div>
                        <input type="text" name="amount" id="amountInput" required
                            class="form-input"
                            placeholder="R$ 0,00" inputmode="numeric">
                    </div>
                </div>

                <div class="quick-amounts">
                    <button type="button" data-value="35,00" class="quick-amount">R$ 35</button>
                    <button type="button" data-value="50,00" class="quick-amount">R$ 50</button>
                    <button type="button" data-value="100,00" class="quick-amount">R$ 100</button>
                    <button type="button" data-value="200,00" class="quick-amount">R$ 200</button>
                    <button type="button" data-value="500,00" class="quick-amount">R$ 500</button>
                    <button type="button" data-value="1000,00" class="quick-amount">R$ 1.000</button>
                </div>

                <div class="form-group">
                    <div class="input-icon">
                        <i class="bi bi-person-vcard"></i>
                    </div>
                    <input type="text" name="cpf" id="cpfInput" required
                        class="form-input"
                        placeholder="CPF (000.000.000-00)" maxlength="14">
                </div>
                
                <div class="form-group" id="coupon-group" style="display: none;">
                    <div class="input-icon">
                        <i class="bi bi-tag-fill"></i>
                    </div>
                    <input type="text" name="coupon" id="couponInput" class="form-input" placeholder="Digite seu cupom">
                </div>

                <div id="bonus-option-wrapper" class="bonus-wrapper">
                    <label for="quer_bonus" class="bonus-label">
                        <input id="quer_bonus" name="quer_bonus" type="checkbox" checked class="bonus-checkbox">
                        <span class="bonus-text">Sim, quero o bônus de Boas-vindas!</span>
                    </label>
                </div>

                <button type="submit" class="submit-btn">
                    Depositar
                </button>
            </form>

            <div id="qrArea" class="qr-area">
                <h3 class="qr-title">Copie o código "copia e cola" abaixo para realizar o pagamento</h3>

                <div class="deposit-value-area">
                    <span class="deposit-value-label">Valor do depósito</span>
                    <span class="deposit-value-amount" id="depositValueDisplay">R$ 0,00</span>
                </div>

                <div class="qr-code-container">
                     <input type="text" id="qrCodeValue" class="qr-input" readonly>
                </div>
                <button id="copyQr" class="copy-btn">
                    <i class="bi bi-clipboard-check"></i>
                    <span>Copiar Código "copia e cola"</span>
                </button>

                <img id="qrImg" src="" alt="QR Code" class="qr-image" style="display: none;">

                <div class="timer-area">
                    <p>O tempo para você pagar acaba em: <span id="countdownTimer">05:00</span></p>
                    <div class="progress-bar-container">
                        <div class="progress-bar" id="countdownProgressBar"></div>
                    </div>
                </div>

                <div class="status-area">
                    <div class="spinner"></div>
                    <span>Aguardando pagamento</span>
                </div>

                <a href="#" id="showQrLink" class="show-qr-link">Exibir QR Code</a>
            </div>
        </div>
    </div>
</section>

<section id="withdrawModal" class="modal-container">
    <div class="modal-wrapper">
        <div class="modal-card">
            <button id="closeWithdrawModal" class="modal-close">
                <i class="bi bi-x"></i>
            </button>

            <h2 class="modal-title">Sacar</h2>

            <div class="balance-card">
                <h3 class="balance-label">Saldo Disponível</h3>
                <p class="balance-amount" id="currentBalance">R$ 0,00</p>
            </div>

            <form id="withdrawForm" class="modal-form">
                 <div class="form-group-column">
                     <div class="label-wrapper">
                        <label for="withdrawAmount" class="form-label">Valor a ser sacado:</label>
                    </div>
                    <div class="form-group">
                        <div class="input-icon">
                           <i class="bi bi-currency-dollar"></i>
                        </div>
                        <input type="text" name="amount" id="withdrawAmount" required
                            class="form-input"
                            placeholder="R$ 0,00" inputmode="numeric">
                    </div>
                </div>

                <div class="quick-amounts">
                    <button type="button" data-value="50,00" class="quick-withdraw">R$ 50</button>
                    <button type="button" data-value="75,00" class="quick-withdraw">R$ 75</button>
                    <button type="button" data-value="100,00" class="quick-withdraw">R$ 100</button>
                    <button type="button" data-value="200,00" class="quick-withdraw">R$ 200</button>
                    <button type="button" data-value="500,00" class="quick-withdraw">R$ 500</button>
                    <button type="button" data-value="1000,00" class="quick-withdraw">R$ 1.000</button>
                </div>


                <p class="form-helper-text">
                    O saque será enviado para o CPF cadastrado em seu perfil.
                </p>

                <?php if (empty(trim($usuario['cpf'] ?? ''))): ?>

                    <button type="button" class="submit-btn" disabled >
                        <i class="bi bi-person-vcard"></i>
                        Cadastre seu CPF no Perfil
                    </button>
                    <p class="form-helper-text" style="color: #ffc107;">
                        É necessário ter um CPF cadastrado para poder sacar.
                    </p>

                <?php else: ?>

                    <button type="submit" class="submit-btn withdraw-btn">
                        Solicitar Saque
                    </button>

                <?php endif; ?>
            </form>
        </div>
    </div>
</section>

<style>
    @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap');

    :root {
        --brand-color: #78d403;
        --brand-hover: #6abf03;
        --modal-bg: linear-gradient(145deg, #2b2b2b, #1a1a1a);
        --modal-border-color: rgba(255, 255, 255, 0.1);
        --text-light: #F9FAFB;
        --text-dark: #9ca3af;
        --input-bg: #2c2c2c;
        --input-border: #4a4a4a;
        --danger-color: #ef4444;
        --danger-hover: #dc2626;
        --font-family: 'Poppins', sans-serif;
    }

    body.modal-open {
        position: fixed;
        overflow: hidden;
        width: 100%;
        height: 100%;
    }

    /* General Modal Styles */
    .modal-backdrop {
        position: fixed;
        inset: 0;
        background: rgba(0, 0, 0, 0.85);
        backdrop-filter: blur(10px);
        z-index: 1200;
        opacity: 0;
        visibility: hidden;
        transition: opacity 0.3s ease, visibility 0.3s ease;
    }

    .modal-backdrop.active {
        opacity: 1;
        visibility: visible;
    }

    .modal-container {
        position: fixed;
        inset: 0;
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 1250;
        overflow-y: auto;
        padding: 1rem;
        opacity: 0;
        visibility: hidden;
        transform: scale(0.95) translateY(10px);
        transition: opacity 0.3s ease, visibility 0.3s ease, transform 0.3s ease;
    }

    .modal-container.active {
        opacity: 1;
        visibility: visible;
        transform: scale(1) translateY(0);
    }

    .modal-wrapper {
        width: 100%;
        max-width: 480px;
        font-family: var(--font-family);
    }
    
    .modal-card {
        background: var(--modal-bg);
        border-top: 4px solid var(--brand-color);
        border-radius: 1.5rem;
        padding: 2.5rem;
        position: relative;
        box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.7);
        overflow: hidden;
        max-height: 90vh;
        overflow-y: auto;
    }

    .modal-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 200px;
        background: radial-gradient(circle at 50% -20%, rgba(120, 212, 3, 0.15), transparent 70%);
        pointer-events: none;
    }
    
    .modal-title {
        text-align: center;
        font-size: 1.75rem;
        font-weight: 700;
        margin-bottom: 2rem;
        color: var(--text-light);
        padding: 0 2.5rem;
    }
    
    .modal-close, .modal-back {
        position: absolute;
        top: 1rem;
        width: 36px;
        height: 36px;
        background: rgba(255, 255, 255, 0.08);
        border: none;
        border-radius: 50%;
        color: var(--text-dark);
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        transition: all 0.2s ease;
        font-size: 1.2rem;
        z-index: 10;
    }
    .modal-close { right: 1rem; }
    .modal-back { left: 1rem; }

    .modal-close:hover, .modal-back:hover {
        background: var(--danger-color);
        color: white;
        transform: scale(1.1);
    }
    .modal-close:hover { transform: scale(1.1) rotate(90deg); }
    .modal-back:hover { background-color: var(--input-border); }
    
    .modal-form {
        display: flex;
        flex-direction: column;
        gap: 1.5rem;
    }
    
    .quick-amounts {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 0.75rem;
    }

    .quick-amount, .quick-withdraw {
        background: var(--input-bg);
        border: 1px solid var(--input-border);
        color: var(--text-light);
        padding: 0.75rem;
        border-radius: 12px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.2s ease;
        text-align: center;
        font-size: 0.9rem;
    }
    .quick-amount:hover, .quick-withdraw:hover {
        background-color: var(--brand-color);
        border-color: var(--brand-hover);
        transform: translateY(-2px);
    }
    
    .form-group-column {
        display: flex;
        flex-direction: column;
        gap: 0.75rem;
    }

    .label-wrapper {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 0 0.25rem;
    }

    .form-label {
        font-size: 1rem;
        color: var(--text-dark);
        font-weight: 500;
    }

    .coupon-text {
        font-size: 0.875rem;
        color: var(--brand-color);
        cursor: pointer;
        font-weight: 600;
    }
    
    .form-group { position: relative; }
    
    .input-icon {
        position: absolute;
        left: 1.25rem;
        top: 50%;
        transform: translateY(-50%);
        color: var(--text-dark);
        font-size: 1.1rem;
        transition: color 0.3s ease;
    }

    .form-input {
        width: 100%;
        padding: 1.1rem 1rem 1.1rem 3.5rem;
        background: var(--input-bg);
        border: 1px solid var(--input-border);
        border-radius: 12px;
        color: var(--text-light);
        font-size: 1rem;
        font-family: var(--font-family);
        transition: all 0.3s ease;
    }
    .form-input::placeholder { color: #6b7280; }

    .form-input:focus {
        outline: none;
        border-color: var(--brand-color);
        box-shadow: 0 0 0 4px rgba(120, 212, 3, 0.2);
    }
    
    .form-group:focus-within .input-icon { color: var(--brand-color); }
    
    .balance-card {
        background: var(--input-bg);
        border: 1px solid var(--input-border);
        border-radius: 1rem;
        padding: 1.25rem;
        text-align: center;
        margin-bottom: 1.5rem;
    }

    .balance-label {
        color: var(--text-dark);
        font-size: 0.9rem;
        margin-bottom: 0.25rem;
    }

    .balance-amount {
        color: var(--text-light);
        font-size: 1.75rem;
        font-weight: 700;
    }

    .submit-btn {
        background: var(--brand-color);
        color: #1a1a1a;
        border: none;
        padding: 1.1rem;
        border-radius: 12px;
        font-size: 1.125rem;
        font-weight: 700;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 0.5rem;
        transition: all 0.3s ease;
        width: 100%;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .submit-btn:hover {
        background: var(--brand-hover);
        transform: translateY(-3px);
        box-shadow: 0 10px 20px rgba(0, 0, 0, 0.2);
    }
    
    .submit-btn:disabled {
        background-color: #555;
        cursor: not-allowed;
    }

    .withdraw-btn { background: var(--brand-color); }
    .withdraw-btn:hover { background: var(--brand-hover); }

    .form-helper-text {
        color: var(--text-dark);
        font-size: 0.875rem;
        text-align: center;
        margin: -0.75rem 0 0 0;
        line-height: 1.5;
        white-space: nowrap;
    }
    
    /* --- QR AREA STYLES --- */
    .qr-area {
        display: none;
        flex-direction: column;
        align-items: center;
        gap: 1.25rem;
        text-align: center;
        animation: fadeIn 0.5s ease;
    }
    .qr-area.active { display: flex; }
    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(10px); }
        to { opacity: 1; transform: translateY(0); }
    }
    .qr-area .qr-title {
        font-size: 1rem;
        font-weight: 500;
        color: var(--text-dark);
        margin: 0;
    }
    .deposit-value-area {
        background-color: var(--input-bg);
        border: 1px solid var(--input-border);
        border-radius: 12px;
        padding: 0.75rem 1.5rem;
        width: 100%;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    .deposit-value-label {
        font-size: 0.9rem;
        color: var(--text-dark);
    }
    .deposit-value-amount {
        font-size: 1.1rem;
        font-weight: 600;
        color: var(--text-light);
    }
    .qr-code-container {
        width: 100%;
        border: 2px solid var(--brand-color);
        border-radius: 14px;
        padding: 0.25rem;
        cursor: pointer;
    }
    .qr-input {
        width: 100%;
        padding: 1rem;
        font-family: monospace;
        font-size: 0.9rem;
        text-align: left;
        cursor: pointer;
        background: transparent;
        border: none;
        color: var(--text-light);
    }
    .qr-input:focus { outline: none; }
    .copy-btn {
        width: 100%;
        padding: 1rem;
        font-size: 1rem;
        letter-spacing: 0;
        text-transform: none;
        gap: 0.75rem;
        box-shadow: 0 0 20px rgba(120, 212, 3, 0.3);
    }
    .qr-image {
        width: 100%;
        max-width: 260px;
        height: auto;
        aspect-ratio: 1 / 1;
        background: white;
        padding: 1rem;
        border-radius: 1.5rem;
        margin: 0.5rem auto;
        box-shadow: 0 10px 25px rgba(0, 0, 0, 0.3);
    }
    .timer-area {
        color: var(--text-dark);
        font-size: 0.9rem;
        width: 100%;
    }
    .timer-area p { margin: 0 0 0.5rem 0;}
    #countdownTimer {
        font-weight: 700;
        color: var(--text-light);
    }
    .progress-bar-container {
        width: 100%;
        height: 8px;
        background-color: var(--input-bg);
        border-radius: 4px;
        overflow: hidden;
    }
    .progress-bar {
        height: 100%;
        width: 100%;
        background-color: var(--brand-color);
        border-radius: 4px;
        transition: width 1s linear;
    }
    
    .status-area {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        color: var(--brand-color);
        font-weight: 600;
    }
    .spinner {
        width: 20px;
        height: 20px;
        border: 3px solid rgba(120, 212, 3, 0.3);
        border-top-color: var(--brand-color);
        border-radius: 50%;
        animation: spin 1s linear infinite;
    }
    @keyframes spin { to { transform: rotate(360deg); } }

    .show-qr-link {
        color: var(--text-dark);
        text-decoration: underline;
        cursor: pointer;
        font-size: 0.9rem;
    }
    .show-qr-link:hover { color: var(--text-light); }
    
    /* --- BÔNUS --- */
    .bonus-wrapper { display: none; margin: -0.5rem 0 0.5rem 0; padding: 1rem; background-color: rgba(120, 212, 3, 0.1); border: 1px solid rgba(120, 212, 3, 0.2); border-radius: 12px; }
    .bonus-label { display: flex; align-items: center; cursor: pointer; }
    .bonus-checkbox { appearance: none; -webkit-appearance: none; height: 1.5rem; width: 1.5rem; min-width: 1.5rem; background-color: rgba(255, 255, 255, 0.1); border: 1px solid var(--input-border); border-radius: 8px; cursor: pointer; display: flex; align-items: center; justify-content: center; transition: all 0.2s ease; }
    .bonus-checkbox:checked { background-color: var(--brand-color); border-color: var(--brand-hover); }
    .bonus-checkbox:checked::before { content: '✔'; font-size: 1rem; color: #1a1a1a; }
    .bonus-text { margin-left: 0.75rem; color: var(--text-light); font-weight: 500; }

    /* Responsive */
    @media (max-width: 480px) {
        .modal-card { padding: 2.5rem 1.5rem; }
        .form-helper-text {
            font-size: 0.75rem;
            white-space: normal;
        }
        #depositModal .modal-title {
            padding: 0 2rem;
            font-size: 1.5rem;
        }
    }
</style>

<script>
    let paymentPollingInterval;
    let countdownTimerInterval;

    function resetDepositModal() {
        const form = $('#depositForm');
        form.show();
        form[0].reset();
        
        $('#qrArea').removeClass('active').hide();
        $('#qrImg').hide();
        $('#showQrLink').text('Exibir QR Code').show();
        $('.timer-area, .qr-code-container, .copy-btn').show();
        $('#countdownProgressBar').css('width', '100%');
        const copyBtn = $('#copyQr');
        copyBtn.find('span').text('Copiar Código "copia e cola"');
        copyBtn.find('i').removeClass('bi-check-circle').addClass('bi-clipboard-check');
        $('.status-area').html('<div class="spinner"></div><span>Aguardando pagamento</span>').show().css('color', 'var(--brand-color)');
        
        $('#amountInput').val('').maskMoney('mask');
        $('#coupon-group').hide();
        $('#show-coupon-field').show();
        $('#backToDepositForm').hide();
        $('.modal-title').text('Depositar');
    }

    function openDepositModal() {
        $('#depositModal').addClass('active');
        $('#backdrop2').addClass('active');
        $('body').addClass('modal-open');
    }

    function closeDepositModal() {
        $('#depositModal').removeClass('active');
        $('#backdrop2').removeClass('active');
        $('body').removeClass('modal-open');
        
        clearInterval(paymentPollingInterval);
        clearInterval(countdownTimerInterval);
        
        setTimeout(resetDepositModal, 300);
    }

    function openWithdrawModal(balance) {
        $('#withdrawModal').addClass('active');
        $('#backdrop2').addClass('active');
        $('body').addClass('modal-open');
        $('#currentBalance').text(`R$ ${balance.toFixed(2).replace('.', ',')}`);
    }

    function closeWithdrawModal() {
        $('#withdrawModal').removeClass('active');
        $('#backdrop2').removeClass('active');
        $('body').removeClass('modal-open');
        
        setTimeout(() => {
            $('#withdrawForm')[0]?.reset();
            $('#withdrawAmount').val('').maskMoney('mask');
        }, 300);
    }
    
    function startCountdown(duration, display, progressBar) {
        let timer = duration, minutes, seconds;
        const totalTime = duration;

        clearInterval(countdownTimerInterval);
        progressBar.css('width', '100%');

        countdownTimerInterval = setInterval(function () {
            minutes = parseInt(timer / 60, 10);
            seconds = parseInt(timer % 60, 10);

            minutes = minutes < 10 ? "0" + minutes : minutes;
            seconds = seconds < 10 ? "0" + seconds : seconds;

            display.text(minutes + ":" + seconds);

            const percentage = (timer / totalTime) * 100;
            progressBar.css('width', percentage + '%');

            if (--timer < 0) {
                clearInterval(countdownTimerInterval);
                clearInterval(paymentPollingInterval);
                $('.timer-area, .qr-code-container, .copy-btn, .show-qr-link, #qrImg').hide();
                $('.status-area').text('Código PIX expirado').css('color', 'var(--danger-color)').show();
            }
        }, 1000);
    }

    function handleCopy(button, input) {
        if(button.find('span').text() === 'Código copiado') return;
        
        input.select();
        navigator.clipboard.writeText(input.val()).then(() => {
            const originalText = 'Copiar Código "copia e cola"';
            const originalIcon = 'bi bi-clipboard-check';
            button.find('span').text('Código copiado');
            button.find('i').removeClass(originalIcon).addClass('bi bi-check-circle');

            setTimeout(() => {
                button.find('span').text(originalText);
                button.find('i').removeClass('bi-check-circle').addClass(originalIcon);
            }, 2000);
        });
    }

    document.addEventListener('DOMContentLoaded', function () {
        $('#closeDepositModal, #closeWithdrawModal, #backdrop2').on('click', () => { 
            closeDepositModal(); 
            closeWithdrawModal(); 
        });

        $('#amountInput, #withdrawAmount').maskMoney({
            prefix: 'R$ ', allowNegative: false, thousands: '.', decimal: ',', affixesStay: true
        });

        function formatCPF(input) {
            if (!input) return;
            input.addEventListener('input', e => {
                let v = e.target.value.replace(/\D/g, '').slice(0, 11);
                v = v.replace(/(\d{3})(\d)/, '$1.$2').replace(/(\d{3})(\d)/, '$1.$2').replace(/(\d{3})(\d{1,2})$/, '$1-$2');
                e.target.value = v;
            });
        }
        formatCPF(document.getElementById('cpfInput'));
        
        $('#show-coupon-field')?.on('click', function(e) {
            e.preventDefault();
            $(this).fadeOut(150, () => $('#coupon-group').slideDown(200, () => $('#couponInput')?.focus()));
        });
        
        $('#showQrLink')?.on('click', function(e) {
            e.preventDefault();
            const qrImg = $('#qrImg');
            qrImg.slideToggle(200);
            $(this).text(qrImg.is(':visible') ? 'Ocultar QR Code' : 'Exibir QR Code');
        });

        $('.quick-amount').on('click', function() {
            $('#amountInput').val($(this).data('value')).maskMoney('mask').trigger('keyup');
        });

        $('.quick-withdraw').on('click', function() {
            $('#withdrawAmount').val($(this).data('value')).maskMoney('mask');
        });

        const isBonusActive = <?= $is_bonus_active; ?>;
        const isUserEligible = <?= $is_user_eligible_for_bonus ? 'true' : 'false'; ?>;
        const minDepositForBonus = <?= $min_deposit_for_bonus; ?>;
        $('#amountInput').on('keyup input', function() {
            if (!isBonusActive || !isUserEligible) return;
            let value = parseFloat($(this).maskMoney('unmasked')[0] || 0);
            $('#bonus-option-wrapper').toggle(value >= minDepositForBonus);
        });

        $('#depositForm')?.on('submit', async function(e) {
            e.preventDefault();
            const form = this;
            const amountValue = $('#amountInput').val();
            const value = parseFloat(amountValue.replace('R$ ', '').replace(/\./g, '').replace(',', '.'));
            const depositoMin = <?= isset($depositoMin) ? $depositoMin : 20 ?>;

            if (isNaN(value) || value < depositoMin) {
                Notiflix.Notify.failure(`O valor mínimo para depósito é R$ ${depositoMin.toFixed(2).replace('.', ',')}`);
                return;
            }

            Notiflix.Loading.standard('Gerando pagamento...');
            const formData = new FormData(form);
            if ($('#bonus-option-wrapper').is(':visible')) {
                formData.append('quer_bonus', $('#quer_bonus').is(':checked') ? 'true' : 'false');
            }

            try {
                const res = await fetch('/api/payment.php', { method: 'POST', body: formData });
                const data = await res.json();
                Notiflix.Loading.remove();

                if (data.qrcode) {
                    $(form).fadeOut(200, function() {
                        $('.modal-title').text('Realizar Pagamento');
                        $('#backToDepositForm').show();
                        $('#depositValueDisplay').text(amountValue);
                        $('#qrImg').attr('src', `https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=${encodeURIComponent(data.qrcode)}`);
                        $('#qrCodeValue').val(data.qrcode);
                        $('#qrArea').addClass('active').fadeIn(200);
                        
                        const fiveMinutes = 60 * 5;
                        startCountdown(fiveMinutes, $('#countdownTimer'), $('#countdownProgressBar'));
                        
                        paymentPollingInterval = setInterval(async () => {
                            try {
                                const resConsult = await fetch('/api/consult_pix.php', {
                                    method: 'POST', body: new URLSearchParams({ qrcode: data.qrcode })
                                });
                                const consultData = await resConsult.json();
                                if (consultData.paid === true) {
                                    clearInterval(paymentPollingInterval);
                                    clearInterval(countdownTimerInterval);
                                    Notiflix.Notify.success('Pagamento aprovado!');
                                    setTimeout(() => window.location.href = '/', 2000);
                                }
                            } catch (err) { clearInterval(paymentPollingInterval); }
                        }, 3000);
                    });
                } else {
                    Notiflix.Notify.failure(data.message || 'Erro ao gerar QR Code.');
                }
            } catch (err) {
                Notiflix.Loading.remove();
                Notiflix.Notify.failure('Erro na requisição. Verifique sua conexão.');
            }
        });

        $('#backToDepositForm').on('click', function(e) {
            e.preventDefault();
            clearInterval(paymentPollingInterval);
            clearInterval(countdownTimerInterval);
            $('#qrArea').fadeOut(200, () => {
                resetDepositModal();
                $('#depositForm').fadeIn(200);
            });
        });
        
        $('#copyQr, .qr-code-container').on('click', () => {
            handleCopy($('#copyQr'), $('#qrCodeValue'));
        });

        $('#withdrawForm')?.on('submit', async function(e) {
            e.preventDefault();
            const amount = parseFloat($('#withdrawAmount').val().replace('R$ ', '').replace(/\./g, '').replace(',', '.'));
            const saqueMin = <?= isset($saqueMin) ? $saqueMin : 50 ?>;

            if (isNaN(amount) || amount < saqueMin) {
                Notiflix.Notify.failure(`O valor mínimo para saque é R$ ${saqueMin.toFixed(2).replace('.', ',')}`);
                return;
            }

            Notiflix.Loading.standard('Processando saque...');
            try {
                const res = await fetch('/api/withdraw.php', {
                    method: 'POST', body: JSON.stringify({ amount }), headers: { 'Content-Type': 'application/json' }
                });
                const data = await res.json();
                Notiflix.Loading.remove();

                if (data.success) {
                    Notiflix.Notify.success(data.message);
                    setTimeout(() => {
                        closeWithdrawModal();
                        window.location.reload();
                    }, 2000);
                } else {
                    Notiflix.Notify.failure(data.message || 'Erro ao processar saque');
                }
            } catch (err) {
                Notiflix.Loading.remove();
                Notiflix.Notify.failure('Erro na conexão com o servidor');
            }
        });
    });
</script>