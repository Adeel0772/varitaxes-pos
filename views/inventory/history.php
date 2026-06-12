<?php
use Core\Auth;
use Core\Helpers;

$baseUrl = Auth::baseUrl('inventory/history?product_id=' . (int) $productId);
?>
<div class="mb-4">
    <a href="<?= Auth::baseUrl('inventory') ?>" class="text-decoration-none"><i class="bi bi-arrow-left"></i> Back to Inventory</a>
</div>

<?php if (!$product): ?>
<div class="alert alert-warning">Product not found. <a href="<?= Auth::baseUrl('inventory') ?>">Return to inventory</a>.</div>
<?php else: ?>

<div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2">
    <div>
        <h4 class="mb-0"><i class="bi bi-clock-history"></i> Stock History</h4>
        <p class="mb-0 text-muted">
            <code><?= htmlspecialchars($product['product_code']) ?></code> —
            <?= htmlspecialchars($product['name']) ?> ·
            Current stock: <strong><?= (int) $product['qty_in_stock'] ?></strong>
        </p>
    </div>
    <a href="<?= Auth::baseUrl('products/view?id=' . (int) $product['id']) ?>" class="btn btn-outline-secondary btn-sm">View Product</a>
</div>

<div class="card shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead class="table-light">
                <tr>
                    <th>Date</th>
                    <th class="text-end">Change</th>
                    <th class="text-end">Before</th>
                    <th class="text-end">After</th>
                    <th>Type</th>
                    <th>Reason</th>
                    <th>By</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($history['data'])): ?>
                <tr><td colspan="7" class="text-center text-muted py-4">No stock movements recorded.</td></tr>
                <?php else: ?>
                <?php foreach ($history['data'] as $h): ?>
                <?php
                $change = (int) $h['change_qty'];
                $changeClass = $change > 0 ? 'text-success' : ($change < 0 ? 'text-danger' : '');
                $typeBadge = match ($h['reference_type']) {
                    'purchase'   => 'bg-primary',
                    'sale'       => 'bg-info',
                    'adjustment' => 'bg-secondary',
                    'return'     => 'bg-warning text-dark',
                    'initial'    => 'bg-success',
                    default      => 'bg-light text-dark',
                };
                ?>
                <tr>
                    <td><?= Helpers::formatDateTime($h['created_at']) ?></td>
                    <td class="text-end fw-semibold <?= $changeClass ?>"><?= $change > 0 ? '+' : '' ?><?= $change ?></td>
                    <td class="text-end"><?= (int) $h['qty_before'] ?></td>
                    <td class="text-end"><?= (int) $h['qty_after'] ?></td>
                    <td><span class="badge <?= $typeBadge ?>"><?= ucfirst($h['reference_type']) ?></span></td>
                    <td><?= htmlspecialchars($h['reason']) ?></td>
                    <td><?= htmlspecialchars($h['created_by_name'] ?? '—') ?></td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <?php if ($history['last_page'] > 1): ?>
    <div class="card-footer">
        <?= Helpers::paginationHtml($history, $baseUrl) ?>
    </div>
    <?php endif; ?>
</div>

<?php endif; ?>
