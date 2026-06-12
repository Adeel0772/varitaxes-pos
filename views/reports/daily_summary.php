<?php use Core\Helpers; ?>
<h4 class="mb-3"><?= htmlspecialchars($pageTitle) ?></h4>
<?php require __DIR__ . '/_filters.php'; ?>

<div class="row g-3 mb-4">
    <div class="col-md-4"><div class="card"><div class="card-body"><small class="text-muted">Total Sales</small><h5><?= (int) $data['summary']['sale_count'] ?></h5></div></div></div>
    <div class="col-md-4"><div class="card"><div class="card-body"><small class="text-muted">Total Amount</small><h5><?= Helpers::formatMoney($data['summary']['total_amount']) ?></h5></div></div></div>
</div>

<div class="table-responsive">
    <table class="table table-striped table-hover">
        <thead class="table-light">
            <tr>
                <th>Date</th>
                <th class="text-end">Sales Count</th>
                <th class="text-end">Subtotal</th>
                <th class="text-end">Discounts</th>
                <th class="text-end">Total</th>
                <th class="text-end">Cash</th>
                <th class="text-end">Credit</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($data['rows'])): ?>
            <tr><td colspan="7" class="text-center text-muted">No data for this period.</td></tr>
            <?php else: foreach ($data['rows'] as $row): ?>
            <tr>
                <td><?= Helpers::formatDate($row['sale_day']) ?></td>
                <td class="text-end"><?= (int) $row['sale_count'] ?></td>
                <td class="text-end"><?= Helpers::formatMoney($row['subtotal']) ?></td>
                <td class="text-end"><?= Helpers::formatMoney($row['discount_total']) ?></td>
                <td class="text-end"><?= Helpers::formatMoney($row['total_amount']) ?></td>
                <td class="text-end"><?= Helpers::formatMoney($row['cash_total']) ?></td>
                <td class="text-end"><?= Helpers::formatMoney($row['credit_total']) ?></td>
            </tr>
            <?php endforeach; endif; ?>
        </tbody>
    </table>
</div>
