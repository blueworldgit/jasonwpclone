(function ($) {

    let accessWorldpayCheckout = {
        config: {
            merchantCheckoutId: null,
            paymentForm: {
                selector: null,
                fields: {
                    pan: {
                        selector: null,
                        placeholder: null,
                    },
                    expiry: {
                        selector: null,
                        placeholder: null,
                    },
                    cvv: {
                        selector: null,
                        placeholder: null,
                    }
                },
                tokenFields: {
                    cvvOnly: {
                        selector: null,
                        placeholder: null,
                    }
                },
                acceptedCardBrands: null,
                styles: null,
                enablePanFormatting: null
            },
            paymentFormExtraFields: {
                cardHolderName: {
                    selector: null,
                    placeholder: null
                }
            },
            checkoutForm: {
                selector: null,
                submitUrl: null,
            },
            deviceDataCollection: {
                iframe: {
                    selector: null
                },
                submitUrl: null
            },
            threeDSChallenge: {
                iframe: {
                    selector: null
                },
                modal: {
                    selector: null
                },
                overlay: {
                    selector: null
                }
            },
            events: [
                {
                    // name: 'myEvent',
                    // callback: 'myEventCallback',
                    // selector: null || #mySelector,
                }
            ],
        },
        checkout: null,
        paymentSession: null,
        init: function (initCallback, paymentType = 'card') {
            // accessWorldpayCheckout.checkout must be set in initCallback
            accessWorldpayCheckout.remove();
            Worldpay.checkout.init(accessWorldpayCheckout.getCheckoutParameters(paymentType), initCallback);
        },
        alreadyInitEvents: false,
        initEvents: function () {
            if (accessWorldpayCheckout.alreadyInitEvents) {
                return;
            }

            accessWorldpayCheckout.alreadyInitEvents = true;
            accessWorldpayCheckout.config.events.forEach(function (event) {
                if (null === event.selector) {
                    window.addEventListener(event.name, event.callback);
                    return;
                }

                let element = $(event.selector);
                if (element.length) {
                    element.on(event.name, event.callback);
                }
            });
        },
        getCheckoutParameters: function (paymentType = 'card') {
            return {
                id: accessWorldpayCheckout.config.merchantCheckoutId,
                form: accessWorldpayCheckout.config.paymentForm.selector,
                fields: paymentType === 'card' ? accessWorldpayCheckout.config.paymentForm.fields : accessWorldpayCheckout.config.paymentForm.tokenFields,
                styles: accessWorldpayCheckout.config.paymentForm.styles,
                acceptedCardBrands: accessWorldpayCheckout.config.paymentForm.acceptedCardBrands,
                enablePanFormatting: accessWorldpayCheckout.config.paymentForm.enablePanFormatting
            }
        },
        request: function (requestType, requestUrl, data, dataType, successCallback, errorCallback) {
            $.ajax({
                type: requestType,
                url: requestUrl,
                data: data,
                dataType: dataType,
                success: successCallback || function () {
                },
                error: errorCallback || function () {
                },
            })
        },
        generatePaymentSession: function (generatePaymentSessionCallback) {
            if (accessWorldpayCheckout.checkout !== null) {
                // accessWorldpayCheckout.session must be set in generatePaymentSessionCallback
                accessWorldpayCheckout.checkout.generateSessionState(generatePaymentSessionCallback);
            }
        },
        setupDeviceDataCollectionIframe: function (deviceDataCollectionUrl) {
            $(accessWorldpayCheckout.config.deviceDataCollection.iframe.selector).html(
                '<iframe height="1" width="1" style="display: none;" src="' + deviceDataCollectionUrl + '"></iframe>'
            );
        },
        submitDeviceDataCollectionRequest: function (ddcData, submitDeviceDataCollectionRequestSuccessCallback, submitDeviceDataCollectionRequestFailureCallback) {
            if (accessWorldpayCheckout.config.deviceDataCollection.submitUrl !== null) {
                accessWorldpayCheckout.request(
                    'POST',
                    accessWorldpayCheckout.config.deviceDataCollection.submitUrl,
                    ddcData,
                    'json',
                    submitDeviceDataCollectionRequestSuccessCallback,
                    submitDeviceDataCollectionRequestFailureCallback
                );
            }
        },
        setupThreeDSChallengeIframe: function (challengeUrl) {
            $(accessWorldpayCheckout.config.threeDSChallenge.iframe.selector).html('<iframe height="100%" width="100%" src="' + challengeUrl + '"></iframe>');
        },
        clear: function () {
            accessWorldpayCheckout.checkout.clear();
        },
        remove: function () {
            if(accessWorldpayCheckout.checkout !== null) {
                accessWorldpayCheckout.checkout.remove();
            }
        }
    }
    window.accessWorldpayCheckout = accessWorldpayCheckout;
})(jQuery);
