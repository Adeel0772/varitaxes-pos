<?php use Core\Helpers; ?>
<h4 class="mb-3"><?= htmlspecialchars($pageTitle) ?></h4>
<?php require __DIR__ . '/_filters.php'; ?>

<div class="alert alert-warning mb-4">
    <i class="bi bi-exclamation-triangle"></i> <?= (int) $data['summary']['item_count'] ?> product(s) at or below low stock threshold.
</div>

<div class="table-responsive">
    <table class="table table-striped table-hover">
        <thead class="table-light">
            <tr>
                <th>Code</th>
                <th>Product</th>
                <th>Category</th>
                <th class="text-end">In Stock</th>
                <th class="text-end">Threshold</th>
                <th class="text-end">Shortage</th>
                <th class="text-end">Sale Price</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($data['rows'])): ?>
            <tr><td colspan="7" class="text-center text-muted">All stock levels are healthy.</td></tr>
            <?php else: foreach ($data['rows'] as $row): ?>
            <tr>
                <td><?= htmlspecialchars($row['product_code']) ?></td>
                <td><?= htmlspecialchars($row['name']) ?></td>
                <td><?= htmlspecialchars($row['category_name'] ?? '—') ?></td>
                <td class="text-end text-danger fw-bold"><?= (int) $row['qty_in_stock'] ?></td>
                <td class="text-end"><?= (int) $row['low_stock_threshold'] ?></td>
                <td class="text-end"><?= (int) $row['shortage'] ?></td>
                <td class="text-end"><?= Helpers::formatMoney($row['sale_price']) ?></td>
            </tr>
            <?php endforeach; endif; ?>
        </tbody>
    </table>
</div>
