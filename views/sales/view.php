<?php

use Core\Auth;
use Core\Helpers;
?>

<div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2">
    <div>
        <h4 class="mb-1">Sale <?= htmlspecialchars($sale['sale_number']) ?></h4>
        <span class="text-muted"><?= Helpers::formatDateTime($sale['sale_date']) ?></span>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        <a href="<?= Auth::baseUrl('sales') ?>" class="btn btn-outline-secondary"><i class="bi bi-arrow-left"></i> Back</a>
        <?php if (Auth::can('invoices', 'print')): ?>
        <a href="<?= Auth::baseUrl('invoices/print?id=' . (int) $sale['id']) ?>" class="btn btn-outline-dark" target="_blank">
            <i class="bi bi-printer"></i> Print Invoice
        </a>
        <?php endif; ?>
        <?php if (Auth::can('sales', 'create')): ?>
        <a href="<?= Auth::baseUrl('sales/pos') ?>" class="btn btn-warning"><i class="bi bi-cart-check"></i> New Sale</a>
        <?php endif; ?>
    </div>
</div>

<div class="row g-4">
    <div class="col-lg-8">
        <div class="card shadow-sm">
            <div class="card-header"><strong>Items</strong></div>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Product</th>
                            <th class="text-center">Qty</th>
                            <th class="text-end">Unit Price</th>
                            <th class="text-end">Discount</th>
                            <th class="text-end">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($sale['items'] as $item): ?>
                        <tr>
                            <td>
                                <div><?= htmlspecialchars($item['product_name_snapshot']) ?></div>
                                <small class="text-muted"><?= htmlspecialchars($item['product_code']) ?></small>
                            </td>
                            <td class="text-center"><?= (int) $item['qty'] ?></td>
                            <td class="text-end"><?= Helpers::formatMoney($item['unit_price']) ?></td>
                            <td class="text-end">
                                <?php if ((float) $item['discount_per_item'] > 0): ?>
                                <?= $item['discount_type'] === 'percent'
                                    ? (float) $item['discount_per_item'] . '%'
                                    : Helpers::formatMoney($item['discount_per_item']) ?>
                                <?php else: ?>
                                —
                                <?php endif; ?>
                            </td>
                            <td class="text-end fw-medium"><?= Helpers::formatMoney($item['final_price']) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot class="table-light">
                        <tr>
                            <th colspan="4" class="text-end">Subtotal</th>
                            <th class="text-end"><?= Helpers::formatMoney($sale['subtotal']) ?></th>
                        </tr>
                        <?php if ((float) $sale['discount_amount'] > 0): ?>
                        <tr>
                            <th colspan="4" class="text-end">Sale Discount</th>
                            <th class="text-end text-danger">-<?= Helpers::formatMoney($sale['discount_amount']) ?></th>
                        </tr>
                        <?php endif; ?>
                        <tr>
                            <th colspan="4" class="text-end">Total</th>
                            <th class="text-end fs-5"><?= Helpers::formatMoney($sale['total_amount']) ?></th>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card shadow-sm mb-4">
            <div class="card-header"><strong>Sale Info</strong></div>
            <div class="card-body">
                <dl class="row mb-0">
                    <dt class="col-5">Salesman</dt>
                    <dd class="col-7"><?= htmlspecialchars($sale['salesman_name']) ?></dd>
                    <dt class="col-5">Customer</dt>
                    <dd class="col-7">
                        <?php if ($sale['customer_name']): ?>
                        <?= htmlspecialchars($sale['customer_name']) ?>
                        <?php if ($sale['customer_phone']): ?>
                        <br><small class="text-muted"><?= htmlspecialchars($sale['customer_phone']) ?></small>
                        <?php endif; ?>
                        <?php else: ?>
                        Walk-in
                        <?php endif; ?>
                    </dd>
                    <dt class="col-5">Payment</dt>
                    <dd class="col-7"><span class="badge bg-primary"><?= ucfirst($sale['payment_method']) ?></span></dd>
                    <?php if ($sale['payment_method'] === 'cash' && $sale['amount_tendered'] !== null): ?>
                    <dt class="col-5">Tendered</dt>
                    <dd class="col-7"><?= Helpers::formatMoney($sale['amount_tendered']) ?></dd>
                    <dt class="col-5">Change</dt>
                    <dd class="col-7"><?= Helpers::formatMoney($sale['change_amount'] ?? 0) ?></dd>
                    <?php endif; ?>
                    <?php if ($sale['notes']): ?>
                    <dt class="col-5">Notes</dt>
                    <dd class="col-7"><?= htmlspecialchars($sale['notes']) ?></dd>
                    <?php endif; ?>
                </dl>
            </div>
        </div>

        <div class="card shadow-sm">
            <div class="card-header"><strong>Shop</strong></div>
            <div class="card-body">
                <strong><?= htmlspecialchars($sale['shop_name']) ?></strong>
                <?php if ($sale['shop_phone']): ?>
                <br><small class="text-muted"><?= htmlspecialchars($sale['shop_phone']) ?></small>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
