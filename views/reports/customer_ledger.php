<?php use Core\Helpers; ?>
<h4 class="mb-3"><?= htmlspecialchars($pageTitle) ?></h4>
<?php require __DIR__ . '/_filters.php'; ?>

<div class="row g-3 mb-4">
    <div class="col-md-4"><div class="card"><div class="card-body"><small class="text-muted">Entries</small><h5><?= (int) $data['summary']['entry_count'] ?></h5></div></div></div>
    <div class="col-md-4"><div class="card"><div class="card-body"><small class="text-muted">Credit Sales</small><h5><?= Helpers::formatMoney($data['summary']['sale_total']) ?></h5></div></div></div>
    <div class="col-md-4"><div class="card"><div class="card-body"><small class="text-muted">Payments Received</small><h5><?= Helpers::formatMoney($data['summary']['payment_total']) ?></h5></div></div></div>
</div>

<div class="table-responsive">
    <table class="table table-striped table-hover">
        <thead class="table-light">
            <tr>
                <th>Date</th>
                <th>Customer</th>
                <th>Type</th>
                <th>Invoice</th>
                <th class="text-end">Amount</th>
                <th class="text-end">Balance</th>
                <th>By</th>
                <th>Notes</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($data['rows'])): ?>
            <tr><td colspan="8" class="text-center text-muted">No ledger entries in this period.</td></tr>
            <?php else: foreach ($data['rows'] as $row): ?>
            <tr>
                <td><?= Helpers::formatDateTime($row['created_at']) ?></td>
                <td><?= htmlspecialchars($row['customer_name']) ?></td>
                <td><span class="badge bg-<?= $row['transaction_type'] === 'payment' ? 'success' : ($row['transaction_type'] === 'sale' ? 'primary' : 'warning') ?>"><?= ucfirst($row['transaction_type']) ?></span></td>
                <td><?= htmlspecialchars($row['sale_number'] ?? '—') ?></td>
                <td class="text-end"><?= Helpers::formatMoney($row['amount']) ?></td>
                <td class="text-end"><?= Helpers::formatMoney($row['balance_after']) ?></td>
                <td><?= htmlspecialchars($row['created_by_name'] ?? '—') ?></td>
                <td><?= htmlspecialchars($row['notes'] ?? '') ?></td>
            </tr>
            <?php endforeach; endif; ?>
        </tbody>
    </table>
</div>
