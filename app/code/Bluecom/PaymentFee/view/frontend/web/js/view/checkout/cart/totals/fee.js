define(
    [
        'Bluecom_PaymentFee/js/view/checkout/summary/fee'
    ],
    function (Component) {
        'use strict';

        return Component.extend({

            /**
             * @override
             */
            isDisplayed: function () {
                return this.getPureValue() != 0;
            }
        });
    }
);