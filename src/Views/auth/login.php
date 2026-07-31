<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar sessão — Repositório Digital</title>
    <link rel="icon" type="image/svg+xml" href="https://assets.caetano.pt/img/favicon.svg">
    <link rel="stylesheet" href="<?= e(url('assets/css/app.css')) ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap">
</head>
<body class="login-page">

<div class="login-layout">
    <div class="login-brand">
        <img src="<?= e(url('assets/img/caetano-logo-white.svg')) ?>" alt="Caetano" class="login-brand-logo">
        <h1>Repositório Digital</h1>
        <p>Caetano Automotive Portugal</p>
    </div>

    <div class="login-card">
        <div id="loginFormPanel">
            <h2 class="login-title">Iniciar sessão</h2>
            <p class="login-subtitle">Introduza o seu email e enviamos-lhe um link de acesso.</p>

            <?php if (!empty($error)): ?>
            <div class="alert alert-error" role="alert">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>
                </svg>
                <?= e($error) ?>
            </div>
            <?php endif; ?>

            <?php if (!empty($success)): ?>
            <div class="alert alert-success" role="status">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M20 6 9 17l-5-5"/>
                </svg>
                <?= e($success) ?>
            </div>
            <?php endif; ?>

            <form id="loginForm" action="<?= url('/login') ?>" method="post" novalidate>
                <input type="hidden" name="csrf_token" value="<?= e($csrf_token) ?>">

                <div class="form-group">
                    <label class="form-label" for="email">Email</label>
                    <input
                        type="email"
                        id="email"
                        name="email"
                        class="form-input"
                        value="<?= e(old('email')) ?>"
                        required
                        autocomplete="email"
                        autofocus
                        placeholder="utilizador@caetano.pt"
                    >
                </div>

                <div class="form-group form-group--inline">
                    <label class="checkbox-label">
                        <input type="checkbox" name="remember_me" value="1" class="checkbox">
                        <span class="checkbox-custom"></span>
                        Manter sessão activa
                    </label>
                </div>

                <button type="submit" class="btn btn-primary btn-block btn-lg">
                    Enviar link de acesso
                </button>
            </form>
        </div>

        <div id="linkSentPanel" hidden>
            <div class="login-sent-icon">
                <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M4 4h16v16H4z" opacity="0"/>
                    <path d="M22 6 12 13 2 6"/>
                    <path d="M2 6h20v12H2z"/>
                </svg>
            </div>
            <h2 class="login-sent-title">Verifique o seu email</h2>
            <p class="login-sent-text">
                Caso seja um utilizador autorizado, enviámos-lhe um link de acesso para
                <strong id="linkSentEmail"></strong>. O link é válido durante 15 minutos.
            </p>
            <a href="#" id="linkSentRetry" class="login-sent-retry">Usar outro email</a>
        </div>
    </div>

    <p class="login-footer">
        &copy; <?= date('Y') ?> Caetano Automotive Portugal S.A.
    </p>
</div>

<script>
(function () {
    const formPanel  = document.getElementById('loginFormPanel');
    const sentPanel  = document.getElementById('linkSentPanel');
    const form       = document.getElementById('loginForm');
    const emailInput = document.getElementById('email');
    const sentEmail  = document.getElementById('linkSentEmail');
    const retryLink  = document.getElementById('linkSentRetry');

    form.addEventListener('submit', function (event) {
        event.preventDefault();

        const submitBtn = form.querySelector('button[type="submit"]');
        submitBtn.disabled = true;

        fetch(form.action, {
            method: 'POST',
            body: new FormData(form),
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        })
            .then(res => res.json().catch(() => ({ success: false, error: 'Erro de comunicação.' })))
            .then(data => {
                submitBtn.disabled = false;
                if (data.success) {
                    sentEmail.textContent = data.email || emailInput.value;
                    formPanel.hidden = true;
                    sentPanel.hidden = false;
                } else {
                    form.submit();
                }
            })
            .catch(() => {
                submitBtn.disabled = false;
                form.submit();
            });
    });

    retryLink.addEventListener('click', function (event) {
        event.preventDefault();
        sentPanel.hidden = true;
        formPanel.hidden = false;
        emailInput.focus();
    });
})();
</script>
</body>
</html>
