/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
/*jshint browser:true jquery:true*/
/*global alert*/
define(
    [
        'jquery',
        "underscore",
        'uiComponent',
        'ko',
        'Magento_Checkout/js/model/quote',
        'Magento_Checkout/js/model/step-navigator',
        'Magento_Checkout/js/model/payment-service',
        'Magento_Checkout/js/model/payment/method-converter',
        'Magento_Checkout/js/model/checkout-data-resolver',
        'mage/url',
        'uiRegistry',
        'Magento_Ui/js/modal/modal',
        'mage/translate'
    ],
    function (
        $,
        _,
        Component,
        ko,
        quote,
        stepNavigator,
        paymentService,
        methodConverter,
        checkoutDataResolver,
        urlBuilder,
        registry,
        modal,
        $t
    ) {
        'use strict';

        /** Set payment methods to collection */
        paymentService.setPaymentMethods(methodConverter(window.checkoutConfig.paymentMethods));

        return Component.extend({
            defaults: {
                template: 'Magento_Checkout/payment-in-shipping',
                activeMethod: ''
            },
            isVisible: ko.observable(quote.isVirtual()),
            quoteIsVirtual: quote.isVirtual(),
            isPaymentMethodsAvailable: ko.computed(function () {
                return paymentService.getAvailablePaymentMethods().length > 0;
            }),
            isPreferredChecked: ko.observable(false),
            isSavePreferredMethodAvailable: ko.observable(true),
            redirectCartPage : ko.observable(""),
            modalWindow: null,
            selectedPaymentLabel: ko.observable('選択してください'),

            initialize: function () {
                this._super();
                this.redirectCartPage(urlBuilder.build('checkout/cart'));              
                return this;
            },

            isSavePreferredMethodVisible: ko.computed(function () {
                var countMethod = 0;
                $.each(paymentService.getAvailablePaymentMethods(), function (index, item) {
                    registry.get('checkout.steps.billing-step.payment.payments-list.' + item.method , function (obj){
                       if(obj.isAvailable()) countMethod++;
                    });
                });
                return countMethod > 1;
            }),

            getFormKey: function() {
                return window.checkoutConfig.formKey;
            },

            goTo: function (str) {
                stepNavigator.navigateToCustom(str);
            },

            goBack: function () {
                window.history.go(-1);
                return false;
            },

            showPaymentPopupModal: function () {
                $(this.modalWindow).modal('openModal');
            },

            initModalPopup: function (element) {
                var optionsPopup;
                this.modalWindow = element;

                optionsPopup = {
                    innerScroll: true,
                    responsive: false,
                    title: $t('Select a payment method'),
                    modalClass: 'select-payment-method-modal checkout-payment-method modal_checkout',
                    type: 'popup',
                    overlayClass: 'modals-overlay-payment-method-modal',
                    buttons: []
                };
                modal(optionsPopup, $(this.modalWindow));
            }
        });
    }
);