<?php use Core\Auth; use Core\Helpers; ?>

<?php
$maxDaily = 1;
foreach ($monthComparison['daily'] as $day) {
    $maxDaily = max($maxDaily, (float) $day['total']);
}
$changeClass = $monthComparison['change_percent'] >= 0 ? 'text-success' : 'text-danger';
$changeIcon = $monthComparison['change_percent'] >= 0 ? 'bi-arrow-up' : 'bi-arrow-down';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0"><i class="bi bi-speedometer2"></i> Dashboard</h4>
    <span class="text-muted"><?= date('l, ' . DATE_FORMAT) ?></span>
</div>

<div class="row g-3 mb-4">
    <div class="col-sm-6 col-xl-3">
        <div class="card stat-card shadow-sm h-100">
            <div class="card-body">
                <div class="text-muted small">Today's Sales</div>
                <div class="fs-4 fw-bold"><?= Helpers::formatMoney($todaySales['total_amount']) ?></div>
                <small class="text-muted"><?= (int) $todaySales['sale_count'] ?> transaction(s)</small>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="card stat-card success shadow-sm h-100">
            <div class="card-body">
                <div class="text-muted small">This Month</div>
                <div class="fs-4 fw-bold"><?= Helpers::formatMoney($monthComparison['current_month']['total']) ?></div>
                <small class="<?= $changeClass ?>">
                    <i class="bi <?= $changeIcon ?>"></i>
                    <?= abs($monthComparison['change_percent']) ?>% vs last month
                </small>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="card stat-card warning shadow-sm h-100">
            <div class="card-body">
                <div class="text-muted small">Low Stock Items</div>
                <div class="fs-4 fw-bold text-warning"><?= (int) $lowStockCount ?></div>
                <?php if ($lowStockCount > 0 && Auth::can('inventory', 'read')): ?>
                <a href="<?= Auth::baseUrl('inventory/low-stock') ?>" class="small">View items</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="card stat-card danger shadow-sm h-100">
            <div class="card-body">
                <div class="text-muted small">Outstanding Udhaar</div>
                <div class="fs-4 fw-bold text-danger"><?= Helpers::formatMoney($outstandingUdhaar['total_amount']) ?></div>
                <small class="text-muted"><?= (int) $outstandingUdhaar['customer_count'] ?> customer(s)</small>
            </div>
        </div>
    </div>
</div>

<div class="row g-4 mb-4">
    <div class="col-lg-8">
        <div class="card shadow-sm h-100">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Daily Sales — <?= htmlspecialchars($monthComparison['current_month']['label']) ?></h5>
                <small class="text-muted">
                    Prev: <?= Helpers::formatMoney($monthComparison['previous_month']['total']) ?>
                </small>
            </div>
            <div class="card-body">
                <?php if (empty($monthComparison['daily'])): ?>
                <div class="text-center text-muted py-4">No sales recorded this month yet.</div>
                <?php else: ?>
                <div class="css-bar-chart d-flex align-items-end gap-1" style="height: 180px;">
                    <?php foreach ($monthComparison['daily'] as $day): ?>
                    <?php $barHeight = max(4, (int) round(((float) $day['total'] / $maxDaily) * 140)); ?>
                    <div class="flex-fill text-center bar-col" title="Day <?= (int) $day['day_label'] ?>: <?= Helpers::formatMoney($day['total']) ?>">
                        <div class="bg-primary rounded-top mx-auto bar-fill" style="height: <?= $barHeight ?>px; width: 80%;"></div>
                        <small class="text-muted d-block mt-1" style="font-size: 0.65rem;"><?= (int) $day['day_label'] ?></small>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card shadow-sm h-100">
            <div class="card-header bg-white"><h5 class="mb-0">Quick Actions</h5></div>
            <div class="card-body d-grid gap-2">
                <?php if (Auth::can('sales', 'create')): ?>
                <a href="<?= Auth::baseUrl('sales/pos') ?>" class="btn btn-warning">
                    <i class="bi bi-cart-check"></i> New POS Sale
                </a>
                <?php endif; ?>
                <?php if (Auth::can('products', 'create')): ?>
                <a href="<?= Auth::baseUrl('products/create') ?>" class="btn btn-outline-primary">
                    <i class="bi bi-plus-circle"></i> Add Product
                </a>
                <?php endif; ?>
                <?php if (Auth::can('purchases', 'create')): ?>
                <a href="<?= Auth::baseUrl('purchases/create') ?>" class="btn btn-outline-primary">
                    <i class="bi bi-truck"></i> New Purchase
                </a>
                <?php endif; ?>
                <?php if (Auth::can('customers', 'create')): ?>
                <a href="<?= Auth::baseUrl('customers/create') ?>" class="btn btn-outline-primary">
                    <i class="bi bi-person-plus"></i> Add Customer
                </a>
                <?php endif; ?>
                <?php if (Auth::can('reports', 'read')): ?>
                <a href="<?= Auth::baseUrl('reports/daily-summary') ?>" class="btn btn-outline-secondary">
                    <i class="bi bi-graph-up"></i> Daily Summary
                </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<div class="row g-4">
    <div class="col-lg-5">
        <div class="card shadow-sm">
            <div class="card-header bg-white"><h5 class="mb-0">Top Products This Month</h5></div>
            <div class="card-body p-0">
                <?php if (empty($topProducts)): ?>
                <div class="p-3 text-muted">No product sales this month.</div>
                <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-sm mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Product</th>
                                <th class="text-end">Qty</th>
                                <th class="text-end">Revenue</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($topProducts as $product): ?>
                            <tr>
                                <td>
                                    <?= htmlspecialchars($product['name']) ?>
                                    <br><small class="text-muted"><?= htmlspecialchars($product['product_code']) ?></small>
                                </td>
                                <td class="text-end"><?= (int) $product['qty_sold'] ?></td>
                                <td class="text-end"><?= Helpers::formatMoney($product['revenue']) ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="col-lg-7">
        <div class="card shadow-sm">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Recent Sales</h5>
                <?php if (Auth::can('sales', 'read')): ?>
                <a href="<?= Auth::baseUrl('sales') ?>" class="btn btn-sm btn-link">View all</a>
                <?php endif; ?>
            </div>
            <div class="card-body p-0">
                <?php if (empty($recentSales)): ?>
                <div class="p-3 text-muted">No sales recorded yet.</div>
                <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover table-sm mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Sale #</th>
                                <th>Date</th>
                                <th>Customer</th>
                                <th>Salesman</th>
                                <th class="text-end">Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recentSales as $sale): ?>
                            <tr>
                                <td>
                                    <?php if (Auth::can('sales', 'read')): ?>
                                    <a href="<?= Auth::baseUrl('sales/view?id=' . (int) $sale['id']) ?>">
                                        <?= htmlspecialchars($sale['sale_number']) ?>
                                    </a>
                                    <?php else: ?>
                                    <?= htmlspecialchars($sale['sale_number']) ?>
                                    <?php endif; ?>
                                </td>
                                <td><?= Helpers::formatDateTime($sale['sale_date']) ?></td>
                                <td><?= htmlspecialchars($sale['customer_name'] ?? 'Walk-in') ?></td>
                                <td><?= htmlspecialchars($sale['salesman_name']) ?></td>
                                <td class="text-end"><?= Helpers::formatMoney($sale['total_amount']) ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<style>
.css-bar-chart .bar-col { display: flex; flex-direction: column; justify-content: flex-end; height: 100%; }
.css-bar-chart .bar-fill { min-height: 4px; transition: height 0.3s ease; }
</style>
