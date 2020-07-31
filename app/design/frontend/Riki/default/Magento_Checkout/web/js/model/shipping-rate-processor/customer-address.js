define(
    [
        'Magento_Checkout/js/model/quote',
        'Magento_Checkout/js/model/shipping-service'
    ],
    function (quote, shippingService) {
        "use strict";
        return {
            getRates: function(address) {
                shippingService.setShippingRates([{"carrier_code":"riki_shipping","method_code":"riki_shipping"}]);
            }
        };
    }
);
