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

<?php if ($can_hard_delete && $old_count > 0): ?>
<div class="alert alert-error" role="alert" style="display:flex; align-items:center; justify-content:space-between; gap:1rem;">
    <span><?= e($old_count) ?> imagem(ns) está(ão) na lixeira há mais de <?= e($retention_days) ?> dias.</span>
    <form method="post" action="<?= e(url('/admin/lixeira/purgar-antigas')) ?>" style="margin:0;">
        <?= csrf_field() ?>
        <button type="submit" class="btn btn-xs btn-danger"
                onclick="return confirm('Eliminar definitivamente todas as imagens com mais de <?= e($retention_days) ?> dias na lixeira? Esta acção não pode ser revertida.');">
            Eliminar imagens antigas agora
        </button>
    </form>
</div>
<?php endif; ?>

<?php if ($can_hard_delete): ?>
<div class="toolbar" id="bulkToolbar" style="display:none; margin-bottom:.75rem;">
    <span id="bulkCount">0 seleccionada(s)</span>
    <button type="button" class="btn btn-xs btn-danger" id="bulkDeleteBtn">Eliminar seleccionadas</button>
</div>
<?php endif; ?>

<div class="card">
    <div class="table-wrap">
        <table class="admin-table">
            <thead>
                <tr>
                    <?php if ($can_hard_delete): ?>
                    <th style="width:2rem;"><input type="checkbox" id="selectAll"></th>
                    <?php endif; ?>
                    <th>Pré-visualização</th>
                    <th>Ficheiro</th>
                    <th>Marca / Localização</th>
                    <th>Eliminada em</th>
                    <th class="table-actions-col">Acções</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($images)): ?>
                <tr><td colspan="6" class="table-empty">A lixeira está vazia.</td></tr>
                <?php else: ?>
                <?php foreach ($images as $image): ?>
                <tr data-image-row="<?= e($image['id']) ?>">
                    <?php if ($can_hard_delete): ?>
                    <td><input type="checkbox" class="image-row-select" value="<?= e($image['id']) ?>"></td>
                    <?php endif; ?>
                    <td>
                        <img src="<?= e($image['thumb_url']) ?>" alt=""
                             style="width:56px;height:56px;object-fit:cover;border-radius:6px;">
                    </td>
                    <td><?= e($image['original_filename']) ?></td>
                    <td>
                        <span class="badge badge-brand"><?= e($image['brand_name']) ?></span>
                        <span class="badge badge-location"><?= e($image['location_name']) ?></span>
                    </td>
                    <td class="table-date">
                        <?= e(date('d/m/Y H:i', strtotime($image['deleted_at']))) ?>
                        <?php if ($image['is_old']): ?>
                        <span class="badge badge-admin badge-sm" title="Mais de <?= e($retention_days) ?> dias na lixeira">antiga</span>
                        <?php endif; ?>
                    </td>
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
            const cols = document.getElementById('selectAll') ? 6 : 5;
            tbody.innerHTML = `<tr><td colspan="${cols}" class="table-empty">A lixeira está vazia.</td></tr>`;
        }
        const counter = document.querySelector('.total-count');
        if (counter) {
            const n = Math.max(0, (parseInt(counter.textContent) || 1) - 1);
            counter.textContent = `${n} imagem(ns) eliminada(s)`;
        }
        updateBulkToolbar();
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

// ─── Bulk selection & delete ────────────────────────────────────────────────

function updateBulkToolbar() {
    const toolbar = document.getElementById('bulkToolbar');
    if (!toolbar) return;
    const checked = document.querySelectorAll('.image-row-select:checked');
    const count   = document.getElementById('bulkCount');
    toolbar.style.display = checked.length > 0 ? 'flex' : 'none';
    if (count) count.textContent = `${checked.length} seleccionada(s)`;
}

document.addEventListener('change', function (e) {
    if (e.target.matches('.image-row-select')) {
        updateBulkToolbar();
    }
    if (e.target.id === 'selectAll') {
        document.querySelectorAll('.image-row-select').forEach(cb => { cb.checked = e.target.checked; });
        updateBulkToolbar();
    }
});

const bulkDeleteBtn = document.getElementById('bulkDeleteBtn');
if (bulkDeleteBtn) {
    bulkDeleteBtn.addEventListener('click', async function () {
        const ids = Array.from(document.querySelectorAll('.image-row-select:checked')).map(cb => cb.value);
        if (!ids.length) return;

        const ok = await window.confirm2(
            `Eliminar definitivamente ${ids.length} imagem(ns) seleccionada(s)? Esta acção não pode ser revertida.`,
            'Eliminar seleccionadas'
        );
        if (!ok) return;

        this.disabled = true;
        try {
            const body = new URLSearchParams();
            body.set('csrf_token', window.APP?.csrfToken ?? '');
            ids.forEach(id => body.append('ids[]', id));

            const res  = await fetch('/admin/imagens/eliminar-em-massa', {
                method : 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body   : body.toString(),
            });
            const data = await res.json();
            if (data.success) {
                ids.forEach(removeTrashRow);
                window.toast?.success(`${data.deleted} imagem(ns) eliminada(s) definitivamente.`);
            } else {
                window.toast?.error(data.error || 'Não foi possível eliminar as imagens.');
            }
        } catch (e) {
            window.toast?.error('Erro de comunicação.');
        } finally {
            this.disabled = false;
        }
    });
}
</script>

<?php require_once __DIR__ . '/../../layout/footer.php'; ?>
