/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
define(
    [
        'Magento_Checkout/js/model/quote',
        'Magento_Checkout/js/model/url-builder',
        'mage/storage',
        'mage/url',
        'Magento_Checkout/js/model/error-processor',
        'Magento_Customer/js/model/customer',
        'Magento_Checkout/js/model/full-screen-loader',
        'Magento_Checkout/js/action/set-shipping-information'
    ],
    function (quote, urlBuilder, storage, url, errorProcessor, customer, fullScreenLoader, setShippingInformationAction) {
        'use strict';

        return function (paymentData, redirectOnSuccess, messageContainer) {
            var serviceUrl,
                payload;

            redirectOnSuccess = redirectOnSuccess !== false;

            /** Checkout for guest and registered customer. */
            if (!customer.isLoggedIn()) {
                serviceUrl = urlBuilder.createUrl('/guest-carts/:quoteId/payment-information', {
                    quoteId: quote.getQuoteId()
                });
                payload = {
                    cartId: quote.getQuoteId(),
                    email: quote.guestEmail,
                    paymentMethod: paymentData,
                    billingAddress: quote.billingAddress()
                };
            } else {
                serviceUrl = urlBuilder.createUrl('/carts/mine/payment-information', {});
                payload = {
                    cartId: quote.getQuoteId(),
                    paymentMethod: paymentData,
                    billingAddress: quote.billingAddress()
                };
            }

            fullScreenLoader.startLoader();

            return setShippingInformationAction().done(function(){
                storage.post(
                    serviceUrl, JSON.stringify(payload)
                ).done(
                    function () {
                        if (redirectOnSuccess) {
                            window.location.replace(url.build('checkout/onepage/success/') + '?_=' + new Date().getTime() + Math.floor(Math.random() * 10000));
                        }
                    }
                ).fail(
                    function (response) {
                        errorProcessor.process(response, messageContainer);
                        fullScreenLoader.stopLoader();
                    }
                )
            });
        };
    }
);
