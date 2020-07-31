define(
    [
        'jquery',
        'ko',
        'uiComponent',
        'Magento_Checkout/js/model/quote',
        'Amasty_Coupons/js/action/set-coupon-code',
        'Magento_SalesRule/js/action/cancel-coupon',
        'Magento_Checkout/js/action/get-totals',
        'Magento_Checkout/js/model/resource-url-manager',
        'Magento_Checkout/js/model/error-processor',
        'mage/storage',
        'mage/translate'
    ],
    function (
        $,
        ko,
        Component,
        quote,
        setCouponCodeAction,
        cancelCouponAction,
        getTotalsAction,
        urlManager,
        errorProcessor,
        storage,
        $t
    ) {
        'use strict';

        var totals = quote.getTotals();
        var couponCode = ko.observable(null);
        var fakeCouponCode = ko.observable('');
        var isApplied = ko.observable(couponCode() != null);
        var isLoading = ko.observable(false);
        if (!couponCode()) {
            couponCode('');
        }
        var selectedCoupon = ko.observableArray();

        totals.subscribe(
            function(newValue) {
                var updateCoupon = newValue['coupon_code'] ? newValue['coupon_code'] : '';
                couponCode(updateCoupon);
            }
        );

        if (totals()['coupon_code']) {
            couponCode(totals()['coupon_code']);
        } else {
            if (window.checkoutConfig.hasOwnProperty('quoteData')
                && window.checkoutConfig.quoteData.hasOwnProperty('coupon_code')
            ) {
                var code = window.checkoutConfig.quoteData.coupon_code;
                if (code) {
                    var url = urlManager.getApplyCouponUrl(code, quote.getQuoteId());
                    storage
                        .put(url, {}, false)
                        .done(
                            function (response) {
                                if (response) {
                                    var deferred = $.Deferred();
                                    isLoading(false);
                                    isApplied(true);
                                    getTotalsAction([], deferred);
                                    $.when(deferred).done(
                                        function() {
                                            $('#discount-code').val(response).change();
                                            $('#discount-code-fake').val('');
                                        }
                                    );
                                }
                            }
                        )
                        .fail(
                            function (response) {
                                isLoading(false);
                                errorProcessor.process(response);
                            }
                        );
                }
            }
        }

        return Component.extend(
            {
                defaults: {
                    template: 'Amasty_Coupons/payment/discount'
                },
                couponCode: couponCode,
                fakeCouponCode: fakeCouponCode,
                selectedCoupon: selectedCoupon,

                /**
                 * Applied flag
                 */
                isApplied: isApplied,
                isLoading: isLoading,

                getPromotionDetail: ko.observable(null),

                initialize: function () {
                    this._super();

                    totals.subscribe(function (newValue) {
//                         var ruleName = '';
//                         if(typeof newValue.extension_attributes.promotion_rules != 'undefined') {
//                             var ruleArr = newValue.extension_attributes.promotion_rules;
//                             ruleArr.forEach(
//                                 function(item) {
//                                     if(typeof item != "object") {
//                                         try {
//                                             item = JSON.parse(item);
//                                         } catch (e) {
//                                             return;
//                                         }
//                                     }
//                                     if(item.visible == 1)
//                                         ruleName+= '<span class="promotion">'+ item.title + '</span>';
//                                 }
//                             )
//                         }
//                         this.getPromotionDetail(ruleName);
                    }.bind(this));
                },

                removeSelected : function (obj) {

                    var currentCodeList = couponCode().split(',');
                    var index = currentCodeList.indexOf(obj);
                    if (index > -1) {
                        currentCodeList.splice(index, 1);
                    }

                    isLoading(true);
                    if (currentCodeList.length > 0) {
                       setCouponCodeAction(currentCodeList.join(',') , isApplied, isLoading , true);
                    } else {
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
                        if (code.indexOf(newDiscountCode) == -1) {
                            code.push(newDiscountCode);
                        }
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
                        isLoading(false);
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
                },

                checkIsApplied: function() {
                    return (totals()['coupon_code'] != null);
                }
            }
        );
    }
);
