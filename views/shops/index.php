<?php use Core\Auth; use Core\Helpers; ?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0"><i class="bi bi-building"></i> Shops</h4>
    <a href="<?= Auth::baseUrl('admin/dashboard') ?>" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left"></i> Dashboard
    </a>
</div>

<div class="card shadow-sm mb-4">
    <div class="card-body">
        <form method="GET" action="<?= Auth::baseUrl('admin/shops') ?>" class="row g-3 align-items-end">
            <div class="col-md-5">
                <label class="form-label">Search</label>
                <input type="text" name="search" class="form-control" placeholder="Name, owner, phone, city..."
                       value="<?= htmlspecialchars($search) ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label">Status</label>
                <select name="status" class="form-select">
                    <option value="">All statuses</option>
                    <option value="active" <?= $status === 'active' ? 'selected' : '' ?>>Active</option>
                    <option value="pending" <?= $status === 'pending' ? 'selected' : '' ?>>Pending</option>
                    <option value="suspended" <?= $status === 'suspended' ? 'selected' : '' ?>>Suspended</option>
                </select>
            </div>
            <div class="col-md-4">
                <button type="submit" class="btn btn-primary"><i class="bi bi-search"></i> Filter</button>
                <a href="<?= Auth::baseUrl('admin/shops') ?>" class="btn btn-outline-secondary">Reset</a>
            </div>
        </form>
    </div>
</div>

<div class="card shadow-sm">
    <div class="card-header bg-white d-flex justify-content-between align-items-center">
        <span><?= (int) $shops['total'] ?> shop(s) found</span>
        <small class="text-muted">Page <?= (int) $shops['current_page'] ?> of <?= max(1, (int) $shops['last_page']) ?></small>
    </div>
    <div class="card-body p-0">
        <?php if (empty($shops['data'])): ?>
        <div class="p-4 text-center text-muted">No shops match your criteria.</div>
        <?php else: ?>
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Shop</th>
                        <th>Owner</th>
                        <th>Type</th>
                        <th>Status</th>
                        <th>Users</th>
                        <th>Registered</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($shops['data'] as $shop): ?>
                    <?php
                    $badgeClass = match ($shop['status']) {
                        'active'    => 'bg-success',
                        'pending'   => 'bg-warning text-dark',
                        'suspended' => 'bg-danger',
                        default     => 'bg-secondary',
                    };
                    ?>
                    <tr>
                        <td>
                            <a href="<?= Auth::baseUrl('admin/shops/view?id=' . (int) $shop['id']) ?>">
                                <?= htmlspecialchars($shop['name']) ?>
                            </a>
                            <br><small class="text-muted"><?= htmlspecialchars($shop['city'] ?? '') ?></small>
                        </td>
                        <td>
                            <?= htmlspecialchars($shop['owner_name']) ?>
                            <?php if (!empty($shop['owner_email'])): ?>
                            <br><small class="text-muted"><?= htmlspecialchars($shop['owner_email']) ?></small>
                            <?php endif; ?>
                        </td>
                        <td><?= htmlspecialchars(ucfirst($shop['shop_type'])) ?></td>
                        <td><span class="badge <?= $badgeClass ?>"><?= htmlspecialchars(ucfirst($shop['status'])) ?></span></td>
                        <td><?= (int) $shop['user_count'] ?></td>
                        <td><?= Helpers::formatDate($shop['created_at']) ?></td>
                        <td class="text-end text-nowrap">
                            <a href="<?= Auth::baseUrl('admin/shops/view?id=' . (int) $shop['id']) ?>" class="btn btn-sm btn-outline-secondary" title="View">
                                <i class="bi bi-eye"></i>
                            </a>
                            <?php if ($shop['status'] === 'pending'): ?>
                            <form method="POST" action="<?= Auth::baseUrl('admin/shops/approve') ?>" class="d-inline">
                                <?= Auth::csrfField() ?>
                                <input type="hidden" name="id" value="<?= (int) $shop['id'] ?>">
                                <button type="submit" class="btn btn-sm btn-success" title="Approve"><i class="bi bi-check-lg"></i></button>
                            </form>
                            <?php endif; ?>
                            <?php if ($shop['status'] === 'active'): ?>
                            <form method="POST" action="<?= Auth::baseUrl('admin/shops/suspend') ?>" class="d-inline"
                                  onsubmit="return confirm('Suspend this shop?');">
                                <?= Auth::csrfField() ?>
                                <input type="hidden" name="id" value="<?= (int) $shop['id'] ?>">
                                <button type="submit" class="btn btn-sm btn-outline-danger" title="Suspend"><i class="bi bi-pause-circle"></i></button>
                            </form>
                            <?php endif; ?>
                            <?php if ($shop['status'] === 'suspended'): ?>
                            <form method="POST" action="<?= Auth::baseUrl('admin/shops/approve') ?>" class="d-inline">
                                <?= Auth::csrfField() ?>
                                <input type="hidden" name="id" value="<?= (int) $shop['id'] ?>">
                                <button type="submit" class="btn btn-sm btn-outline-success" title="Reactivate"><i class="bi bi-play-circle"></i></button>
                            </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
    <?php if ($shops['last_page'] > 1): ?>
    <div class="card-footer bg-white">
        <?php
        $baseUrl = Auth::baseUrl('admin/shops') . '?';
        if ($search !== '') {
            $baseUrl .= 'search=' . urlencode($search) . '&';
        }
        if ($status !== '') {
            $baseUrl .= 'status=' . urlencode($status) . '&';
        }
        echo Helpers::paginationHtml($shops, rtrim($baseUrl, '&?'));
        ?>
    </div>
    <?php endif; ?>
</div>
