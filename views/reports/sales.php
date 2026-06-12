<?php use Core\Auth; use Core\Helpers; ?>
<h4 class="mb-3"><?= htmlspecialchars($pageTitle) ?></h4>
<?php require __DIR__ . '/_filters.php'; ?>

<div class="row g-3 mb-4">
    <div class="col-md-3"><div class="card"><div class="card-body"><small class="text-muted">Sales</small><h5><?= (int) $data['summary']['sale_count'] ?></h5></div></div></div>
    <div class="col-md-3"><div class="card"><div class="card-body"><small class="text-muted">Total Amount</small><h5><?= Helpers::formatMoney($data['summary']['total_amount']) ?></h5></div></div></div>
    <div class="col-md-3"><div class="card"><div class="card-body"><small class="text-muted">Total Discount</small><h5><?= Helpers::formatMoney($data['summary']['total_discount']) ?></h5></div></div></div>
</div>

<div class="table-responsive">
    <table class="table table-striped table-hover">
        <thead class="table-light">
            <tr>
                <th>Invoice #</th>
                <th>Date</th>
                <th>Customer</th>
                <th>Salesman</th>
                <th>Payment</th>
                <th class="text-end">Subtotal</th>
                <th class="text-end">Discount</th>
                <th class="text-end">Total</th>
                <th>Printed</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($data['rows'])): ?>
            <tr><td colspan="9" class="text-center text-muted">No sales in this period.</td></tr>
            <?php else: foreach ($data['rows'] as $row): ?>
            <tr>
                <td><a href="<?= Auth::baseUrl('sales/view?id=' . (int) $row['id']) ?>"><?= htmlspecialchars($row['sale_number']) ?></a></td>
                <td><?= Helpers::formatDateTime($row['sale_date']) ?></td>
                <td><?= htmlspecialchars($row['customer_name'] ?? 'Walk-in') ?></td>
                <td><?= htmlspecialchars($row['salesman_name']) ?></td>
                <td><?= htmlspecialchars(ucfirst($row['payment_method'])) ?></td>
                <td class="text-end"><?= Helpers::formatMoney($row['subtotal']) ?></td>
                <td class="text-end"><?= Helpers::formatMoney($row['discount_amount']) ?></td>
                <td class="text-end"><?= Helpers::formatMoney($row['total_amount']) ?></td>
                <td><?= $row['invoice_printed'] ? '<span class="badge bg-success">Yes</span>' : '<span class="badge bg-secondary">No</span>' ?></td>
            </tr>
            <?php endforeach; endif; ?>
        </tbody>
    </table>
</div>
