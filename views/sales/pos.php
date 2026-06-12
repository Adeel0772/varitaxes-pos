<?php use Core\Auth; ?>

<div class="pos-container d-flex flex-column">
    <div class="pos-header d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <h4 class="mb-0"><i class="bi bi-cart-check text-warning"></i> Point of Sale</h4>
        <div class="d-flex gap-2 flex-wrap">
            <a href="<?= Auth::baseUrl('dashboard') ?>" class="btn btn-outline-secondary btn-sm"><i class="bi bi-house"></i> <span class="d-none d-sm-inline">Dashboard</span></a>
            <?php if (Auth::can('sales', 'read')): ?>
            <a href="<?= Auth::baseUrl('sales') ?>" class="btn btn-outline-primary btn-sm"><i class="bi bi-receipt"></i> <span class="d-none d-sm-inline">Sales List</span></a>
            <?php endif; ?>
        </div>
    </div>

    <div class="row g-3 flex-grow-1 pos-layout mx-0">
        <!-- Search + Cart -->
        <div class="col-12 col-lg-7 col-xl-8 d-flex flex-column px-0 pe-lg-2">
            <div class="card shadow-sm mb-3">
                <div class="card-body py-2">
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-search"></i></span>
                        <input type="text" id="productSearch" class="form-control form-control-lg" placeholder="Search name, code, or scan barcode..." autofocus autocomplete="off">
                        <button type="button" id="clearSearch" class="btn btn-outline-secondary" aria-label="Clear search"><i class="bi bi-x-lg"></i></button>
                    </div>
                </div>
            </div>

            <div id="searchResults" class="card shadow-sm mb-3 d-none">
                <div class="card-header py-2"><strong>Search Results</strong></div>
                <div class="list-group list-group-flush pos-products" id="searchResultsList"></div>
            </div>

            <div class="card shadow-sm flex-grow-1 d-flex flex-column pos-cart-card">
                <div class="card-header d-flex justify-content-between align-items-center py-2">
                    <strong><i class="bi bi-cart3"></i> Cart <span id="cartCountBadge" class="badge bg-primary ms-1 d-none">0</span></strong>
                    <button type="button" id="clearCart" class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i> <span class="d-none d-sm-inline">Clear</span></button>
                </div>
                <div class="card-body p-0 flex-grow-1 pos-cart" id="cartBody">
                    <div id="emptyCartRow" class="text-center text-muted py-5 px-3">
                        <i class="bi bi-cart-x display-6 d-block mb-2"></i>
                        Cart is empty. Search and add products.
                    </div>
                </div>
            </div>
        </div>

        <!-- Payment -->
        <div class="col-12 col-lg-5 col-xl-4 px-0 ps-lg-2">
            <div class="card shadow-sm pos-payment-card">
                <div class="card-header py-2"><strong><i class="bi bi-credit-card"></i> Payment</strong></div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label small fw-medium">Customer</label>
                        <div class="input-group">
                            <input type="text" id="customerSearch" class="form-control" placeholder="Walk-in customer" autocomplete="off">
                            <button type="button" id="clearCustomer" class="btn btn-outline-secondary" title="Clear" aria-label="Clear customer"><i class="bi bi-x"></i></button>
                        </div>
                        <input type="hidden" id="customerId" value="">
                        <div id="customerResults" class="list-group mt-1 d-none shadow-sm pos-customer-results"></div>
                        <div id="selectedCustomer" class="small mt-1 text-primary d-none"></div>
                    </div>

                    <div class="border rounded p-3 mb-3 bg-light pos-totals-box">
                        <div class="d-flex justify-content-between mb-2">
                            <span>Subtotal</span>
                            <span id="subtotalDisplay" class="fw-medium"><?= htmlspecialchars($currencySymbol) ?> 0.00</span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center mb-2 gap-2">
                            <span class="flex-shrink-0">Sale Discount</span>
                            <div class="input-group input-group-sm pos-discount-input">
                                <input type="number" id="saleDiscount" class="form-control text-end" value="0" min="0" step="0.01">
                                <select id="saleDiscountType" class="form-select">
                                    <option value="flat">Rs</option>
                                    <option value="percent">%</option>
                                </select>
                            </div>
                        </div>
                        <hr class="my-2">
                        <div class="d-flex justify-content-between fs-4 fw-bold text-primary">
                            <span>Total</span>
                            <span id="totalDisplay"><?= htmlspecialchars($currencySymbol) ?> 0.00</span>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-medium">Payment Method</label>
                        <div class="pos-pay-methods" role="group">
                            <?php foreach (['cash' => 'Cash', 'jazzcash' => 'JazzCash', 'easypaisa' => 'EasyPaisa', 'credit' => 'Credit', 'other' => 'Other'] as $val => $label): ?>
                            <input type="radio" class="btn-check" name="paymentMethod" id="pay_<?= $val ?>" value="<?= $val ?>" <?= ($defaultPayment ?? 'cash') === $val ? 'checked' : '' ?>>
                            <label class="btn btn-outline-primary btn-sm" for="pay_<?= $val ?>"><?= $label ?></label>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <div class="mb-3" id="cashTenderedWrap">
                        <label class="form-label small fw-medium">Amount Tendered</label>
                        <input type="number" id="amountTendered" class="form-control form-control-lg" min="0" step="0.01" placeholder="0.00">
                        <div class="d-flex justify-content-between mt-2 small">
                            <span>Change</span>
                            <strong id="changeDisplay" class="text-success fs-6"><?= htmlspecialchars($currencySymbol) ?> 0.00</strong>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-medium">Notes</label>
                        <input type="text" id="saleNotes" class="form-control" placeholder="Optional note">
                    </div>

                    <button type="button" id="completeSaleBtn" class="btn btn-success btn-lg w-100 pos-complete-btn" disabled>
                        <i class="bi bi-check-circle"></i> Complete Sale
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Mobile sticky checkout bar -->
<div class="pos-mobile-bar d-lg-none" id="posMobileBar">
    <div class="d-flex justify-content-between align-items-center gap-2">
        <div>
            <small class="text-muted d-block">Total</small>
            <strong id="mobileTotalDisplay" class="text-primary"><?= htmlspecialchars($currencySymbol) ?> 0.00</strong>
        </div>
        <button type="button" id="mobileCompleteBtn" class="btn btn-success btn-lg flex-shrink-0" disabled>
            <i class="bi bi-check-circle"></i> Pay
        </button>
    </div>
</div>

<div class="modal fade" id="variantModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Select Variant</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="mb-2 fw-medium" id="variantProductName"></p>
                <div id="variantList" class="list-group"></div>
            </div>
        </div>
    </div>
</div>

<script>
window.POS_CONFIG = {
    baseUrl: '<?= Auth::baseUrl('') ?>',
    searchUrl: '<?= Auth::baseUrl('products/search') ?>',
    variantsUrl: '<?= Auth::baseUrl('sales/variants') ?>',
    customersUrl: '<?= Auth::baseUrl('customers/search') ?>',
    completeUrl: '<?= Auth::baseUrl('sales/complete') ?>',
    csrfToken: '<?= Auth::csrfToken() ?>',
    currencySymbol: '<?= htmlspecialchars($currencySymbol) ?>'
};
</script>
