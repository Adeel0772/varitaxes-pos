<?php use Core\Auth; use Core\Helpers; ?>

<div class="row justify-content-center">
    <div class="col-lg-8 col-xl-6">
        <div class="text-center mb-4">
            <h4 class="mb-1">Welcome, <?= htmlspecialchars(Auth::user()['name'] ?? 'Salesman') ?></h4>
            <p class="text-muted mb-0"><?= date('l, ' . DATE_FORMAT) ?></p>
        </div>

        <?php if (Auth::can('sales', 'create')): ?>
        <div class="card shadow-sm border-warning mb-4">
            <div class="card-body text-center py-5">
                <i class="bi bi-cart-check display-1 text-warning"></i>
                <h5 class="mt-3">Ready to make a sale?</h5>
                <p class="text-muted">Open the POS to start billing customers.</p>
                <a href="<?= Auth::baseUrl('sales/pos') ?>" class="btn btn-warning btn-lg px-5">
                    <i class="bi bi-cart-check"></i> Open POS
                </a>
            </div>
        </div>
        <?php endif; ?>

        <div class="row g-3">
            <div class="col-sm-6">
                <div class="card stat-card shadow-sm h-100">
                    <div class="card-body text-center">
                        <div class="text-muted small">My Sales Today</div>
                        <div class="fs-2 fw-bold text-primary"><?= (int) $todayStats['sale_count'] ?></div>
                        <div class="text-muted">transaction(s)</div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6">
                <div class="card stat-card success shadow-sm h-100">
                    <div class="card-body text-center">
                        <div class="text-muted small">My Revenue Today</div>
                        <div class="fs-2 fw-bold text-success"><?= Helpers::formatMoney($todayStats['total_amount']) ?></div>
                    </div>
                </div>
            </div>
        </div>

        <?php if (Auth::can('sales', 'read')): ?>
        <div class="text-center mt-4">
            <a href="<?= Auth::baseUrl('sales/my-today') ?>" class="btn btn-outline-primary">
                <i class="bi bi-receipt"></i> View My Today's Sales
            </a>
        </div>
        <?php endif; ?>
    </div>
</div>
