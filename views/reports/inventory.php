<?php use Core\Helpers; use Core\Auth; ?>
<h4 class="mb-3"><?= htmlspecialchars($pageTitle) ?></h4>
<?php require __DIR__ . '/_filters.php'; ?>

<div class="row g-3 mb-4">
    <div class="col-md-4"><div class="card"><div class="card-body"><small class="text-muted">Products</small><h5><?= (int) $data['summary']['product_count'] ?></h5></div></div></div>
    <div class="col-md-4"><div class="card"><div class="card-body"><small class="text-muted">Total Qty</small><h5><?= (int) $data['summary']['total_qty'] ?></h5></div></div></div>
    <div class="col-md-4"><div class="card"><div class="card-body"><small class="text-muted">Stock Value</small><h5><?= Helpers::formatMoney($data['summary']['stock_value']) ?></h5></div></div></div>
</div>

<div class="table-responsive">
    <table class="table table-striped table-hover">
        <thead class="table-light">
            <tr>
                <th>Code</th>
                <th>Product</th>
                <th>Category</th>
                <th>Brand</th>
                <th class="text-end">Qty</th>
                <th class="text-end">Threshold</th>
                <?php if (Auth::canSeePurchasePrice()): ?><th class="text-end">Cost</th><?php endif; ?>
                <th class="text-end">Sale Price</th>
                <th class="text-end">Stock Value</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($data['rows'])): ?>
            <tr><td colspan="10" class="text-center text-muted">No products found.</td></tr>
            <?php else: foreach ($data['rows'] as $row): ?>
            <tr>
                <td><?= htmlspecialchars($row['product_code']) ?></td>
                <td><?= htmlspecialchars($row['name']) ?></td>
                <td><?= htmlspecialchars($row['category_name'] ?? '—') ?></td>
                <td><?= htmlspecialchars($row['brand_name'] ?? '—') ?></td>
                <td class="text-end"><?= (int) $row['qty_in_stock'] ?></td>
                <td class="text-end"><?= (int) $row['low_stock_threshold'] ?></td>
                <?php if (Auth::canSeePurchasePrice()): ?><td class="text-end"><?= Helpers::formatMoney($row['purchase_price']) ?></td><?php endif; ?>
                <td class="text-end"><?= Helpers::formatMoney($row['sale_price']) ?></td>
                <td class="text-end"><?= Helpers::formatMoney($row['stock_value']) ?></td>
                <td><span class="badge bg-<?= $row['status'] === 'active' ? 'success' : 'secondary' ?>"><?= ucfirst($row['status']) ?></span></td>
            </tr>
            <?php endforeach; endif; ?>
        </tbody>
    </table>
</div>
