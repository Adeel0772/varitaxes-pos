<?php use Core\Auth; use Core\Helpers; ?>

<?php
$badgeClass = match ($shop['status']) {
    'active'    => 'bg-success',
    'pending'   => 'bg-warning text-dark',
    'suspended' => 'bg-danger',
    default     => 'bg-secondary',
};
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-1"><?= htmlspecialchars($shop['name']) ?></h4>
        <span class="badge <?= $badgeClass ?>"><?= htmlspecialchars(ucfirst($shop['status'])) ?></span>
    </div>
    <a href="<?= Auth::baseUrl('admin/shops') ?>" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left"></i> Back to list
    </a>
</div>

<div class="row g-4">
    <div class="col-lg-8">
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-white"><h5 class="mb-0">Shop Information</h5></div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <div class="text-muted small">Slug</div>
                        <div><?= htmlspecialchars($shop['slug']) ?></div>
                    </div>
                    <div class="col-md-6">
                        <div class="text-muted small">Shop Type</div>
                        <div><?= htmlspecialchars(ucfirst($shop['shop_type'])) ?></div>
                    </div>
                    <div class="col-md-6">
                        <div class="text-muted small">Owner</div>
                        <div><?= htmlspecialchars($shop['owner_name']) ?></div>
                    </div>
                    <div class="col-md-6">
                        <div class="text-muted small">Phone</div>
                        <div><?= htmlspecialchars($shop['phone']) ?></div>
                    </div>
                    <div class="col-md-6">
                        <div class="text-muted small">City</div>
                        <div><?= htmlspecialchars($shop['city'] ?? '—') ?></div>
                    </div>
                    <div class="col-md-6">
                        <div class="text-muted small">Invoice Format</div>
                        <div><?= htmlspecialchars(strtoupper($shop['invoice_format'])) ?></div>
                    </div>
                    <div class="col-12">
                        <div class="text-muted small">Address</div>
                        <div><?= htmlspecialchars($shop['address'] ?? '—') ?></div>
                    </div>
                    <div class="col-md-6">
                        <div class="text-muted small">Registered</div>
                        <div><?= Helpers::formatDateTime($shop['created_at']) ?></div>
                    </div>
                    <div class="col-md-6">
                        <div class="text-muted small">Last Updated</div>
                        <div><?= Helpers::formatDateTime($shop['updated_at']) ?></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card shadow-sm">
            <div class="card-header bg-white"><h5 class="mb-0">Users (<?= count($shop['users']) ?>)</h5></div>
            <div class="card-body p-0">
                <?php if (empty($shop['users'])): ?>
                <div class="p-3 text-muted">No users found.</div>
                <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-sm mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Role</th>
                                <th>Status</th>
                                <th>Last Login</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($shop['users'] as $user): ?>
                            <tr>
                                <td><?= htmlspecialchars($user['name']) ?></td>
                                <td><?= htmlspecialchars($user['email']) ?></td>
                                <td><span class="badge bg-secondary"><?= htmlspecialchars(ucfirst($user['role'])) ?></span></td>
                                <td><?= htmlspecialchars(ucfirst($user['status'])) ?></td>
                                <td><?= $user['last_login'] ? Helpers::formatDateTime($user['last_login']) : '—' ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-white"><h5 class="mb-0">Statistics</h5></div>
            <div class="card-body">
                <div class="d-flex justify-content-between mb-2">
                    <span class="text-muted">Users</span>
                    <strong><?= (int) $shop['user_count'] ?></strong>
                </div>
                <div class="d-flex justify-content-between mb-2">
                    <span class="text-muted">Products</span>
                    <strong><?= (int) $shop['product_count'] ?></strong>
                </div>
                <div class="d-flex justify-content-between">
                    <span class="text-muted">Sales</span>
                    <strong><?= (int) $shop['sale_count'] ?></strong>
                </div>
            </div>
        </div>

        <div class="card shadow-sm">
            <div class="card-header bg-white"><h5 class="mb-0">Actions</h5></div>
            <div class="card-body d-grid gap-2">
                <?php if ($shop['status'] === 'pending'): ?>
                <form method="POST" action="<?= Auth::baseUrl('admin/shops/approve') ?>">
                    <?= Auth::csrfField() ?>
                    <input type="hidden" name="id" value="<?= (int) $shop['id'] ?>">
                    <input type="hidden" name="redirect" value="admin/shops/view?id=<?= (int) $shop['id'] ?>">
                    <button type="submit" class="btn btn-success w-100">
                        <i class="bi bi-check-lg"></i> Approve Shop
                    </button>
                </form>
                <?php endif; ?>

                <?php if ($shop['status'] === 'active'): ?>
                <form method="POST" action="<?= Auth::baseUrl('admin/impersonate') ?>">
                    <?= Auth::csrfField() ?>
                    <input type="hidden" name="id" value="<?= (int) $shop['id'] ?>">
                    <button type="submit" class="btn btn-outline-primary w-100">
                        <i class="bi bi-eye"></i> View as Shop (Read-only)
                    </button>
                </form>
                <form method="POST" action="<?= Auth::baseUrl('admin/shops/suspend') ?>"
                      onsubmit="return confirm('Suspend this shop? All users will be locked out.');">
                    <?= Auth::csrfField() ?>
                    <input type="hidden" name="id" value="<?= (int) $shop['id'] ?>">
                    <input type="hidden" name="redirect" value="admin/shops/view?id=<?= (int) $shop['id'] ?>">
                    <button type="submit" class="btn btn-outline-danger w-100">
                        <i class="bi bi-pause-circle"></i> Suspend Shop
                    </button>
                </form>
                <?php endif; ?>

                <?php if ($shop['status'] === 'suspended'): ?>
                <form method="POST" action="<?= Auth::baseUrl('admin/shops/approve') ?>">
                    <?= Auth::csrfField() ?>
                    <input type="hidden" name="id" value="<?= (int) $shop['id'] ?>">
                    <input type="hidden" name="redirect" value="admin/shops/view?id=<?= (int) $shop['id'] ?>">
                    <button type="submit" class="btn btn-success w-100">
                        <i class="bi bi-play-circle"></i> Reactivate Shop
                    </button>
                </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
