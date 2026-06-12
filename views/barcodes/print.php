<?php use Core\Auth; use Core\Helpers; ?>
<div class="no-print mb-3">
    <button type="button" onclick="window.print()">Print Labels</button>
    <a href="<?= Auth::baseUrl('barcodes') ?>">Back</a>
</div>

<div class="barcode-labels">
    <?php foreach ($labels as $label):
        $p = $label['product'];
        $code = $label['barcode'];
    ?>
    <div class="barcode-label <?= htmlspecialchars($labelSize ?? 'medium') ?>">
        <div><strong><?= htmlspecialchars($p['name']) ?></strong></div>
        <div><?= htmlspecialchars($p['product_code']) ?></div>
        <img src="<?= Auth::baseUrl('barcodes/image?code=' . urlencode($code)) ?>" alt="<?= htmlspecialchars($code) ?>" style="max-width:100%; height:auto;">
        <div><small><?= htmlspecialchars($code) ?></small></div>
        <?php if (!empty($showPrice)): ?>
        <div><strong><?= Helpers::formatMoney($p['sale_price']) ?></strong></div>
        <?php endif; ?>
    </div>
    <?php endforeach; ?>
</div>
