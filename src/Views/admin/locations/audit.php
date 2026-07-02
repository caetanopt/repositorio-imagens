<?php
$pageTitle = 'Auditoria de localizações';
require_once __DIR__ . '/../../layout/header.php';
?>

<div class="page-header">
    <div class="page-header-left">
        <h1 class="page-title">Auditoria de localizações</h1>
        <span class="total-count">
            <?= e($missing_count) ?> de <?= e($total_count) ?> localizações com fotos em falta
        </span>
    </div>
    <div class="page-header-right">
        <?php if ($only_missing): ?>
        <a href="<?= url('/admin/localizacoes/auditoria?apenas_incompletas=0') ?>" class="btn btn-secondary">
            Mostrar todas
        </a>
        <?php else: ?>
        <a href="<?= url('/admin/localizacoes/auditoria?apenas_incompletas=1') ?>" class="btn btn-secondary">
            Mostrar só incompletas
        </a>
        <?php endif; ?>
    </div>
</div>

<div class="card" style="margin-bottom: 1rem;">
    <div class="toolbar" style="padding: 1rem;">
        <select id="brandFilter" class="form-select form-select--sm" style="max-width: 220px;">
            <option value="">Todas as marcas</option>
            <?php foreach ($brands as $brandName): ?>
            <option value="<?= e($brandName) ?>"><?= e($brandName) ?></option>
            <?php endforeach; ?>
        </select>
        <input type="search" id="locationSearch" class="form-input" style="max-width: 260px;"
               placeholder="Pesquisar localização...">
        <span id="visibleCount" class="total-count"></span>
    </div>
</div>

<div class="card">
    <div class="table-wrap">
        <table class="admin-table" id="auditTable">
            <thead>
                <tr>
                    <th>Marca</th>
                    <th>Localização</th>
                    <th>Fotos</th>
                    <th>Em falta</th>
                    <th class="table-actions-col">Acções</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($locations)): ?>
                <tr>
                    <td colspan="5" class="table-empty">
                        <?= $only_missing ? 'Todas as localizações têm o número completo de fotos.' : 'Nenhuma localização encontrada.' ?>
                    </td>
                </tr>
                <?php else: ?>
                <?php foreach ($locations as $loc): ?>
                <tr data-brand="<?= e($loc['brand_name']) ?>"
                    data-search="<?= e(mb_strtolower($loc['brand_name'] . ' ' . $loc['name'])) ?>">
                    <td><span class="badge badge-brand"><?= e($loc['brand_name']) ?></span></td>
                    <td><?= e($loc['name']) ?></td>
                    <td>
                        <span class="badge <?= $loc['photo_count'] >= $loc['max_photos'] ? 'badge-viewer' : 'badge-admin' ?>">
                            <?= e($loc['photo_count']) ?> / <?= e($loc['max_photos']) ?>
                        </span>
                    </td>
                    <td>
                        <?php if ($loc['missing'] > 0): ?>
                        <span class="badge badge-admin"><?= e($loc['missing']) ?> em falta</span>
                        <?php else: ?>
                        <span class="badge badge-viewer">Completa</span>
                        <?php endif; ?>
                    </td>
                    <td class="table-actions">
                        <a href="<?= url('/marcas/' . $loc['brand_slug'] . '/' . $loc['slug']) ?>"
                           class="btn btn-xs btn-secondary" target="_blank">
                            Ver fotos
                        </a>
                    </td>
                </tr>
                <?php endforeach; ?>
                <tr id="noMatchesRow" class="table-empty-row" hidden>
                    <td colspan="5" class="table-empty">Nenhuma localização corresponde aos filtros.</td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php if (!empty($locations)): ?>
<script>
(function () {
    const brandFilter  = document.getElementById('brandFilter');
    const searchInput  = document.getElementById('locationSearch');
    const rows          = Array.from(document.querySelectorAll('#auditTable tbody tr[data-brand]'));
    const noMatchesRow  = document.getElementById('noMatchesRow');
    const visibleCount  = document.getElementById('visibleCount');

    function applyFilters() {
        const brand = brandFilter.value;
        const term  = searchInput.value.trim().toLowerCase();
        let visible = 0;

        rows.forEach(row => {
            const matchesBrand  = !brand || row.dataset.brand === brand;
            const matchesSearch = !term || row.dataset.search.includes(term);
            const show = matchesBrand && matchesSearch;
            row.hidden = !show;
            if (show) visible++;
        });

        if (noMatchesRow) noMatchesRow.hidden = visible !== 0;
        if (visibleCount) visibleCount.textContent = `${visible} de ${rows.length} visíveis`;
    }

    brandFilter.addEventListener('change', applyFilters);
    searchInput.addEventListener('input', applyFilters);
    applyFilters();
})();
</script>
<?php endif; ?>

<?php require_once __DIR__ . '/../../layout/footer.php'; ?>
