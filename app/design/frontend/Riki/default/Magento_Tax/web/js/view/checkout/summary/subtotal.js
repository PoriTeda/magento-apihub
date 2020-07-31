/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
/*jshint browser:true jquery:true*/
/*global alert*/
define(
    [
        'Magento_Checkout/js/view/summary/abstract-total',
        'Magento_Checkout/js/model/quote',
        'Magento_Checkout/js/model/totals'
    ],
    function (Component, quote, totals) {
        var displaySubtotalMode = window.checkoutConfig.reviewTotalsDisplayMode;
        return Component.extend({
            defaults: {
                displaySubtotalMode: displaySubtotalMode,
                template: 'Magento_Tax/checkout/summary/subtotal'
            },
            totals: quote.getTotals(),
            getValue: function () {
                var price = 0;
                if (this.totals()) {
                    price = this.totals().subtotal;
                }
                return this.getFormattedPrice(price);
            },
            isBothPricesDisplayed: function() {
                return 'both' == this.displaySubtotalMode;
            },
            isIncludingTaxDisplayed: function() {
                return 'including' == this.displaySubtotalMode;
            },
            getValueInclTax: function() {
                var price = 0;
                if (this.totals()) {
                    price = this.totals().subtotal_incl_tax;
                }
                if (window.checkoutConfig.quoteData.riki_hanpukai_qty != null) {
                    if (document.body.className.startsWith("checkout-index-index")) {
                        price = this.totals().subtotal_incl_tax;
                    } else {
                        price = this.totals().subtotal_incl_tax + this.totals().discount_amount;
                    }
                }
                return this.getFormattedPrice(price);
            },
            getValueTax: function() {
                var price = 0;
                if (totals.getSegment('tax_riki')) {
                    price = totals.getSegment('tax_riki').value;
                }
                return this.getFormattedPrice(price);
            }
        });
    }
);
