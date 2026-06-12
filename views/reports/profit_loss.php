<?php use Core\Helpers; ?>
<h4 class="mb-3"><?= htmlspecialchars($pageTitle) ?></h4>
<?php require __DIR__ . '/_filters.php'; ?>

<div class="row g-3 mb-4">
    <?php $s = $data['summary']; ?>
    <div class="col-md-3"><div class="card"><div class="card-body"><small class="text-muted">Sales Count</small><h5><?= (int) $s['sale_count'] ?></h5></div></div></div>
    <div class="col-md-3"><div class="card"><div class="card-body"><small class="text-muted">Revenue</small><h5><?= Helpers::formatMoney($s['revenue']) ?></h5></div></div></div>
    <div class="col-md-3"><div class="card"><div class="card-body"><small class="text-muted">COGS</small><h5><?= Helpers::formatMoney($s['cogs']) ?></h5></div></div></div>
    <div class="col-md-3"><div class="card border-success"><div class="card-body"><small class="text-muted">Gross Profit</small><h5 class="text-success"><?= Helpers::formatMoney($s['gross_profit']) ?></h5></div></div></div>
</div>

<div class="card">
    <div class="card-body">
        <table class="table mb-0">
            <tr><th style="width:40%;">Total Revenue</th><td class="text-end"><?= Helpers::formatMoney($s['revenue']) ?></td></tr>
            <tr><th>Discounts Given</th><td class="text-end"><?= Helpers::formatMoney($s['discounts']) ?></td></tr>
            <tr><th>Cost of Goods Sold (estimated)</th><td class="text-end"><?= Helpers::formatMoney($s['cogs']) ?></td></tr>
            <tr class="table-light"><th>Gross Profit</th><td class="text-end fw-bold"><?= Helpers::formatMoney($s['gross_profit']) ?></td></tr>
            <tr><th>Purchases in Period</th><td class="text-end"><?= Helpers::formatMoney($s['purchase_total']) ?></td></tr>
        </table>
        <p class="text-muted small mt-3 mb-0">COGS is estimated from current product purchase prices × quantities sold.</p>
    </div>
</div>
