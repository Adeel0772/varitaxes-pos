<?php
use Core\Auth;
use Core\Helpers;

$sale = $invoice['sale'];
$items = $invoice['items'];
$shop = $invoice['shop'];
$customer = $invoice['customer'];
$salesman = $invoice['salesman'];
$settings = $invoice['settings'];
$currency = $settings['currency_symbol'] ?? CURRENCY_SYMBOL;
$header = $settings['invoice_header'] ?? ($shop['name'] ?? APP_NAME);
$footer = $settings['invoice_footer'] ?? '';
$shopPhone = $settings['shop_phone'] ?? ($shop['phone'] ?? '');
$logoUrl = !empty($shop['logo']) ? Helpers::shopLogoUrl() : '';
$forPdf = $forPdf ?? false;
?>
<div class="invoice-a4">
    <div class="no-print mb-3">
        <button type="button" class="btn btn-primary" onclick="window.print()"><i class="bi bi-printer"></i> Print</button>
        <a href="<?= Auth::baseUrl('invoices/pdf?sale_id=' . (int) $sale['id']) ?>" class="btn btn-outline-secondary">Download PDF</a>
        <a href="<?= Auth::baseUrl('sales/view?id=' . (int) $sale['id']) ?>" class="btn btn-outline-secondary">Back to Sale</a>
    </div>

    <table style="border:none; margin-bottom:15px;">
        <tr>
            <td style="border:none; width:60%; vertical-align:top;">
                <?php if ($logoUrl && !$forPdf): ?>
                <img src="<?= htmlspecialchars($logoUrl) ?>" alt="Logo" style="max-height:60px; margin-bottom:8px;">
                <?php elseif ($logoUrl && $forPdf): ?>
                <?php
                $logoPath = dirname(__DIR__, 2) . '/uploads/' . ltrim($shop['logo'], '/');
                if (file_exists($logoPath)):
                ?>
                <img src="<?= htmlspecialchars($logoPath) ?>" alt="Logo" style="max-height:60px; margin-bottom:8px;">
                <?php endif; ?>
                <?php endif; ?>
                <h2 style="margin:0 0 5px;"><?= htmlspecialchars($header) ?></h2>
                <?php if (!empty($shop['address'])): ?>
                <div><?= htmlspecialchars($shop['address']) ?></div>
                <?php endif; ?>
                <?php if (!empty($shop['city'])): ?>
                <div><?= htmlspecialchars($shop['city']) ?></div>
                <?php endif; ?>
                <?php if ($shopPhone): ?>
                <div>Tel: <?= htmlspecialchars($shopPhone) ?></div>
                <?php endif; ?>
            </td>
            <td style="border:none; width:40%; text-align:right; vertical-align:top;">
                <h3 style="margin:0 0 10px;">INVOICE</h3>
                <div><strong>Invoice #:</strong> <?= htmlspecialchars($sale['sale_number']) ?></div>
                <div><strong>Date:</strong> <?= Helpers::formatDateTime($sale['sale_date']) ?></div>
                <div><strong>Payment:</strong> <?= htmlspecialchars(ucfirst($sale['payment_method'])) ?></div>
            </td>
        </tr>
    </table>

    <table style="border:none; margin-bottom:15px;">
        <tr>
            <td style="border:none; width:50%; vertical-align:top;">
                <strong>Customer</strong><br>
                <?php if ($customer): ?>
                <?= htmlspecialchars($customer['name']) ?><br>
                <?php if (!empty($customer['phone'])): ?>Tel: <?= htmlspecialchars($customer['phone']) ?><br><?php endif; ?>
                <?php if (!empty($customer['address'])): ?><?= htmlspecialchars($customer['address']) ?><?php endif; ?>
                <?php else: ?>
                Walk-in Customer
                <?php endif; ?>
            </td>
            <td style="border:none; width:50%; vertical-align:top;">
                <strong>Salesman</strong><br>
                <?= htmlspecialchars($salesman['name']) ?>
                <?php if (!empty($salesman['phone'])): ?><br>Tel: <?= htmlspecialchars($salesman['phone']) ?><?php endif; ?>
            </td>
        </tr>
    </table>

    <table>
        <thead>
            <tr>
                <th style="width:5%;">#</th>
                <th style="width:12%;">Code</th>
                <th>Description</th>
                <th style="width:8%; text-align:center;">Qty</th>
                <th style="width:12%; text-align:right;">Unit Price</th>
                <th style="width:10%; text-align:right;">Discount</th>
                <th style="width:12%; text-align:right;">Amount</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($items as $i => $item): ?>
            <tr>
                <td><?= $i + 1 ?></td>
                <td><?= htmlspecialchars($item['product_code']) ?></td>
                <td>
                    <?= htmlspecialchars($item['product_name_snapshot']) ?>
                    <?php if (!empty($settings['show_barcode_on_invoice']) && !empty($item['barcode'])): ?>
                    <br><small>Barcode: <?= htmlspecialchars($item['barcode']) ?></small>
                    <?php endif; ?>
                </td>
                <td style="text-align:center;"><?= (int) $item['qty'] ?></td>
                <td style="text-align:right;"><?= Helpers::formatMoney($item['unit_price'], false) ?></td>
                <td style="text-align:right;">
                    <?php if ((float) $item['discount_per_item'] > 0): ?>
                    <?= Helpers::formatMoney($item['discount_per_item'], false) ?>
                    <?= $item['discount_type'] === 'percent' ? '%' : '' ?>
                    <?php else: ?>—<?php endif; ?>
                </td>
                <td style="text-align:right;"><?= Helpers::formatMoney($item['final_price'], false) ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
        <tfoot>
            <tr>
                <td colspan="6" style="text-align:right; border:none;"><strong>Subtotal</strong></td>
                <td style="text-align:right;"><?= Helpers::formatMoney($sale['subtotal'], false) ?></td>
            </tr>
            <?php if ((float) $sale['discount_amount'] > 0): ?>
            <tr>
                <td colspan="6" style="text-align:right; border:none;">
                    <strong>Discount<?= $sale['discount_type'] === 'percent' ? ' (' . (float) $sale['discount_amount'] . '%)' : '' ?></strong>
                </td>
                <td style="text-align:right;">-<?= Helpers::formatMoney($sale['discount_amount'], false) ?></td>
            </tr>
            <?php endif; ?>
            <tr>
                <td colspan="6" style="text-align:right; border:none;"><strong>Total</strong></td>
                <td style="text-align:right;"><strong><?= $currency ?> <?= number_format((float) $sale['total_amount'], 2) ?></strong></td>
            </tr>
            <?php if ($sale['payment_method'] === 'cash' && $sale['amount_tendered'] !== null): ?>
            <tr>
                <td colspan="6" style="text-align:right; border:none;">Amount Tendered</td>
                <td style="text-align:right;"><?= Helpers::formatMoney($sale['amount_tendered'], false) ?></td>
            </tr>
            <tr>
                <td colspan="6" style="text-align:right; border:none;">Change</td>
                <td style="text-align:right;"><?= Helpers::formatMoney($sale['change_amount'] ?? 0, false) ?></td>
            </tr>
            <?php endif; ?>
        </tfoot>
    </table>

    <?php if (!empty($sale['notes'])): ?>
    <p style="margin-top:15px;"><strong>Notes:</strong> <?= htmlspecialchars($sale['notes']) ?></p>
    <?php endif; ?>

    <?php if ($footer): ?>
    <p style="margin-top:20px; text-align:center; font-size:11px; color:#666;"><?= htmlspecialchars($footer) ?></p>
    <?php endif; ?>
</div>
