<?php
$pageTitle = 'Utilizadores';
require_once __DIR__ . '/../../layout/header.php';
?>

<div class="page-header">
    <div class="page-header-left">
        <h1 class="page-title">Utilizadores</h1>
        <span class="total-count"><?= e($total_count) ?> utilizador<?= $total_count === 1 ? '' : 'es' ?></span>
    </div>
    <div class="page-header-right">
        <a href="<?= url('/admin/utilizadores/criar') ?>" class="btn btn-primary">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                <line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>
            </svg>
            Novo utilizador
        </a>
    </div>
</div>

<?php if (!empty($flash_ok)): ?>
<div class="alert alert-success" role="alert"><?= e($flash_ok) ?></div>
<?php endif; ?>
<?php if (!empty($flash_error)): ?>
<div class="alert alert-error" role="alert"><?= e($flash_error) ?></div>
<?php endif; ?>

<div class="card" style="margin-bottom: 1rem;">
    <form class="toolbar" method="get" action="<?= url('/admin/utilizadores') ?>" style="padding: 1rem;">
        <input type="search" name="pesquisa" id="userSearch" class="form-input" style="max-width: 280px;"
               placeholder="Pesquisar por nome ou email..." value="<?= e($search) ?>">
        <select name="funcao" id="roleFilter" class="form-select form-select--sm" style="max-width: 200px;">
            <option value="">Todas as funções</option>
            <option value="admin"  <?= $role === 'admin'  ? 'selected' : '' ?>>Administrador</option>
            <option value="editor" <?= $role === 'editor' ? 'selected' : '' ?>>Editor</option>
            <option value="viewer" <?= $role === 'viewer' ? 'selected' : '' ?>>Visualizador</option>
        </select>
        <?php if ($search !== '' || $role !== ''): ?>
        <a href="<?= url('/admin/utilizadores') ?>" class="btn btn-secondary btn-sm">Limpar</a>
        <?php endif; ?>
    </form>
</div>

<div class="card">
    <div class="table-wrap">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Nome</th>
                    <th>Email</th>
                    <th>Função</th>
                    <th>Estado</th>
                    <th>Criado em</th>
                    <th class="table-actions-col">Acções</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($users)): ?>
                <tr>
                    <td colspan="6" class="table-empty">Nenhum utilizador encontrado.</td>
                </tr>
                <?php else: ?>
                <?php foreach ($users as $user): ?>
                <tr class="<?= !$user['active'] ? 'row-inactive' : '' ?>">
                    <td>
                        <div class="user-cell">
                            <?php if (!empty($user['photo_path'])): ?>
                            <img class="user-avatar user-avatar--sm user-avatar--photo" src="<?= e($user['photo_path']) ?>" alt="">
                            <?php else: ?>
                            <div class="user-avatar user-avatar--sm"><?= e(initials($user['name'])) ?></div>
                            <?php endif; ?>
                            <?= e($user['name']) ?>
                        </div>
                    </td>
                    <td><?= e($user['email']) ?></td>
                    <td>
                        <span class="badge badge-role badge-<?= e($user['role']) ?>">
                            <?= e(match($user['role']) {
                                'admin'  => 'Administrador',
                                'editor' => 'Editor',
                                default  => 'Visualizador',
                            }) ?>
                        </span>
                    </td>
                    <td>
                        <span class="status-dot <?= $user['active'] ? 'status-active' : 'status-inactive' ?>">
                            <?= $user['active'] ? 'Activo' : 'Inactivo' ?>
                        </span>
                        <?php if ($user['is_locked']): ?>
                        <span class="badge badge-admin badge-sm" data-locked-badge>Bloqueado</span>
                        <?php endif; ?>
                    </td>
                    <td class="table-date"><?= e(date('d/m/Y', strtotime($user['created_at']))) ?></td>
                    <td class="table-actions">
                        <a href="<?= url('/admin/utilizadores/' . $user['id'] . '/editar') ?>" class="btn btn-xs btn-secondary">
                            Editar
                        </a>
                        <?php if ($user['is_locked']): ?>
                        <button class="btn btn-xs btn-secondary" data-unlock-user="<?= e($user['id']) ?>">
                            Desbloquear
                        </button>
                        <?php endif; ?>
                        <?php if ((int)$user['id'] !== (int)$auth->user()['id']): ?>
                        <button class="btn btn-xs <?= $user['active'] ? 'btn-warning' : 'btn-success' ?>"
                                data-toggle-user="<?= e($user['id']) ?>"
                                data-active="<?= (int)$user['active'] ?>">
                            <?= $user['active'] ? 'Desactivar' : 'Activar' ?>
                        </button>
                        <?php if (!$user['active']): ?>
                        <button class="btn btn-xs btn-danger"
                                data-delete-user="<?= e($user['id']) ?>"
                                data-name="<?= e($user['name']) ?>">
                            Apagar
                        </button>
                        <?php endif; ?>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php
