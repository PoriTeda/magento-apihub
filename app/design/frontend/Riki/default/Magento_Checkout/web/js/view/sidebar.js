/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
/*global define*/
define(
    [
        'uiComponent',
        'ko',
        'jquery',
        'Magento_Checkout/js/model/sidebar',
        'Magento_Checkout/js/model/step-navigator',
        'Magento_Catalog/js/price-utils',
        'Magento_Checkout/js/model/totals',
        'Magento_Checkout/js/model/quote',
        'mage/translate',
        'uiRegistry',
    ],
    function(Component, ko, $, sidebarModel, stepNavigator, priceUtils, totals, quote, $t,registry) {
        'use strict';
        return Component.extend({

            getPromotionDetail: ko.observable(null),
            formattedSubTotal: ko.observable(""),
            formattedGiftWrappingFee: ko.observable(0),
            formattedShippingFee: ko.observable(""),
            formattedSurchargeFee: ko.observable(0),
            discountValue: ko.observable(0),
            formattedGrandTotalNotApplyPoint : ko.observable(""),
            formattedGiftWrappingTaxAmount: ko.observable(0),
            formattedTax: ko.observable(""),
            formattedShippingTax: ko.observable(""),
            fmattedGrandTotal: ko.observable(""),
            formattedGrandTotalTaxAmount: ko.observable(0),
            isDisplayDiscount: ko.observable(true),
            formattedGrandTotal: ko.observable(""),
            initialize: function () {
                this._super();
                var self = this;
                self.geRuleName();
                /* default value */
                this.formattedSubTotal(
                    priceUtils.formatPrice(
                        window.checkoutConfig.totalsData.subtotal_incl_tax, window.checkoutConfig.priceFormat
                    )
                );
                this.formattedTax(
                    priceUtils.formatPrice(
                        window.checkoutConfig.totalsData.tax_amount, window.checkoutConfig.priceFormat
                    )
                );
                this.formattedShippingFee(
                    priceUtils.formatPrice(
                        window.checkoutConfig.totalsData.shipping_incl_tax, window.checkoutConfig.priceFormat
                    )
                );
                this.formattedShippingTax(
                    priceUtils.formatPrice(
                        window.checkoutConfig.totalsData.shipping_tax_amount, window.checkoutConfig.priceFormat
                    )
                );

                this.formattedGrandTotal(
                    priceUtils.formatPrice(
                        window.checkoutConfig.totalsData.base_grand_total, window.checkoutConfig.priceFormat
                    )
                );
                this.formattedGrandTotalTaxAmount(
                    priceUtils.formatPrice(
                        window.checkoutConfig.totalsData.shipping_tax_amount
                        + window.checkoutConfig.totalsData.tax_amount
                        , window.checkoutConfig.priceFormat
                    )
                );

                quote.totals.subscribe(function (newTotalObject) {
                    registry.get('checkout.sidebar.summary.totals.before_grandtotal.gift-wrapping-item-level', function (giftWrapping) {
                        self.formattedGiftWrappingFee(giftWrapping.getIncludingTaxValue());
                        self.formattedGiftWrappingTaxAmount(giftWrapping.getTaxValue());
                    });
                    registry.get('checkout.sidebar.summary.totals.discount', function (discount) {
                        self.discountValue(discount.getValue());
                        self.isDisplayDiscount(discount.isDisplayed());
                    });
                    self.formattedSubTotal(
                        priceUtils.formatPrice(newTotalObject.subtotal_incl_tax, window.checkoutConfig.priceFormat)
                    );
                    self.formattedTax(
                        priceUtils.formatPrice(
                            newTotalObject.tax_amount, window.checkoutConfig.priceFormat
                        )
                    );
                    self.formattedShippingFee(
                        priceUtils.formatPrice(
                            newTotalObject.shipping_incl_tax, window.checkoutConfig.priceFormat
                        )
                    );
                    self.formattedShippingTax(
                        priceUtils.formatPrice(
                            newTotalObject.shipping_tax_amount, window.checkoutConfig.priceFormat
                        )
                    );
                    registry.get('checkout.sidebar.summary.totals.grand-total', function (grandtotal) {
                        self.formattedGrandTotal(grandtotal.getValue());
                    });
                    registry.get('checkout.sidebar.summary.totals.total-not-apply-point', function (total_not_apply_point) {
                        self.formattedGrandTotalNotApplyPoint(total_not_apply_point.getValue());
                    });

                    self.formattedSurchargeFee(
                        priceUtils.formatPrice(
                            totals.getSegment('fee').value, window.checkoutConfig.priceFormat
                        )
                    );

                    self.formattedGrandTotalTaxAmount(
                        priceUtils.formatPrice(
                            newTotalObject.shipping_tax_amount
                            + newTotalObject.tax_amount
                            , window.checkoutConfig.priceFormat
                        )
                    );
                });
                totals.totals.subscribe(function (newValue) {
                    var ruleName = '';

                    if(typeof newValue.extension_attributes.promotion_rules != 'undefined') {
                        var ruleArr = newValue.extension_attributes.promotion_rules;
                        ruleArr.forEach(
                            function(item) {
                                if(typeof item != "object") {
                                    try {
                                        item = JSON.parse(item);
                                    } catch (e) {
                                        return;
                                    }
                                }
                                if(item.visible == 1)
                                    ruleName+= '<span class="promotion">'+ item.title + '</span> <br>';
                            }
                        )
                    }
                    this.getPromotionDetail(ruleName);
                }.bind(this));

                return this;
            },
            setModalElement: function(element) {
                sidebarModel.setPopup($(element));
            },
            getStepCode: function() {
                var stepIndex = stepNavigator.getActiveItemIndex();
                return 'sidebar';
            },
            /**
             * Get earn point
             * @returns {*|Int}
             */
            getValueEarnPoint: function() {
                var point = 0;
                if (totals.getSegment('earn_point')) {
                    point = totals.getSegment('earn_point').value;
                }
                return point;
            },
            /**
             * Get Grand Total
             * @returns {*|Int}
             */
            getValueGrandTotal: function() {
                return priceUtils.formatPrice(
                    this.getPureValueGrandTotal(), window.checkoutConfig.priceFormat
                );
            },
            getPureValueGrandTotal: function () {
                var grandTotal = 0;
                if (totals.getSegment('grand_total')) {
                    grandTotal = totals.getSegment('grand_total').value;
                }
                return grandTotal;
            },

            geRuleName: function() {
                var ruleName = '';
                if(typeof totals.totals().extension_attributes.promotion_rules != 'undefined') {
                    var ruleArr = totals.totals().extension_attributes.promotion_rules;
                    ruleArr.forEach(
                        function(item) {
                            if(typeof item != "object") {
                                try {
                                    item = JSON.parse(item);
                                } catch (e) {
                                    return;
                                }
                            }
                            if(item.visible == 1)
                                ruleName+= '<span class="promotion">'+ item.title + '</span> <br>';
                        }
                    )
                }
                this.getPromotionDetail(ruleName);
            },

            formattedPointBalance: function () {
                var pointBalance = window.customerData.loyalty_reward_point, format = JSON.parse(JSON.stringify(quote.getPriceFormat()));
                format.pattern = '%s ' + $t("Point");
                return priceUtils.formatPrice(pointBalance, format);
            },
            formattedPointUsed: function () {
                var applyPoint = 0, format = JSON.parse(JSON.stringify(quote.getPriceFormat()));
                format.pattern = '%s ' + $.mage.__("Point");
                if (totals.getSegment('apply_point')) {
                    applyPoint += totals.getSegment('apply_point').value;
                }
                return priceUtils.formatPrice(applyPoint, format);
            },
            checkMultiple: function() {
                if( window.location.href.indexOf('multicheckout') >= 0){
                    return true;
                }
                return  false;
            },

        });
    }
);
