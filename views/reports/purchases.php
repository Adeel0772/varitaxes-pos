<?php use Core\Auth; use Core\Helpers; ?>
<h4 class="mb-3"><?= htmlspecialchars($pageTitle) ?></h4>
<?php require __DIR__ . '/_filters.php'; ?>

<div class="row g-3 mb-4">
    <div class="col-md-4"><div class="card"><div class="card-body"><small class="text-muted">Purchases</small><h5><?= (int) $data['summary']['purchase_count'] ?></h5></div></div></div>
    <div class="col-md-4"><div class="card"><div class="card-body"><small class="text-muted">Total Amount</small><h5><?= Helpers::formatMoney($data['summary']['total_amount']) ?></h5></div></div></div>
</div>

<div class="table-responsive">
    <table class="table table-striped table-hover">
        <thead class="table-light">
            <tr>
                <th>Date</th>
                <th>Supplier</th>
                <th class="text-end">Items</th>
                <th class="text-end">Total</th>
                <th>Created By</th>
                <th>Notes</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($data['rows'])): ?>
            <tr><td colspan="6" class="text-center text-muted">No purchases in this period.</td></tr>
            <?php else: foreach ($data['rows'] as $row): ?>
            <tr>
                <td><?= Helpers::formatDate($row['purchase_date']) ?></td>
                <td><?= htmlspecialchars($row['supplier_name']) ?></td>
                <td class="text-end"><?= (int) $row['item_count'] ?></td>
                <td class="text-end"><?= Helpers::formatMoney($row['total_amount']) ?></td>
                <td><?= htmlspecialchars($row['created_by_name'] ?? '—') ?></td>
                <td><?= htmlspecialchars($row['notes'] ?? '') ?></td>
            </tr>
            <?php endforeach; endif; ?>
        </tbody>
    </table>
</div>
