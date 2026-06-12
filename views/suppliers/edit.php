<?php use Core\Auth; ?>

<div class="mb-4">
    <a href="<?= Auth::baseUrl('suppliers') ?>" class="text-decoration-none"><i class="bi bi-arrow-left"></i> Back to Suppliers</a>
</div>

<h4 class="mb-4"><i class="bi bi-pencil"></i> Edit Supplier</h4>

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
        <form method="POST" action="<?= Auth::baseUrl('suppliers/update') ?>">
            <?= Auth::csrfField() ?>
            <input type="hidden" name="id" value="<?= (int) $supplier['id'] ?>">
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Supplier Name *</label>
                    <input type="text" name="name" class="form-control" required
                           value="<?= htmlspecialchars($supplier['name']) ?>">
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Phone</label>
                    <input type="text" name="phone" class="form-control"
                           value="<?= htmlspecialchars($supplier['phone'] ?? '') ?>">
                </div>
            </div>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">City</label>
                    <input type="text" name="city" class="form-control"
                           value="<?= htmlspecialchars($supplier['city'] ?? '') ?>">
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Address</label>
                    <input type="text" name="address" class="form-control"
                           value="<?= htmlspecialchars($supplier['address'] ?? '') ?>">
                </div>
            </div>
            <div class="mb-3">
                <label class="form-label">Notes</label>
                <textarea name="notes" class="form-control" rows="3"><?= htmlspecialchars($supplier['notes'] ?? '') ?></textarea>
            </div>
            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg"></i> Update Supplier</button>
                <a href="<?= Auth::baseUrl('suppliers') ?>" class="btn btn-outline-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>
