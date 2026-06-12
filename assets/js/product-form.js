$(function () {
    var $form = $('#productForm');
    if (!$form.length) {
        return;
    }

    var generateCodeUrl = $form.data('generate-code-url');
    var categoryStoreUrl = $form.data('category-store-url');
    var brandStoreUrl = $form.data('brand-store-url');
    var csrfToken = $('meta[name="csrf-token"]').attr('content');

    $('#btnGenerateCode').on('click', function () {
        var btn = $(this);
        btn.prop('disabled', true);
        $.get(generateCodeUrl, {
            category_id: $('#category_id').val(),
            product_type: $('#product_type').val(),
            size: $('#size').val(),
            origin: $('#origin').val()
        }).done(function (res) {
            if (res && res.success) {
                $('#product_code').val(res.product_code);
                showToast('Product code generated.', 'success');
            } else {
                showToast((res && res.message) || 'Failed to generate code.', 'danger');
            }
        }).fail(function (xhr) {
            var msg = (xhr.responseJSON && xhr.responseJSON.message) || 'Failed to generate code.';
            showToast(msg, 'danger');
        }).always(function () {
            btn.prop('disabled', false);
        });
    });

    $('#btnSaveCategory').on('click', function () {
        var $btn = $(this);
        var $err = $('#catAjaxError').addClass('d-none').text('');
        var name = $.trim($('#ajax_cat_name').val());
        var code = $.trim($('#ajax_cat_code').val());

        if (!name) {
            $err.removeClass('d-none').text('Category name is required.');
            return;
        }

        $btn.prop('disabled', true);
        $.ajax({
            url: categoryStoreUrl,
            method: 'POST',
            dataType: 'json',
            data: {
                csrf_token: csrfToken,
                name: name,
                code: code
            }
        }).done(function (res) {
            if (res && res.success) {
                var label = res.category.name + (res.category.code ? ' (' + res.category.code + ')' : '');
                var $opt = $('<option>')
                    .val(res.category.id)
                    .text(label)
                    .attr('data-code', res.category.code || '');
                $('#category_id').append($opt).val(res.category.id);
                var modalEl = document.getElementById('addCategoryModal');
                if (modalEl) {
                    bootstrap.Modal.getOrCreateInstance(modalEl).hide();
                }
                $('#ajax_cat_name, #ajax_cat_code').val('');
                showToast(res.message || 'Category added.', 'success');
            } else {
                $err.removeClass('d-none').text((res && res.message) || 'Failed to save category.');
            }
        }).fail(function (xhr) {
            var msg = (xhr.responseJSON && xhr.responseJSON.message) || 'Failed to save category.';
            $err.removeClass('d-none').text(msg);
        }).always(function () {
            $btn.prop('disabled', false);
        });
    });

    $('#btnSaveBrand').on('click', function () {
        var $btn = $(this);
        var $err = $('#brandAjaxError').addClass('d-none').text('');
        var name = $.trim($('#ajax_brand_name').val());

        if (!name) {
            $err.removeClass('d-none').text('Brand name is required.');
            return;
        }

        $btn.prop('disabled', true);
        $.ajax({
            url: brandStoreUrl,
            method: 'POST',
            dataType: 'json',
            data: {
                csrf_token: csrfToken,
                name: name
            }
        }).done(function (res) {
            if (res && res.success) {
                $('#brand_id').append($('<option>').val(res.brand.id).text(res.brand.name)).val(res.brand.id);
                var modalEl = document.getElementById('addBrandModal');
                if (modalEl) {
                    bootstrap.Modal.getOrCreateInstance(modalEl).hide();
                }
                $('#ajax_brand_name').val('');
                showToast(res.message || 'Brand added.', 'success');
            } else {
                $err.removeClass('d-none').text((res && res.message) || 'Failed to save brand.');
            }
        }).fail(function (xhr) {
            var msg = (xhr.responseJSON && xhr.responseJSON.message) || 'Failed to save brand.';
            $err.removeClass('d-none').text(msg);
        }).always(function () {
            $btn.prop('disabled', false);
        });
    });
});
