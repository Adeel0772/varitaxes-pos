<?php use Core\Auth; ?>
<div class="mb-4">
    <a href="<?= Auth::baseUrl('users') ?>" class="text-decoration-none"><i class="bi bi-arrow-left"></i> Back to Users</a>
</div>

<h4 class="mb-4"><i class="bi bi-person-plus"></i> Add User</h4>

<?php if (!empty($errors)): ?>
<div class="alert alert-danger">
    <ul class="mb-0">
        <?php foreach ($errors as $e): ?>
        <li><?= htmlspecialchars($e) ?></li>
        <?php endforeach; ?>
    </ul>
</div>
<?php endif; ?>

<div class="card shadow-sm">
    <div class="card-body">
        <form method="POST" action="<?= Auth::baseUrl('users/store') ?>">
            <?= Auth::csrfField() ?>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Full Name *</label>
                    <input type="text" name="name" class="form-control" required
                           value="<?= htmlspecialchars($old['name'] ?? '') ?>">
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Email (Login) *</label>
                    <input type="email" name="email" class="form-control" required
                           value="<?= htmlspecialchars($old['email'] ?? '') ?>">
                </div>
            </div>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Phone</label>
                    <input type="text" name="phone" class="form-control"
                           value="<?= htmlspecialchars($old['phone'] ?? '') ?>">
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Role *</label>
                    <select name="role" class="form-select" required>
                        <option value="">Select role...</option>
                        <option value="manager" <?= ($old['role'] ?? '') === 'manager' ? 'selected' : '' ?>>Manager</option>
                        <option value="salesman" <?= ($old['role'] ?? '') === 'salesman' ? 'selected' : '' ?>>Salesman</option>
                    </select>
                </div>
            </div>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Password *</label>
                    <input type="password" name="password" class="form-control" required minlength="8">
                    <small class="text-muted">Minimum 8 characters</small>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Status *</label>
                    <select name="status" class="form-select" required>
                        <option value="active" <?= ($old['status'] ?? 'active') === 'active' ? 'selected' : '' ?>>Active</option>
                        <option value="inactive" <?= ($old['status'] ?? '') === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                    </select>
                </div>
            </div>
            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg"></i> Create User</button>
                <a href="<?= Auth::baseUrl('users') ?>" class="btn btn-outline-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>
