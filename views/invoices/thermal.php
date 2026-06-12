<?php
use Core\Auth;
use Core\Helpers;

$sale = $invoice['sale'];
$items = $invoice['items'];
$shop = $invoice['shop'];
$customer = $invoice['customer'];
$salesman = $invoice['salesman'];
$settings = $invoice['settings'];
$currency = Helpers::sanitizeCurrencySymbol($settings['currency_symbol'] ?? null);
$header = $settings['invoice_header'] ?? ($shop['name'] ?? APP_NAME);
$footer = $settings['invoice_footer'] ?? '';
$shopPhone = $settings['shop_phone'] ?? ($shop['phone'] ?? '');
$forPdf = $forPdf ?? false;
?>
<div class="invoice-thermal">
    <div class="no-print mb-3">
        <button type="button" onclick="window.print()">Print</button>
        <a href="<?= Auth::baseUrl('invoices/pdf?sale_id=' . (int) $sale['id']) ?>">PDF</a>
    </div>

    <div class="text-center">
        <strong><?= htmlspecialchars($header) ?></strong><br>
        <?php if (!empty($shop['address'])): ?><?= htmlspecialchars($shop['address']) ?><br><?php endif; ?>
        <?php if ($shopPhone): ?>Tel: <?= htmlspecialchars($shopPhone) ?><br><?php endif; ?>
    </div>
    <hr>
    <div>
        Inv: <?= htmlspecialchars($sale['sale_number']) ?><br>
        Date: <?= Helpers::formatDateTime($sale['sale_date']) ?><br>
        Cashier: <?= htmlspecialchars($salesman['name']) ?><br>
        <?php if ($customer): ?>
        Customer: <?= htmlspecialchars($customer['name']) ?><br>
        <?php if (!empty($customer['phone'])): ?>Ph: <?= htmlspecialchars($customer['phone']) ?><br><?php endif; ?>
        <?php endif; ?>
        Pay: <?= htmlspecialchars(ucfirst($sale['payment_method'])) ?>
    </div>
    <hr>
    <table>
        <thead>
            <tr>
                <th>Item</th>
                <th class="text-center">Qty</th>
                <th class="text-right">Amt</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($items as $item): ?>
            <tr>
                <td>
                    <?= htmlspecialchars($item['product_name_snapshot']) ?>
                    <br><small><?= htmlspecialchars($item['product_code']) ?></small>
                </td>
                <td class="text-center"><?= (int) $item['qty'] ?></td>
                <td class="text-right"><?= number_format((float) $item['final_price'], 2) ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <hr>
    <div class="text-right">
        Subtotal: <?= number_format((float) $sale['subtotal'], 2) ?><br>
        <?php if ((float) $sale['discount_amount'] > 0): ?>
        Discount: -<?= number_format((float) $sale['discount_amount'], 2) ?><br>
        <?php endif; ?>
        <strong>TOTAL: <?= $currency ?> <?= number_format((float) $sale['total_amount'], 2) ?></strong><br>
        <?php if ($sale['payment_method'] === 'cash' && $sale['amount_tendered'] !== null): ?>
        Tendered: <?= number_format((float) $sale['amount_tendered'], 2) ?><br>
        Change: <?= number_format((float) ($sale['change_amount'] ?? 0), 2) ?><br>
        <?php endif; ?>
    </div>
    <hr>
    <?php if ($footer): ?>
    <div class="text-center"><small><?= htmlspecialchars($footer) ?></small></div>
    <?php endif; ?>
    <div class="text-center"><small>Thank you!</small></div>
</div>
