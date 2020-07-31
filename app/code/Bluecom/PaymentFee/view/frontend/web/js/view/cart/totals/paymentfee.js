
/*global define*/
define(
    [
        'Bluecom_PaymentFee/js/view/cart/summary/paymentfee'
    ],
    function (Component) {
        "use strict";
        return Component.extend({
            defaults: {
                template: 'Bluecom_PaymentFee/cart/totals/paymentfee'
            },
            /**
             * @override
             *
             * @returns {boolean}
             */
            isDisplayed: function () {
                return this.getPureValue() != 0;
            }
        });
    }
);
