<?php use Core\Helpers; ?>
<h4 class="mb-3"><?= htmlspecialchars($pageTitle) ?></h4>
<?php require __DIR__ . '/_filters.php'; ?>

<div class="row g-3 mb-4">
    <div class="col-md-4"><div class="card"><div class="card-body"><small class="text-muted">Discounted Sales</small><h5><?= (int) $data['summary']['sale_count'] ?></h5></div></div></div>
    <div class="col-md-4"><div class="card"><div class="card-body"><small class="text-muted">Total Invoice Discounts</small><h5><?= Helpers::formatMoney($data['summary']['total_discounts']) ?></h5></div></div></div>
</div>

<div class="table-responsive">
    <table class="table table-striped table-hover">
        <thead class="table-light">
            <tr>
                <th>Invoice #</th>
                <th>Date</th>
                <th>Customer</th>
                <th>Salesman</th>
                <th class="text-end">Subtotal</th>
                <th class="text-end">Invoice Disc.</th>
                <th class="text-end">Item Disc.</th>
                <th class="text-end">Total</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($data['rows'])): ?>
            <tr><td colspan="8" class="text-center text-muted">No discounted sales in this period.</td></tr>
            <?php else: foreach ($data['rows'] as $row): ?>
            <tr>
                <td><?= htmlspecialchars($row['sale_number']) ?></td>
                <td><?= Helpers::formatDateTime($row['sale_date']) ?></td>
                <td><?= htmlspecialchars($row['customer_name'] ?? 'Walk-in') ?></td>
                <td><?= htmlspecialchars($row['salesman_name']) ?></td>
                <td class="text-end"><?= Helpers::formatMoney($row['subtotal']) ?></td>
                <td class="text-end"><?= Helpers::formatMoney($row['discount_amount']) ?> <?= $row['discount_type'] === 'percent' ? '%' : '' ?></td>
                <td class="text-end"><?= Helpers::formatMoney($row['item_discounts']) ?></td>
                <td class="text-end"><?= Helpers::formatMoney($row['total_amount']) ?></td>
            </tr>
            <?php endforeach; endif; ?>
        </tbody>
    </table>
</div>
