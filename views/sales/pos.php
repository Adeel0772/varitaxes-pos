<?php use Core\Auth; ?>

<div class="pos-container d-flex flex-column">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4 class="mb-0"><i class="bi bi-cart-check text-warning"></i> Point of Sale</h4>
        <div class="d-flex gap-2">
            <a href="<?= Auth::baseUrl('dashboard') ?>" class="btn btn-outline-secondary btn-sm"><i class="bi bi-house"></i> Dashboard</a>
            <?php if (Auth::can('sales', 'read')): ?>
            <a href="<?= Auth::baseUrl('sales') ?>" class="btn btn-outline-primary btn-sm"><i class="bi bi-receipt"></i> Sales List</a>
            <?php endif; ?>
        </div>
    </div>

    <div class="row g-3 flex-grow-1 pos-layout mx-0">
        <!-- Left 70%: Search + Cart -->
        <div class="col-12 col-xl-8 d-flex flex-column px-0 pe-xl-2">
            <div class="card shadow-sm mb-3">
                <div class="card-body py-2">
                    <div class="input-group input-group-lg">
                        <span class="input-group-text"><i class="bi bi-search"></i></span>
                        <input type="text" id="productSearch" class="form-control" placeholder="Search by name, code, or barcode..." autofocus autocomplete="off">
                        <button type="button" id="clearSearch" class="btn btn-outline-secondary"><i class="bi bi-x-lg"></i></button>
                    </div>
                </div>
            </div>

            <div id="searchResults" class="card shadow-sm mb-3 d-none">
                <div class="card-header py-2"><strong>Search Results</strong></div>
                <div class="list-group list-group-flush pos-products" id="searchResultsList"></div>
            </div>

            <div class="card shadow-sm flex-grow-1 d-flex flex-column">
                <div class="card-header d-flex justify-content-between align-items-center py-2">
                    <strong><i class="bi bi-cart3"></i> Cart</strong>
                    <button type="button" id="clearCart" class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i> Clear</button>
                </div>
                <div class="card-body p-0 flex-grow-1 pos-cart">
                    <div class="table-responsive">
                        <table class="table table-sm table-hover mb-0" id="cartTable">
                            <thead class="table-light sticky-top">
                                <tr>
                                    <th>Product</th>
                                    <th class="text-end" style="width:100px">Price</th>
                                    <th class="text-center" style="width:90px">Qty</th>
                                    <th class="text-end" style="width:100px">Disc.</th>
                                    <th class="text-end" style="width:100px">Total</th>
                                    <th style="width:40px"></th>
                                </tr>
                            </thead>
                            <tbody id="cartBody">
                                <tr id="emptyCartRow">
                                    <td colspan="6" class="text-center text-muted py-5">
                                        <i class="bi bi-cart-x display-6 d-block mb-2"></i>
                                        Cart is empty. Search and add products.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right 30%: Payment -->
        <div class="col-12 col-xl-4 px-0 ps-xl-2">
            <div class="card shadow-sm sticky-top" style="top:1rem">
                <div class="card-header py-2"><strong><i class="bi bi-credit-card"></i> Payment</strong></div>
                <div class="card-body">
                    <!-- Customer -->
                    <div class="mb-3">
                        <label class="form-label small fw-medium">Customer</label>
                        <div class="input-group input-group-sm">
                            <input type="text" id="customerSearch" class="form-control" placeholder="Walk-in customer" autocomplete="off">
                            <button type="button" id="clearCustomer" class="btn btn-outline-secondary" title="Clear"><i class="bi bi-x"></i></button>
                        </div>
                        <input type="hidden" id="customerId" value="">
                        <div id="customerResults" class="list-group mt-1 d-none shadow-sm"></div>
                        <div id="selectedCustomer" class="small mt-1 text-primary d-none"></div>
                    </div>

                    <!-- Totals -->
                    <div class="border rounded p-3 mb-3 bg-light">
                        <div class="d-flex justify-content-between mb-1">
                            <span>Subtotal</span>
                            <span id="subtotalDisplay"><?= htmlspecialchars($currencySymbol) ?> 0.00</span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <span>Sale Discount</span>
                            <div class="input-group input-group-sm" style="width:55%">
                                <input type="number" id="saleDiscount" class="form-control text-end" value="0" min="0" step="0.01">
                                <select id="saleDiscountType" class="form-select" style="max-width:70px">
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

                    <!-- Payment Method -->
                    <div class="mb-3">
                        <label class="form-label small fw-medium">Payment Method</label>
                        <div class="btn-group w-100 flex-wrap" role="group">
                            <?php foreach (['cash' => 'Cash', 'jazzcash' => 'JazzCash', 'easypaisa' => 'EasyPaisa', 'credit' => 'Credit', 'other' => 'Other'] as $val => $label): ?>
                            <input type="radio" class="btn-check" name="paymentMethod" id="pay_<?= $val ?>" value="<?= $val ?>" <?= ($defaultPayment ?? 'cash') === $val ? 'checked' : '' ?>>
                            <label class="btn btn-outline-primary btn-sm" for="pay_<?= $val ?>"><?= $label ?></label>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- Cash tendered -->
                    <div class="mb-3" id="cashTenderedWrap">
                        <label class="form-label small fw-medium">Amount Tendered</label>
                        <input type="number" id="amountTendered" class="form-control" min="0" step="0.01" placeholder="0.00">
                        <div class="d-flex justify-content-between mt-1 small">
                            <span>Change</span>
                            <strong id="changeDisplay" class="text-success"><?= htmlspecialchars($currencySymbol) ?> 0.00</strong>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-medium">Notes</label>
                        <input type="text" id="saleNotes" class="form-control form-control-sm" placeholder="Optional note">
                    </div>

                    <button type="button" id="completeSaleBtn" class="btn btn-success btn-lg w-100" disabled>
                        <i class="bi bi-check-circle"></i> Complete Sale
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Variant Picker Modal -->
<div class="modal fade" id="variantModal" tabindex="-1">
    <div class="modal-dialog">
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

<div class="loading-overlay"><div class="spinner-border text-primary" role="status"></div></div>

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
