<?php
use Core\Auth;
use Core\Helpers;

$reportKey = $reportKey ?? '';
$showDateFilter = $showDateFilter ?? true;
$enabledFilters = $enabledFilters ?? [];
$filterOptions = $filterOptions ?? [];
$extraFilters = $extraFilters ?? [];
$categoryId = $categoryId ?? ($extraFilters['category_id'] ?? null);
?>
<div class="card mb-4">
    <div class="card-body">
        <form method="get" action="" class="row g-3 align-items-end">
            <?php if ($showDateFilter): ?>
            <div class="col-md-2">
                <label class="form-label">From (DD-MM-YYYY)</label>
                <input type="text" name="date_from" class="form-control" placeholder="01-06-2026"
                       value="<?= htmlspecialchars($filters['date_from_display'] ?? '') ?>" pattern="\d{2}-\d{2}-\d{4}">
            </div>
            <div class="col-md-2">
                <label class="form-label">To (DD-MM-YYYY)</label>
                <input type="text" name="date_to" class="form-control" placeholder="12-06-2026"
                       value="<?= htmlspecialchars($filters['date_to_display'] ?? '') ?>" pattern="\d{2}-\d{2}-\d{4}">
            </div>
            <?php endif; ?>

            <?php if (in_array('salesman', $enabledFilters, true)): ?>
            <div class="col-md-2">
                <label class="form-label">Salesman</label>
                <select name="salesman_id" class="form-select">
                    <option value="">All</option>
                    <?php foreach ($filterOptions['salesmen'] ?? [] as $s): ?>
                    <option value="<?= (int) $s['id'] ?>" <?= ($extraFilters['salesman_id'] ?? 0) == $s['id'] ? 'selected' : '' ?>><?= htmlspecialchars($s['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php endif; ?>

            <?php if (in_array('customer', $enabledFilters, true)): ?>
            <div class="col-md-2">
                <label class="form-label">Customer</label>
                <select name="customer_id" class="form-select">
                    <option value="">All</option>
                    <?php foreach ($filterOptions['customers'] ?? [] as $c): ?>
                    <option value="<?= (int) $c['id'] ?>" <?= ($extraFilters['customer_id'] ?? 0) == $c['id'] ? 'selected' : '' ?>><?= htmlspecialchars($c['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php endif; ?>

            <?php if (in_array('supplier', $enabledFilters, true)): ?>
            <div class="col-md-2">
                <label class="form-label">Supplier</label>
                <select name="supplier_id" class="form-select">
                    <option value="">All</option>
                    <?php foreach ($filterOptions['suppliers'] ?? [] as $s): ?>
                    <option value="<?= (int) $s['id'] ?>" <?= ($extraFilters['supplier_id'] ?? 0) == $s['id'] ? 'selected' : '' ?>><?= htmlspecialchars($s['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php endif; ?>

            <?php if (in_array('category', $enabledFilters, true) || $reportKey === 'inventory'): ?>
            <div class="col-md-2">
                <label class="form-label">Category</label>
                <select name="category_id" class="form-select">
                    <option value="">All</option>
                    <?php foreach ($filterOptions['categories'] ?? [] as $c): ?>
                    <option value="<?= (int) $c['id'] ?>" <?= ($categoryId ?? 0) == $c['id'] ? 'selected' : '' ?>><?= htmlspecialchars($c['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php endif; ?>

            <?php if (in_array('payment', $enabledFilters, true)): ?>
            <div class="col-md-2">
                <label class="form-label">Payment</label>
                <select name="payment_method" class="form-select">
                    <option value="">All</option>
                    <?php foreach (['cash','jazzcash','easypaisa','credit','other'] as $pm): ?>
                    <option value="<?= $pm ?>" <?= ($extraFilters['payment_method'] ?? '') === $pm ? 'selected' : '' ?>><?= ucfirst($pm) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php endif; ?>

            <?php if (in_array('module', $enabledFilters, true)): ?>
            <div class="col-md-2">
                <label class="form-label">Module</label>
                <input type="text" name="module" class="form-control" value="<?= htmlspecialchars($extraFilters['module'] ?? '') ?>" placeholder="e.g. sales">
            </div>
            <?php endif; ?>

            <?php if (in_array('user', $enabledFilters, true)): ?>
            <div class="col-md-2">
                <label class="form-label">User</label>
                <select name="user_id" class="form-select">
                    <option value="">All</option>
                    <?php foreach ($filterOptions['users'] ?? [] as $u): ?>
                    <option value="<?= (int) $u['id'] ?>" <?= ($extraFilters['user_id'] ?? 0) == $u['id'] ? 'selected' : '' ?>><?= htmlspecialchars($u['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php endif; ?>

            <div class="col-md-auto">
                <button type="submit" class="btn btn-primary"><i class="bi bi-funnel"></i> Apply</button>
                <a href="<?= Auth::baseUrl('reports') ?>" class="btn btn-outline-secondary">All Reports</a>
            </div>
        </form>
    </div>
</div>

<?php if (Auth::can('reports', 'export') && $reportKey): ?>
<?php
$exportQuery = http_build_query(array_filter(array_merge(
    ['report' => $reportKey],
    $showDateFilter ? ['date_from' => $filters['date_from_display'] ?? '', 'date_to' => $filters['date_to_display'] ?? ''] : [],
    array_filter([
        'salesman_id'    => $extraFilters['salesman_id'] ?? '',
        'customer_id'    => $extraFilters['customer_id'] ?? '',
        'supplier_id'    => $extraFilters['supplier_id'] ?? '',
        'category_id'    => $categoryId ?? ($extraFilters['category_id'] ?? ''),
        'payment_method' => $extraFilters['payment_method'] ?? '',
        'module'         => $extraFilters['module'] ?? '',
        'user_id'        => $extraFilters['user_id'] ?? '',
    ])
)));
?>
<div class="mb-3">
    <a href="<?= Auth::baseUrl('reports/export?' . $exportQuery . '&format=csv') ?>" class="btn btn-outline-success btn-sm"><i class="bi bi-filetype-csv"></i> Export CSV</a>
    <a href="<?= Auth::baseUrl('reports/export?' . $exportQuery . '&format=pdf') ?>" class="btn btn-outline-danger btn-sm"><i class="bi bi-filetype-pdf"></i> Export PDF</a>
</div>
<?php endif; ?>
