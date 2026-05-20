<?php require_once __DIR__ . '/../../layout/header.php'; ?>

<div class="page-header">
    <div class="page-header-left">
        <a href="<?= url('/admin/brands/' . $brand['id'] . '/locations') ?>" class="back-link">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                <path d="m15 18-6-6 6-6"/>
            </svg>
            Localizações — <?= e($brand['name']) ?>
        </a>
        <h1 class="page-title">Nova Localização</h1>
    </div>
</div>

<?php if (!empty($flash_error)): ?>
<div class="alert alert-error"><?= e($flash_error) ?></div>
<?php endif; ?>

<div class="card card--form">
    <form method="post" action="<?= e(url($action)) ?>">
        <?= csrf_field() ?>

        <div class="form-group">
            <label class="form-label" for="name">
                Nome da localização <span class="required">*</span>
            </label>
            <input type="text" id="name" name="name" class="form-input"
                   value="<?= e(old('name')) ?>" placeholder="Ex: Lisboa Norte"
                   required autofocus>
            <p class="form-hint-text">O slug é gerado automaticamente a partir do nome.</p>
        </div>

        <div class="form-group">
            <label class="form-label">Marca</label>
            <input type="text" class="form-input form-input--readonly"
                   value="<?= e($brand['name']) ?>" readonly>
        </div>

        <div class="form-actions">
            <a href="<?= url('/admin/brands/' . $brand['id'] . '/locations') ?>" class="btn btn-secondary">Cancelar</a>
            <button type="submit" class="btn btn-primary">Criar localização</button>
        </div>
    </form>
</div>

<?php require_once __DIR__ . '/../../layout/footer.php'; ?>
