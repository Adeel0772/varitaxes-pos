<?php use Core\Auth; use Core\Helpers; ?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0"><i class="bi bi-speedometer2"></i> Super Admin Dashboard</h4>
    <a href="<?= Auth::baseUrl('admin/shops') ?>" class="btn btn-outline-primary btn-sm">
        <i class="bi bi-building"></i> Manage All Shops
    </a>
</div>

<div class="row g-3 mb-4">
    <div class="col-sm-6 col-xl-3">
        <div class="card stat-card shadow-sm h-100">
            <div class="card-body">
                <div class="text-muted small">Total Shops</div>
                <div class="fs-3 fw-bold"><?= (int) $stats['total'] ?></div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="card stat-card success shadow-sm h-100">
            <div class="card-body">
                <div class="text-muted small">Active</div>
                <div class="fs-3 fw-bold text-success"><?= (int) $stats['active'] ?></div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="card stat-card warning shadow-sm h-100">
            <div class="card-body">
                <div class="text-muted small">Pending Approval</div>
                <div class="fs-3 fw-bold text-warning"><?= (int) $stats['pending'] ?></div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="card stat-card danger shadow-sm h-100">
            <div class="card-body">
                <div class="text-muted small">Suspended</div>
                <div class="fs-3 fw-bold text-danger"><?= (int) $stats['suspended'] ?></div>
            </div>
        </div>
    </div>
</div>

<div class="card shadow-sm">
    <div class="card-header bg-white d-flex justify-content-between align-items-center">
        <h5 class="mb-0"><i class="bi bi-clock-history"></i> Recent Pending Registrations</h5>
        <?php if ((int) $stats['pending'] > count($recentPending)): ?>
        <a href="<?= Auth::baseUrl('admin/shops?status=pending') ?>" class="btn btn-sm btn-link">View all</a>
        <?php endif; ?>
    </div>
    <div class="card-body p-0">
        <?php if (empty($recentPending)): ?>
        <div class="p-4 text-center text-muted">No pending shop registrations.</div>
        <?php else: ?>
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Shop</th>
                        <th>Owner</th>
                        <th>Contact</th>
                        <th>City</th>
                        <th>Registered</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recentPending as $shop): ?>
                    <tr>
                        <td>
                            <a href="<?= Auth::baseUrl('admin/shops/view?id=' . (int) $shop['id']) ?>">
                                <?= htmlspecialchars($shop['name']) ?>
                            </a>
                            <br><small class="text-muted"><?= htmlspecialchars($shop['slug']) ?></small>
                        </td>
                        <td><?= htmlspecialchars($shop['owner_name']) ?></td>
                        <td>
                            <?= htmlspecialchars($shop['phone']) ?>
                            <?php if (!empty($shop['owner_email'])): ?>
                            <br><small class="text-muted"><?= htmlspecialchars($shop['owner_email']) ?></small>
                            <?php endif; ?>
                        </td>
                        <td><?= htmlspecialchars($shop['city'] ?? '—') ?></td>
                        <td><?= Helpers::formatDateTime($shop['created_at']) ?></td>
                        <td class="text-end">
                            <form method="POST" action="<?= Auth::baseUrl('admin/shops/approve') ?>" class="d-inline">
                                <?= Auth::csrfField() ?>
                                <input type="hidden" name="id" value="<?= (int) $shop['id'] ?>">
                                <input type="hidden" name="redirect" value="admin/dashboard">
                                <button type="submit" class="btn btn-sm btn-success" title="Approve">
                                    <i class="bi bi-check-lg"></i> Approve
                                </button>
                            </form>
                            <a href="<?= Auth::baseUrl('admin/shops/view?id=' . (int) $shop['id']) ?>" class="btn btn-sm btn-outline-secondary">
                                <i class="bi bi-eye"></i>
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
</div>
