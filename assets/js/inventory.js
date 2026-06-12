$(function () {
    var modalEl = document.getElementById('adjustStockModal');
    if (!modalEl) {
        return;
    }

    var modal = new bootstrap.Modal(modalEl);

    $('.adjust-btn').on('click', function () {
        $('#adjust_product_id').val($(this).data('product-id'));
        $('#adjust_product_name').text($(this).data('product-name'));
        $('#adjust_current_qty').text($(this).data('qty'));
        $('#adjust_qty').val('');
        $('textarea[name="reason"]').val('');
        modal.show();
    });
});
