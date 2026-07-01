<?php
$pageTitle = 'Lixeira';
require_once __DIR__ . '/../../layout/header.php';
?>

<div class="page-header">
    <div class="page-header-left">
        <h1 class="page-title">Lixeira</h1>
        <span class="total-count"><?= e(count($images)) ?> imagem(ns) eliminada(s)</span>
    </div>
    <div class="page-header-right">
        <a href="<?= url('/') ?>" class="btn btn-secondary">Voltar ao repositório</a>
    </div>
</div>

<?php if (!empty($flash_ok)): ?>
<div class="alert alert-success" role="alert"><?= e($flash_ok) ?></div>
<?php endif; ?>
<?php if (!empty($flash_error)): ?>
<div class="alert alert-error" role="alert"><?= e($flash_error) ?></div>
<?php endif; ?>

<div class="card">
    <div class="table-wrap">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Pré-visualização</th>
                    <th>Ficheiro</th>
                    <th>Marca / Localização</th>
                    <th>Eliminada em</th>
                    <th class="table-actions-col">Acções</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($images)): ?>
                <tr><td colspan="5" class="table-empty">A lixeira está vazia.</td></tr>
                <?php else: ?>
                <?php foreach ($images as $image): ?>
                <tr data-image-row="<?= e($image['id']) ?>">
                    <td>
                        <img src="<?= e($image['thumb_url']) ?>" alt=""
                             style="width:56px;height:56px;object-fit:cover;border-radius:6px;">
                    </td>
                    <td><?= e($image['original_filename']) ?></td>
                    <td>
                        <span class="badge badge-brand"><?= e($image['brand_name']) ?></span>
                        <span class="badge badge-location"><?= e($image['location_name']) ?></span>
                    </td>
                    <td class="table-date"><?= e(date('d/m/Y H:i', strtotime($image['deleted_at']))) ?></td>
                    <td class="table-actions">
                        <button class="btn btn-xs btn-secondary" data-restore-image="<?= e($image['id']) ?>">
                            Restaurar
                        </button>
                        <?php if ($can_hard_delete): ?>
                        <button class="btn btn-xs btn-danger" data-hard-delete-image="<?= e($image['id']) ?>"
                                data-name="<?= e($image['original_filename']) ?>">
                            Eliminar definitivamente
                        </button>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
function removeTrashRow(id) {
    const row = document.querySelector(`[data-image-row="${id}"]`);
    if (!row) return;
    row.style.transition = 'opacity 0.25s';
    row.style.opacity    = '0';
    setTimeout(() => {
        row.remove();
        const tbody = document.querySelector('.admin-table tbody');
        if (tbody && !tbody.querySelector('tr:not([style*="opacity"])')) {
            tbody.innerHTML = '<tr><td colspan="5" class="table-empty">A lixeira está vazia.</td></tr>';
        }
        const counter = document.querySelector('.total-count');
        if (counter) {
            const n = Math.max(0, (parseInt(counter.textContent) || 1) - 1);
            counter.textContent = `${n} imagem(ns) eliminada(s)`;
        }
    }, 260);
}

document.querySelectorAll('[data-restore-image]').forEach(btn => {
    btn.addEventListener('click', async function () {
        const id = this.dataset.restoreImage;
        this.disabled = true;
        try {
            const res  = await fetch(`/admin/imagens/${id}/restaurar`, {
                method : 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body   : `csrf_token=${encodeURIComponent(window.APP?.csrfToken ?? '')}`,
            });
            const data = await res.json();
            if (data.success) {
                removeTrashRow(id);
                window.toast?.success('Imagem restaurada.');
            } else {
                this.disabled = false;
                window.toast?.error(data.error || 'Não foi possível restaurar a imagem.');
            }
        } catch (e) {
            this.disabled = false;
            window.toast?.error('Erro de comunicação.');
        }
    });
});

document.querySelectorAll('[data-hard-delete-image]').forEach(btn => {
    btn.addEventListener('click', async function () {
        const id   = this.dataset.hardDeleteImage;
        const name = this.dataset.name;

        const ok = await window.confirm2(
            `Eliminar definitivamente "${name}"? Esta acção não pode ser revertida.`,
            'Eliminar definitivamente'
        );
        if (!ok) return;

        this.disabled = true;
        try {
            const res  = await fetch(`/admin/imagens/${id}/eliminar`, {
                method : 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body   : `csrf_token=${encodeURIComponent(window.APP?.csrfToken ?? '')}`,
            });
            const data = await res.json();
            if (data.success) {
                removeTrashRow(id);
                window.toast?.success('Imagem eliminada definitivamente.');
            } else {
                this.disabled = false;
                window.toast?.error(data.error || 'Não foi possível eliminar a imagem.');
            }
        } catch (e) {
            this.disabled = false;
            window.toast?.error('Erro de comunicação.');
        }
    });
});
</script>

<?php require_once __DIR__ . '/../../layout/footer.php'; ?>
