<?php
use Core\Auth;
use Core\Helpers;

$baseUrl = Auth::baseUrl('purchases');
$query = [];
if ($supplierId) $query['supplier_id'] = $supplierId;
if ($search) $query['search'] = $search;
if ($dateFrom) $query['date_from'] = $dateFrom;
if ($dateTo) $query['date_to'] = $dateTo;
$filterUrl = $baseUrl . ($query ? '?' . http_build_query($query) : '');
$canCreate = Auth::can('purchases', 'create');
?>
<div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2">
    <div>
        <h4 class="mb-0"><i class="bi bi-truck"></i> Purchases</h4>
        <small class="text-muted"><?= (int) $purchases['total'] ?> records</small>
    </div>
    <?php if ($canCreate): ?>
    <a href="<?= Auth::baseUrl('purchases/create') ?>" class="btn btn-primary">
        <i class="bi bi-plus-lg"></i> New Purchase
    </a>
    <?php endif; ?>
</div>

<div class="card mb-4">
    <div class="card-body">
        <form method="get" action="<?= $baseUrl ?>" class="row g-2 align-items-end">
            <div class="col-md-3">
                <label class="form-label small">Supplier</label>
                <select name="supplier_id" class="form-select">
                    <option value="">All suppliers</option>
                    <?php foreach ($suppliers as $s): ?>
                    <option value="<?= (int) $s['id'] ?>" <?= $supplierId === (int) $s['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($s['name']) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small">From</label>
                <input type="text" name="date_from" class="form-control" placeholder="dd-mm-yyyy"
                       value="<?= htmlspecialchars($dateFrom) ?>">
            </div>
            <div class="col-md-2">
                <label class="form-label small">To</label>
                <input type="text" name="date_to" class="form-control" placeholder="dd-mm-yyyy"
                       value="<?= htmlspecialchars($dateTo) ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label small">Search</label>
                <input type="text" name="search" class="form-control" placeholder="Supplier or notes..."
                       value="<?= htmlspecialchars($search) ?>">
            </div>
            <div class="col-auto">
                <button type="submit" class="btn btn-primary"><i class="bi bi-funnel"></i> Filter</button>
                <a href="<?= $baseUrl ?>" class="btn btn-outline-secondary">Reset</a>
            </div>
        </form>
    </div>
</div>

<div class="card shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead class="table-light">
                <tr>
                    <th>#</th>
                    <th><?= Helpers::sortLink('purchase_date', 'Date', $sort, $dir, $filterUrl) ?></th>
                    <th>Supplier</th>
                    <th>Items</th>
                    <th class="text-end"><?= Helpers::sortLink('total_amount', 'Total', $sort, $dir, $filterUrl) ?></th>
                    <th>Recorded By</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($purchases['data'])): ?>
                <tr><td colspan="7" class="text-center text-muted py-4">No purchases found.</td></tr>
                <?php else: ?>
                <?php foreach ($purchases['data'] as $p): ?>
                <tr>
                    <td><?= (int) $p['id'] ?></td>
                    <td><?= Helpers::formatDate($p['purchase_date']) ?></td>
                    <td><?= htmlspecialchars($p['supplier_name']) ?></td>
                    <td><span class="badge bg-secondary"><?= (int) $p['item_count'] ?></span></td>
                    <td class="text-end"><?= Helpers::formatMoney($p['total_amount']) ?></td>
                    <td><?= htmlspecialchars($p['created_by_name'] ?? '—') ?></td>
                    <td class="text-end">
                        <a href="<?= Auth::baseUrl('purchases/view?id=' . (int) $p['id']) ?>" class="btn btn-sm btn-outline-primary">
                            <i class="bi bi-eye"></i> View
                        </a>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <?php if ($purchases['last_page'] > 1): ?>
    <div class="card-footer">
        <div class="d-flex justify-content-between align-items-center">
            <small class="text-muted">Showing <?= count($purchases['data']) ?> of <?= (int) $purchases['total'] ?></small>
            <?= Helpers::paginationHtml($purchases, $filterUrl) ?>
        </div>
    </div>
    <?php endif; ?>
</div>
