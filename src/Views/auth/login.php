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

        <form action="<?= url('/login') ?>" method="post" novalidate>
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

    <p class="login-footer">
        &copy; <?= date('Y') ?> Caetano Automotive Portugal
    </p>
</div>

</body>
</html>
