<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle ?? 'Login - ' . APP_NAME) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="<?= \Core\Auth::asset('css/app.css') ?>" rel="stylesheet">
</head>
<body class="auth-body">
    <div class="auth-wrapper d-flex align-items-center justify-content-center min-vh-100">
        <div class="auth-card card shadow-lg border-0" style="width:100%;max-width:420px;">
            <div class="card-body p-4">
                <div class="text-center mb-4">
                    <i class="bi bi-shop display-4 text-primary"></i>
                    <h4 class="mt-2"><?= htmlspecialchars(APP_NAME) ?></h4>
                </div>
                <?php if (!empty($error = \Core\Auth::flash('error'))): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>
                <?php if (!empty($success = \Core\Auth::flash('success'))): ?>
                <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
                <?php endif; ?>
                <?= $content ?>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
