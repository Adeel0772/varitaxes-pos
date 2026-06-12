<form method="POST" action="<?= \Core\Auth::baseUrl('auth/login') ?>">
    <?= \Core\Auth::csrfField() ?>
    <div class="mb-3">
        <label class="form-label">Email</label>
        <input type="email" name="email" class="form-control" required autofocus
               value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
    </div>
    <div class="mb-3">
        <label class="form-label">Password</label>
        <input type="password" name="password" class="form-control" required>
    </div>
    <button type="submit" class="btn btn-primary w-100">Login</button>
    <div class="text-center mt-3">
        <a href="<?= \Core\Auth::baseUrl('register') ?>">Register your shop</a>
    </div>
</form>
