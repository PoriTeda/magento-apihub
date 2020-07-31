/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
define(
    [
        'ko',
        'jquery',
        'uiComponent',
        'Magento_Checkout/js/action/place-order',
        'Magento_Checkout/js/action/select-payment-method',
        'Magento_Checkout/js/model/quote',
        'Magento_Customer/js/model/customer',
        'Magento_Checkout/js/model/payment-service',
        'Magento_Checkout/js/checkout-data',
        'Magento_Checkout/js/model/checkout-data-resolver',
        'Magento_Checkout/js/action/set-shipping-information',
        'uiRegistry',
        'Magento_Checkout/js/model/payment/additional-validators',
        'Magento_Ui/js/model/messages',
        'uiLayout',
        'Magento_Catalog/js/price-utils',
        'mage/url',
        'Magento_Checkout/js/model/url-builder',
        'Magento_Checkout/js/model/step-navigator',
        'Magento_Checkout/js/model/full-screen-loader',
        'Magento_Ui/js/model/messageList',
        'mage/translate',
        'mage/storage'
    ],
    function (
        ko,
        $,
        Component,
        placeOrderAction,
        selectPaymentMethodAction,
        quote,
        customer,
        paymentService,
        checkoutData,
        checkoutDataResolver,
        setShippingInformationAction,
        registry,
        additionalValidators,
        Messages,
        layout,
        priceUtils,
        urlBuilder,
        urlCheckoutBuilder,
        stepNavigator,
        fullScreenLoader,
        messageList,
        $t,
        storage
    ) {
        'use strict';
        return Component.extend({
            redirectAfterPlaceOrder: true,
            /**
             * After place order callback
             */
            afterPlaceOrder: function () {
                //
            },
            isPlaceOrderActionAllowed: ko.observable(quote.billingAddress() != null),
            paygentOption: ko.observable(),
            /**
             * Initialize view.
             *
             * @returns {Component} Chainable.
             */
            initialize: function () {
                this._super().initChildren();
                quote.billingAddress.subscribe(function(address) {
                    this.isPlaceOrderActionAllowed((address !== null));
                }, this);
                checkoutDataResolver.resolveBillingAddress();

                var billingAddressCode = 'billingAddress' + this.getCode();
                registry.async('checkoutProvider')(function (checkoutProvider) {
                    var defaultAddressData = checkoutProvider.get(billingAddressCode);
                    if (defaultAddressData === undefined) {
                        // skip if payment does not have a billing address form
                        return;
                    }
                    var billingAddressData = checkoutData.getBillingAddressFromData();
                    if (billingAddressData) {
                        checkoutProvider.set(
                            billingAddressCode,
                            $.extend({}, defaultAddressData, billingAddressData)
                        );
                    }
                    checkoutProvider.on(billingAddressCode, function (billingAddressData) {
                        checkoutData.setBillingAddressFromData(billingAddressData);
                    }, billingAddressCode);
                });

                return this;
            },

            /**
             * Initialize child elements
             *
             * @returns {Component} Chainable.
             */
            initChildren: function () {
                this.messageContainer = new Messages();
                this.createMessagesComponent();

                return this;
            },

            /**
             * Create child message renderer component
             *
             * @returns {Component} Chainable.
             */
            createMessagesComponent: function () {

                var messagesComponent = {
                    parent: this.name,
                    name: this.name + '.messages',
                    displayArea: 'messages',
                    component: 'Magento_Ui/js/view/messages',
                    config: {
                        messageContainer: this.messageContainer
                    }
                };

                layout([messagesComponent]);

                return this;
            },

            /**
             * Place order.
             */
            placeOrder: function (data, event) {
                var self = this;
                event.stopPropagation();

                registry.get('checkout.steps.shipping-step.shippingAddress', function(shippingStep) {
                    if($('body').hasClass('multicheckout-index-index')) {
                        // fullScreenLoader.startLoader();
                        $('#opc-select-payment-method').modal('closeModal');
                        // fullScreenLoader.stopLoader();
                        // shippingStep.updateDeliveryAndTimeSlot();
                        //
                        // /** Transfer new data render at checkout confirm multiple page */
                        // if(shippingStep.validateShippingInformation()) {
                        //     fullScreenLoader.startLoader();
                        //     storage.get(
                        //         urlBuilder.build("rest/V1/multicheckout/manageCart/" + quote.getQuoteId())
                        //     ).done(
                        //         function (response) {
                        //             var payload = JSON.parse(response);
                        //             quote.quoteItemDdateInfo(payload);
                        //             registry.get('checkout.steps.multiple-checkout-order-confirmation' , function (multipleConfirm){
                        //                 multipleConfirm.addressDdateInfoConfirmTmp(quote.quoteItemDdateInfo());
                        //             });
                        //             setShippingInformationAction().done(
                        //                 function() {
                        //                     fullScreenLoader.stopLoader();
                        //                     $('#opc-select-payment-method').modal('closeModal');
                        //                 }
                        //             );
                        //         }
                        //     ).fail(
                        //         function () {
                        //             fullScreenLoader.stopLoader();
                        //         }
                        //     );
                        //
                        // }

                    }else {
                        if(shippingStep.validateShippingInformation()) {
                            var canCheckout = true;
                            var quoteData = window.checkoutConfig.quoteData;
                            if( ! $.isEmptyObject(quoteData['riki_course_id'])) {
                                $('[name="delivery_date"]').each(function (i) {
                                    var deliveryDate = ($('[name="delivery_date"]:eq('+ i +')').val() != undefined) ? $('[name="delivery_date"]:eq('+ i +')').val() : '';
                                    var bound_vm = ko.dataFor(this);
                                    var cartItems = bound_vm.dataBound;
                                    cartItems.forEach(function(item) {
                                        if(item.is_seasonal_skip) {
                                            if(item.allow_skip_from != null && item.allow_skip_to != null) {
                                                deliveryDate = new Date(deliveryDate);
                                                deliveryDate.setHours(0,0,0,0);
                                                var startDate = new Date(item.allow_skip_from),
                                                    endDate = new Date(item.allow_skip_to);
                                                if(deliveryDate >= startDate && deliveryDate <= endDate) {
                                                    canCheckout = false;
                                                    messageList.addErrorMessage({'message': $t('Selected Seasonal Limited items can not be delivered at the specified delivery date and time, so please change the desired delivery date.')});
                                                    return false;
                                                }
                                            }
                                        }
                                    });
                                });
                            }

                            if(canCheckout) {
                                setShippingInformationAction().done(
                                    function() {
                                        $('#checkout .page-title-wrapper .page-title > span').text($.mage.__('Order Confirm'));
                                        if( ! $.isEmptyObject(quoteData['riki_course_id'])) {
                                            var serviceUrl = urlCheckoutBuilder.createUrl('/rikicarts/mine/cart-total-simulation', {}),
                                                singleConfirm = registry.get('checkout.cartSimulation');
                                            singleConfirm.simulationLoading(true);
                                            singleConfirm.cartSimulation([]);
                                            storage.get(
                                                serviceUrl
                                            ).done(
                                                function (response) {
                                                    if(typeof response.message != 'undefined') {
                                                        response = [];
                                                    }
                                                    singleConfirm.cartSimulation(response);
                                                    singleConfirm.simulationLoading(false);
                                                    $('#opc-select-payment-method').modal('closeModal');
                                                }
                                            ).fail(
                                                function () {
                                                    singleConfirm.cartSimulation([]);
                                                    singleConfirm.simulationLoading(false);
                                                }
                                            );
                                        }
                                    }
                                );
                            }
                        }
                    }
                });
            },

            selectPaymentMethod: function() {
                selectPaymentMethodAction(this.getData());
                checkoutData.setSelectedPaymentMethod(this.item.method);
                var paymentMethodName = this.item.title;
                var paygentOption = checkoutData.getPaygentOption();
                if(paygentOption == 0) {
                    paymentMethodName = $t('Credit card(used before)');
                }
                window.dataLayer = window.dataLayer || [];
                window.dataLayer.push({
                    'event': 'checkoutOption',
                    'ecommerce': {
                        'checkout_option': {
                            'actionField': {
                                'step': 2,
                                'option': ['Payment Type - ' + paymentMethodName]
                            }
                        }
                    }
                });
                return true;
            },

            /**
             * set option for paygent
             */
            selectPaygentOption: function() {
                var self = this;
                self.paygentOption($('input[name="paygent_option"]:checked').val());
                checkoutData.setPaygentOption(self.paygentOption());
                self.selectPaymentMethod();
                if(quote.paymentMethod()) {
                    registry.get('checkout.steps.confirm-info-step' , function (singleConfirm){
                        singleConfirm.titleConfirmButton($t('Complete the Order'));
                        if(quote.paymentMethod().method == 'paygent' && (self.paygentOption() == 1)) {
                            singleConfirm.titleConfirmButton($t('Proceed with credit card payment'));
                        }
                    });
                    registry.get('checkout.steps.multiple-checkout-order-confirmation' , function (multipleConfirm){
                        multipleConfirm.titleConfirmButton($t('Complete the Order'));
                        if(quote.paymentMethod().method == 'paygent' && (self.paygentOption() == 1)) {
                            multipleConfirm.titleConfirmButton($t('Proceed with credit card payment'));
                        }
                    });
                }
                return true;
            },


            isChecked: ko.computed(function () {
                if(quote.paymentMethod()) {
                    var paymentMethodName = quote.paymentMethod().title;
                    if(quote.paymentMethod().method != 'paygent') {
                        $('input[name="paygent_option"]').prop('checked', false);
                        registry.get('checkout.steps.billing-step.payment.payments-list.paygent' , function (el) {
                            el.paygentOption(null);
                        })
                    }else {
                        var paygentOption = checkoutData.getPaygentOption();
                        if(paygentOption == null) {
                            paygentOption = (window.checkoutConfig.cc_used_date) ? 0 : 1;
                        }
                        registry.get('checkout.steps.billing-step.payment.payments-list.paygent' , function (el) {
                            el.paygentOption(paygentOption);
                        });
                        if(paygentOption == 0) {
                            paymentMethodName = $t('Credit card(used before)');
                        }
                    }

                    registry.get('checkout.steps.confirm-info-step' , function (singleConfirm){
                        singleConfirm.paymentMethodName(paymentMethodName);
                        singleConfirm.titleConfirmButton($t('Complete the Order'));
                        var formattedSurchargeFee =  priceUtils.formatPrice(
                            0,window.checkoutConfig.priceFormat
                        );

                        if(typeof window.paymentFee[quote.paymentMethod().method] != "undefined") {
                            formattedSurchargeFee =  priceUtils.formatPrice(
                                window.paymentFee[quote.paymentMethod().method],window.checkoutConfig.priceFormat
                            );
                        }
                        singleConfirm.formattedSurchargeFee(formattedSurchargeFee);
                        if(quote.paymentMethod().method == 'paygent' && ($('input[name="paygent_option"]:checked').val() == 1)) {
                            singleConfirm.titleConfirmButton($t('Proceed with credit card payment'));
                        }
                    });
                    registry.get('checkout.steps.multiple-checkout-order-confirmation' , function (multipleConfirm){
                        multipleConfirm.paymentMethodName(paymentMethodName);
                        multipleConfirm.titleConfirmButton($t('Complete the Order'));
                        var formattedSurchargeFee =  priceUtils.formatPrice(
                            0,window.checkoutConfig.priceFormat
                        );

                        if(typeof window.paymentFee[quote.paymentMethod().method] != "undefined") {
                            formattedSurchargeFee =  priceUtils.formatPrice(
                                window.paymentFee[quote.paymentMethod().method],window.checkoutConfig.priceFormat
                            );
                        }
                        multipleConfirm.formattedSurchargeFee(formattedSurchargeFee);
                        if(quote.paymentMethod().method == 'paygent' && ($('input[name="paygent_option"]:checked').val() == 1)) {
                            multipleConfirm.titleConfirmButton($t('Proceed with credit card payment'));
                        }
                    });
                    return quote.paymentMethod().method;
                }
            }),

            isRadioButtonVisible: ko.computed(function () {
                return paymentService.getAvailablePaymentMethods().length !== 1;
            }),

            /**
             * Get payment method data
             */
            getData: function() {
                return {
                    'paygent_option' : checkoutData.getPaygentOption(),
                    "method": this.item.method,
                    "title" : this.item.title,
                    "po_number": null,
                    "additional_data": null
                };
            },

            /**
             * Get payment method type.
             */
            getTitle: function () {
                return this.item.title;
            },

            /**
             * Get payment method code.
             */
            getCode: function () {
                return this.item.method;
            },

            validate: function () {
                return true;
            },

            getBillingAddressFormName: function() {
                return 'billing-address-form-' + this.item.method;
            },

            disposeSubscriptions: function () {
                // dispose all active subscriptions
                var billingAddressCode = 'billingAddress' + this.getCode();
                registry.async('checkoutProvider')(function (checkoutProvider) {
                    checkoutProvider.off(billingAddressCode);
                });
            }
            ,
            //Custom code goes here
            getPaymentFee: function(isChecked) {
                var visible = false;
                if(isChecked == true
                    && (window.paymentFeeKeyCode.indexOf(this.getCode()) > -1)
                    && (window.paymentFee[this.getCode()] > 0)
                ) {
                    visible = true;
                }
                return visible;
            },
            /**
             * Format fixed amount.
             * @returns {String}
             */
            getFormattedPrice: function (price) {
                return priceUtils.formatPrice(price, quote.getPriceFormat());
            },

            getFixedAmount: function(methodCode) {
                return this.getFormattedPrice(window.paymentFee[methodCode]);
            }
        });
    }
);