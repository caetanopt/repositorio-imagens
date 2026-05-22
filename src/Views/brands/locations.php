<?php require_once __DIR__ . '/../layout/header.php'; ?>

<div class="page-header">
    <div class="page-header-left">
        <a href="<?= url('/') ?>" class="back-link">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                <path d="m15 18-6-6 6-6"/>
            </svg>
            Marcas
        </a>
        <h1 class="page-title"><?= e($brand['name']) ?></h1>
        <span class="total-count"><?= e(count($locations)) ?> localizações</span>
    </div>
    <?php if ($auth->can('manage_brands')): ?>
    <div class="page-header-right">
        <a href="<?= url('/admin/brands/' . $brand['id'] . '/locations') ?>" class="btn btn-secondary btn-sm">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                <path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"/>
            </svg>
            Gerir localizações
        </a>
    </div>
    <?php endif; ?>
</div>

<?php if (empty($locations)): ?>
<div class="empty-state">
    <svg class="empty-state-svg" viewBox="0 0 160 120" fill="none">
        <circle cx="80" cy="55" r="35" fill="#e2e8f0"/>
        <path d="M80 30 L80 80" stroke="#cbd5e1" stroke-width="3"/>
        <path d="M55 55 L105 55" stroke="#cbd5e1" stroke-width="3"/>
    </svg>
    <h2 class="empty-state-title">Nenhuma localização configurada</h2>
    <p class="empty-state-text">As localizações desta marca ainda não foram criadas.</p>
    <?php if ($auth->can('manage_brands')): ?>
    <a href="<?= url('/admin/brands/' . $brand['id'] . '/locations') ?>" class="btn btn-primary">
        Criar localizações
    </a>
    <?php endif; ?>
</div>
<?php else: ?>
<?php $brandLocations = $locations; ?>
<div class="brand-layout">
    <?php require_once __DIR__ . '/../layout/_brand_sidebar.php'; ?>

    <div class="brand-content">
        <div class="locations-grid">
            <?php foreach ($locations as $loc): ?>
            <a href="<?= url('/brand/' . $brand['id'] . '/location/' . $loc['id']) ?>" class="location-card">
                <div class="location-card-thumbnails">
                    <?php
                    $previews = $loc['preview_images'] ?? [];
                    for ($i = 0; $i < 3; $i++):
                        if (!empty($previews[$i])):
                    ?>
                    <img src="<?= e($previews[$i]['thumb_url']) ?>"
                         alt="<?= e($previews[$i]['original_filename'] ?? '') ?>"
                         class="location-card-thumb" loading="lazy">
                    <?php else: ?>
                    <div class="location-card-empty-thumb">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                            <rect x="3" y="3" width="18" height="18" rx="2"/>
                            <circle cx="8.5" cy="8.5" r="1.5"/>
                            <polyline points="21 15 16 10 5 21"/>
                        </svg>
                    </div>
                    <?php endif; endfor; ?>
                </div>
                <div class="location-card-info">
                    <div class="location-card-name"><?= e($loc['name']) ?></div>
                    <div class="location-card-count">
                        <span><?= e($loc['image_count']) ?> / 4</span>
                        <div class="location-count-bar">
                            <div class="location-count-fill <?= $loc['image_count'] >= 4 ? 'location-count-fill--full' : '' ?>"
                                 style="width:<?= e(($loc['image_count'] / 4) * 100) ?>%"></div>
                        </div>
                    </div>
                </div>
            </a>
            <?php endforeach; ?>
        </div>
    </div>
</div>
<?php endif; ?>

<?php require_once __DIR__ . '/../layout/footer.php'; ?>
