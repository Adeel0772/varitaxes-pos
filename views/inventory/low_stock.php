<?php use Core\Auth; use Core\Helpers; ?>

<div class="mb-4">
    <a href="<?= Auth::baseUrl('inventory') ?>" class="text-decoration-none"><i class="bi bi-arrow-left"></i> Back to Inventory</a>
</div>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-0"><i class="bi bi-exclamation-triangle text-warning"></i> Low Stock Alert</h4>
        <small class="text-muted"><?= count($items) ?> products at or below threshold</small>
    </div>
</div>

<?php if (empty($items)): ?>
<div class="alert alert-success">
    <i class="bi bi-check-circle"></i> All products are above their low-stock thresholds.
</div>
<?php else: ?>

<div class="card shadow-sm border-warning">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead class="table-warning">
                <tr>
                    <th>Product</th>
                    <th>Code</th>
                    <th class="text-end">In Stock</th>
                    <th class="text-end">Threshold</th>
                    <th class="text-end">Shortfall</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($items as $row): ?>
                <?php $shortfall = (int) $row['low_stock_threshold'] - (int) $row['qty_in_stock']; ?>
                <tr class="<?= (int) $row['qty_in_stock'] === 0 ? 'table-danger' : '' ?>">
                    <td>
                        <a href="<?= Auth::baseUrl('products/view?id=' . (int) $row['product_id']) ?>">
                            <?= htmlspecialchars($row['name']) ?>
                        </a>
                    </td>
                    <td><code><?= htmlspecialchars($row['product_code']) ?></code></td>
                    <td class="text-end fw-bold"><?= (int) $row['qty_in_stock'] ?></td>
                    <td class="text-end"><?= (int) $row['low_stock_threshold'] ?></td>
                    <td class="text-end text-danger"><?= $shortfall > 0 ? $shortfall : 0 ?></td>
                    <td class="text-end">
                        <?php if (Auth::can('purchases', 'create')): ?>
                        <a href="<?= Auth::baseUrl('purchases/create') ?>" class="btn btn-sm btn-primary">
                            <i class="bi bi-truck"></i> Purchase
                        </a>
                        <?php endif; ?>
                        <a href="<?= Auth::baseUrl('inventory/history?product_id=' . (int) $row['product_id']) ?>" class="btn btn-sm btn-outline-secondary">
                            <i class="bi bi-clock-history"></i>
                        </a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php endif; ?>
