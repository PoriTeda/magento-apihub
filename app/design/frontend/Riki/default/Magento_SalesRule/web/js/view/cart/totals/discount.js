/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
/*global define*/
define(
    [
        'Magento_SalesRule/js/view/summary/discount'
    ],
    function (Component) {
        "use strict";
        return Component.extend({
            defaults: {
                template: 'Magento_SalesRule/cart/totals/discount'
            },
            /**
             * @override
             *
             * @returns {boolean}
             */
            isDisplayed: function () {
                return this.getPureValue() != 0;
            },
            getTaxValue: function() {
                var discountExclTax = window.checkoutConfig.discount_amount_excl_tax;
                return this.getFormattedPrice(- this.getPureValue() - discountExclTax);
            }
        });
    }
);
