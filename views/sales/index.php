<?php

use Core\Auth;
use Core\Helpers;

$baseUrl = Auth::baseUrl('sales');
$query = [];
if ($search !== '') $query['search'] = $search;
if ($dateFrom !== '') $query['date_from'] = $dateFrom;
if ($dateTo !== '') $query['date_to'] = $dateTo;
$baseUrl .= $query ? '?' . http_build_query($query) : '';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0">
        <i class="bi bi-receipt"></i>
        <?= !empty($todayOnly) ? "My Today's Sales" : 'Sales' ?>
    </h4>
    <div class="d-flex gap-2">
        <?php if (Auth::can('sales', 'create')): ?>
        <a href="<?= Auth::baseUrl('sales/pos') ?>" class="btn btn-warning"><i class="bi bi-cart-check"></i> New Sale</a>
        <?php endif; ?>
        <?php if (!empty($todayOnly)): ?>
        <a href="<?= Auth::baseUrl('sales') ?>" class="btn btn-outline-secondary">All Sales</a>
        <?php endif; ?>
    </div>
</div>

<?php if (!empty($todayOnly)): ?>
<div class="alert alert-info">
    <strong>Today's Total:</strong> <?= Helpers::formatMoney($todayTotal ?? 0) ?>
    &mdash; <?= count($sales['data']) ?> sale(s)
</div>
<?php else: ?>
<form method="GET" action="<?= Auth::baseUrl('sales') ?>" class="row g-2 mb-4">
    <div class="col-md-3">
        <input type="text" name="search" class="form-control" placeholder="Sale # or customer..."
               value="<?= htmlspecialchars($search) ?>">
    </div>
    <div class="col-md-2">
        <input type="date" name="date_from" class="form-control" value="<?= htmlspecialchars($dateFrom) ?>">
    </div>
    <div class="col-md-2">
        <input type="date" name="date_to" class="form-control" value="<?= htmlspecialchars($dateTo) ?>">
    </div>
    <div class="col-auto">
        <button type="submit" class="btn btn-outline-secondary"><i class="bi bi-search"></i> Filter</button>
        <a href="<?= Auth::baseUrl('sales') ?>" class="btn btn-outline-secondary">Clear</a>
    </div>
</form>
<?php endif; ?>

<div class="card shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead class="table-light">
                <tr>
                    <th>Sale #</th>
                    <th>Date</th>
                    <?php if (empty($isSalesman)): ?>
                    <th>Salesman</th>
                    <?php endif; ?>
                    <th>Customer</th>
                    <th>Payment</th>
                    <th class="text-end">Total</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($sales['data'])): ?>
                <tr>
                    <td colspan="7" class="text-center text-muted py-4">No sales found.</td>
                </tr>
                <?php else: ?>
                <?php foreach ($sales['data'] as $s): ?>
                <tr>
                    <td><code><?= htmlspecialchars($s['sale_number']) ?></code></td>
                    <td><?= Helpers::formatDateTime($s['sale_date']) ?></td>
                    <?php if (empty($isSalesman)): ?>
                    <td><?= htmlspecialchars($s['salesman_name'] ?? '—') ?></td>
                    <?php endif; ?>
                    <td><?= htmlspecialchars($s['customer_name'] ?? 'Walk-in') ?></td>
                    <td><span class="badge bg-secondary"><?= ucfirst($s['payment_method']) ?></span></td>
                    <td class="text-end fw-medium"><?= Helpers::formatMoney($s['total_amount']) ?></td>
                    <td class="text-end">
                        <a href="<?= Auth::baseUrl('sales/view?id=' . (int) $s['id']) ?>" class="btn btn-sm btn-outline-primary">
                            <i class="bi bi-eye"></i>
                        </a>
                        <?php if (Auth::can('invoices', 'print')): ?>
                        <a href="<?= Auth::baseUrl('invoices/print?id=' . (int) $s['id']) ?>" class="btn btn-sm btn-outline-dark" target="_blank">
                            <i class="bi bi-printer"></i>
                        </a>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <?php if (empty($todayOnly) && $sales['last_page'] > 1): ?>
    <div class="card-footer">
        <div class="d-flex justify-content-between align-items-center">
            <small class="text-muted">Showing <?= count($sales['data']) ?> of <?= (int) $sales['total'] ?> sales</small>
            <?= Helpers::paginationHtml($sales, $baseUrl) ?>
        </div>
    </div>
    <?php endif; ?>
</div>
