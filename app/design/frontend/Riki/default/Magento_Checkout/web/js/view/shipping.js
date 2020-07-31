/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
/*global define*/
define(
    [
        'jquery',
        "underscore",
        'Magento_Ui/js/form/form',
        'ko',
        'mage/storage',
        'Magento_Customer/js/model/customer',
        'Magento_Customer/js/model/customer/address',
        'Magento_Customer/js/model/address-list',
        'Magento_Checkout/js/model/address-converter',
        'Magento_Checkout/js/model/quote',
        'Magento_Ui/js/model/messageList',
        'Magento_Checkout/js/action/create-shipping-address',
        'Magento_Checkout/js/action/select-shipping-address',
        'Magento_Checkout/js/model/shipping-rates-validator',
        'Magento_Checkout/js/model/shipping-address/form-popup-state',
        'Magento_Checkout/js/model/shipping-service',
        'Magento_Checkout/js/action/select-shipping-method',
        'Magento_Checkout/js/model/shipping-rate-registry',
        'Magento_Checkout/js/action/set-shipping-information',
        'Magento_Checkout/js/model/step-navigator',
        'Magento_Ui/js/modal/modal',
        'Magento_Checkout/js/model/checkout-data-resolver',
        'Magento_Checkout/js/checkout-data',
        'uiRegistry',
        'mage/translate',
        'mage/url',
        'Magento_Checkout/js/model/full-screen-loader',
        'Magento_Checkout/js/model/payment-service',
        'Magento_Checkout/js/model/shipping-rate-service'
    ],
    function(
        $,
        _,
        Component,
        ko,
        storage,
        customer,
        address,
        addressList,
        addressConverter,
        quote,
        messageList,
        createShippingAddress,
        selectShippingAddress,
        shippingRatesValidator,
        formPopUpState,
        shippingService,
        selectShippingMethodAction,
        rateRegistry,
        setShippingInformationAction,
        stepNavigator,
        modal,
        checkoutDataResolver,
        checkoutData,
        registry,
        $t,
        urlBuilder,
        fullScreenLoader,
        paymentService
    ) {
        'use strict';
        var popUp = null;
        return Component.extend({
            defaults: {
                template: 'Magento_Checkout/shipping'
            },
            visible: ko.observable(!quote.isVirtual()),
            errorValidationMessage: ko.observable(false),
            isCustomerLoggedIn: customer.isLoggedIn,
            isFormPopUpVisible: formPopUpState.isVisible,
            isFormInline: ko.observable(addressList().length == 0),
            isNewAddressAdded: ko.observable(false),
            saveInAddressBook: false,
            quoteIsVirtual: quote.isVirtual(),
            redirectCheckoutMulti: urlBuilder.build('multicheckout/#shipping'),
            collectedTotals: ko.observable(false),
            collectedTotalsWhenPaymentInit: ko.observable(false),
            isEditAddress: ko.observable(false),

            initialize: function () {
                var self = this;
                this._super();
                if (!quote.isVirtual()) {
                    stepNavigator.registerStep(
                        'single_order_confirm',
                        '',
                        $t('Order Confirmation'),
                        this.visible, _.bind(this.navigate, this),
                        10
                    );
                }

                /** handle browser back button or back link flow */
                window.onhashchange = function() {
                    if(window.location.hash == '#single_order_confirm') {
                        window.dataLayer = window.dataLayer || [];
                        window.dataLayer.push({
                            'event': 'checkout',
                            'membershipID': window.memberShipId,
                            'currencyCode': 'JPY',
                            'checkoutStepName': 'Payment and Address|お届け先とお支払方法の選択',
                            'ecommerce': {

                                'checkout': {
                                    'actionField': {'step': 2},
                                    'products': window.listProductInQuote
                                }
                            }
                        });

                        var sortedItems = stepNavigator.steps().sort(this.sortItems);
                        var bodyElem = $.browser.safari || $.browser.chrome ? $("body") : $("html");
                        var scrollToElementId = null;
                        sortedItems.forEach(function(element) {
                            if (element.code == 'shipping') {
                                element.isVisible(true);
                            } else {
                                element.isVisible(false);
                            }
                        });
                        $('#checkout .page-title-wrapper .page-title > span').text($.mage.__('Order Confirmation'));
                        bodyElem.animate({scrollTop: $('#shipping').offset().top}, 0);
                    }
                };

                var hasNewAddress = addressList.some(function (address) {
                    return address.getType() == 'new-customer-address';
                });

                this.isNewAddressAdded(hasNewAddress);

                this.isFormPopUpVisible.subscribe(function (value) {
                    if (value) {
                        self.resetShippingAddressError();
                        self.getPopUp().openModal();
                    }
                });

                quote.shippingMethod.subscribe(function (shippingMethod) {
                    self.errorValidationMessage(false);
                    if(shippingMethod != null && !self.isFormInline() && !self.collectedTotals()) {
                        self.collectedTotals(true);
                        registry.get('checkout.rewardPoints' , function (rewardPointsObj){
                            /*use setting using point from profile page*/
                            rewardPointsObj.pointControl(window.customerData.reward_user_setting);
                            rewardPointsObj.pointAmount(window.customerData.reward_user_redeem);
                            if(rewardPointsObj.pointControl() == 2 && rewardPointsObj.pointAmount() >= 0) {
                                rewardPointsObj.apply();
                            }else {
                                rewardPointsObj.applyLabel();
                            }
                        });
                    }
                });

                quote.paymentMethod.subscribe(function (paymentMethod) {
                    if(paymentMethod && paymentMethod.method != "") {
                        if (!self.collectedTotalsWhenPaymentInit()) {
                            var availableList = paymentService.getAvailablePaymentMethods();
                            var i, available = false;
                            for(i = 0; i < availableList.length; i++){
                                if(availableList[i] === paymentMethod){
                                    available = true;
                                    break;
                                }
                            }
                            if(!available){
                                self.collectedTotalsWhenPaymentInit(true);
                            }
                            else {
                                setShippingInformationAction();
                                self.collectedTotalsWhenPaymentInit(true);
                            }
                        }
                    } else{
                        self.collectedTotalsWhenPaymentInit(true);
                    }
                });

                checkoutDataResolver.resolveShippingAddress();

                registry.async('checkoutProvider')(function (checkoutProvider) {
                    var shippingAddressData = checkoutData.getShippingAddressFromData();
                    if (shippingAddressData) {
                        checkoutProvider.set(
                            'shippingAddress',
                            $.extend({}, checkoutProvider.get('shippingAddress'), shippingAddressData)
                        );
                    }
                    checkoutProvider.on('shippingAddress', function (shippingAddressData) {
                        checkoutData.setShippingAddressFromData(shippingAddressData);
                    });
                });

                return this;
            },

            navigate: function () {

            },

            initElement: function(element) {
                if (element.index === 'shipping-address-fieldset') {
                    shippingRatesValidator.bindChangeHandlers(element.elems(), false);
                }
            },

            getPopUp: function() {
                var self = this;
                if (!popUp) {
                    var buttons = this.popUpForm.options.buttons;
                    this.popUpForm.options.buttons = [
                        {
                            text: buttons.save.text ? buttons.save.text : $t('Save Address'),
                            class: buttons.save.class ? buttons.save.class : 'action primary action-save-address',
                            click: self.saveNewAddress.bind(self)
                        }
                    ];
                    this.popUpForm.options.modalClass = "add-address modal_checkout";
                    if(self.isFormInline()) {
                        this.popUpForm.options.modalClass = "add-address no-close";
                    }
                    this.popUpForm.options.clickableOverlay = false;
                    this.popUpForm.options.closed = function() {
                        self.isFormPopUpVisible(false);
                        self.resetShippingAddressError();
                    };
                    popUp = modal(this.popUpForm.options, $(this.popUpForm.element));
                }
                if(this.isEditAddress()) {
                    $('.add-address .modal-footer .action-save-address span').text($t('Save'));
                } else {
                    $('.add-address .modal-footer .action-save-address span').text($t('Save New Address'));
                }
                return popUp;
            },

            resetShippingAddressError: function () {
                var elementFormArray = ['riki_normal_name_group.0', 'riki_normal_name_group.1', 'riki_kana_name_group.0', 'riki_kana_name_group.1', 'riki_nickname', 'postcode', 'region_id', 'street.0', 'telephone'];
                elementFormArray.forEach(function(el) {
                    registry.get('checkout.steps.shipping-step.shippingAddress.shipping-address-fieldset.' + el).error(false);
                });
            },

            /** Show address form popup */
            showFormPopUp: function() {
                this.isFormPopUpVisible(true);
            },

            /** Save new shipping address */
            saveNewAddress: function() {
                var self = this,
                    shippingAddressAction = checkoutData.getShippingAddressAction();
                if(shippingAddressAction == 'add' || (shippingAddressAction == 'edit' && quote.shippingAddress().getType() == 'new-customer-address')) {
                    this.source.set('params.invalid', false);
                    this.source.trigger('shippingAddress.data.validate');
                    this.source.trigger('shippingAddress.custom_attributes.data.validate');

                    if (!this.source.get('params.invalid')) {
                        this.isFormInline(false);
                        fullScreenLoader.startLoader();
                        var payload = checkoutData.getShippingAddressFromData();
                        return storage.post(
                            urlBuilder.build('riki-checkout/address/save'),
                            payload,
                            false,
                            'application/x-www-form-urlencoded'
                        ).done(
                            function (response) {
                                var items = [];
                                /** reset localStorage */
                                checkoutData.setSelectedShippingAddress(null);
                                checkoutData.setNewCustomerShippingAddress(null);
                                messageList.addSuccessMessage({'message': response.message});
                                var customerData = response.customerData;
                                if (Object.keys(customerData).length) {
                                    $.each(customerData.addresses, function (key, item) {
                                        items.push(new address(item));
                                    });
                                }
                                addressList(items);
                                var selectedAddressItem = _.filter(addressList(), function (item) {
                                    return item.customerAddressId == response.addressId;
                                });
                                if(selectedAddressItem.length) {
                                    // Address after edited must be selected as a shipping address
                                    selectShippingAddress(selectedAddressItem[0]);
                                    checkoutData.setSelectedShippingAddress(selectedAddressItem[0].getKey());
                                }

                                //set selected shipping address and billing address are the same
                                quote.billingAddress(quote.shippingAddress());
                                self.getPopUp().closeModal();
                                fullScreenLoader.stopLoader();
                                window.dataLayer = window.dataLayer || [];
                                window.dataLayer.push({
                                    'event': 'checkoutOption',
                                    'ecommerce': {
                                        'checkout_option': {
                                            'actionField': {
                                                'step': 2,
                                                'option': ['Shipping Address - Change']
                                            }
                                        }
                                    }
                                });
                            }).fail(
                            function (response) {
                                messageList.addErrorMessage({'message': response.message});
                                self.getPopUp().closeModal();
                                fullScreenLoader.stopLoader();
                            }
                        );
                    }
                }else {
                    fullScreenLoader.startLoader();
                    var payload = checkoutData.getShippingAddressFromData();
                    return storage.post(
                        urlBuilder.build('riki-checkout/address/save'),
                        payload,
                        false,
                        'application/x-www-form-urlencoded'
                    ).done(
                        function (response) {
                            var items = [];
                            if(!response.error){
                                messageList.addSuccessMessage({'message': response.message});
                                var customerData = response.customerData;
                                if (Object.keys(customerData).length) {
                                    $.each(customerData.addresses, function (key, item) {
                                        items.push(new address(item));
                                    });
                                }
                                var newAddressItem = _.filter(addressList(), function (item) {
                                    return item.getType() == 'new-customer-address';
                                });
                                addressList(items);
                                if(newAddressItem.length) {
                                    addressList.push(newAddressItem[0]);
                                }
                                var selectedAddressId = checkoutData.getShippingAddressFromData().customer_address_id,
                                    selectedAddressItem = _.filter(addressList(), function (item) {
                                        return item.customerAddressId == selectedAddressId;
                                    });
                                if(selectedAddressItem.length) {
                                    // Address after edited must be selected as a shipping address
                                    selectShippingAddress(selectedAddressItem[0]);
                                    checkoutData.setSelectedShippingAddress(selectedAddressItem[0].getKey());
                                }

                                //set selected shipping address and billing address are the same
                                quote.billingAddress(quote.shippingAddress());

                            }else {
                                messageList.addErrorMessage({'message': response.message});
                            }
                            self.getPopUp().closeModal();
                            fullScreenLoader.stopLoader();
                            window.dataLayer = window.dataLayer || [];
                            window.dataLayer.push({
                                'event': 'checkoutOption',
                                'ecommerce': {
                                    'checkout_option': {
                                        'actionField': {
                                            'step': 2,
                                            'option': ['Shipping Address - Change']
                                        }
                                    }
                                }
                            });
                        }
                    ).fail(
                        function (response) {
                            messageList.addErrorMessage({'message': response.message});
                            self.getPopUp().closeModal();
                            fullScreenLoader.stopLoader();
                        }
                    );
                }
            },

            /** Shipping Method View **/
            rates: shippingService.getShippingRates(),
            isLoading: shippingService.isLoading,
            isSelected: ko.computed(function () {
                    return quote.shippingMethod()
                        ? quote.shippingMethod().carrier_code + '_' + quote.shippingMethod().method_code
                        : null;
                }
            ),

            selectShippingMethod: function(shippingMethod) {
                selectShippingMethodAction(shippingMethod);
                checkoutData.setSelectedShippingRate(shippingMethod.carrier_code + '_' + shippingMethod.method_code);
                return true;
            },

            setShippingInformation: function () {
                if (this.validateShippingInformation()) {
                    setShippingInformationAction().done(
                        function() {
                            stepNavigator.next();
                            registry.get('checkout.rewardPoints' , function (rewardPointsObj){
                                rewardPointsObj.pointControl(window.customerData.reward_user_setting);
                                rewardPointsObj.pointAmount(window.customerData.reward_user_redeem);
                                if(rewardPointsObj.pointControl() == 2 && rewardPointsObj.pointAmount() >= 0) {
                                    rewardPointsObj.apply();
                                }else {
                                    rewardPointsObj.applyLabel();
                                }
                            });
                            $('#checkout .page-title-wrapper .page-title > span').text($.mage.__('Review & Payments'));
                        }
                    );
                }
            },

            validateShippingInformation: function () {
                var loginFormSelector = 'form[data-role=email-with-possible-login]',
                    emailValidationResult = customer.isLoggedIn();

                if (!$("input[name='shipping_method']:checked").val()) {
                    this.errorValidationMessage('Please specify a shipping method');
                    return false;
                }

                if (!customer.isLoggedIn()) {
                    $(loginFormSelector).validation();
                    emailValidationResult = Boolean($(loginFormSelector + ' input[name=username]').valid());
                }

                if (!emailValidationResult) {
                    $(loginFormSelector + ' input[name=username]').focus();
                }

                var quoteData = window.checkoutConfig.quoteData;
                if( ! $.isEmptyObject(quoteData['riki_course_id'])) {
                    /* The delivery date is automatically set as the earliest day (for subscription) */
                    $(("input[name='delivery_date'], input[name='next_delivery_date']")).each(function() {
                        var $this = $(this);
                        if($this.val() == "") {
                            $this.val($this.attr('data-earliest'));
                        }
                    });
                }
                return true;
            }
        });
    }
);
