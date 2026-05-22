<?php require_once __DIR__ . '/../layout/header.php'; ?>

<?php
$slotNames = [
    1 => 'Foto da Fachada',
    2 => 'Foto do Showroom',
    3 => 'Foto de Oficina — Exterior',
    4 => 'Foto de Oficina — Interior',
];
?>

<div class="brand-header">
    <a href="<?= url('/brand/' . $brand['id']) ?>" class="brand-header-back">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
            <path d="m15 18-6-6 6-6"/>
        </svg>
        <?= e($brand['name']) ?>
    </a>
    <div class="brand-header-body">
        <div class="brand-header-identity">
            <div class="brand-header-monogram"><?= e(mb_substr($brand['name'], 0, 1)) ?></div>
            <div>
                <h1 class="brand-header-name"><?= e($location['name']) ?></h1>
                <p class="brand-header-meta"><?= e(count($images)) ?> / <?= e($max_photos) ?> fotos</p>
            </div>
        </div>
    </div>
</div>

<?php if (!empty($flash_ok)): ?>
<div class="alert alert-success" role="alert"><?= e($flash_ok) ?></div>
<?php endif; ?>
<?php if (!empty($flash_error)): ?>
<div class="alert alert-error" role="alert"><?= e($flash_error) ?></div>
<?php endif; ?>

<div class="brand-layout">
<?php $currentLocationId = $location['id']; require_once __DIR__ . '/../layout/_brand_sidebar.php'; ?>

<div class="brand-content">
<div class="photo-slots" id="photoSlots">
    <?php
    $uploadUrl = url('/brand/' . $brand['id'] . '/location/' . $location['id'] . '/upload');
    $canUpload = $auth->can('upload') && count($images) < $max_photos;
    $canDelete = $auth->can('delete_any') || $auth->can('delete_own');
    ?>

    <?php foreach ($images as $i => $img): ?>
    <div class="photo-slot photo-slot--filled" data-image-id="<?= e($img['id']) ?>">
        <div class="photo-slot-inner">
            <div class="photo-slot-thumb">
                <img src="<?= e($img['thumb_url']) ?>"
                     alt="<?= e($img['original_filename']) ?>"
                     class="photo-slot-img" loading="lazy">
                <div class="photo-slot-overlay">
                    <a href="<?= e($img['download_url']) ?>" class="overlay-btn" title="Transferir" download>
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                            <polyline points="7 10 12 15 17 10"/>
                            <line x1="12" y1="15" x2="12" y2="3"/>
                            <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
                        </svg>
                    </a>
                    <?php if ($canDelete): ?>
                    <button class="overlay-btn overlay-btn--danger" title="Eliminar"
                            data-delete-image="<?= e($img['id']) ?>"
                            data-filename="<?= e($img['original_filename']) ?>">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                            <polyline points="3 6 5 6 21 6"/>
                            <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"/>
                        </svg>
                    </button>
                    <?php endif; ?>
                </div>
                <span class="photo-slot-number"><?= $i + 1 ?></span>
            </div>
            <div class="photo-slot-meta">
                <span class="photo-slot-label"><?= e($slotNames[$i + 1] ?? 'Slot ' . ($i + 1)) ?></span>
                <span class="photo-slot-size"><?= e($img['filesize_human']) ?></span>
            </div>
        </div>
    </div>
    <?php endforeach; ?>

    <?php for ($i = count($images); $i < $max_photos; $i++): ?>
    <?php if ($canUpload): ?>
    <div class="photo-slot photo-slot--empty" data-slot="<?= $i + 1 ?>" id="slot-<?= $i ?>">
        <input type="file" id="fileInput-<?= $i ?>" accept="image/jpeg,image/png,image/webp,image/gif" hidden>
        <div class="photo-slot-uploading" id="uploading-<?= $i ?>">
            <div class="spinner" style="border-top-color: var(--accent)"></div>
            <span style="font-size:.8rem;color:var(--text-muted)">A carregar...</span>
        </div>
        <div class="photo-slot-upload-content" id="uploadContent-<?= $i ?>">
            <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
                <polyline points="17 8 12 3 7 8"/>
                <line x1="12" y1="3" x2="12" y2="15"/>
            </svg>
            <p class="photo-slot-upload-text"><?= e($slotNames[$i + 1] ?? 'Slot ' . ($i + 1)) ?></p>
            <p class="photo-slot-upload-hint">Clique ou arraste</p>
        </div>
    </div>
    <?php else: ?>
    <div class="photo-slot photo-slot--empty photo-slot--readonly">
        <div class="photo-slot-upload-content">
            <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                <rect x="3" y="3" width="18" height="18" rx="2"/>
                <circle cx="8.5" cy="8.5" r="1.5"/>
                <polyline points="21 15 16 10 5 21"/>
            </svg>
            <p class="photo-slot-upload-text"><?= e($slotNames[$i + 1] ?? 'Slot ' . ($i + 1)) ?></p>
            <p class="photo-slot-upload-hint">Vazio</p>
        </div>
    </div>
    <?php endif; ?>
    <?php endfor; ?>
