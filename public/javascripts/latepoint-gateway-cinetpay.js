class LatepointGatewayCinetpayAddon {

    // Init
    constructor() {
        this.ready();
    }

    ready() {
        jQuery(document).ready(() => {
            jQuery('body').on('latepoint:submitBookingForm', '.latepoint-booking-form-element', (e, data) => {
                if (!latepoint_helper.demo_mode && data.is_final_submit && data.direction == 'next') {
                    let payment_method = jQuery(e.currentTarget).find('input[name="booking[payment_method]"]').val();
                    switch (payment_method) {
                        case 'inline_checkout':
                            latepoint_add_action(data.callbacks_list, () => {
                                return this.initPaymentModal(jQuery(e.currentTarget), payment_method);
                            });
                            break;
                    }
                }
            });

            jQuery('body').on('latepoint:nextStepClicked', '.latepoint-booking-form-element', (e, data) => {
                if (!latepoint_helper.demo_mode && (data.current_step == 'payment')) {
                    let payment_method = jQuery(e.currentTarget).find('input[name="booking[payment_method]"]').val();
                    switch (payment_method) {
                        case 'inline_checkout':
                            latepoint_add_action(data.callbacks_list, () => {});
                            break;
                    }
                }
            });

            jQuery('body').on('latepoint:initPaymentMethod', '.latepoint-booking-form-element', (e, data) => {
                if (data.payment_method == 'inline_checkout') {
                    let $booking_form_element = jQuery(e.currentTarget);
                    let $latepoint_form = $booking_form_element.find('.latepoint-form');
                    latepoint_add_action(data.callbacks_list, () => {
                        latepoint_show_next_btn($booking_form_element);
                    });
                }
            });

            jQuery('body').on('latepoint:initStep:payment', '.latepoint-booking-form-element', (e, data) => {});
        });
    }

    initPaymentModal($booking_form_element, payment_method) {
        let deferred = jQuery.Deferred();
        let $latepoint_form = $booking_form_element.find('.latepoint-form');
        var data = {
            action: 'latepoint_route_call',
            route_name: latepoint_helper.cinetpay_payment_options_route,
            params: $booking_form_element.find('.latepoint-form').serialize(),
            layout: 'none',
            return_format: 'json'
        }
        jQuery.ajax({
            type: "post",
            dataType: "json",
            url: latepoint_helper.ajaxurl,
            data: data,
            success: (data) => {
                if (data.status === "success") {
                    if (data.amount > 0) {
                        $booking_form_element.find('input[name="booking[intent_key]"]').val(data.booking_intent_key);
                        data.options.callback = (response) => {
                            if (response.transaction_id) {
                                let $payment_token_field = $booking_form_element.find('input[name="booking[payment_token]"]');
                                if ($payment_token_field.length) {
                                    $booking_form_element.find('input[name="booking[payment_token]"]').val(response.transaction_id);
                                } else {
                                    // create payment token field if it doesn ot exist (when payment step is skipped)
                                    $booking_form_element.find('.latepoint-booking-params-w').append('<input type="hidden" value="' + response.transaction_id + '" name="booking[payment_token]" class="latepoint_payment_token"/>');
                                }
                                // remove cinetpay iframe
                                jQuery('iframe[name="checkout"]').remove();
                                jQuery('body').css('overflow', '');
                                deferred.resolve();
                            } else {
                                deferred.reject({ message: 'Processor Payment Error' });
                            }
                        };

                        data.options.onclose = () => {
                            deferred.reject({ message: 'Checkout form closed' });
                        }
                        cinetpayCheckout(data.options);
                    } else {
                        // free booking
                        deferred.resolve();
                    }
                } else {
                    deferred.reject({ message: data.message });
                }
            },
            error: function(request, status, error) {
                deferred.reject({ message: result.error.message });
            }
        });
        return deferred;
    }

}


let latepointGatewayCinetpayAddon = new LatepointGatewayCinetpayAddon();