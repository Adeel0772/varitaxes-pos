<?php use Core\Auth; ?>
<h4 class="mb-4"><i class="bi bi-graph-up"></i> Reports</h4>

<div class="row g-3">
    <?php
    $reports = [
        ['sales', 'Sales Report', 'bi-receipt', 'All sales with filters'],
        ['daily-summary', 'Daily Summary', 'bi-calendar-day', 'Day-wise totals'],
        ['inventory', 'Inventory', 'bi-boxes', 'Current stock levels'],
        ['low-stock', 'Low Stock', 'bi-exclamation-triangle', 'Items below threshold'],
        ['purchases', 'Purchases', 'bi-truck', 'Purchase history'],
        ['profit-loss', 'Profit & Loss', 'bi-currency-dollar', 'Revenue vs cost'],
        ['product-sales', 'Product Sales', 'bi-bar-chart', 'Sales by product'],
        ['salesman', 'Salesman Performance', 'bi-person-badge', 'Staff performance'],
        ['customer-ledger', 'Customer Ledger', 'bi-journal', 'Khata / credit entries'],
        ['discounts', 'Discounts', 'bi-percent', 'Discounted sales'],
        ['activity', 'Activity Log', 'bi-journal-text', 'System activity report'],
    ];
    foreach ($reports as [$slug, $title, $icon, $desc]):
    ?>
    <div class="col-md-6 col-lg-4">
        <a href="<?= Auth::baseUrl('reports/' . $slug) ?>" class="text-decoration-none">
            <div class="card h-100 shadow-sm report-card">
                <div class="card-body">
                    <h5 class="card-title"><i class="bi <?= $icon ?> text-primary"></i> <?= htmlspecialchars($title) ?></h5>
                    <p class="card-text text-muted small mb-0"><?= htmlspecialchars($desc) ?></p>
                </div>
            </div>
        </a>
    </div>
    <?php endforeach; ?>
</div>

<style>
.report-card { transition: transform .15s, box-shadow .15s; }
.report-card:hover { transform: translateY(-2px); box-shadow: 0 .5rem 1rem rgba(0,0,0,.1)!important; }
</style>
