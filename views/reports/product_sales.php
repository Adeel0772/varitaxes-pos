<?php use Core\Helpers; ?>
<h4 class="mb-3"><?= htmlspecialchars($pageTitle) ?></h4>
<?php require __DIR__ . '/_filters.php'; ?>

<div class="row g-3 mb-4">
    <div class="col-md-4"><div class="card"><div class="card-body"><small class="text-muted">Products Sold</small><h5><?= (int) $data['summary']['product_count'] ?></h5></div></div></div>
    <div class="col-md-4"><div class="card"><div class="card-body"><small class="text-muted">Total Qty</small><h5><?= (int) $data['summary']['qty_sold'] ?></h5></div></div></div>
    <div class="col-md-4"><div class="card"><div class="card-body"><small class="text-muted">Revenue</small><h5><?= Helpers::formatMoney($data['summary']['revenue']) ?></h5></div></div></div>
</div>

<div class="table-responsive">
    <table class="table table-striped table-hover">
        <thead class="table-light">
            <tr>
                <th>Code</th>
                <th>Product</th>
                <th class="text-end">Qty Sold</th>
                <th class="text-end">Avg Price</th>
                <th class="text-end">Revenue</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($data['rows'])): ?>
            <tr><td colspan="5" class="text-center text-muted">No product sales in this period.</td></tr>
            <?php else: foreach ($data['rows'] as $row): ?>
            <tr>
                <td><?= htmlspecialchars($row['product_code']) ?></td>
                <td><?= htmlspecialchars($row['product_name']) ?></td>
                <td class="text-end"><?= (int) $row['qty_sold'] ?></td>
                <td class="text-end"><?= Helpers::formatMoney($row['avg_price']) ?></td>
                <td class="text-end"><?= Helpers::formatMoney($row['revenue']) ?></td>
            </tr>
            <?php endforeach; endif; ?>
        </tbody>
    </table>
</div>
