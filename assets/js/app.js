function showToast(message, type) {
    type = type || 'success';
    var container = document.getElementById('toast-container');
    if (!container) return;
    var id = 'toast-' + Date.now();
    var html = '<div id="' + id + '" class="toast align-items-center text-bg-' + type + ' border-0" role="alert">' +
        '<div class="d-flex"><div class="toast-body">' + message + '</div>' +
        '<button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button></div></div>';
    container.insertAdjacentHTML('beforeend', html);
    var el = document.getElementById(id);
    var toast = new bootstrap.Toast(el, { delay: 4000 });
    toast.show();
    el.addEventListener('hidden.bs.toast', function() { el.remove(); });
}

function closeSidebar() {
    $('#sidebar').removeClass('show');
    $('body').removeClass('sidebar-open');
}

$(document).ready(function() {
    $('#sidebarToggle').on('click', function() {
        var opening = !$('#sidebar').hasClass('show');
        $('#sidebar').toggleClass('show');
        $('body').toggleClass('sidebar-open', opening);
    });

    $('#sidebarBackdrop').on('click', closeSidebar);

    $(document).on('click', '#sidebar .nav-link', function() {
        if (window.innerWidth < 992) {
            closeSidebar();
        }
    });

    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
            'X-Requested-With': 'XMLHttpRequest'
        },
        beforeSend: function(xhr, settings) {
            if (settings.url && settings.url.indexOf('sales/complete') === -1) {
                $('.loading-overlay').addClass('show');
            }
        },
        complete: function() {
            $('.loading-overlay').removeClass('show');
        }
    });

    $(document).on('submit', 'form[data-confirm]', function(e) {
        if (!confirm($(this).data('confirm'))) {
            e.preventDefault();
        }
    });
});

function formatMoney(amount) {
    return 'Rs. ' + parseFloat(amount).toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
}
