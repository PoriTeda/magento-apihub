define(
    [
        'Magento_Checkout/js/view/payment/default',
        'Magento_Catalog/js/price-utils'
    ],
    function (Component, priceUtils) {
        'use strict';
        return Component.extend({
            defaults: {
                template: 'Riki_NpAtobarai/payment/npatobarai'
            },
            getMailingAddress: function () {
                return window.checkoutConfig.payment.checkmo.mailingAddress;
            },
            isAvailable: function () {
                return true;
            },
            getPaymentFee: function () {
                return priceUtils.formatPrice(window.checkoutConfig.payment.npatobarai.paymentFee);
            },
        });
    }
);
