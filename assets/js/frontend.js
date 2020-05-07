jQuery(function($) {
    let order_review = false;
    let order_review_url;

    const dc_nagad = {
        order_review: "form#order_review",
        order_review_submit: function(e) {
            let method = $(this).find('input[name="payment_method"]:checked').val();

            if (method === "dc_nagad") {
                e.preventDefault();
                order_review = true;

                let url = $(this).find('input[name="_wp_http_referer"]').val().match(/^.*\/(\d+)\/.*$/);
                order_review_url = url[0];
                let order_number = url[1];
                dc_nagad.get_pgw_url(order_number);
            }
        },
        get_pgw_url: function(order_number) {
            let create_payment_data = {
                order_number: order_number,
                action: "dc-nagad-create-payment-request",
                _ajax_nonce: nagad_params.nonce
            };

            $.ajax({
                url: nagad_params.ajax_url,
                method: "POST",
                data: create_payment_data,
                success: function(data) {
                    if (data.success && data.data != null) {
                        data = data.data;
                        window.location = data;
                    }
                },
                error: function(errorMessage) {
                }
            });
        },
        init: function() {
            $(dc_nagad.order_review).on("submit", dc_nagad.order_review_submit);
        },
    };

    dc_nagad.init();
});
