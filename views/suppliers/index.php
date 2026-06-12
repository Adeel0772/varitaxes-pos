<?php
use Core\Auth;
use Core\Helpers;

$baseUrl = Auth::baseUrl('suppliers');
$filterUrl = $baseUrl . ($search ? '?search=' . urlencode($search) : '');
$canCreate = Auth::can('suppliers', 'create');
$canUpdate = Auth::can('suppliers', 'update');
$canDelete = Auth::can('suppliers', 'delete');
?>
<div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2">
    <div>
        <h4 class="mb-0"><i class="bi bi-building-add"></i> Suppliers</h4>
        <small class="text-muted"><?= (int) $suppliers['total'] ?> suppliers</small>
    </div>
    <?php if ($canCreate): ?>
    <a href="<?= Auth::baseUrl('suppliers/create') ?>" class="btn btn-primary">
        <i class="bi bi-plus-lg"></i> Add Supplier
    </a>
    <?php endif; ?>
</div>

<div class="card mb-4">
    <div class="card-body">
        <form method="get" action="<?= $baseUrl ?>" class="row g-2 align-items-end">
            <div class="col-md-4">
                <input type="text" name="search" class="form-control" placeholder="Search name, phone, or city..."
                       value="<?= htmlspecialchars($search) ?>">
            </div>
            <div class="col-auto">
                <button type="submit" class="btn btn-primary"><i class="bi bi-search"></i> Search</button>
                <?php if ($search !== ''): ?>
                <a href="<?= $baseUrl ?>" class="btn btn-outline-secondary">Clear</a>
                <?php endif; ?>
            </div>
        </form>
    </div>
</div>

<div class="card shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead class="table-light">
                <tr>
                    <th><?= Helpers::sortLink('name', 'Name', $sort, $dir, $filterUrl) ?></th>
                    <th><?= Helpers::sortLink('phone', 'Phone', $sort, $dir, $filterUrl) ?></th>
                    <th><?= Helpers::sortLink('city', 'City', $sort, $dir, $filterUrl) ?></th>
                    <th>Purchases</th>
                    <th><?= Helpers::sortLink('created_at', 'Added', $sort, $dir, $filterUrl) ?></th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($suppliers['data'])): ?>
                <tr><td colspan="6" class="text-center text-muted py-4">No suppliers found.</td></tr>
                <?php else: ?>
                <?php foreach ($suppliers['data'] as $s): ?>
                <tr>
                    <td><?= htmlspecialchars($s['name']) ?></td>
                    <td><?= htmlspecialchars($s['phone'] ?? '—') ?></td>
                    <td><?= htmlspecialchars($s['city'] ?? '—') ?></td>
                    <td><span class="badge bg-secondary"><?= (int) $s['purchase_count'] ?></span></td>
                    <td><?= Helpers::formatDate($s['created_at']) ?></td>
                    <td class="text-end">
                        <?php if ($canUpdate): ?>
                        <a href="<?= Auth::baseUrl('suppliers/edit?id=' . (int) $s['id']) ?>" class="btn btn-sm btn-outline-primary" title="Edit">
                            <i class="bi bi-pencil"></i>
                        </a>
                        <?php endif; ?>
                        <?php if ($canDelete && (int) $s['purchase_count'] === 0): ?>
                        <form method="post" action="<?= Auth::baseUrl('suppliers/delete') ?>" class="d-inline" data-confirm="Delete this supplier?">
                            <?= Auth::csrfField() ?>
                            <input type="hidden" name="id" value="<?= (int) $s['id'] ?>">
                            <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete">
                                <i class="bi bi-trash"></i>
                            </button>
                        </form>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <?php if ($suppliers['last_page'] > 1): ?>
    <div class="card-footer">
        <div class="d-flex justify-content-between align-items-center">
            <small class="text-muted">Showing <?= count($suppliers['data']) ?> of <?= (int) $suppliers['total'] ?></small>
            <?= Helpers::paginationHtml($suppliers, $filterUrl) ?>
        </div>
    </div>
    <?php endif; ?>
</div>