</div>

<script>
(function () {
    const uploadUrl = '<?= e($uploadUrl) ?>';
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content ?? '';

    function showToast(msg, type) {
        const tc = document.getElementById('toastContainer');
        if (!tc) { alert(msg); return; }
        const t = document.createElement('div');
        t.className = 'toast toast--' + type;
        t.innerHTML = '<span class="toast-message">' + msg + '</span>';
        tc.appendChild(t);
        requestAnimationFrame(() => t.classList.add('toast--visible'));
        setTimeout(() => { t.classList.remove('toast--visible'); setTimeout(() => t.remove(), 300); }, 3500);
    }

    async function uploadFile(file, slotIndex) {
        const uploading = document.getElementById('uploading-' + slotIndex);
        const content   = document.getElementById('uploadContent-' + slotIndex);
        if (uploading) { uploading.style.display = 'flex'; }
        if (content)   { content.style.display   = 'none'; }

        const fd = new FormData();
        fd.append('image', file);
        fd.append('csrf_token', csrfToken);

        try {
            const res  = await fetch(uploadUrl, { method: 'POST', body: fd });
            const data = await res.json();

            if (data.success) {
                showToast('Foto carregada com sucesso.', 'success');
                setTimeout(() => location.reload(), 800);
            } else {
                showToast(data.error || 'Erro no upload.', 'error');
                if (uploading) uploading.style.display = 'none';
                if (content)   content.style.display   = '';
            }
        } catch (e) {
            showToast('Erro de comunicação.', 'error');
            if (uploading) uploading.style.display = 'none';
            if (content)   content.style.display   = '';
        }
    }

    document.querySelectorAll('.photo-slot--empty:not(.photo-slot--readonly)').forEach((slot) => {
        const idx   = slot.dataset.slot - 1;
        const input = document.getElementById('fileInput-' + idx);

        slot.addEventListener('click', (e) => {
            if (!e.target.closest('input')) input?.click();
        });

        input?.addEventListener('change', (e) => {
            const file = e.target.files[0];
            if (file) uploadFile(file, idx);
        });

        slot.addEventListener('dragover', (e) => { e.preventDefault(); slot.classList.add('drag-over'); });
        slot.addEventListener('dragleave', () => slot.classList.remove('drag-over'));
        slot.addEventListener('drop', (e) => {
            e.preventDefault();
            slot.classList.remove('drag-over');
            const file = e.dataTransfer.files[0];
            if (file) uploadFile(file, idx);
        });
    });

    document.querySelectorAll('[data-delete-image]').forEach(btn => {
        btn.addEventListener('click', async function (e) {
            e.preventDefault();
            const id       = this.dataset.deleteImage;
            const filename = this.dataset.filename;
            if (!confirm('Eliminar "' + filename + '"? Esta acção pode ser revertida pela administração.')) return;

            try {
                const res  = await fetch('/image/' + id + '/delete', {
                    method : 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body   : 'csrf_token=' + encodeURIComponent(csrfToken),
                });
                const data = await res.json();
                if (data.success) {
                    showToast('Foto eliminada.', 'success');
                    setTimeout(() => location.reload(), 600);
                } else {
                    showToast(data.error || 'Erro ao eliminar.', 'error');
                }
            } catch (e) {
                showToast('Erro de comunicação.', 'error');
            }
        });
    });
})();
</script>
</div><!-- /.brand-content -->
</div><!-- /.brand-layout -->

<?php require_once __DIR__ . '/../layout/footer.php'; ?>
