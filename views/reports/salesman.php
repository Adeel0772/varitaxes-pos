<?php use Core\Helpers; ?>
<h4 class="mb-3"><?= htmlspecialchars($pageTitle) ?></h4>
<?php require __DIR__ . '/_filters.php'; ?>

<div class="row g-3 mb-4">
    <div class="col-md-4"><div class="card"><div class="card-body"><small class="text-muted">Salesmen</small><h5><?= (int) $data['summary']['salesman_count'] ?></h5></div></div></div>
    <div class="col-md-4"><div class="card"><div class="card-body"><small class="text-muted">Total Sales</small><h5><?= Helpers::formatMoney($data['summary']['total_sales']) ?></h5></div></div></div>
</div>

<div class="table-responsive">
    <table class="table table-striped table-hover">
        <thead class="table-light">
            <tr>
                <th>Salesman</th>
                <th class="text-end">Sales Count</th>
                <th class="text-end">Total Amount</th>
                <th class="text-end">Discounts</th>
                <th class="text-end">Avg Sale</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($data['rows'])): ?>
            <tr><td colspan="5" class="text-center text-muted">No sales data in this period.</td></tr>
            <?php else: foreach ($data['rows'] as $row): ?>
            <tr>
                <td><?= htmlspecialchars($row['salesman_name']) ?></td>
                <td class="text-end"><?= (int) $row['sale_count'] ?></td>
                <td class="text-end"><?= Helpers::formatMoney($row['total_sales']) ?></td>
                <td class="text-end"><?= Helpers::formatMoney($row['total_discounts']) ?></td>
                <td class="text-end"><?= Helpers::formatMoney($row['avg_sale']) ?></td>
            </tr>
            <?php endforeach; endif; ?>
        </tbody>
    </table>
</div>
