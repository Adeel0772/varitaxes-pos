<?php $old = $old ?? []; ?>
<?php if (!empty($errors)): ?>
<div class="alert alert-danger"><ul class="mb-0"><?php foreach ($errors as $e): ?><li><?= htmlspecialchars($e) ?></li><?php endforeach; ?></ul></div>
<?php endif; ?>
<form method="POST" action="<?= \Core\Auth::baseUrl('register/submit') ?>">
    <?= \Core\Auth::csrfField() ?>
    <div class="mb-3">
        <label class="form-label">Shop Name *</label>
        <input type="text" name="shop_name" class="form-control" required value="<?= htmlspecialchars($old['shop_name'] ?? '') ?>">
    </div>
    <div class="mb-3">
        <label class="form-label">Owner Name *</label>
        <input type="text" name="owner_name" class="form-control" required value="<?= htmlspecialchars($old['owner_name'] ?? '') ?>">
    </div>
    <div class="row">
        <div class="col-md-6 mb-3">
            <label class="form-label">Phone *</label>
            <input type="text" name="phone" class="form-control" required value="<?= htmlspecialchars($old['phone'] ?? '') ?>">
        </div>
        <div class="col-md-6 mb-3">
            <label class="form-label">City</label>
            <input type="text" name="city" class="form-control" value="<?= htmlspecialchars($old['city'] ?? '') ?>">
        </div>
    </div>
    <div class="mb-3">
        <label class="form-label">Address</label>
        <textarea name="address" class="form-control" rows="2"><?= htmlspecialchars($old['address'] ?? '') ?></textarea>
    </div>
    <div class="mb-3">
        <label class="form-label">Shop Type</label>
        <select name="shop_type" class="form-select">
            <?php foreach (['sports','stationery','clothing','general','other'] as $t): ?>
            <option value="<?= $t ?>" <?= ($old['shop_type'] ?? '') === $t ? 'selected' : '' ?>><?= ucfirst($t) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <hr>
    <div class="mb-3">
        <label class="form-label">Email (Login) *</label>
        <input type="email" name="email" class="form-control" required value="<?= htmlspecialchars($old['email'] ?? '') ?>">
    </div>
    <div class="mb-3">
        <label class="form-label">Password *</label>
        <input type="password" name="password" class="form-control" required minlength="8">
        <small class="text-muted">Minimum 8 characters</small>
    </div>
    <button type="submit" class="btn btn-primary w-100">Register Shop</button>
    <div class="text-center mt-3">
        <a href="<?= \Core\Auth::baseUrl('auth/login') ?>">Already have an account? Login</a>
    </div>
</form>
