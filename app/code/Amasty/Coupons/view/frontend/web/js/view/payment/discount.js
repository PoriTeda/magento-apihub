/**
 * Copyright © 2015 Amasty. All rights reserved.
 */
define(
    [
        'jquery',
        'ko',
        'uiComponent',
        'Magento_Checkout/js/model/quote',
        'Amasty_Coupons/js/action/set-coupon-code',
        'Magento_SalesRule/js/action/cancel-coupon'
    ],
    function ($, ko, Component, quote, setCouponCodeAction, cancelCouponAction) {
        'use strict';

        var totals = quote.getTotals();
        var couponCode = ko.observable(null);
        if (!couponCode()){
            couponCode('');
        }
        var selectedCoupon = ko.observableArray();

        if (totals()['coupon_code']) {
            couponCode(totals()['coupon_code']);
        }
        var isApplied = ko.observable(couponCode() != null);
        var isLoading = ko.observable(false);
        return Component.extend({
            defaults: {
                template: 'Amasty_Coupons/payment/discount'
            },
            couponCode: couponCode,

            selectedCoupon: selectedCoupon,

            /**
             * Applied flag
             */
            isApplied: isApplied,
            isLoading: isLoading,

            removeSelected : function (obj) {
                var currentCodeList = couponCode().split(',');
                var index = currentCodeList.indexOf(obj);
                if (index > -1) {
                    currentCodeList.splice(index, 1);
                }

                isLoading(true);
                if( currentCodeList.length > 0 ){
                    setCouponCodeAction( currentCodeList.join(',') , isApplied, isLoading , true);
                }else{
                    couponCode('');
                    cancelCouponAction(isApplied, isLoading);
                }
            },


            /**
             * Coupon code application procedure
             */
            apply: function() {
                if (this.validate()) {
                    isLoading(true);
                    var newDiscountCode =  $('#discount-code-fake').val();
                    var code = [];
                    code = couponCode().split(',');
                    code.push(newDiscountCode);
                    code = code.filter(function(n){ return n != '' });
                    code = code.join(',');
                    setCouponCodeAction(code, isApplied, isLoading);
                    //couponCode(code);
                }
            },
            /**
             * Cancel using coupon
             */
            cancel: function() {
                if (this.validate()) {
                    isLoading(true);
                    couponCode('');
                    cancelCouponAction(isApplied, isLoading);
                }
            },
            /**
             * Coupon form validation
             *
             * @returns {boolean}
             */
            validate: function() {
                var form = '#discount-form';
                return $(form).validation() && $(form).validation('isValid');
            }
        });
    }
);
