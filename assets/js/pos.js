(function ($) {
    'use strict';

    var cfg = window.POS_CONFIG || {};
    var cart = [];
    var searchTimer = null;
    var customerTimer = null;
    var pendingProduct = null;
    var variantModal = null;
    var completing = false;

    function money(n) {
        return cfg.currencySymbol + ' ' + parseFloat(n || 0).toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
    }

    function parseStock(value) {
        var n = parseInt(value, 10);
        return Number.isFinite(n) && n >= 0 ? n : 0;
    }

    function mergeProductStock(apiProduct, searchProduct) {
        if (!apiProduct) {
            return searchProduct;
        }
        if (apiProduct.qty_in_stock == null || !Number.isFinite(parseInt(apiProduct.qty_in_stock, 10))) {
            apiProduct.qty_in_stock = searchProduct ? parseStock(searchProduct.qty_in_stock) : 0;
        } else {
            apiProduct.qty_in_stock = parseStock(apiProduct.qty_in_stock);
        }
        return apiProduct;
    }

    function cartKey(productId, variantId) {
        return productId + '_' + (variantId || '0');
    }

    function calcLineTotal(item) {
        var sub = item.unit_price * item.qty;
        var disc = 0;
        if (item.discount_type === 'percent') {
            disc = sub * (Math.min(100, item.discount_per_item) / 100);
        } else {
            disc = item.discount_per_item * item.qty;
        }
        return Math.max(0, Math.round((sub - disc) * 100) / 100);
    }

    function calcSubtotal() {
        return cart.reduce(function (sum, item) {
            return sum + calcLineTotal(item);
        }, 0);
    }

    function calcSaleDiscount(subtotal) {
        var amt = parseFloat($('#saleDiscount').val()) || 0;
        var type = $('#saleDiscountType').val();
        if (type === 'percent') {
            amt = Math.min(100, Math.max(0, amt));
            return Math.min(subtotal, subtotal * (amt / 100));
        }
        return Math.min(subtotal, Math.max(0, amt));
    }

    function calcTotal() {
        var sub = calcSubtotal();
        var disc = calcSaleDiscount(sub);
        return Math.max(0, Math.round((sub - disc) * 100) / 100);
    }

    function updateTotals() {
        var sub = calcSubtotal();
        var saleDisc = calcSaleDiscount(sub);
        var total = Math.max(0, sub - saleDisc);

        $('#subtotalDisplay').text(money(sub));
        $('#totalDisplay').text(money(total));
        $('#mobileTotalDisplay').text(money(total));

        var tendered = parseFloat($('#amountTendered').val()) || 0;
        var change = Math.max(0, tendered - total);
        $('#changeDisplay').text(money(change));

        var hasItems = cart.length > 0;
        $('#completeSaleBtn, #mobileCompleteBtn').prop('disabled', !hasItems || completing);
        $('#cartCountBadge').text(cart.length).toggleClass('d-none', !hasItems);
    }

    function renderCart() {
        var $body = $('#cartBody');
        $body.empty();

        if (cart.length === 0) {
            $body.html(
                '<div id="emptyCartRow" class="text-center text-muted py-5 px-3">' +
                '<i class="bi bi-cart-x display-6 d-block mb-2"></i>Cart is empty. Search and add products.</div>'
            );
            updateTotals();
            return;
        }

        cart.forEach(function (item, idx) {
            item.max_stock = parseStock(item.max_stock);
            var lineTotal = calcLineTotal(item);
            var minHint = item.min_sale_price > 0
                ? '<small class="text-muted d-block">Min: ' + money(item.min_sale_price) + '</small>'
                : '';
            var stockHint = '<small class="text-muted">Stock: ' + item.max_stock + '</small>';

            var card = $(
                '<div class="pos-cart-item border-bottom p-3" data-idx="' + idx + '">' +
                '<div class="d-flex justify-content-between align-items-start gap-2 mb-2">' +
                '<div class="flex-grow-1 min-w-0">' +
                '<div class="fw-medium text-break">' + escapeHtml(item.name) + '</div>' +
                '<small class="text-muted">' + escapeHtml(item.product_code) + '</small>' +
                minHint + stockHint +
                '</div>' +
                '<button type="button" class="btn btn-sm btn-outline-danger btn-remove flex-shrink-0" title="Remove">' +
                '<i class="bi bi-trash"></i></button></div>' +
                '<div class="row g-2 g-md-3 align-items-end">' +
                '<div class="col-6 col-md-3">' +
                '<label class="form-label small mb-1">Price</label>' +
                '<input type="number" class="form-control cart-price" value="' + item.unit_price + '" min="0" step="0.01">' +
                '</div>' +
                '<div class="col-6 col-md-3">' +
                '<label class="form-label small mb-1">Qty</label>' +
                '<div class="input-group pos-qty-group">' +
                '<button type="button" class="btn btn-outline-secondary btn-qty-minus" aria-label="Decrease">−</button>' +
                '<input type="number" class="form-control text-center cart-qty" value="' + item.qty + '" min="1" max="' + item.max_stock + '">' +
                '<button type="button" class="btn btn-outline-secondary btn-qty-plus" aria-label="Increase">+</button>' +
                '</div></div>' +
                '<div class="col-6 col-md-3">' +
                '<label class="form-label small mb-1">Discount</label>' +
                '<div class="input-group pos-disc-group">' +
                '<input type="number" class="form-control cart-disc" value="' + item.discount_per_item + '" min="0" step="0.01">' +
                '<select class="form-select cart-disc-type">' +
                '<option value="flat"' + (item.discount_type === 'flat' ? ' selected' : '') + '>Rs</option>' +
                '<option value="percent"' + (item.discount_type === 'percent' ? ' selected' : '') + '>%</option>' +
                '</select></div></div>' +
                '<div class="col-6 col-md-3 text-md-end">' +
                '<label class="form-label small mb-1">Line Total</label>' +
                '<div class="fw-bold fs-6 line-total">' + money(lineTotal) + '</div>' +
                '</div></div></div>'
            );
            $body.append(card);
        });

        updateTotals();
    }

    function escapeHtml(str) {
        return $('<div>').text(str || '').html();
    }

    function addToCart(product, variant) {
        var variantId = variant ? variant.id : null;
        var key = cartKey(product.id, variantId);
        var unitPrice = variant
            ? parseFloat(variant.unit_price)
            : parseFloat(product.sale_price);
        var minPrice = parseFloat(product.min_sale_price) || 0;
        var maxStock = variant
            ? parseStock(variant.qty_in_stock)
            : parseStock(product.qty_in_stock);
        var name = product.name;

        if (variant && variant.label) {
            name += ' (' + variant.label + ')';
        }

        if (maxStock <= 0) {
            showToast('Product is out of stock.', 'danger');
            return;
        }

        var existing = cart.find(function (c) {
            return cartKey(c.product_id, c.variant_id) === key;
        });

        if (existing) {
            if (existing.qty >= maxStock) {
                showToast('Maximum stock reached for this item.', 'warning');
                return;
            }
            existing.qty += 1;
        } else {
            cart.push({
                product_id: product.id,
                variant_id: variantId,
                name: name,
                product_code: product.product_code,
                unit_price: unitPrice,
                min_sale_price: minPrice,
                qty: 1,
                discount_per_item: 0,
                discount_type: 'flat',
                max_stock: maxStock
            });
        }

        renderCart();
        $('#productSearch').val('').focus();
        $('#searchResults').addClass('d-none');
    }

    function validateCartPrices() {
        for (var i = 0; i < cart.length; i++) {
            var item = cart[i];
            if (item.unit_price <= 0) {
                showToast('"' + item.name + '" price must be greater than zero.', 'danger');
                return false;
            }
            if (item.min_sale_price > 0 && item.unit_price < item.min_sale_price - 0.001) {
                showToast('"' + item.name + '" price cannot be below minimum (' + money(item.min_sale_price) + ').', 'danger');
                return false;
            }
            if (item.discount_type === 'percent' && item.discount_per_item > 100) {
                showToast('"' + item.name + '" discount cannot exceed 100%.', 'danger');
                return false;
            }
            var effective = calcLineTotal(item) / item.qty;
            if (item.min_sale_price > 0 && effective < item.min_sale_price - 0.001) {
                showToast('"' + item.name + '" effective price is below minimum after discount.', 'danger');
                return false;
            }
            if (item.qty < 1 || item.qty > item.max_stock) {
                showToast('Invalid quantity for "' + item.name + '".', 'danger');
                return false;
            }
        }
        return true;
    }

    function validateCheckout() {
        if (cart.length === 0) {
            showToast('Cart is empty.', 'warning');
            return false;
        }
        if (!validateCartPrices()) {
            return false;
        }

        var subtotal = calcSubtotal();
        var saleDiscAmt = parseFloat($('#saleDiscount').val()) || 0;
        var saleDiscType = $('#saleDiscountType').val();
        if (saleDiscType === 'percent' && saleDiscAmt > 100) {
            showToast('Sale discount cannot exceed 100%.', 'danger');
            return false;
        }
        if (saleDiscType === 'flat' && saleDiscAmt > subtotal) {
            showToast('Sale discount cannot exceed subtotal.', 'danger');
            return false;
        }

        var paymentMethod = $('input[name="paymentMethod"]:checked').val();
        var customerId = $('#customerId').val() || null;
        var total = calcTotal();

        if (total <= 0) {
            showToast('Sale total must be greater than zero.', 'danger');
            return false;
        }

        if (paymentMethod === 'credit' && !customerId) {
            showToast('Please select a customer for credit sales.', 'danger');
            $('#customerSearch').focus();
            return false;
        }

        if (paymentMethod === 'cash') {
            var tendered = parseFloat($('#amountTendered').val());
            if (isNaN(tendered) || tendered <= 0) {
                showToast('Enter amount tendered for cash payment.', 'danger');
                $('#amountTendered').focus();
                return false;
            }
            if (tendered < total - 0.001) {
                showToast('Amount tendered (' + money(tendered) + ') is less than total (' + money(total) + ').', 'danger');
                $('#amountTendered').focus();
                return false;
            }
        }

        return true;
    }

    function searchProducts(q) {
        $.getJSON(cfg.searchUrl, { q: q })
            .done(function (res) {
                if (!res.success) return;
                var $list = $('#searchResultsList').empty();
                if (!res.products.length) {
                    $list.html('<div class="list-group-item text-muted">No products found.</div>');
                } else {
                    res.products.forEach(function (p) {
                        var stockClass = p.qty_in_stock <= 0 && !p.has_variants ? 'text-danger' : 'text-success';
                        var stockLabel = p.has_variants ? 'Variants' : ('Stock: ' + p.qty_in_stock);
                        var img = p.image
                            ? '<img src="' + p.image + '" class="rounded me-2 flex-shrink-0" style="width:44px;height:44px;object-fit:cover" alt="">'
                            : '<span class="bg-light rounded me-2 d-inline-flex align-items-center justify-content-center flex-shrink-0" style="width:44px;height:44px"><i class="bi bi-box text-muted"></i></span>';

                        var $item = $(
                            '<a href="#" class="list-group-item list-group-item-action search-result-item d-flex align-items-center py-3">' +
                            img +
                            '<div class="flex-grow-1 min-w-0"><div class="fw-medium text-truncate">' + escapeHtml(p.name) + '</div>' +
                            '<small class="text-muted">' + escapeHtml(p.product_code) +
                            (p.barcode ? ' | ' + escapeHtml(p.barcode) : '') + '</small></div>' +
                            '<div class="text-end ms-2 flex-shrink-0"><div class="fw-bold">' + money(p.sale_price) + '</div>' +
                            '<small class="' + stockClass + '">' + stockLabel + '</small></div></a>'
                        );
                        $item.data('product', p);
                        $list.append($item);
                    });
                }
                $('#searchResults').removeClass('d-none');
            })
            .fail(function (xhr) {
                var msg = (xhr.responseJSON && xhr.responseJSON.message) || 'Product search failed.';
                showToast(msg, 'danger');
                $('#searchResultsList').html('<div class="list-group-item text-danger">' + msg + '</div>');
                $('#searchResults').removeClass('d-none');
            });
    }

    function loadVariants(product, callback) {
        $.getJSON(cfg.variantsUrl, { product_id: product.id })
            .done(function (res) {
                if (!res.success) {
                    showToast(res.message || 'Failed to load variants.', 'danger');
                    return;
                }
                callback(res);
            })
            .fail(function () {
                showToast('Failed to load variants.', 'danger');
            });
    }

    function showVariantModal(product, variants) {
        pendingProduct = product;
        $('#variantProductName').text(product.name);
        var $list = $('#variantList').empty();

        variants.forEach(function (v) {
            var stockClass = v.qty_in_stock <= 0 ? 'disabled text-muted' : '';
            var $btn = $(
                '<button type="button" class="list-group-item list-group-item-action ' + stockClass + '">' +
                '<div class="d-flex justify-content-between gap-2"><span>' + escapeHtml(v.label) + '</span>' +
                '<span class="text-end flex-shrink-0"><strong>' + money(v.unit_price) + '</strong><br><small>Stock: ' + v.qty_in_stock + '</small></span></div></button>'
            );
            if (v.qty_in_stock > 0) {
                $btn.data('variant', v);
            }
            $list.append($btn);
        });

        variantModal.show();
    }

    function searchCustomers(q) {
        $.getJSON(cfg.customersUrl, { q: q })
            .done(function (res) {
                if (!res.success) return;
                var $list = $('#customerResults').empty();
                if (!res.customers.length) {
                    $list.addClass('d-none');
                    return;
                }
                res.customers.forEach(function (c) {
                    var bal = parseFloat(c.balance) || 0;
                    var $item = $(
                        '<button type="button" class="list-group-item list-group-item-action">' +
                        '<div class="fw-medium">' + escapeHtml(c.name) + '</div>' +
                        '<small class="text-muted">' + (c.phone ? escapeHtml(c.phone) + ' | ' : '') +
                        'Balance: ' + money(bal) + '</small></button>'
                    );
                    $item.data('customer', c);
                    $list.append($item);
                });
                $list.removeClass('d-none');
            });
    }

    function selectCustomer(c) {
        $('#customerId').val(c.id);
        $('#customerSearch').val(c.name);
        var bal = parseFloat(c.balance) || 0;
        $('#selectedCustomer').html(
            '<i class="bi bi-person-check"></i> ' + escapeHtml(c.name) +
            (bal > 0 ? ' &mdash; Balance: <span class="text-danger">' + money(bal) + '</span>' : '')
        ).removeClass('d-none');
        $('#customerResults').addClass('d-none');
    }

    function clearCustomer() {
        $('#customerId').val('');
        $('#customerSearch').val('');
        $('#selectedCustomer').addClass('d-none').empty();
        $('#customerResults').addClass('d-none');
    }

    function togglePaymentUI() {
        var method = $('input[name="paymentMethod"]:checked').val();
        if (method === 'cash') {
            $('#cashTenderedWrap').show();
        } else {
            $('#cashTenderedWrap').hide();
        }
        if (method === 'credit') {
            $('#customerSearch').attr('placeholder', 'Credit sale requires customer *');
        } else {
            $('#customerSearch').attr('placeholder', 'Walk-in customer');
        }
    }

    function scrollToPayment() {
        var el = document.querySelector('.pos-payment-card');
        if (el) {
            el.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
    }

    function completeSale() {
        if (completing) return;
        if (!validateCheckout()) return;

        var paymentMethod = $('input[name="paymentMethod"]:checked').val();
        var customerId = $('#customerId').val() || null;

        var payload = {
            items: cart.map(function (item) {
                return {
                    product_id: item.product_id,
                    variant_id: item.variant_id,
                    qty: item.qty,
                    unit_price: item.unit_price,
                    discount_per_item: item.discount_per_item,
                    discount_type: item.discount_type
                };
            }),
            customer_id: customerId,
            payment_method: paymentMethod,
            discount_amount: parseFloat($('#saleDiscount').val()) || 0,
            discount_type: $('#saleDiscountType').val(),
            amount_tendered: paymentMethod === 'cash' ? (parseFloat($('#amountTendered').val()) || null) : null,
            notes: $('#saleNotes').val() || ''
        };

        completing = true;
        $('#completeSaleBtn, #mobileCompleteBtn').prop('disabled', true).addClass('disabled');

        $.ajax({
            url: cfg.completeUrl,
            method: 'POST',
            contentType: 'application/json',
            data: JSON.stringify(payload),
            headers: { 'X-CSRF-TOKEN': cfg.csrfToken, 'X-Requested-With': 'XMLHttpRequest' }
        })
            .done(function (res) {
                if (res.success) {
                    window.location.href = res.redirect;
                } else {
                    showToast(res.message || 'Sale failed.', 'danger');
                    completing = false;
                    updateTotals();
                }
            })
            .fail(function (xhr) {
                var msg = 'Failed to complete sale.';
                try {
                    var r = JSON.parse(xhr.responseText);
                    if (r.message) msg = r.message;
                } catch (e) {}
                showToast(msg, 'danger');
                completing = false;
                updateTotals();
            });
    }

    $(document).ready(function () {
        if (!$('#productSearch').length || !window.POS_CONFIG || !window.POS_CONFIG.searchUrl) {
            return;
        }

        $('body').addClass('pos-page');

        var variantEl = document.getElementById('variantModal');
        if (variantEl) {
            variantModal = new bootstrap.Modal(variantEl);
        }
        togglePaymentUI();

        $('#productSearch').on('input', function () {
            var q = $(this).val().trim();
            clearTimeout(searchTimer);
            if (q.length < 1) {
                $('#searchResults').addClass('d-none');
                return;
            }
            searchTimer = setTimeout(function () { searchProducts(q); }, 250);
        });

        $('#clearSearch').on('click', function () {
            $('#productSearch').val('').focus();
            $('#searchResults').addClass('d-none');
        });

        $(document).on('click', '.search-result-item', function (e) {
            e.preventDefault();
            var product = $(this).data('product');
            loadVariants(product, function (res) {
                var p = mergeProductStock(res.product, product);
                if (res.variants && res.variants.length > 0) {
                    showVariantModal(p, res.variants);
                } else {
                    addToCart(p, null);
                }
            });
        });

        $('#variantList').on('click', 'button', function () {
            var variant = $(this).data('variant');
            if (variant && pendingProduct) {
                addToCart(pendingProduct, variant);
                variantModal.hide();
                pendingProduct = null;
            }
        });

        $(document).on('click', '.btn-qty-minus', function () {
            var idx = $(this).closest('.pos-cart-item').data('idx');
            if (cart[idx].qty > 1) {
                cart[idx].qty -= 1;
                renderCart();
            }
        });

        $(document).on('click', '.btn-qty-plus', function () {
            var idx = $(this).closest('.pos-cart-item').data('idx');
            var maxStock = parseStock(cart[idx].max_stock);
            cart[idx].max_stock = maxStock;
            if (cart[idx].qty < maxStock) {
                cart[idx].qty += 1;
                renderCart();
            } else {
                showToast('Maximum stock reached (' + maxStock + ' available).', 'warning');
            }
        });

        $(document).on('change input', '.cart-qty', function () {
            var idx = $(this).closest('.pos-cart-item').data('idx');
            var maxStock = parseStock(cart[idx].max_stock);
            cart[idx].max_stock = maxStock;
            var val = parseInt($(this).val(), 10) || 1;
            val = Math.max(1, Math.min(maxStock || 1, val));
            cart[idx].qty = val;
            $(this).val(val);
            renderCart();
        });

        $(document).on('change input', '.cart-price', function () {
            var idx = $(this).closest('.pos-cart-item').data('idx');
            var val = parseFloat($(this).val()) || 0;
            if (cart[idx].min_sale_price > 0 && val < cart[idx].min_sale_price) {
                showToast('Price cannot be below minimum: ' + money(cart[idx].min_sale_price), 'warning');
                val = cart[idx].min_sale_price;
                $(this).val(val);
            }
            cart[idx].unit_price = val;
            renderCart();
        });

        $(document).on('change input', '.cart-disc, .cart-disc-type', function () {
            var $card = $(this).closest('.pos-cart-item');
            var idx = $card.data('idx');
            var disc = parseFloat($card.find('.cart-disc').val()) || 0;
            var discType = $card.find('.cart-disc-type').val();
            if (discType === 'percent' && disc > 100) {
                showToast('Item discount cannot exceed 100%.', 'warning');
                disc = 100;
                $card.find('.cart-disc').val(100);
            }
            cart[idx].discount_per_item = disc;
            cart[idx].discount_type = discType;
            renderCart();
        });

        $(document).on('click', '.btn-remove', function () {
            var idx = $(this).closest('.pos-cart-item').data('idx');
            cart.splice(idx, 1);
            renderCart();
        });

        $('#clearCart').on('click', function () {
            if (cart.length && confirm('Clear all items from cart?')) {
                cart = [];
                renderCart();
            }
        });

        $('#saleDiscount').on('input change', function () {
            var type = $('#saleDiscountType').val();
            var val = parseFloat($(this).val()) || 0;
            if (type === 'percent' && val > 100) {
                $(this).val(100);
            }
            updateTotals();
        });

        $('#saleDiscountType, #amountTendered').on('input change', updateTotals);

        $('input[name="paymentMethod"]').on('change', togglePaymentUI);

        $('#customerSearch').on('input', function () {
            var q = $(this).val().trim();
            clearTimeout(customerTimer);
            if ($('#customerId').val() && q !== $(this).data('lastSelected')) {
                $('#customerId').val('');
                $('#selectedCustomer').addClass('d-none').empty();
            }
            if (q.length < 1) {
                $('#customerResults').addClass('d-none');
                return;
            }
            customerTimer = setTimeout(function () { searchCustomers(q); }, 250);
        });

        $('#customerResults').on('click', 'button', function () {
            var c = $(this).data('customer');
            selectCustomer(c);
            $('#customerSearch').data('lastSelected', c.name);
        });

        $('#clearCustomer').on('click', clearCustomer);

        $('#completeSaleBtn').on('click', completeSale);
        $('#mobileCompleteBtn').on('click', function () {
            if (!validateCheckout()) {
                scrollToPayment();
                return;
            }
            completeSale();
        });

        $('#productSearch').on('keydown', function (e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                var q = $(this).val().trim();
                if (!q) return;
                $.getJSON(cfg.searchUrl, { q: q })
                    .done(function (res) {
                        if (res.success && res.products.length === 1) {
                            var product = res.products[0];
                            loadVariants(product, function (vr) {
                                var p = mergeProductStock(vr.product, product);
                                if (vr.variants && vr.variants.length === 1 && parseStock(vr.variants[0].qty_in_stock) > 0) {
                                    addToCart(p, vr.variants[0]);
                                } else if (vr.variants && vr.variants.length > 0) {
                                    showVariantModal(p, vr.variants);
                                } else {
                                    addToCart(p, null);
                                }
                            });
                        } else if (res.success && res.products.length > 1) {
                            searchProducts(q);
                        } else {
                            showToast('No product found for barcode/code.', 'warning');
                        }
                    });
            }
        });
    });
})(jQuery);