$pageUrl = fn(int $p) => url(
    '/admin/utilizadores?pagina=' . $p
    . '&pesquisa=' . urlencode($search)
    . '&funcao=' . urlencode($role)
);
?>
<?php if ($pagination['total_pages'] > 1): ?>
<nav class="pagination" aria-label="Paginação" style="margin-top: 1rem;">
    <?php if ($pagination['has_prev']): ?>
    <a href="<?= e($pageUrl($pagination['prev_page'])) ?>" class="pagination-btn">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
            <path d="m15 18-6-6 6-6"/>
        </svg>
        Anterior
    </a>
    <?php endif; ?>

    <div class="pagination-pages">
        <?php
        $current = $pagination['current_page'];
        $totalPages = $pagination['total_pages'];
        $range   = 2;
        for ($i = 1; $i <= $totalPages; $i++):
            if ($i === 1 || $i === $totalPages || abs($i - $current) <= $range):
        ?>
        <a href="<?= e($pageUrl($i)) ?>" class="pagination-page <?= $i === $current ? 'active' : '' ?>"><?= $i ?></a>
        <?php
            elseif (abs($i - $current) === $range + 1):
        ?>
        <span class="pagination-ellipsis">…</span>
        <?php
            endif;
        endfor;
        ?>
    </div>

    <?php if ($pagination['has_next']): ?>
    <a href="<?= e($pageUrl($pagination['next_page'])) ?>" class="pagination-btn">
        Seguinte
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
            <path d="m9 18 6-6-6-6"/>
        </svg>
    </a>
    <?php endif; ?>
</nav>
<?php endif; ?>

<script>
document.querySelectorAll('[data-toggle-user]').forEach(btn => {
    btn.addEventListener('click', async function () {
        const userId   = this.dataset.toggleUser;
        const isActive = this.dataset.active === '1';
        const action   = isActive ? 'desactivar' : 'activar';
        const row      = this.closest('tr');

        const ok = await window.confirm2(`Tem a certeza que deseja ${action} este utilizador?`);
        if (!ok) return;

        this.disabled = true;
        try {
            const res  = await fetch(`/admin/utilizadores/${userId}/activar`, {
                method : 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body   : `csrf_token=${encodeURIComponent(window.APP?.csrfToken ?? '')}`,
            });
            const data = await res.json();
            if (!data.success) {
                this.disabled = false;
                window.toast?.error(data.error || 'Operação falhada.');
                return;
            }
            const nowActive = data.active;

            // Update row style
            row.classList.toggle('row-inactive', !nowActive);

            // Update status dot
            const dot = row.querySelector('.status-dot');
            if (dot) {
                dot.className = `status-dot ${nowActive ? 'status-active' : 'status-inactive'}`;
                dot.textContent = nowActive ? 'Activo' : 'Inactivo';
            }

            // Update button
            this.className   = `btn btn-xs ${nowActive ? 'btn-warning' : 'btn-success'}`;
            this.textContent = nowActive ? 'Desactivar' : 'Activar';
            this.dataset.active = nowActive ? '1' : '0';
            this.disabled = false;

            // Show/hide the "Apagar" button depending on the new status
            let deleteBtn = row.querySelector('[data-delete-user]');
            if (!nowActive && !deleteBtn) {
                deleteBtn = document.createElement('button');
                deleteBtn.className = 'btn btn-xs btn-danger';
                deleteBtn.dataset.deleteUser = userId;
                deleteBtn.dataset.name = row.querySelector('.user-cell')?.textContent.trim() ?? '';
                deleteBtn.textContent = 'Apagar';
                bindDeleteUserButton(deleteBtn);
                this.after(deleteBtn);
            } else if (nowActive && deleteBtn) {
                deleteBtn.remove();
            }

            window.toast?.success(`Utilizador ${nowActive ? 'activado' : 'desactivado'}.`);
        } catch (e) {
            this.disabled = false;
            window.toast?.error('Erro de comunicação.');
        }
    });
});

function bindDeleteUserButton(btn) {
    btn.addEventListener('click', async function () {
        const userId = this.dataset.deleteUser;
        const name   = this.dataset.name;
        const row    = this.closest('tr');

        const ok = await window.confirm2(
            `Apagar definitivamente o utilizador "${name}"? Esta acção não pode ser revertida.`,
            'Apagar utilizador'
        );
        if (!ok) return;

        this.disabled = true;
        try {
            const res  = await fetch(`/admin/utilizadores/${userId}/eliminar`, {
                method : 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body   : `csrf_token=${encodeURIComponent(window.APP?.csrfToken ?? '')}`,
            });
            const data = await res.json();
            if (!data.success) {
                this.disabled = false;
                window.toast?.error(data.error || 'Não foi possível apagar o utilizador.');
                return;
            }
            row.style.transition = 'opacity 0.25s';
            row.style.opacity    = '0';
            setTimeout(() => {
                row.remove();
                const counter = document.querySelector('.total-count');
                if (counter) {
                    const n = Math.max(0, (parseInt(counter.textContent) || 1) - 1);
                    counter.textContent = `${n} utilizadores`;
                }
            }, 260);
            window.toast?.success(`Utilizador "${name}" apagado.`);
        } catch (e) {
            this.disabled = false;
            window.toast?.error('Erro de comunicação.');
        }
    });
}

document.querySelectorAll('[data-delete-user]').forEach(bindDeleteUserButton);

document.querySelectorAll('[data-unlock-user]').forEach(btn => {
    btn.addEventListener('click', async function () {
        const userId = this.dataset.unlockUser;
        const row    = this.closest('tr');

        this.disabled = true;
        try {
            const res  = await fetch(`/admin/utilizadores/${userId}/desbloquear`, {
                method : 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body   : `csrf_token=${encodeURIComponent(window.APP?.csrfToken ?? '')}`,
            });
            const data = await res.json();
            if (!data.success) {
                this.disabled = false;
                window.toast?.error(data.error || 'Não foi possível desbloquear o utilizador.');
                return;
            }
            row.querySelector('[data-locked-badge]')?.remove();
            this.remove();
            window.toast?.success('Utilizador desbloqueado.');
        } catch (e) {
            this.disabled = false;
            window.toast?.error('Erro de comunicação.');
        }
    });
});

document.getElementById('roleFilter')?.addEventListener('change', function () {
    this.form.submit();
});
</script>

<?php require_once __DIR__ . '/../../layout/footer.php'; ?>
