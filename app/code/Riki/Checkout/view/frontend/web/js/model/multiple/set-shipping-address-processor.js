/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
/*global define,alert*/
define(
    [
        'ko',
        'Magento_Checkout/js/model/resource-url-manager',
        'mage/storage',
        'Magento_Checkout/js/model/payment-service',
        'Magento_Checkout/js/model/payment/method-converter',
        'Magento_Checkout/js/model/error-processor',
        'Magento_Checkout/js/model/full-screen-loader',
        'Magento_Checkout/js/action/select-billing-address',
        'mage/url'
    ],
    function (
        ko,
        resourceUrlManager,
        storage,
        paymentService,
        methodConverter,
        errorProcessor,
        fullScreenLoader,
        selectBillingAddressAction,
        urlBuilder
    ) {
        'use strict';

        return {
            saveShippingInformation: function (quote,dataString) {

                if (!quote.billingAddress()) {
                    quote.billingAddress(quote.shippingAddress());
                }

                fullScreenLoader.startLoader();
                return storage.get(
                    urlBuilder.build("rest/V1/multicheckout/setShippingAddress/" + quote.getQuoteId() + "/" + dataString)
                ).done(
                    function (response) {
                        var payload = JSON.parse(response);
                        quote.quoteItemDdateInfo(payload);
                        fullScreenLoader.stopLoader();
                    }
                ).fail(
                    function (response) {
                        errorProcessor.process(response);
                        fullScreenLoader.stopLoader();
                    }
                );
            }
        };
    }
);
