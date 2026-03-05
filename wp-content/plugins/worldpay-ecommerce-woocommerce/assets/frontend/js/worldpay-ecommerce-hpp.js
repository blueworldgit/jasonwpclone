(function ($) {
    function toggleSaveCardCheckbox()
    {
        let isNewCardSelected = $('#wc-access_worldpay_hpp-payment-token-new').is(':checked');
        let saveNewPaymentMethod = $('div.payment_method_access_worldpay_hpp p.woocommerce-SavedPaymentMethods-saveNew');

        saveNewPaymentMethod.hide();
        if ( isNewCardSelected ) {
            saveNewPaymentMethod.show();
        }
    }

    if (access_worldpay_hpp_params.isPayForOrder) {
        $(document).on('change', 'input[name="wc-access_worldpay_hpp-payment-token"]', toggleSaveCardCheckbox);
        $(document.body).on('updated_checkout payment_method_selected', toggleSaveCardCheckbox);

        let chooseInputOptions = 'div.payment_method_access_worldpay_hpp input.woocommerce-SavedPaymentMethods-tokenInput';
        let tokensInput = $(chooseInputOptions+":checked:not(#payment_method_access_worldpay_hpp)");
        if ( tokensInput.length === 0 && $('input#payment_method_access_worldpay_hpp:checked').length ) {
            $('#wc-access_worldpay_hpp-payment-token-new').prop('checked', true).trigger('change');
        }
    }
})(jQuery);
