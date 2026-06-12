<?php

use Core\Auth;
use Core\Helpers;
?>

<div class="print-header text-center mb-4">
    <h2>Customer Statement</h2>
    <p class="mb-0">Generated on <?= date(DATETIME_FORMAT) ?></p>
</div>

<div class="mb-4">
    <table class="w-100">
        <tr>
            <td><strong>Customer:</strong> <?= htmlspecialchars($customer['name']) ?></td>
            <td class="text-end"><strong>Phone:</strong> <?= htmlspecialchars($customer['phone'] ?? '—') ?></td>
        </tr>
        <?php if ($customer['address']): ?>
        <tr>
            <td colspan="2"><strong>Address:</strong> <?= htmlspecialchars($customer['address']) ?></td>
        </tr>
        <?php endif; ?>
        <tr>
            <td><strong>Credit Limit:</strong> <?= Helpers::formatMoney($customer['credit_limit']) ?></td>
            <td class="text-end"><strong>Current Balance:</strong> <?= Helpers::formatMoney($balance) ?></td>
        </tr>
    </table>
</div>

<table class="table table-bordered table-sm">
    <thead>
        <tr>
            <th>Date</th>
            <th>Type</th>
            <th>Reference / Notes</th>
            <th class="text-end">Amount</th>
            <th class="text-end">Balance</th>
        </tr>
    </thead>
    <tbody>
        <?php if (empty($ledger)): ?>
        <tr>
            <td colspan="5" class="text-center">No transactions recorded.</td>
        </tr>
        <?php else: ?>
        <?php foreach (array_reverse($ledger) as $entry): ?>
        <tr>
            <td><?= Helpers::formatDateTime($entry['created_at']) ?></td>
            <td><?= ucfirst($entry['transaction_type']) ?></td>
            <td><?= htmlspecialchars($entry['sale_number'] ?? $entry['notes'] ?? '—') ?></td>
            <td class="text-end"><?= Helpers::formatMoney($entry['amount']) ?></td>
            <td class="text-end"><?= Helpers::formatMoney($entry['balance_after']) ?></td>
        </tr>
        <?php endforeach; ?>
        <?php endif; ?>
    </tbody>
    <tfoot>
        <tr>
            <th colspan="4" class="text-end">Outstanding Balance</th>
            <th class="text-end"><?= Helpers::formatMoney($balance) ?></th>
        </tr>
    </tfoot>
</table>

<p class="text-muted small mt-4 text-center">This is a computer-generated statement from <?= htmlspecialchars(APP_NAME) ?>.</p>
