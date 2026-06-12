<?php

use Core\Auth;
use Core\Helpers;
?>

<div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2">
    <div>
        <h4 class="mb-1"><?= htmlspecialchars($customer['name']) ?></h4>
        <?php if ($customer['phone']): ?>
        <span class="text-muted"><i class="bi bi-telephone"></i> <?= htmlspecialchars($customer['phone']) ?></span>
        <?php endif; ?>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        <a href="<?= Auth::baseUrl('customers') ?>" class="btn btn-outline-secondary"><i class="bi bi-arrow-left"></i> Back</a>
        <?php if (Auth::can('customers', 'update')): ?>
        <a href="<?= Auth::baseUrl('customers/edit?id=' . (int) $customer['id']) ?>" class="btn btn-primary"><i class="bi bi-pencil"></i> Edit</a>
        <?php endif; ?>
        <a href="<?= Auth::baseUrl('customers/statement?id=' . (int) $customer['id']) ?>" class="btn btn-outline-dark" target="_blank">
            <i class="bi bi-printer"></i> Print Statement
        </a>
        <?php if (Auth::can('customers', 'delete')): ?>
        <form method="POST" action="<?= Auth::baseUrl('customers/delete') ?>" class="d-inline" data-confirm="Delete this customer?">
            <?= Auth::csrfField() ?>
            <input type="hidden" name="id" value="<?= (int) $customer['id'] ?>">
            <button type="submit" class="btn btn-outline-danger"><i class="bi bi-trash"></i> Delete</button>
        </form>
        <?php endif; ?>
    </div>
</div>

<div class="row g-4">
    <div class="col-lg-4">
        <div class="card shadow-sm">
            <div class="card-header"><strong>Account Summary</strong></div>
            <div class="card-body">
                <div class="d-flex justify-content-between mb-3">
                    <span>Outstanding Balance</span>
                    <strong class="text-<?= $balance > 0 ? 'danger' : 'success' ?> fs-5"><?= Helpers::formatMoney($balance) ?></strong>
                </div>
                <div class="d-flex justify-content-between mb-3">
                    <span>Credit Limit</span>
                    <span><?= Helpers::formatMoney($customer['credit_limit']) ?></span>
                </div>
                <div class="d-flex justify-content-between">
                    <span>Available Credit</span>
                    <span><?= Helpers::formatMoney(max(0, (float) $customer['credit_limit'] - $balance)) ?></span>
                </div>
            </div>
        </div>

        <?php if (Auth::can('customers', 'update') && $balance > 0): ?>
        <div class="card shadow-sm mt-4">
            <div class="card-header"><strong>Record Payment</strong></div>
            <div class="card-body">
                <form method="POST" action="<?= Auth::baseUrl('customers/payment') ?>">
                    <?= Auth::csrfField() ?>
                    <input type="hidden" name="customer_id" value="<?= (int) $customer['id'] ?>">
                    <div class="mb-3">
                        <label class="form-label">Amount *</label>
                        <input type="number" name="amount" class="form-control" step="0.01" min="0.01"
                               max="<?= htmlspecialchars((string) $balance) ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Notes</label>
                        <input type="text" name="notes" class="form-control" placeholder="Optional note">
                    </div>
                    <button type="submit" class="btn btn-success w-100"><i class="bi bi-cash"></i> Record Payment</button>
                </form>
            </div>
        </div>
        <?php endif; ?>

        <div class="card shadow-sm mt-4">
            <div class="card-header"><strong>Details</strong></div>
            <div class="card-body">
                <?php if ($customer['address']): ?>
                <p class="mb-2"><strong>Address:</strong><br><?= nl2br(htmlspecialchars($customer['address'])) ?></p>
                <?php endif; ?>
                <?php if ($customer['notes']): ?>
                <p class="mb-0"><strong>Notes:</strong><br><?= nl2br(htmlspecialchars($customer['notes'])) ?></p>
                <?php endif; ?>
                <?php if (!$customer['address'] && !$customer['notes']): ?>
                <p class="text-muted mb-0">No additional details.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="col-lg-8">
        <div class="card shadow-sm">
            <div class="card-header"><strong>Ledger (Khata)</strong></div>
            <div class="table-responsive">
                <table class="table table-sm table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Date</th>
                            <th>Type</th>
                            <th>Reference</th>
                            <th class="text-end">Amount</th>
                            <th class="text-end">Balance</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($ledger)): ?>
                        <tr>
                            <td colspan="5" class="text-center text-muted py-4">No ledger entries yet.</td>
                        </tr>
                        <?php else: ?>
                        <?php foreach ($ledger as $entry): ?>
                        <tr>
                            <td><?= Helpers::formatDateTime($entry['created_at']) ?></td>
                            <td>
                                <span class="badge bg-<?= $entry['transaction_type'] === 'payment' ? 'success' : ($entry['transaction_type'] === 'sale' ? 'danger' : 'info') ?>">
                                    <?= ucfirst($entry['transaction_type']) ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($entry['sale_number']): ?>
                                <a href="<?= Auth::baseUrl('sales/view?id=' . (int) $entry['sale_id']) ?>"><?= htmlspecialchars($entry['sale_number']) ?></a>
                                <?php else: ?>
                                <?= htmlspecialchars($entry['notes'] ?? '—') ?>
                                <?php endif; ?>
                            </td>
                            <td class="text-end"><?= Helpers::formatMoney($entry['amount']) ?></td>
                            <td class="text-end fw-medium"><?= Helpers::formatMoney($entry['balance_after']) ?></td>
                        </tr>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
