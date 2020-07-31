define(
    [
        'jquery',
        'ko',
        'uiComponent',
        'Magento_Checkout/js/model/totals',
        'Magento_Catalog/js/price-utils'
    ],
    function (
        $,
        ko,
        Component,
        totals,
        priceUtils
    ) {
        'use strict';
        return Component.extend({
            defaults: {
                template: 'Riki_Loyalty/checkout/summary/point-used'
            },

            pointAmount: function () {
                var point = 0;
                if (totals.getSegment('apply_point')) {
                    point = '-' + priceUtils.formatPrice(
                        totals.getSegment('apply_point').value, window.checkoutConfig.priceFormat
                    );
                }
                return point;
            },

            hasPointForTrial: function () {
                var hasPointForTrial = new Boolean(window.checkoutConfig.quoteData.point_for_trial);
                return hasPointForTrial.valueOf();
            },

            trialPointAmount: function () {
                var point = 0;
                if (totals.getSegment('apply_point')) {
                    return totals.getSegment('apply_point').value;
                }
                return point;
            },

            initialize: function () {
                this._super();
                return this;
            }
        });
    }
);