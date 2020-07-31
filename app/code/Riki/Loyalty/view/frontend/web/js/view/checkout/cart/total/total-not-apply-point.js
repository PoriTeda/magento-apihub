/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
/*global define*/
define(
    [
        'Magento_Checkout/js/view/summary/abstract-total',
        'Magento_Checkout/js/model/quote',
        'Magento_Checkout/js/model/totals'
    ],
    function (Component, quote, totals) {
        "use strict";
        return Component.extend({
            defaults: {
                template: 'Riki_Loyalty/checkout/cart/total/total-not-apply-point'
            },
            totals: quote.getTotals(),
            getValue: function() {
                var price = 0;
                if (totals.getSegment('apply_point')) {
                    price += totals.getSegment('apply_point').value;
                }
                if (totals.getSegment('grand_total')) {
                    price += totals.getSegment('grand_total').value;
                }
                return this.getFormattedPrice(price);
            }
        });
    }
);
