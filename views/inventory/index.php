<?php
use Core\Auth;
use Core\Helpers;

$baseUrl = Auth::baseUrl('inventory');
$query = [];
if ($search) $query['search'] = $search;
if ($stockFilter) $query['stock'] = $stockFilter;
$filterUrl = $baseUrl . ($query ? '?' . http_build_query($query) : '');
$canAdjust = Auth::can('inventory', 'adjust');
?>
<div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2">
    <div>
        <h4 class="mb-0"><i class="bi bi-boxes"></i> Inventory</h4>
        <small class="text-muted"><?= (int) $inventory['total'] ?> products</small>
    </div>
    <div class="d-flex gap-2">
        <?php if ($lowStockCount > 0): ?>
        <a href="<?= Auth::baseUrl('inventory/low-stock') ?>" class="btn btn-warning">
            <i class="bi bi-exclamation-triangle"></i> Low Stock (<?= (int) $lowStockCount ?>)
        </a>
        <?php endif; ?>
    </div>
</div>

<div class="card mb-4">
    <div class="card-body">
        <form method="get" action="<?= $baseUrl ?>" class="row g-2 align-items-end">
            <div class="col-md-4">
                <input type="text" name="search" class="form-control" placeholder="Search name, code, or barcode..."
                       value="<?= htmlspecialchars($search) ?>">
            </div>
            <div class="col-md-3">
                <select name="stock" class="form-select">
                    <option value="">All stock levels</option>
                    <option value="low" <?= $stockFilter === 'low' ? 'selected' : '' ?>>Low stock only</option>
                    <option value="out" <?= $stockFilter === 'out' ? 'selected' : '' ?>>Out of stock</option>
                </select>
            </div>
            <div class="col-auto">
                <button type="submit" class="btn btn-primary"><i class="bi bi-search"></i> Filter</button>
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
                    <th><?= Helpers::sortLink('name', 'Product', $sort, $dir, $filterUrl) ?></th>
                    <th><?= Helpers::sortLink('product_code', 'Code', $sort, $dir, $filterUrl) ?></th>
                    <th class="text-end"><?= Helpers::sortLink('qty_in_stock', 'In Stock', $sort, $dir, $filterUrl) ?></th>
                    <th class="text-end"><?= Helpers::sortLink('low_stock_threshold', 'Threshold', $sort, $dir, $filterUrl) ?></th>
                    <th>Status</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($inventory['data'])): ?>
                <tr><td colspan="6" class="text-center text-muted py-4">No products found.</td></tr>
                <?php else: ?>
                <?php foreach ($inventory['data'] as $row): ?>
                <?php
                $isLow = (int) $row['is_low_stock'] === 1;
                $isOut = (int) $row['qty_in_stock'] === 0;
                $rowClass = $isOut ? 'table-danger' : ($isLow ? 'table-warning' : '');
                ?>
                <tr class="<?= $rowClass ?>" id="inv-row-<?= (int) $row['product_id'] ?>">
                    <td>
                        <a href="<?= Auth::baseUrl('products/view?id=' . (int) $row['product_id']) ?>">
                            <?= htmlspecialchars($row['name']) ?>
                        </a>
                    </td>
                    <td><code><?= htmlspecialchars($row['product_code']) ?></code></td>
                    <td class="text-end fw-semibold qty-cell"><?= (int) $row['qty_in_stock'] ?></td>
                    <td class="text-end"><?= (int) $row['low_stock_threshold'] ?></td>
                    <td>
                        <?php if ($isOut): ?>
                        <span class="badge bg-danger">Out of stock</span>
                        <?php elseif ($isLow): ?>
                        <span class="badge bg-warning text-dark">Low stock</span>
                        <?php else: ?>
                        <span class="badge bg-success">OK</span>
                        <?php endif; ?>
                    </td>
                    <td class="text-end">
                        <a href="<?= Auth::baseUrl('inventory/history?product_id=' . (int) $row['product_id']) ?>" class="btn btn-sm btn-outline-secondary" title="History">
                            <i class="bi bi-clock-history"></i>
                        </a>
                        <?php if ($canAdjust): ?>
                        <button type="button" class="btn btn-sm btn-outline-primary adjust-btn"
                                data-product-id="<?= (int) $row['product_id'] ?>"
                                data-product-name="<?= htmlspecialchars($row['name'], ENT_QUOTES) ?>"
                                data-qty="<?= (int) $row['qty_in_stock'] ?>">
                            <i class="bi bi-sliders"></i> Adjust
                        </button>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <?php if ($inventory['last_page'] > 1): ?>
    <div class="card-footer">
        <div class="d-flex justify-content-between align-items-center">
            <small class="text-muted">Showing <?= count($inventory['data']) ?> of <?= (int) $inventory['total'] ?></small>
            <?= Helpers::paginationHtml($inventory, $filterUrl) ?>
        </div>
    </div>
    <?php endif; ?>
</div>

<?php if ($canAdjust): ?>
<div class="modal fade" id="adjustStockModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="<?= Auth::baseUrl('inventory/adjust') ?>" id="adjustStockForm">
                <?= Auth::csrfField() ?>
                <input type="hidden" name="product_id" id="adjust_product_id">
                <div class="modal-header">
                    <h5 class="modal-title">Adjust Stock</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="mb-3">Product: <strong id="adjust_product_name"></strong></p>
                    <p class="text-muted mb-3">Current stock: <strong id="adjust_current_qty">0</strong></p>
                    <div class="mb-3">
                        <label class="form-label">Adjustment Qty *</label>
                        <input type="number" name="qty" id="adjust_qty" class="form-control" required>
                        <small class="text-muted">Use positive to add, negative to remove (e.g. +10 or -5)</small>
                    </div>
                    <div class="mb-0">
                        <label class="form-label">Reason *</label>
                        <textarea name="reason" class="form-control" rows="2" required placeholder="e.g. Damaged goods, stock count correction"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Apply Adjustment</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php endif; ?>
