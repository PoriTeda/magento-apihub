/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
/*global define,alert*/
define(
    [
        'Magento_Checkout/js/model/quote',
        'Riki_Checkout/js/model/multiple/set-shipping-address-processor'
    ],
    function (quote, setShippingAddressProcessor) {
        'use strict';
        return function (quote,dataString) {
            return setShippingAddressProcessor.saveShippingInformation(quote,dataString);
        }
    }
);
