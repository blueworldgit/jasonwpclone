(function ($) {
    let accessWorldpayCheckout = window.accessWorldpayCheckout;

    let accessWorldpayCheckoutIntegration = {
        shortCodeCheckout: {
            wc_checkout_form: null,
        },
        deviceSessionIsSent: false,
        transactionReference: null,
        orderId: null,
        initCheckoutCallback: function (error, checkout) {
            if (error) {
                if (typeof error === 'object') {
                    error = error.toString()
                }
                accessWorldpayCheckoutIntegration.logError(error);
                return;
            }
            accessWorldpayCheckout.checkout = checkout;
        },
        handleCardHolderNameInput: function () {
            let cardHolderInput = $(accessWorldpayCheckout.config.paymentFormExtraFields.cardHolderName.selector);
            cardHolderInput.attr('placeholder', accessWorldpayCheckout.config.paymentFormExtraFields.cardHolderName.placeholder);
            cardHolderInput.on('focus', function () {
                if ($(this).val().length === 0) {
                    $(this).attr('placeholder', accessWorldpayCheckout.config.paymentFormExtraFields.cardHolderName.placeholder);
                    $(this).css('color', '');
                    $(this).removeClass('valid invalid');
                }
            });
            cardHolderInput.on('keydown', function () {
                if ($(this).val().length === 0) {
                    $(this).attr('placeholder', '');
                }
            });
            cardHolderInput.on('input', function () {
                let length = this.value.length;
                if (length > 0 && length <= 255) {
                    $(this).css('color', 'green').removeClass('invalid').addClass('valid');
                } else if (length > 255) {
                    $(this).css('color', 'red').removeClass('valid').addClass('invalid');
                }
            });
            cardHolderInput.on('blur', function () {
                if ($(this).val().length === 0) {
                    $(this).attr('placeholder', accessWorldpayCheckout.config.paymentFormExtraFields.cardHolderName.placeholder);
                    $(this).css('color', '');
                }
            });
        },
        setCardHolderInputPlaceholder: function () {
            $(accessWorldpayCheckout.config.paymentFormExtraFields.cardHolderName.selector).attr('placeholder',
                accessWorldpayCheckout.config.paymentFormExtraFields.cardHolderName.placeholder);
        },
        getCheckoutTypeFormSelector: function () {
            let isPaymentMethod = !(access_worldpay_checkout_params.isCheckout || access_worldpay_checkout_params.isPayForOrder);

            if ( isPaymentMethod ) {
                return 'form#add_payment_method';
            }

            if ( access_worldpay_checkout_params.isCheckout ){
                return 'form.checkout';
            }

            return 'form#order_review';
        },
        getCheckoutFormSubmitUrl: function () {
            return access_worldpay_checkout_params.isCheckout ?
                wc_checkout_params.checkout_url :
                window.location;
        },
        getSelectedPaymentMethodId: function () {
            return $(accessWorldpayCheckoutIntegration.getCheckoutTypeFormSelector() + ' input[name="payment_method"]:checked').attr('id');
        },
        checkoutPaymentFormInit: function () {
            if ( $("#wc-access_worldpay_checkout-token-form").length ) {
                if( typeof tokenForm !== 'undefined' && ! tokenForm ) {
                    tokenForm = $("#wc-access_worldpay_checkout-token-form").prop('outerHTML').replaceAll('token-form', 'cc-form');
                }
                $("#wc-access_worldpay_checkout-token-form").remove();
            }
            let selector = typeof accessWorldpayCheckout.config.paymentForm.fields.cvv !== 'undefined' ? accessWorldpayCheckout.config.paymentForm.fields.cvv.selector : accessWorldpayCheckout.config.paymentForm.fields.cvvOnly.selector;

            if (accessWorldpayCheckoutIntegration.isSelectedPaymentMethodCheckout() && $(selector).children().length < 1) {
                $("#wc-access_worldpay_checkout-cc-form").remove();
                accessWorldpayWooIntegration.creditCardFunctionality();
                $("#wc-access_worldpay_checkout-cc-form").show();
            }
            accessWorldpayCheckoutIntegration.setCardHolderInputPlaceholder();
        },
        isSelectedPaymentMethodCheckout: function () {
            let selectedPaymentMethodId = accessWorldpayCheckoutIntegration.getSelectedPaymentMethodId();
            return selectedPaymentMethodId.includes(access_worldpay_checkout_params.paymentMethodId);
        },
        validateCardHolderName: function () {
            if (!accessWorldpayCheckoutIntegration.isTokenPaymentMethodSelected()) {
                let cardHolderInput = $(accessWorldpayCheckout.config.paymentFormExtraFields.cardHolderName.selector);
                if (cardHolderInput.val().trim().length === 0 || cardHolderInput.val().length > 255) {
                    accessWorldpayCheckoutIntegration.displayError('The payment form is invalid or incomplete.');
                    return false;
                }
            }

            return true;
        },
        shortcodeSubmitCallback: function (event, wc_checkout_form) {
            accessWorldpayCheckoutIntegration.shortCodeCheckout.wc_checkout_form = wc_checkout_form;
            if (null === accessWorldpayCheckout.paymentSession && false === wc_checkout_form.dirtyInput) {

                if (!accessWorldpayCheckoutIntegration.validateCardHolderName()) {
                    return false;
                }
                accessWorldpayCheckout.generatePaymentSession(accessWorldpayCheckoutIntegration.generatePaymentSessionCallback);

                return false;
            }
            let form = $(accessWorldpayCheckoutIntegration.getCheckoutTypeFormSelector());
            form.addClass('processing');
            wc_checkout_form.blockOnSubmit(form);
            wc_checkout_form.attachUnloadEventsOnSubmit();
            accessWorldpayCheckoutIntegration.placeOrderRequest();

            return false;
        },
        payForOrderSubmitCallback: function (event) {
            let selectedPaymentMethodId = accessWorldpayCheckoutIntegration.getSelectedPaymentMethodId();
            if (selectedPaymentMethodId.includes(access_worldpay_checkout_params.paymentMethodId)) {
                event.preventDefault();
                event.stopPropagation();
                if (null === accessWorldpayCheckout.paymentSession) {
                    if (!accessWorldpayCheckoutIntegration.validateCardHolderName()) {
                        return false;
                    }
                    accessWorldpayCheckout.generatePaymentSession(accessWorldpayCheckoutIntegration.generatePaymentSessionCallback);

                    return false;
                } else {
                    accessWorldpayCheckoutIntegration.placeOrderRequest();
                }
            }
        },
        addPaymentMethodSubmitCallback: function (event) {
            event.preventDefault();
            event.stopPropagation();

            if (null === accessWorldpayCheckout.paymentSession) {
                if (!accessWorldpayCheckoutIntegration.validateCardHolderName()) {
                    return false;
                }
                accessWorldpayCheckout.generatePaymentSession(accessWorldpayCheckoutIntegration.generatePaymentSessionCallback);

                return false;
            }

            let form = $("form#add_payment_method");
            form.off('submit');
            form[0].submit();

            return false;
        },
        generatePaymentSessionCallback: function (error, paymentSession) {
            if (error) {
                accessWorldpayCheckoutIntegration.logError(error);

                return;
            }
            accessWorldpayCheckout.paymentSession = paymentSession;
            accessWorldpayCheckoutIntegration.createPaymentSessionInput(paymentSession);
            $(document.body).trigger('access_worldpay_checkout_payment_session_generated');
        },
        resetPaymentSession: function () {
            accessWorldpayCheckout.paymentSession = null;
            accessWorldpayCheckoutIntegration.removePaymentSessionInput();
        },
        createPaymentSessionInput: function (value) {
            let element = $('#access_worldpay_checkout-session');
            if (!element.length) {
                $('<input>')
                    .attr({
                        type: 'hidden',
                        id: '#access_worldpay_checkout-session',
                        name: 'access_worldpay_checkout[session]',
                        value: value
                    })
                    .appendTo(accessWorldpayCheckoutIntegration.getCheckoutTypeFormSelector());
            }
            element.val(value);
        },
        removePaymentSessionInput: function () {
            $('#access_worldpay_checkout-session').val('');
        },
        submitOrder: function () {
            $('#place_order').submit();
        },
        placeOrderRequest: function () {
            accessWorldpayCheckout.request(
                'POST',
                accessWorldpayCheckoutIntegration.getCheckoutFormSubmitUrl(),
                $(accessWorldpayCheckoutIntegration.getCheckoutTypeFormSelector()).serialize(),
                'json',
                accessWorldpayCheckoutIntegration.placeOrderRequestSuccessCallback,
                accessWorldpayCheckoutIntegration.placeOrderRequestErrorCallback,
            );
        },
        placeOrderRequestSuccessCallback: function (result) {
            if (null !== accessWorldpayCheckoutIntegration.shortCodeCheckout.wc_checkout_form) {
                accessWorldpayCheckoutIntegration.shortCodeCheckout.wc_checkout_form.detachUnloadEventsOnSubmit();
            }
            if ('success' === result.result) {
                accessWorldpayCheckout.transactionReference = result.transaction_reference;
                accessWorldpayCheckout.orderId = result.order_id;
                if (access_worldpay_checkout_params.threeDSDataRequiredStatus === result.outcome) {
                    if (result.submitThreeDsDeviceDataEndpoint) {
                        if (accessWorldpayCheckout.config && accessWorldpayCheckout.config.deviceDataCollection) {
                            accessWorldpayCheckout.config.deviceDataCollection.submitUrl = result.submitThreeDsDeviceDataEndpoint;
                        }

                        access_worldpay_checkout_params.submitThreeDsDeviceDataEndpoint = result.submitThreeDsDeviceDataEndpoint;
                    }

                    accessWorldpayCheckout.setupDeviceDataCollectionIframe(result.deviceDataCollectionUrl);
                    accessWorldpayCheckoutIntegration.listen3DSDeviceDataCollectionResultMessage();

                    return;
                }
                window.location = result.redirect.includes('https://') || result.redirect.includes('http://')
                    ? decodeURI(result.redirect)
                    : result.redirect;
            } else {
                accessWorldpayCheckoutIntegration.displayError(result.messages);
                accessWorldpayCheckoutIntegration.resetPaymentSession();

                return false;
            }
        },
        placeOrderRequestErrorCallback: function (jqXHR, textStatus, errorThrown) {
            if (null !== accessWorldpayCheckoutIntegration.shortCodeCheckout.wc_checkout_form) {
                accessWorldpayCheckoutIntegration.shortCodeCheckout.wc_checkout_form.detachUnloadEventsOnSubmit();
            }
            let errorMessage = errorThrown;
            if ('object' === typeof wc_checkout_params && null !== wc_checkout_params && wc_checkout_params.hasOwnProperty('i18n_checkout_error') &&
                'string' === typeof wc_checkout_params.i18n_checkout_error && '' !== wc_checkout_params.i18n_checkout_error.trim()) {
                errorMessage = wc_checkout_params.i18n_checkout_error;
            }
            accessWorldpayCheckoutIntegration.displayError(errorMessage);
            accessWorldpayCheckoutIntegration.resetPaymentSession();

            return false;
        },
        listen3DSDeviceDataCollectionResultMessage: function () {
            let timeoutID;
            let data = {
                order_id: accessWorldpayCheckout.orderId,
                transaction_reference: accessWorldpayCheckout.transactionReference,
                collection_reference: '',
            };

            function handleMessage(event) {
                if (event.origin !== access_worldpay_checkout_params.threeDSAuthenticationApplicationUrl) {
                    return;
                }

                if (typeof event.data === 'undefined' || !event.data) {
                    return;
                }

                let postMessage = JSON.parse(event.data);
                if (typeof postMessage.MessageType === 'undefined' || postMessage.MessageType !== 'profile.completed' || typeof postMessage.Status === 'undefined' || postMessage.Status !== true) {
                    return;
                }

                accessWorldpayCheckoutIntegration.deviceSessionIsSent = true;
                window.removeEventListener('message', handleMessage);
                clearTimeout(timeoutID);
                data.collection_reference = postMessage.SessionId;
                accessWorldpayCheckout.submitDeviceDataCollectionRequest(data,
                    accessWorldpayCheckoutIntegration.submitDeviceDataCollectionRequestSuccessCallback,
                    accessWorldpayCheckoutIntegration.submitDeviceDataCollectionRequestErrorCallback);
            }

            window.addEventListener('message', handleMessage);
            timeoutID = setTimeout(function () {
                window.removeEventListener('message', handleMessage);
                if (false === accessWorldpayCheckoutIntegration.deviceSessionIsSent) {
                    accessWorldpayCheckoutIntegration.deviceSessionIsSent = true;
                    accessWorldpayCheckout.submitDeviceDataCollectionRequest(data,
                        accessWorldpayCheckoutIntegration.submitDeviceDataCollectionRequestSuccessCallback,
                        accessWorldpayCheckoutIntegration.submitDeviceDataCollectionRequestErrorCallback);
                }
            }, 10000);
        },
        submitDeviceDataCollectionRequestSuccessCallback: function (result) {
            if (access_worldpay_checkout_params.authorizedStatus === result.outcome || access_worldpay_checkout_params.sentForSettlementStatus === result.outcome) {
                window.location = result.redirect;
            } else if (access_worldpay_checkout_params.threeDSDataChallengedStatus === result.outcome) {
                accessWorldpayCheckoutIntegration.setChallengeModalSize(result.challengeWindowSize);
                accessWorldpayCheckout.setupThreeDSChallengeIframe(result.challengeUrl);
                $(accessWorldpayCheckoutIntegration.getCheckoutTypeFormSelector()).removeClass('processing').unblock();
                accessWorldpayCheckoutIntegration.displayChallengeModal();
            } else {
                accessWorldpayCheckoutIntegration.deviceSessionIsSent = false;
                accessWorldpayCheckoutIntegration.displayError(result.messages);
                accessWorldpayCheckoutIntegration.resetDeviceDataCollectionIframe();
                accessWorldpayCheckoutIntegration.resetPaymentSession();

                return false;
            }
        },
        submitDeviceDataCollectionRequestErrorCallback: function (result) {
            accessWorldpayCheckoutIntegration.deviceSessionIsSent = false;
            accessWorldpayCheckoutIntegration.displayError(result.messages);
            accessWorldpayCheckoutIntegration.resetDeviceDataCollectionIframe();
            accessWorldpayCheckoutIntegration.resetPaymentSession();

            return false;
        },
        setChallengeModalSize: function (challengeWindowSize) {
            if (challengeWindowSize) {
                $(accessWorldpayCheckout.config.threeDSChallenge.modal.selector).width(challengeWindowSize.width);
                $(accessWorldpayCheckout.config.threeDSChallenge.modal.selector).height(challengeWindowSize.height);
            }
        },
        displayChallengeModal: function () {
            if (access_worldpay_checkout_params.threeDSChallengeDisplayLightbox) {
                $(accessWorldpayCheckout.config.threeDSChallenge.modal.selector).fadeIn();
                $(accessWorldpayCheckout.config.threeDSChallenge.overlay.selector).fadeIn();
            }
        },
        listen3DSChallengeResultMessage: function (event) {
            if ('undefined' !== typeof event.detail.data) {
                let challengeResponse = JSON.parse(event.detail.data);
                if ('success' === challengeResponse.result) {
                    window.location = challengeResponse.redirect.includes('https://') || challengeResponse.redirect.includes('http://')
                        ? decodeURI(challengeResponse.redirect)
                        : challengeResponse.redirect;
                } else {
                    accessWorldpayCheckoutIntegration.displayError(challengeResponse.messages)
                    accessWorldpayCheckoutIntegration.reset3DSChallengeIframe();
                    accessWorldpayCheckoutIntegration.resetDeviceDataCollectionIframe();
                    accessWorldpayCheckoutIntegration.resetPaymentSession();
                }
            }
        },
        reset3DSChallengeIframe: function () {
            if (access_worldpay_checkout_params.threeDSChallengeDisplayLightbox) {
                $(accessWorldpayCheckout.config.threeDSChallenge.modal.selector).fadeOut();
                $(accessWorldpayCheckout.config.threeDSChallenge.overlay.selector).fadeOut();
            }
            $(accessWorldpayCheckout.config.threeDSChallenge.iframe.selector).empty();
        },
        resetDeviceDataCollectionIframe: function () {
            $(accessWorldpayCheckout.config.deviceDataCollection.iframe.selector).empty();
        },
        isTokenPaymentMethodSelected: function () {
            if (!$("#payment_method_access_worldpay_checkout").is(':checked')) {
                return false;
            }

            let checkInput = $('div.payment_method_access_worldpay_checkout input.woocommerce-SavedPaymentMethods-tokenInput:checked');
            if (checkInput.length === 0 || checkInput.attr('id') === 'wc-access_worldpay_checkout-payment-token-new') {
                return false;
            }

            return true;
        },
        displayError: function (errorMessage) {
            let regexForHtml = /(<([^>]+)>)/i;
            let formattedMessage = regexForHtml.test(errorMessage)
                ? errorMessage
                : '<div class="woocommerce-error">' + errorMessage + '</div>';
            $('.woocommerce-NoticeGroup-checkout, .woocommerce-error, .woocommerce-message, .is-error, .is-success').remove();
            $(accessWorldpayCheckoutIntegration.getCheckoutTypeFormSelector()).prepend('<div class="woocommerce-NoticeGroup woocommerce-NoticeGroup-checkout">' + formattedMessage + '</div>');
            $('html, body').animate({
                    scrollTop: $('.woocommerce-NoticeGroup').offset().top - 100,
                },
                1000
            ).promise().done(function(){
                $(accessWorldpayCheckoutIntegration.getCheckoutTypeFormSelector()).removeClass('processing').unblock();
            });
            $(document.body).trigger('checkout_error', [formattedMessage]);
        },
        logError: function (errorMessage) {
            if (access_worldpay_checkout_params.debugMode && !errorMessage.includes('The payment form is invalid or incomplete')) {
                accessWorldpayCheckout.request(
                    'POST',
                    access_worldpay_checkout_params.logFrontendErrorEndpoint,
                    {message: errorMessage},
                    'json',
                    function (result) {
                        accessWorldpayCheckoutIntegration.displayError(result);
                    }
                );
            } else {
                errorMessage = errorMessage.includes('The payment form is invalid or incomplete') ?
                    'The payment form is invalid or incomplete.' :
                    'Something went wrong while processing your payment. Please try again later.';
                accessWorldpayCheckoutIntegration.displayError(errorMessage);
            }
        },
        getConfig: function(isTokenConfig = false) {
            let output =  {
                merchantCheckoutId: access_worldpay_checkout_params.checkout_id,
                paymentForm: {
                    selector: accessWorldpayCheckoutIntegration.getCheckoutTypeFormSelector(),
                    fields: {
                        pan: {
                            selector: "#access_worldpay_checkout-card-number",
                            placeholder: "4444 3333 2222 1111"
                        },
                        expiry: {
                            selector: "#access_worldpay_checkout-card-expiry",
                            placeholder: "MM/YY"
                        },
                        cvv: {
                            selector: "#access_worldpay_checkout-card-cvc",
                            placeholder: "123"
                        }
                    },
                    tokenFields: {
                        cvvOnly: {
                            selector: "#access_worldpay_checkout-card-cvc",
                            placeholder: "123"
                        }
                    },
                    acceptedCardBrands: access_worldpay_checkout_params.card_brands,
                    enablePanFormatting: true,
                    styles: {
                        "input": {
                            "color": "black",
                            "font-weight": "bold",
                            "font-size": "15px",
                            "letter-spacing": "3px"
                        },
                        "input.is-valid": {
                            "color": "green !important"
                        },
                        "input.is-invalid": {
                            "color": "red !important"
                        },
                        "input.is-onfocus": {
                            "color": "black !important"
                        }
                    }
                },
                paymentFormExtraFields: {
                    cardHolderName: {
                        selector: '.wc-credit-card-form-card-holder-name',
                        placeholder: 'Card holder name'
                    }
                },
                checkoutForm: {
                    selector: accessWorldpayCheckoutIntegration.getCheckoutTypeFormSelector(),
                    submitUrl: accessWorldpayCheckoutIntegration.getCheckoutFormSubmitUrl()
                },
                deviceDataCollection: {
                    iframe: {
                        selector: '#access_worldpay_checkout-ddc-iframe'
                    },
                    submitUrl: access_worldpay_checkout_params.submitThreeDsDeviceDataEndpoint
                },
                threeDSChallenge: {
                    iframe: {
                        selector: '#access_worldpay_checkout-challenge-iframe'
                    },
                    modal: {
                        selector: '#access_worldpay_checkout-modal'
                    },
                    overlay: {
                        selector: '#access_worldpay_checkout-modal-overlay'
                    }
                },
                events: [
                    {
                        name: 'updated_checkout',
                        callback: accessWorldpayCheckoutIntegration.checkoutPaymentFormInit,
                        selector: document.body
                    },
                    {
                        name: 'checkout_place_order_access_worldpay_checkout',
                        callback: accessWorldpayCheckoutIntegration.shortcodeSubmitCallback,
                        selector: 'form.checkout'
                    },
                    {
                        name: 'submit',
                        callback: accessWorldpayCheckoutIntegration.payForOrderSubmitCallback,
                        selector: 'form#order_review'
                    },
                    {
                        name: 'submit',
                        callback: accessWorldpayCheckoutIntegration.addPaymentMethodSubmitCallback,
                        selector: 'form#add_payment_method'
                    },
                    {
                        name: 'checkout_error',
                        callback: accessWorldpayCheckoutIntegration.resetPaymentSession,
                        selector: document.body
                    },
                    {
                        name: 'updated_checkout',
                        callback: accessWorldpayCheckoutIntegration.resetPaymentSession,
                        selector: document.body
                    },
                    {
                        name: 'access_worldpay_checkout_payment_session_generated',
                        callback: accessWorldpayCheckoutIntegration.submitOrder,
                        selector: document.body
                    },
                    {
                        name: 'access_worldpay_checkout_payment_3ds_completed',
                        callback: accessWorldpayCheckoutIntegration.listen3DSChallengeResultMessage,
                        selector: null
                    },
                    {
                        name: 'wp:form:ready',
                        callback: accessWorldpayCheckoutIntegration.handleCardHolderNameInput,
                        selector: accessWorldpayCheckoutIntegration.getCheckoutTypeFormSelector()
                    },
                    {
                        name: 'DOMContentLoaded',
                        callback: accessWorldpayCheckoutIntegration.checkoutPaymentFormInit,
                        selector: null,
                    },
                    {
                        name: 'payment_method_selected',
                        callback: accessWorldpayCheckoutIntegration.checkoutPaymentFormInit,
                        selector: document.body,
                    },
                    {
                        name: 'wc-credit-card-form-init',
                        callback: accessWorldpayCheckoutIntegration.handleCardHolderNameInput,
                        selector: document.body
                    },
                ],
            }

            if (isTokenConfig) {
                output.paymentForm.fields = output.paymentForm.tokenFields;
            }

            return output;
        }
    }

    const isPayForOrder      = access_worldpay_checkout_params.isPayForOrder;
    const addForm = $('form#add_payment_method');

    let accessWorldpayWooIntegration = {
        tokenIsSelected: null,

        validateTriggerEvent: function () {
            if(this.tokenIsSelected !== null && this.tokenIsSelected === accessWorldpayCheckoutIntegration.isTokenPaymentMethodSelected()){
                return false;
            }
            this.tokenIsSelected = accessWorldpayCheckoutIntegration.isTokenPaymentMethodSelected();

            accessWorldpayCheckout.remove();
            $("#wc-access_worldpay_checkout-token-form, #wc-access_worldpay_checkout-cc-form").remove();

            return true;
        },

        creditCardFunctionality: function () {

            $(creditcardForm).insertBefore("#access_worldpay_checkout-ddc-iframe");
            $("#wc-access_worldpay_checkout-cc-form").show();

            let isAddPaymentMethod = addForm.length > 0 || !accessWorldpayCheckoutIntegration.isTokenPaymentMethodSelected();

            accessWorldpayCheckout.config = accessWorldpayCheckoutIntegration.getConfig(false);
            accessWorldpayCheckout.initEvents();

            if ( isPayForOrder || isAddPaymentMethod ) {
                setTimeout(function(){
                    $( document.body ).trigger('wc-credit-card-form-init');
                }, 100);

                const config = accessWorldpayCheckout.config;

                if ( ! document.querySelector( config.paymentForm.selector ) ) {
                    config.paymentForm.selector = 'body';
                }

                accessWorldpayCheckout.init( accessWorldpayCheckoutIntegration.initCheckoutCallback , 'card' );
            }
        },

        tokenFunctionality: function () {
            $(tokenForm).insertBefore("#access_worldpay_checkout-ddc-iframe");
            $("#wc-access_worldpay_checkout-cc-form").show();

            accessWorldpayCheckout.config = accessWorldpayCheckoutIntegration.getConfig(true);

            accessWorldpayCheckout.initEvents();

            accessWorldpayCheckout.init( accessWorldpayCheckoutIntegration.initCheckoutCallback , 'token' );
        }
    }


    let tokenForm = null;
    if ($("#wc-access_worldpay_checkout-token-form").length > 0) {
        tokenForm = $("#wc-access_worldpay_checkout-token-form").prop('outerHTML').replaceAll('token-form', 'cc-form');
    }
    let creditcardForm = $("#wc-access_worldpay_checkout-cc-form").prop('outerHTML');
    $("#wc-access_worldpay_checkout-token-form").remove();
    $("#wc-access_worldpay_checkout-cc-form").remove();


    jQuery(function ($) {
        let chooseInputOptions = 'div.payment_method_access_worldpay_checkout input.woocommerce-SavedPaymentMethods-tokenInput';
        if (!tokenForm) {
            accessWorldpayWooIntegration.creditCardFunctionality();
        } else {
            $(document.body).on('change', chooseInputOptions, function(event){
                let isValidChange = accessWorldpayWooIntegration.validateTriggerEvent();
                if( !isValidChange ){
                    return;
                }

                switch (accessWorldpayCheckoutIntegration.isTokenPaymentMethodSelected()){
                    case true:
                        accessWorldpayWooIntegration.tokenFunctionality();
                        $('input#wc-access_worldpay_checkout-new-payment-method').prop('checked', false);
                        break;

                    case false:
                        accessWorldpayWooIntegration.creditCardFunctionality();
                        break
                }
            });

            $(document.body).on('change', '#payment_method_access_worldpay_checkout', function(event){
                if ($(this).prop('checked')) {
                    $(chooseInputOptions+':checked').trigger('change')
                }

            });

            if ( isPayForOrder ) {

                let tokensInput = $(chooseInputOptions+":checked:not(#payment_method_access_worldpay_checkout)");
                $('input#payment_method_access_worldpay_checkout').on('change', function () {

                    if ( $(this).prop('checked') && tokensInput.length === 0 ) {
                        $('#wc-access_worldpay_checkout-payment-token-new').prop('checked', true).trigger('change');
                    }
                });

                if ( tokensInput.length === 0 && $('input#payment_method_access_worldpay_checkout:checked').length ) {
                    $('#wc-access_worldpay_checkout-payment-token-new').prop('checked', true).trigger('change');
                }

                let chooseInput = $(chooseInputOptions);
                if ( chooseInput.length  === 1 && chooseInput.attr('id') === 'wc-access_worldpay_checkout-payment-token-new' && chooseInput.prop('checked') === false ) {
                    chooseInput.prop('checked', true);
                }

                let checkedInput = $(chooseInputOptions+':checked');

                if ( checkedInput.length ) {
                    checkedInput.trigger('change');

                    $('div.payment_method_access_worldpay_checkout p.woocommerce-SavedPaymentMethods-saveNew').hide();
                    $('input#wc-access_worldpay_checkout-new-payment-method').prop('checked', false);
                }
            }
        }


    });
})(jQuery);
