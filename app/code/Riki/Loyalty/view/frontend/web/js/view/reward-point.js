define(
    [
        'jquery',
        'ko',
        'uiComponent',
        'Magento_Checkout/js/model/quote',
        'Magento_Customer/js/customer-data',
        'Magento_Checkout/js/model/totals',
        'Magento_Catalog/js/price-utils',
        'Riki_Loyalty/js/action/set-reward-point',
        'Magento_Ui/js/modal/modal',
        'mage/translate',
        'mage/url'
    ],
    function ($,
              ko,
              Component,
              quote,
              customerData,
              total,
              priceUtils,
              setRewardPoint,
              modal,
              $t,
              urlBuilder

    ) {
        'use strict';
        return Component.extend({
            defaults: {
                template: 'Riki_Loyalty/reward-point'
            },
            pointControl: ko.observable(window.customerData.reward_user_setting),
            pointAmount: ko.observable(window.customerData.reward_user_redeem),
            modalWindow: null,
            urlPointsHistory: urlBuilder.build('loyalty/reward/ '),
            urlPointsExpired: urlBuilder.build('loyalty/reward/expired/'),

            initialize: function () {
                this._super();
                var self = this;
                quote.totals.subscribe(function (newValue) {
                    self.pointEstimation();
                });
                self.pointControl.subscribe(function (option) {
                    /** START google dataLayer tag */
                    var optionMessage = '';
                    if(option == 0) {
                        optionMessage = 'Do Not Use Points Option Selected';
                    }else if(option == 1) {
                        optionMessage = 'All Points Used On Order';
                    }else {
                        optionMessage = 'Some Points Used On Order';
                    }
                    window.dataLayer = window.dataLayer || [];
                    window.dataLayer.push({
                        'event': 'checkoutOption',
                        'ecommerce': {
                            'checkout_option': {
                                'actionField': {
                                    'step': 2,
                                    'option': [optionMessage]
                                }
                            }
                        }
                    });
                    
                    if(option == 2 && $('#point-amount').val() >= 0) {
                        self.apply();
                    }else {
                        self.applyLabel();
                    }
                });

                return this;
            },
            isSubscription: function () {
                var isSubscription = new Boolean(window.checkoutConfig.quoteData.riki_course_id);
                return isSubscription.valueOf();
            },
            getFormattedPrice: function (price) {
                return priceUtils.formatPrice(price, quote.getPriceFormat());
            },
            pointEstimation: function () {
                var price = 0, paymentMethod = quote.getPaymentMethod()();
                if (total.getSegment('apply_point')) {
                    price += total.getSegment('apply_point').value;
                }
                if (total.getSegment('grand_total')) {
                    price += total.getSegment('grand_total').value;
                }
                var paymentFee = parseInt(window.paymentFee.cashondelivery);
                if (!this.isSubscription() && paymentMethod && paymentMethod.method == 'cashondelivery' &&
                    window.customerData.loyalty_reward_point < price && price > paymentFee) {
                    price -= paymentFee;
                }
                if (this.isSubscription() && paymentMethod && paymentMethod.method == 'cashondelivery' &&
                    price > paymentFee) {
                    price -= paymentFee;
                }
                return Math.min(window.customerData.loyalty_reward_point, parseInt(price));
            },
            validate: function () {
                var pointEstimation = this.pointEstimation(),
                    pointEstimationFormatted = this.getFormattedPrice(pointEstimation),
                    balance = this.getFormattedPrice(window.customerData.loyalty_reward_point);
                $.validator.addMethod(
                    'point-validate-digits', function (v) {
                        return $.mage.isEmptyNoTrim(v) || !/[^\d]/.test(v);
                    }, $.mage.__('Please enter digits number in this field.'));
                $.validator.addMethod(
                    'point-redeem-negative', function (v) {
                        v = $.mage.parseNumber(v);
                        return !isNaN(v) && v > 0 && !$.mage.isEmpty(v);
                    }, $.mage.__('(Tent) You have input an invalid number'));
                $.validator.addMethod(
                    'point-redeem-total', function (v) {
                        v = $.mage.parseNumber(v);
                        return v <= pointEstimation;
                    }, $.mage.__("Your point is insufficient, you can not use the specified number of points, please allow you to choose your payment method again."));
                $.validator.addMethod(
                    'point-redeem-balance', function (v) {
                        v = $.mage.parseNumber(v);
                        return v <= window.customerData.loyalty_reward_point;
                    }, $.mage.__("Your point is insufficient, you can not use the specified number of points, please allow you to choose your payment method again."));
                var form = '#reward-point-form';
                return $(form).validation() && $(form).validation('isValid');
            },
            apply: function () {
                this.pointControl(2);
                if (this.validate()) {
                    setRewardPoint(this.pointControl(), this.pointAmount(), this.pointAmount);
                } else {
                    /** Set 0 point when specificPoint empty or 0 */
                    setRewardPoint(2, 0, this.pointAmount);
                }
            },
            applyLabel: function () {
                setRewardPoint(this.pointControl(), this.pointAmount(), this.pointAmount);

            },
            specificPoint: function() {
                if ($('#point-amount').val() >= 0) {
                    this.apply();
                }
            },
            hasPointForTrial: function () {
                var hasPointForTrial = new Boolean(window.checkoutConfig.quoteData.point_for_trial);
                return hasPointForTrial.valueOf();
            },

            getGrandTotal: function() {
                var price = 0;
                if (total.getSegment('apply_point')) {
                    price += total.getSegment('apply_point').value;
                }
                if (total.getSegment('grand_total')) {
                    price += total.getSegment('grand_total').value;
                }
                if (total.getSegment('fee')) {
                    price -= total.getSegment('fee').value;
                }
                return price;
            },
            pointForTrial: function() {
                var pointForTrial = (window.checkoutConfig.quoteData.point_for_trial).valueOf();
                var grandTotal = this.getGrandTotal();
                if ( pointForTrial >= grandTotal) {
                    return grandTotal;
                }
                return pointForTrial;
            },

            initPopupModal : function (element) {
                var optionsPopup;
                this.modalWindow = element;

                optionsPopup = {
                    innerScroll: true,
                    responsive: false,
                    title: $t('Reward Points Balance'),
                    modalClass: 'select-reward-point-modal modal_checkout',
                    type: 'popup',
                    buttons: [{
                        text: $t('Ok'),
                        class: 'action primary action-select-option',
                        click: function(){
                            this.closeModal();
                        }
                    }]
                };
                modal(optionsPopup, $(this.modalWindow));
            },

            showPopupModal: function () {
                $(this.modalWindow).modal('openModal');
            }

        });
    }
);