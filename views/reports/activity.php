<?php use Core\Helpers; ?>
<h4 class="mb-3"><?= htmlspecialchars($pageTitle) ?></h4>
<?php require __DIR__ . '/_filters.php'; ?>

<div class="mb-3 text-muted"><?= (int) $data['summary']['entry_count'] ?> entries (max 500 shown)</div>

<div class="table-responsive">
    <table class="table table-striped table-hover table-sm">
        <thead class="table-light">
            <tr>
                <th>Date/Time</th>
                <th>User</th>
                <th>Action</th>
                <th>Module</th>
                <th>Record</th>
                <th>Details</th>
                <th>IP</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($data['rows'])): ?>
            <tr><td colspan="7" class="text-center text-muted">No activity in this period.</td></tr>
            <?php else: foreach ($data['rows'] as $row): ?>
            <tr>
                <td><?= Helpers::formatDateTime($row['created_at']) ?></td>
                <td><?= htmlspecialchars($row['user_name'] ?? 'System') ?></td>
                <td><span class="badge bg-secondary"><?= htmlspecialchars($row['action']) ?></span></td>
                <td><?= htmlspecialchars($row['module']) ?></td>
                <td><?= $row['record_id'] ? (int) $row['record_id'] : '—' ?></td>
                <td><?= htmlspecialchars($row['details'] ?? '') ?></td>
                <td><small><?= htmlspecialchars($row['ip_address'] ?? '') ?></small></td>
            </tr>
            <?php endforeach; endif; ?>
        </tbody>
    </table>
</div>
