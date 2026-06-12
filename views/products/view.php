<?php
use Core\Auth;
use Core\Helpers;

$canUpdate = Auth::can('products', 'update');
$canDelete = Auth::can('products', 'delete');
$showPurchase = Auth::canSeePurchasePrice();
?>
<div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2">
    <div>
        <h4 class="mb-1"><?= htmlspecialchars($product['name']) ?></h4>
        <code class="text-muted"><?= htmlspecialchars($product['product_code']) ?></code>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        <a href="<?= Auth::baseUrl('products') ?>" class="btn btn-outline-secondary"><i class="bi bi-arrow-left"></i> Back</a>
        <?php if ($canUpdate): ?>
        <a href="<?= Auth::baseUrl('products/edit?id=' . $product['id']) ?>" class="btn btn-primary"><i class="bi bi-pencil"></i> Edit</a>
        <form method="post" action="<?= Auth::baseUrl('products/toggle-status') ?>" class="d-inline">
            <?= Auth::csrfField() ?>
            <input type="hidden" name="id" value="<?= (int) $product['id'] ?>">
            <input type="hidden" name="redirect" value="products/view?id=<?= (int) $product['id'] ?>">
            <button type="submit" class="btn btn-<?= $product['status'] === 'active' ? 'warning' : 'success' ?>">
                <?= $product['status'] === 'active' ? 'Deactivate' : 'Activate' ?>
            </button>
        </form>
        <?php endif; ?>
        <?php if ($canDelete): ?>
        <form method="post" action="<?= Auth::baseUrl('products/delete') ?>" class="d-inline" data-confirm="Delete this product permanently?">
            <?= Auth::csrfField() ?>
            <input type="hidden" name="id" value="<?= (int) $product['id'] ?>">
            <button type="submit" class="btn btn-outline-danger"><i class="bi bi-trash"></i> Delete</button>
        </form>
        <?php endif; ?>
    </div>
</div>

<div class="row g-4">
    <div class="col-lg-4">
        <div class="card">
            <div class="card-body text-center">
                <?php if ($product['image']): ?>
                <img src="<?= Helpers::productImageUrl((int) $product['id']) ?>" alt="" class="img-fluid rounded mb-3" style="max-height:240px">
                <?php else: ?>
                <div class="bg-light rounded d-flex align-items-center justify-content-center mb-3" style="height:200px">
                    <i class="bi bi-image text-muted" style="font-size:3rem"></i>
                </div>
                <?php endif; ?>
                <span class="badge bg-<?= $product['status'] === 'active' ? 'success' : 'secondary' ?> fs-6"><?= ucfirst($product['status']) ?></span>
            </div>
        </div>

        <div class="card mt-4">
            <div class="card-header"><strong>Inventory</strong></div>
            <div class="card-body">
                <div class="d-flex justify-content-between mb-2">
                    <span>In Stock</span>
                    <strong class="text-<?= (int)$product['qty_in_stock'] <= 0 ? 'danger' : 'success' ?>"><?= (int) $product['qty_in_stock'] ?></strong>
                </div>
                <div class="d-flex justify-content-between">
                    <span>Low Stock Alert</span>
                    <span><?= (int) ($product['low_stock_threshold'] ?? 5) ?></span>
                </div>
                <?php if (Auth::can('inventory', 'read')): ?>
                <a href="<?= Auth::baseUrl('inventory/history?product_id=' . $product['id']) ?>" class="btn btn-sm btn-outline-primary w-100 mt-3">View Full History</a>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="col-lg-8">
        <div class="card mb-4">
            <div class="card-header"><strong>Details</strong></div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="text-muted small">Category</label>
                        <div><?= htmlspecialchars($product['category_name'] ?? '—') ?><?= !empty($product['category_code']) ? ' <small class="text-muted">(' . htmlspecialchars($product['category_code']) . ')</small>' : '' ?></div>
                    </div>
                    <div class="col-md-6">
                        <label class="text-muted small">Brand</label>
                        <div><?= htmlspecialchars($product['brand_name'] ?? '—') ?></div>
                    </div>
                    <div class="col-md-6">
                        <label class="text-muted small">Product Type</label>
                        <div><?= htmlspecialchars($product['product_type'] ?? '—') ?></div>
                    </div>
                    <div class="col-md-6">
                        <label class="text-muted small">Size</label>
                        <div><?= htmlspecialchars($product['size'] ?? '—') ?></div>
                    </div>
                    <div class="col-md-6">
                        <label class="text-muted small">Color</label>
                        <div><?= htmlspecialchars($product['color'] ?? '—') ?></div>
                    </div>
                    <div class="col-md-6">
                        <label class="text-muted small">Origin</label>
                        <div><?= htmlspecialchars(ucfirst($product['origin'] ?? '—')) ?></div>
                    </div>
                    <div class="col-md-6">
                        <label class="text-muted small">Barcode</label>
                        <div><code><?= htmlspecialchars($product['barcode'] ?? '—') ?></code></div>
                    </div>
                    <div class="col-md-6">
                        <label class="text-muted small">Created By</label>
                        <div><?= htmlspecialchars($product['created_by_name'] ?? '—') ?></div>
                    </div>
                    <?php if (!empty($product['description'])): ?>
                    <div class="col-12">
                        <label class="text-muted small">Description</label>
                        <div><?= nl2br(htmlspecialchars($product['description'])) ?></div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-header"><strong>Pricing</strong></div>
            <div class="card-body">
                <div class="row text-center">
                    <?php if ($showPurchase): ?>
                    <div class="col-md-4">
                        <div class="text-muted small">Purchase Price</div>
                        <div class="fs-5"><?= Helpers::formatMoney($product['purchase_price']) ?></div>
                    </div>
                    <?php endif; ?>
                    <div class="col-md-4">
                        <div class="text-muted small">Sale Price</div>
                        <div class="fs-5 fw-bold text-primary"><?= Helpers::formatMoney($product['sale_price']) ?></div>
                    </div>
                    <div class="col-md-4">
                        <div class="text-muted small">Min Sale Price</div>
                        <div class="fs-5"><?= Helpers::formatMoney($product['min_sale_price']) ?></div>
                    </div>
                </div>
            </div>
        </div>

        <?php if (!empty($stockHistory)): ?>
        <div class="card">
            <div class="card-header"><strong>Recent Stock History</strong></div>
            <div class="table-responsive">
                <table class="table table-sm mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Date</th>
                            <th>Change</th>
                            <th>Before</th>
                            <th>After</th>
                            <th>Reason</th>
                            <th>By</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($stockHistory as $h): ?>
                        <tr>
                            <td><?= Helpers::formatDateTime($h['created_at']) ?></td>
                            <td class="<?= (int)$h['change_qty'] >= 0 ? 'text-success' : 'text-danger' ?>">
                                <?= (int)$h['change_qty'] >= 0 ? '+' : '' ?><?= (int) $h['change_qty'] ?>
                            </td>
                            <td><?= (int) $h['qty_before'] ?></td>
                            <td><?= (int) $h['qty_after'] ?></td>
                            <td>
                                <span class="badge bg-light text-dark"><?= htmlspecialchars($h['reference_type']) ?></span>
                                <?= htmlspecialchars($h['reason']) ?>
                            </td>
                            <td><?= htmlspecialchars($h['created_by_name'] ?? '—') ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<div class="text-muted small mt-3">
    Created: <?= Helpers::formatDateTime($product['created_at']) ?>
    <?php if ($product['updated_at'] !== $product['created_at']): ?>
    &middot; Updated: <?= Helpers::formatDateTime($product['updated_at']) ?>
    <?php endif; ?>
</div>
