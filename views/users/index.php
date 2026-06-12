<?php
use Core\Auth;
use Core\Helpers;

$baseUrl = Auth::baseUrl('users') . ($search !== '' ? '?search=' . urlencode($search) : '');
?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0"><i class="bi bi-person-gear"></i> Users</h4>
    <?php if (Auth::can('users', 'create')): ?>
    <a href="<?= Auth::baseUrl('users/create') ?>" class="btn btn-primary">
        <i class="bi bi-person-plus"></i> Add User
    </a>
    <?php endif; ?>
</div>

<form method="GET" action="<?= Auth::baseUrl('users') ?>" class="row g-2 mb-4">
    <div class="col-md-6">
        <input type="text" name="search" class="form-control" placeholder="Search by name, email, or phone..."
               value="<?= htmlspecialchars($search) ?>">
    </div>
    <div class="col-auto">
        <button type="submit" class="btn btn-outline-secondary"><i class="bi bi-search"></i> Search</button>
        <?php if ($search !== ''): ?>
        <a href="<?= Auth::baseUrl('users') ?>" class="btn btn-outline-secondary">Clear</a>
        <?php endif; ?>
    </div>
</form>

<div class="card shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead class="table-light">
                <tr>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Phone</th>
                    <th>Role</th>
                    <th>Status</th>
                    <th>Last Login</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($users['data'])): ?>
                <tr>
                    <td colspan="7" class="text-center text-muted py-4">No users found.</td>
                </tr>
                <?php else: ?>
                <?php foreach ($users['data'] as $u): ?>
                <tr>
                    <td><?= htmlspecialchars($u['name']) ?></td>
                    <td><?= htmlspecialchars($u['email']) ?></td>
                    <td><?= htmlspecialchars($u['phone'] ?? '—') ?></td>
                    <td>
                        <span class="badge bg-<?= $u['role'] === 'owner' ? 'primary' : ($u['role'] === 'manager' ? 'info' : 'secondary') ?>">
                            <?= ucfirst($u['role']) ?>
                        </span>
                    </td>
                    <td>
                        <span class="badge bg-<?= $u['status'] === 'active' ? 'success' : 'warning' ?>">
                            <?= ucfirst($u['status']) ?>
                        </span>
                    </td>
                    <td><?= $u['last_login'] ? Helpers::formatDateTime($u['last_login']) : '—' ?></td>
                    <td class="text-end">
                        <?php if ($u['role'] !== 'owner' && Auth::can('users', 'update')): ?>
                        <a href="<?= Auth::baseUrl('users/edit?id=' . (int) $u['id']) ?>" class="btn btn-sm btn-outline-primary" title="Edit">
                            <i class="bi bi-pencil"></i>
                        </a>
                        <?php endif; ?>
                        <?php if ($u['role'] !== 'owner' && (int) $u['id'] !== Auth::userId() && Auth::can('users', 'delete')): ?>
                        <form method="POST" action="<?= Auth::baseUrl('users/delete') ?>" class="d-inline"
                              onsubmit="return confirm('Delete this user?');">
                            <?= Auth::csrfField() ?>
                            <input type="hidden" name="id" value="<?= (int) $u['id'] ?>">
                            <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete">
                                <i class="bi bi-trash"></i>
                            </button>
                        </form>
                        <?php endif; ?>
                        <?php if ($u['role'] === 'owner'): ?>
                        <span class="text-muted small">Shop owner</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <?php if ($users['last_page'] > 1): ?>
    <div class="card-footer">
        <div class="d-flex justify-content-between align-items-center">
            <small class="text-muted">
                Showing <?= count($users['data']) ?> of <?= (int) $users['total'] ?> users
            </small>
            <?= Helpers::paginationHtml($users, $baseUrl) ?>
        </div>
    </div>
    <?php endif; ?>
</div>
