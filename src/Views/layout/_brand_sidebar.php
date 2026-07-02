<?php
/**
 * Brand sidebar partial — show inside a brand context.
 * Expected variables:
 *   $brand           — brand row
 *   $brandLocations  — locations array, each with image_count
 *   $currentLocationId (optional) — highlights the active location
 *   $max_photos      (optional) — photo slot limit for this brand
 */
$currentLocationId  = $currentLocationId ?? null;
$sidebarMaxPhotos   = $max_photos ?? \App\Controllers\LocationController::MAX_PHOTOS;
?>
<aside class="brand-sidebar">
    <div class="brand-sidebar-header">
        <a href="<?= url('/marcas/' . $brand['slug']) ?>" class="brand-sidebar-brand-link">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                <path d="m15 18-6-6 6-6"/>
            </svg>
            <?= e($brand['name']) ?>
        </a>
    </div>

    <?php if (empty($brandLocations)): ?>
    <div class="brand-sidebar-empty">Nenhuma localização</div>
    <?php else: ?>
    <nav class="brand-sidebar-nav">
        <?php foreach ($brandLocations as $loc): ?>
        <?php $isActive = $currentLocationId !== null && (int) $loc['id'] === (int) $currentLocationId; ?>
        <a href="<?= url('/marcas/' . $brand['slug'] . '/' . $loc['slug']) ?>"
           class="brand-sidebar-item <?= $isActive ? 'brand-sidebar-item--active' : '' ?>">
            <span class="brand-sidebar-item-name"><?= e($loc['name']) ?></span>
            <div class="brand-sidebar-item-count">
                <span class="brand-sidebar-item-num"><?= e($loc['image_count']) ?>/<?= e($sidebarMaxPhotos) ?></span>
                <div class="location-count-bar">
                    <div class="location-count-fill <?= $loc['image_count'] >= $sidebarMaxPhotos ? 'location-count-fill--full' : '' ?>"
                         style="width:<?= e(($loc['image_count'] / $sidebarMaxPhotos) * 100) ?>%"></div>
                </div>
            </div>
        </a>
        <?php endforeach; ?>
    </nav>
    <?php endif; ?>
</aside>
