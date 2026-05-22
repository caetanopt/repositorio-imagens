<?php require_once __DIR__ . '/../../layout/header.php'; ?>

<div class="brand-header">
    <a href="<?= url('/admin/brands/' . $brand['id'] . '/locations') ?>" class="brand-header-back">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
            <path d="m15 18-6-6 6-6"/>
        </svg>
        <?= e($brand['name']) ?> — Localizações
    </a>
    <div class="brand-header-body">
        <div class="brand-header-identity">
            <div class="brand-header-monogram"><?= e(mb_substr($brand['name'], 0, 1)) ?></div>
            <div>
                <h1 class="brand-header-name">Nova Localização</h1>
                <p class="brand-header-meta"><?= e($brand['name']) ?></p>
            </div>
        </div>
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

        <div class="form-actions">
            <a href="<?= url('/admin/brands/' . $brand['id'] . '/locations') ?>" class="btn btn-secondary">Cancelar</a>
            <button type="submit" class="btn btn-primary">Criar localização</button>
        </div>
    </form>
</div>

<?php require_once __DIR__ . '/../../layout/footer.php'; ?>
