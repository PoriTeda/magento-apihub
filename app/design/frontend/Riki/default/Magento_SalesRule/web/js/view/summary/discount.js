/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
/*global define*/
define(
    [
        'Magento_Checkout/js/view/summary/abstract-total',
        'Magento_Checkout/js/model/quote'
    ],
    function (Component, quote) {
        "use strict";
        return Component.extend({
            defaults: {
                template: 'Magento_SalesRule/summary/discount'
            },
            totals: quote.getTotals(),
            isDisplayed: function() {
                return true; //always display discount as new design
            },
            getCouponCode: function() {
                if (!this.totals()) {
                    return null;
                }
                return this.totals()['coupon_code'];
            },
            getPureValue: function() {
                var price = 0;
                if (this.totals() && this.totals().discount_amount) {
                    price = parseFloat(this.totals().discount_amount);
                }
                return price;
            },
            getValue: function() {
                var value = this.getFormattedPrice(this.getPureValue());
                if(this.getPureValue() == 0) {
                    value = '-' + value;
                }
                return value;
            },
            getTaxValue: function() {
                var discountExclTax = window.checkoutConfig.discount_amount_excl_tax;
                return this.getFormattedPrice(- this.getPureValue() - discountExclTax);
            }
        });
    }
);
