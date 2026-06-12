<?php

use Core\Auth;
use Core\Helpers;

$baseUrl = Auth::baseUrl('customers') . ($search !== '' ? '?search=' . urlencode($search) : '');
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0"><i class="bi bi-people"></i> Customers</h4>
    <?php if (Auth::can('customers', 'create')): ?>
    <a href="<?= Auth::baseUrl('customers/create') ?>" class="btn btn-primary">
        <i class="bi bi-person-plus"></i> Add Customer
    </a>
    <?php endif; ?>
</div>

<form method="GET" action="<?= Auth::baseUrl('customers') ?>" class="row g-2 mb-4">
    <div class="col-md-6">
        <input type="text" name="search" class="form-control" placeholder="Search by name or phone..."
               value="<?= htmlspecialchars($search) ?>">
    </div>
    <div class="col-auto">
        <button type="submit" class="btn btn-outline-secondary"><i class="bi bi-search"></i> Search</button>
        <?php if ($search !== ''): ?>
        <a href="<?= Auth::baseUrl('customers') ?>" class="btn btn-outline-secondary">Clear</a>
        <?php endif; ?>
    </div>
</form>

<div class="card shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead class="table-light">
                <tr>
                    <th>Name</th>
                    <th>Phone</th>
                    <th class="text-end">Balance (Udhaar)</th>
                    <th class="text-end">Credit Limit</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($customers['data'])): ?>
                <tr>
                    <td colspan="5" class="text-center text-muted py-4">No customers found.</td>
                </tr>
                <?php else: ?>
                <?php foreach ($customers['data'] as $c): ?>
                <tr>
                    <td>
                        <a href="<?= Auth::baseUrl('customers/view?id=' . (int) $c['id']) ?>" class="text-decoration-none fw-medium">
                            <?= htmlspecialchars($c['name']) ?>
                        </a>
                    </td>
                    <td><?= htmlspecialchars($c['phone'] ?? '—') ?></td>
                    <td class="text-end">
                        <?php $bal = (float) ($c['balance'] ?? 0); ?>
                        <span class="text-<?= $bal > 0 ? 'danger' : 'success' ?> fw-medium">
                            <?= Helpers::formatMoney($bal) ?>
                        </span>
                    </td>
                    <td class="text-end"><?= Helpers::formatMoney($c['credit_limit']) ?></td>
                    <td class="text-end">
                        <a href="<?= Auth::baseUrl('customers/view?id=' . (int) $c['id']) ?>" class="btn btn-sm btn-outline-primary" title="View">
                            <i class="bi bi-eye"></i>
                        </a>
                        <?php if (Auth::can('customers', 'update')): ?>
                        <a href="<?= Auth::baseUrl('customers/edit?id=' . (int) $c['id']) ?>" class="btn btn-sm btn-outline-secondary" title="Edit">
                            <i class="bi bi-pencil"></i>
                        </a>
                        <?php endif; ?>
                        <a href="<?= Auth::baseUrl('customers/statement?id=' . (int) $c['id']) ?>" class="btn btn-sm btn-outline-dark" title="Statement" target="_blank">
                            <i class="bi bi-printer"></i>
                        </a>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <?php if ($customers['last_page'] > 1): ?>
    <div class="card-footer">
        <div class="d-flex justify-content-between align-items-center">
            <small class="text-muted">Showing <?= count($customers['data']) ?> of <?= (int) $customers['total'] ?> customers</small>
            <?= Helpers::paginationHtml($customers, $baseUrl) ?>
        </div>
    </div>
    <?php endif; ?>
</div>
