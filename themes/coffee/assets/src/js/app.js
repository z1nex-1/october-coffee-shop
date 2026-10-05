$(function () {
    var $status = $('#order-status');

    if ($status.length) {
        var poll = setInterval(function () {
            if (!$status.find('[data-pending="1"]').length) {
                clearInterval(poll);
                return;
            }
            $.request('shopOrderStatus::onRefresh');
        }, 3000);
    }

    // в песочнице показываем, что повторное уведомление не меняет заказ
    $(document).on('click', '[data-resend-notification]', function () {
        var $button = $(this).prop('disabled', true);

        $.getJSON($button.data('resend-notification'))
            .then(function (notification) {
                return $.ajax({
                    url: $button.data('webhook'),
                    method: 'POST',
                    contentType: 'application/json',
                    data: JSON.stringify(notification)
                });
            })
            .always(function () {
                $.request('shopOrderStatus::onRefresh');
            });
    });

    $(document).on('click', '.suggest button', function () {
        $('#city-suggest').empty();
    });

    $(document).on('oc.beforeRequest', '#city-query', function (event) {
        if ($.trim($(this).val()).length < 2) {
            event.preventDefault();
            $('#city-suggest').empty();
        }
    });
});
