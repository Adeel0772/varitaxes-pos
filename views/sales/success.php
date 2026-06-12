<?php use Core\Auth; use Core\Helpers; ?>

<div class="row justify-content-center">
    <div class="col-lg-6">
        <div class="card shadow-sm border-success">
            <div class="card-body text-center py-5">
                <i class="bi bi-check-circle-fill text-success display-1"></i>
                <h3 class="mt-3">Sale Completed!</h3>
                <p class="text-muted mb-1">Sale Number</p>
                <h4 class="text-primary"><code><?= htmlspecialchars($sale['sale_number']) ?></code></h4>
                <p class="text-muted mb-1 mt-3">Sale Total</p>
                <p class="fs-2 fw-bold text-primary my-2">
                    <?= Helpers::formatMoney($sale['total_amount'], true, $currencySymbol ?? null) ?>
                </p>

                <?php if ($sale['payment_method'] === 'cash' && (float) ($sale['change_amount'] ?? 0) > 0): ?>
                <div class="alert alert-success d-inline-block mb-0">
                    Change: <strong><?= Helpers::formatMoney($sale['change_amount'], true, $currencySymbol ?? null) ?></strong>
                </div>
                <?php endif; ?>

                <div class="d-flex gap-2 justify-content-center flex-wrap mt-4">
                    <?php if (Auth::can('invoices', 'print')): ?>
                    <a href="<?= Auth::baseUrl('invoices/print?id=' . (int) $sale['id']) ?>" class="btn btn-primary btn-lg" target="_blank">
                        <i class="bi bi-printer"></i> Print Invoice
                    </a>
                    <?php endif; ?>
                    <a href="<?= Auth::baseUrl('sales/pos') ?>" class="btn btn-warning btn-lg">
                        <i class="bi bi-cart-check"></i> New Sale
                    </a>
                    <a href="<?= Auth::baseUrl('sales/view?id=' . (int) $sale['id']) ?>" class="btn btn-outline-secondary">
                        <i class="bi bi-eye"></i> View Details
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
