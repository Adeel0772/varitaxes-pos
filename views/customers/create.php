<?php use Core\Auth; ?>

<div class="mb-4">
    <a href="<?= Auth::baseUrl('customers') ?>" class="text-decoration-none"><i class="bi bi-arrow-left"></i> Back to Customers</a>
</div>

<h4 class="mb-4"><i class="bi bi-person-plus"></i> Add Customer</h4>

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
        <form method="POST" action="<?= Auth::baseUrl('customers/store') ?>">
            <?= Auth::csrfField() ?>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Full Name *</label>
                    <input type="text" name="name" class="form-control" required
                           value="<?= htmlspecialchars($old['name'] ?? '') ?>">
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Phone</label>
                    <input type="text" name="phone" class="form-control"
                           value="<?= htmlspecialchars($old['phone'] ?? '') ?>">
                </div>
            </div>
            <div class="mb-3">
                <label class="form-label">Address</label>
                <textarea name="address" class="form-control" rows="2"><?= htmlspecialchars($old['address'] ?? '') ?></textarea>
            </div>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Credit Limit</label>
                    <input type="number" name="credit_limit" class="form-control" step="0.01" min="0"
                           value="<?= htmlspecialchars((string) ($old['credit_limit'] ?? '0')) ?>">
                </div>
            </div>
            <div class="mb-3">
                <label class="form-label">Notes</label>
                <textarea name="notes" class="form-control" rows="2"><?= htmlspecialchars($old['notes'] ?? '') ?></textarea>
            </div>
            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg"></i> Create Customer</button>
                <a href="<?= Auth::baseUrl('customers') ?>" class="btn btn-outline-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>
