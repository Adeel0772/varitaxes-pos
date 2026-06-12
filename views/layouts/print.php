<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($pageTitle ?? 'Print') ?></title>
    <link href="<?= \Core\Auth::asset('css/print.css') ?>" rel="stylesheet">
    <style><?= $customCss ?? '' ?></style>
</head>
<body class="print-body <?= htmlspecialchars($printClass ?? '') ?>">
    <?= $content ?>
    <script>window.onload = function() { window.print(); }</script>
</body>
</html>
