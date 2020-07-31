define(
    [
        'jquery',
        'ko',
        'uiComponent',
        'Magento_Checkout/js/model/totals'
    ],
    function (
        $,
        ko,
        Component,
        totals
    ) {
        'use strict';
        return Component.extend({
            defaults: {
                template: 'Riki_Loyalty/checkout/summary/point-used'
            },

            pointAmount: function() {
                var point = 0;
                if (totals.getSegment('apply_point')) {
                    point = totals.getSegment('apply_point').value;
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