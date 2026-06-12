<?php use Core\Auth; use Core\Helpers; ?>
<h4 class="mb-3"><i class="bi bi-journal-text"></i> Activity Log</h4>

<div class="card mb-4">
    <div class="card-body">
        <form method="get" action="<?= Auth::baseUrl('activity-log') ?>" class="row g-3 align-items-end">
            <div class="col-md-2">
                <label class="form-label">From (DD-MM-YYYY)</label>
                <input type="text" name="date_from" class="form-control" value="<?= htmlspecialchars($filters['date_from_display']) ?>" pattern="\d{2}-\d{2}-\d{4}">
            </div>
            <div class="col-md-2">
                <label class="form-label">To (DD-MM-YYYY)</label>
                <input type="text" name="date_to" class="form-control" value="<?= htmlspecialchars($filters['date_to_display']) ?>" pattern="\d{2}-\d{2}-\d{4}">
            </div>
            <div class="col-md-2">
                <label class="form-label">Module</label>
                <input type="text" name="module" class="form-control" value="<?= htmlspecialchars($module ?? '') ?>" placeholder="e.g. sales">
            </div>
            <div class="col-md-2">
                <label class="form-label">User</label>
                <select name="user_id" class="form-select">
                    <option value="">All</option>
                    <?php foreach ($filterOptions['users'] ?? [] as $u): ?>
                    <option value="<?= (int) $u['id'] ?>" <?= ($userId ?? 0) == $u['id'] ? 'selected' : '' ?>><?= htmlspecialchars($u['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-auto">
                <button type="submit" class="btn btn-primary"><i class="bi bi-funnel"></i> Filter</button>
            </div>
        </form>
    </div>
</div>

<div class="mb-3 text-muted"><?= (int) $data['summary']['entry_count'] ?> entries (max 500 shown)</div>

<div class="table-responsive">
    <table class="table table-striped table-hover">
        <thead class="table-light">
            <tr>
                <th>Date/Time</th>
                <th>User</th>
                <th>Action</th>
                <th>Module</th>
                <th>Record ID</th>
                <th>Details</th>
                <th>IP Address</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($data['rows'])): ?>
            <tr><td colspan="7" class="text-center text-muted py-4">No activity found for the selected filters.</td></tr>
            <?php else: foreach ($data['rows'] as $row): ?>
            <tr>
                <td><?= Helpers::formatDateTime($row['created_at']) ?></td>
                <td><?= htmlspecialchars($row['user_name'] ?? 'System') ?></td>
                <td><span class="badge bg-secondary"><?= htmlspecialchars($row['action']) ?></span></td>
                <td><?= htmlspecialchars($row['module']) ?></td>
                <td><?= $row['record_id'] ? (int) $row['record_id'] : '—' ?></td>
                <td><?= htmlspecialchars($row['details'] ?? '') ?></td>
                <td><small class="text-muted"><?= htmlspecialchars($row['ip_address'] ?? '') ?></small></td>
            </tr>
            <?php endforeach; endif; ?>
        </tbody>
    </table>
</div>
