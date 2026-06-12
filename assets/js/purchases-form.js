$(function () {
    var $config = $('#purchaseFormConfig');
    if (!$config.length || !$('#purchaseItemsBody').length) {
        return;
    }

    var searchUrl = $config.data('search-url');
    var supplierAjaxUrl = $config.data('supplier-ajax-url');
    var canAddSupplier = String($config.data('can-add-supplier')) === '1';
    var rowIndex = $('#purchaseItemsBody tr').length;
    var searchTimer;

    function formatMoney(n) {
        return 'Rs. ' + parseFloat(n).toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
    }

    function recalcTotals() {
        var total = 0;
        $('#purchaseItemsBody tr').each(function () {
            var qty = parseInt($(this).find('.item-qty').val(), 10) || 0;
            var price = parseFloat($(this).find('.item-price').val()) || 0;
            var line = qty * price;
            $(this).find('.line-total').text(formatMoney(line));
            total += line;
        });
        $('#purchaseGrandTotal').text(formatMoney(total));
    }

    function buildRow(idx, data) {
        data = data || {};
        var name = data.product_name || '';
        var code = data.product_code || '';
        var label = name ? (name + (code ? ' (' + code + ')' : '')) : '';
        return '<tr data-row="' + idx + '">' +
            '<td class="position-relative">' +
            '<input type="hidden" name="items[' + idx + '][product_id]" class="item-product-id" value="' + (data.product_id || '') + '">' +
            '<input type="hidden" name="items[' + idx + '][product_name]" class="item-product-name" value="' + $('<div>').text(name).html() + '">' +
            '<input type="hidden" name="items[' + idx + '][product_code]" class="item-product-code" value="' + $('<div>').text(code).html() + '">' +
            '<input type="text" class="form-control form-control-sm product-search" placeholder="Search product..." value="' + $('<div>').text(label).html() + '" autocomplete="off">' +
            '<div class="list-group position-absolute w-100 shadow-sm product-results d-none" style="z-index:1050;max-height:200px;overflow-y:auto;"></div>' +
            '</td>' +
            '<td><input type="number" name="items[' + idx + '][qty]" class="form-control form-control-sm item-qty" min="1" value="' + (data.qty || 1) + '" required></td>' +
            '<td><input type="number" name="items[' + idx + '][purchase_price]" class="form-control form-control-sm item-price" min="0" step="0.01" value="' + (data.purchase_price || 0) + '" required></td>' +
            '<td class="line-total align-middle">' + formatMoney((data.qty || 1) * (data.purchase_price || 0)) + '</td>' +
            '<td><button type="button" class="btn btn-sm btn-outline-danger remove-row"><i class="bi bi-trash"></i></button></td>' +
            '</tr>';
    }

    $('#addPurchaseRow').on('click', function () {
        $('#purchaseItemsBody').append(buildRow(rowIndex++));
        recalcTotals();
    });

    $(document).on('click', '.remove-row', function () {
        if ($('#purchaseItemsBody tr').length <= 1) {
            showToast('At least one line is required.', 'danger');
            return;
        }
        $(this).closest('tr').remove();
        recalcTotals();
    });

    $(document).on('input', '.item-qty, .item-price', recalcTotals);

    $(document).on('input', '.product-search', function () {
        var $input = $(this);
        var $row = $input.closest('tr');
        var $results = $row.find('.product-results');
        var q = $input.val().trim();

        clearTimeout(searchTimer);
        if (q.length < 1) {
            $results.addClass('d-none').empty();
            return;
        }

        searchTimer = setTimeout(function () {
            $.getJSON(searchUrl, { q: q })
                .done(function (res) {
                    $results.empty();
                    if (!res.success || !res.products.length) {
                        $results.html('<div class="list-group-item text-muted small">No products found</div>').removeClass('d-none');
                        return;
                    }
                    res.products.forEach(function (p) {
                        var $item = $('<button type="button" class="list-group-item list-group-item-action py-2"></button>');
                        $item.html('<strong>' + $('<div>').text(p.name).html() + '</strong><br><small class="text-muted">' +
                            $('<div>').text(p.product_code).html() + ' · Stock: ' + p.qty_in_stock + '</small>');
                        $item.data('product', p);
                        $results.append($item);
                    });
                    $results.removeClass('d-none');
                })
                .fail(function () {
                    $results.html('<div class="list-group-item text-danger small">Search failed</div>').removeClass('d-none');
                });
        }, 300);
    });

    $(document).on('click', '.product-results .list-group-item-action', function () {
        var p = $(this).data('product');
        var $row = $(this).closest('tr');
        $row.find('.item-product-id').val(p.id);
        $row.find('.item-product-name').val(p.name);
        $row.find('.item-product-code').val(p.product_code);
        $row.find('.product-search').val(p.name + ' (' + p.product_code + ')');
        if (typeof p.purchase_price !== 'undefined' && p.purchase_price > 0) {
            $row.find('.item-price').val(p.purchase_price);
        }
        $row.find('.product-results').addClass('d-none').empty();
        recalcTotals();
    });

    $(document).on('click', function (e) {
        if (!$(e.target).closest('.product-search, .product-results').length) {
            $('.product-results').addClass('d-none');
        }
    });

    if (canAddSupplier) {
        $('#inlineSupplierForm').on('submit', function (e) {
            e.preventDefault();
            var $btn = $(this).find('button[type=submit]');
            $btn.prop('disabled', true);
            $.ajax({
                url: supplierAjaxUrl,
                method: 'POST',
                dataType: 'json',
                data: $(this).serialize()
            }).done(function (res) {
                if (res.success) {
                    var opt = $('<option></option>').val(res.supplier.id).text(res.supplier.name).prop('selected', true);
                    $('#supplier_id').append(opt);
                    var modalEl = document.getElementById('addSupplierModal');
                    if (modalEl) {
                        bootstrap.Modal.getOrCreateInstance(modalEl).hide();
                    }
                    $('#inlineSupplierForm')[0].reset();
                    showToast(res.message, 'success');
                } else {
                    showToast(res.message || 'Failed to add supplier.', 'danger');
                }
            }).fail(function (xhr) {
                var msg = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : 'Failed to add supplier.';
                showToast(msg, 'danger');
            }).always(function () {
                $btn.prop('disabled', false);
            });
        });
    }

    recalcTotals();
});
