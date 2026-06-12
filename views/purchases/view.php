<?php
use Core\Auth;
use Core\Helpers;
?>
<div class="mb-4">
    <a href="<?= Auth::baseUrl('purchases') ?>" class="text-decoration-none"><i class="bi bi-arrow-left"></i> Back to Purchases</a>
</div>

<div class="d-flex flex-wrap justify-content-between align-items-start mb-4 gap-2">
    <div>
        <h4 class="mb-1"><i class="bi bi-truck"></i> Purchase #<?= (int) $purchase['id'] ?></h4>
        <small class="text-muted">Recorded <?= Helpers::formatDateTime($purchase['created_at']) ?> by <?= htmlspecialchars($purchase['created_by_name'] ?? '—') ?></small>
    </div>
    <?php if (!empty($canEdit)): ?>
    <a href="<?= Auth::baseUrl('purchases/edit?id=' . (int) $purchase['id']) ?>" class="btn btn-outline-primary">
        <i class="bi bi-pencil"></i> Edit (today only)
    </a>
    <?php endif; ?>
</div>

<div class="row g-4 mb-4">
    <div class="col-md-6">
        <div class="card shadow-sm h-100">
            <div class="card-header"><strong>Supplier</strong></div>
            <div class="card-body">
                <p class="mb-1 fw-semibold"><?= htmlspecialchars($purchase['supplier_name']) ?></p>
                <?php if ($purchase['supplier_phone']): ?>
                <p class="mb-1 text-muted"><i class="bi bi-telephone"></i> <?= htmlspecialchars($purchase['supplier_phone']) ?></p>
                <?php endif; ?>
                <?php if ($purchase['supplier_city']): ?>
                <p class="mb-0 text-muted"><i class="bi bi-geo-alt"></i> <?= htmlspecialchars($purchase['supplier_city']) ?></p>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card shadow-sm h-100">
            <div class="card-header"><strong>Summary</strong></div>
            <div class="card-body">
                <dl class="row mb-0">
                    <dt class="col-sm-5">Purchase Date</dt>
                    <dd class="col-sm-7"><?= Helpers::formatDate($purchase['purchase_date']) ?></dd>
                    <dt class="col-sm-5">Total Amount</dt>
                    <dd class="col-sm-7 fw-bold"><?= Helpers::formatMoney($purchase['total_amount']) ?></dd>
                    <?php if ($purchase['notes']): ?>
                    <dt class="col-sm-5">Notes</dt>
                    <dd class="col-sm-7"><?= htmlspecialchars($purchase['notes']) ?></dd>
                    <?php endif; ?>
                </dl>
            </div>
        </div>
    </div>
</div>

<div class="card shadow-sm">
    <div class="card-header"><strong>Line Items</strong></div>
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead class="table-light">
                <tr>
                    <th>Product</th>
                    <th>Code</th>
                    <th class="text-end">Qty</th>
                    <th class="text-end">Unit Price</th>
                    <th class="text-end">Line Total</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($purchase['items'] as $item): ?>
                <tr>
                    <td>
                        <a href="<?= Auth::baseUrl('products/view?id=' . (int) $item['product_id']) ?>">
                            <?= htmlspecialchars($item['product_name']) ?>
                        </a>
                    </td>
                    <td><code><?= htmlspecialchars($item['product_code']) ?></code></td>
                    <td class="text-end"><?= (int) $item['qty'] ?></td>
                    <td class="text-end"><?= Helpers::formatMoney($item['purchase_price']) ?></td>
                    <td class="text-end"><?= Helpers::formatMoney((int) $item['qty'] * (float) $item['purchase_price']) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot class="table-light">
                <tr>
                    <td colspan="4" class="text-end fw-bold">Grand Total</td>
                    <td class="text-end fw-bold"><?= Helpers::formatMoney($purchase['total_amount']) ?></td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>
