<?php require_once __DIR__ . '/../layout/header.php'; ?>

<div class="page-header">
    <div class="page-header-left">
        <h1 class="page-title">Repositório de Imagens</h1>
        <span class="total-count"><?= e(count($brands)) ?> marcas</span>
    </div>
</div>

<?php if (empty($brands)): ?>
<div class="empty-state">
    <svg class="empty-state-svg" viewBox="0 0 160 120" fill="none" xmlns="http://www.w3.org/2000/svg">
        <rect x="20" y="30" width="120" height="80" rx="8" fill="#e2e8f0"/>
        <rect x="35" y="45" width="40" height="50" rx="4" fill="#cbd5e1"/>
        <rect x="85" y="45" width="40" height="50" rx="4" fill="#cbd5e1"/>
    </svg>
    <h2 class="empty-state-title">Nenhuma marca disponível</h2>
    <p class="empty-state-text">As marcas são criadas pela administração.</p>
    <?php if ($auth->can('manage_brands')): ?>
    <a href="<?= url('/admin/brands') ?>" class="btn btn-primary">Gerir marcas</a>
    <?php endif; ?>
</div>
<?php else: ?>
<div class="brands-grid">
    <?php foreach ($brands as $brand): ?>
    <a href="<?= url('/brand/' . $brand['id']) ?>" class="brand-card">
        <div class="brand-card-icon">
            <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                <rect x="2" y="3" width="20" height="14" rx="2"/>
                <path d="M8 21h8M12 17v4"/>
            </svg>
        </div>
        <div class="brand-card-name"><?= e($brand['name']) ?></div>
        <div class="brand-card-meta"><?= e($brand['image_count']) ?> <?= $brand['image_count'] === 1 ? 'foto' : 'fotos' ?></div>
    </a>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<?php require_once __DIR__ . '/../layout/footer.php'; ?>
