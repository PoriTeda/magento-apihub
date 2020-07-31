/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
/*jshint browser:true*/
/*global alert*/
/**
 * Checkout adapter for customer data storage
 */
define([
    'jquery',
    'Magento_Customer/js/model/address-list',
    'Magento_Checkout/js/model/quote',
    'Magento_Checkout/js/checkout-data',
    'Magento_Checkout/js/action/create-shipping-address',
    'Magento_Checkout/js/action/select-shipping-address',
    'Magento_Checkout/js/action/select-shipping-method',
    'Magento_Checkout/js/model/payment-service',
    'Magento_Checkout/js/action/select-payment-method',
    'Magento_Checkout/js/model/address-converter',
    'Magento_Checkout/js/action/select-billing-address',
    'Magento_Checkout/js/action/create-billing-address',
    'underscore',
    'Magento_Catalog/js/price-utils',
    'uiRegistry',
    'mage/url'
], function (
    $,
    addressList,
    quote,
    checkoutData,
    createShippingAddress,
    selectShippingAddress,
    selectShippingMethodAction,
    paymentService,
    selectPaymentMethodAction,
    addressConverter,
    selectBillingAddress,
    createBillingAddress,
    _,
    priceUtils,
    registry,
    urlBuilder
) {
    'use strict';

    return {
        resolveEstimationAddress: function () {
            if (checkoutData.getShippingAddressFromData()) {
                var address = addressConverter.formAddressDataToQuoteAddress(checkoutData.getShippingAddressFromData());
                selectShippingAddress(address);
            } else {
                this.resolveShippingAddress();
            }
            if (quote.isVirtual()) {
               if  (checkoutData.getBillingAddressFromData()) {
                    address = addressConverter.formAddressDataToQuoteAddress(checkoutData.getBillingAddressFromData());
                    selectBillingAddress(address);
                } else {
                   this.resolveBillingAddress();
               }
            }

        },

        resolveShippingAddress: function () {
            var newCustomerShippingAddress = checkoutData.getNewCustomerShippingAddress();
            if (newCustomerShippingAddress) {
                createShippingAddress(newCustomerShippingAddress);
            }
            this.applyShippingAddress();
        },

        applyShippingAddress: function (isEstimatedAddress) {
            if (addressList().length == 0) {
                var address = addressConverter.formAddressDataToQuoteAddress(checkoutData.getShippingAddressFromData());
                selectShippingAddress(address);
            }
            var shippingAddress = quote.shippingAddress(),
                isConvertAddress = isEstimatedAddress || false,
                addressData;
            if (!shippingAddress) {
                var isShippingAddressInitialized = addressList.some(function (address) {
                    if (checkoutData.getSelectedShippingAddress() == address.getKey()) {
                        addressData = isConvertAddress
                            ? addressConverter.addressToEstimationAddress(address)
                            : address;
                        selectShippingAddress(addressData);
                        return true;
                    }
                    return false;
                });

                if (!isShippingAddressInitialized) {
                    isShippingAddressInitialized = addressList.some(function (address) {
                        if(!_.isUndefined(window.checkoutConfig.customerData.default_shipping)){
                            if(address.customerAddressId == window.checkoutConfig.customerData.default_shipping){
                                addressData = isConvertAddress
                                    ? addressConverter.addressToEstimationAddress(address)
                                    : address;
                                selectShippingAddress(addressData);
                                return true;
                            }
                        }
                        return false;
                    });
                }

                if (!isShippingAddressInitialized) {
                    if (!_.isUndefined(window.checkoutConfig.customerData.custom_attributes.amb_type) && window.checkoutConfig.customerData.custom_attributes.amb_type.value == '1' && !isShippingAddressInitialized) {
                        isShippingAddressInitialized = addressList.some(function (address) {
                            if (!_.isUndefined(address.customAttributes.riki_type_address) && address.customAttributes.riki_type_address.value == 'company') {
                                addressData = isConvertAddress
                                    ? addressConverter.addressToEstimationAddress(address)
                                    : address;
                                selectShippingAddress(addressData);
                                return true;
                            }
                            return false;
                        });
                    }
                }

                if (!isShippingAddressInitialized) {
                    isShippingAddressInitialized = addressList.some(function (address) {
                        if(!_.isUndefined(window.checkoutConfig.customerData.default_shipping)){
                            if(address.customerAddressId == window.checkoutConfig.customerData.default_shipping){
                                addressData = isConvertAddress
                                    ? addressConverter.addressToEstimationAddress(address)
                                    : address;
                                selectShippingAddress(addressData);
                                return true;
                            }
                        } else if (address.isDefaultShipping()) {
                            addressData = isConvertAddress
                                ? addressConverter.addressToEstimationAddress(address)
                                : address;
                            selectShippingAddress(addressData);
                            return true;
                        }
                        return false;
                    });
                }
                if (!isShippingAddressInitialized && addressList().length > 0) {
                    addressData = isConvertAddress
                        ? addressConverter.addressToEstimationAddress(addressList()[0])
                        : addressList()[0];
                    selectShippingAddress(addressData);
                }
            }
        },

        resolveShippingRates: function (ratesData) {
            var selectedShippingRate = checkoutData.getSelectedShippingRate();
            var availableRate = false;

            if (ratesData.length == 1) {
                //set shipping rate if we have only one available shipping rate
                selectShippingMethodAction(ratesData[0]);
                return;
            }

            if (quote.shippingMethod()) {
                availableRate = _.find(ratesData, function (rate) {
                    return rate.carrier_code == quote.shippingMethod().carrier_code
                        && rate.method_code == quote.shippingMethod().method_code;
                });
            }

            if (!availableRate && selectedShippingRate) {
                availableRate = _.find(ratesData, function (rate) {
                    return rate.carrier_code + "_" + rate.method_code === selectedShippingRate;
                });
            }

            if (!availableRate && window.checkoutConfig.selectedShippingMethod) {
                availableRate = true;
                selectShippingMethodAction(window.checkoutConfig.selectedShippingMethod);
            }

            //Unset selected shipping method if not available
            if (!availableRate) {
                selectShippingMethodAction(null);
            } else {
                selectShippingMethodAction(availableRate);
            }
        },

        resolvePaymentMethod: function () {
            var availablePaymentMethods = paymentService.getAvailablePaymentMethods();
            var selectedPaymentMethod = checkoutData.getSelectedPaymentMethod();
            var preferendMethod = {
                "method": "",
                "title": "",
                "po_number": null,
                "additional_data": null
            };
            if(selectedPaymentMethod == null && availablePaymentMethods.length > 1 && !_.isUndefined( window.customerData.custom_attributes.preferred_payment_method) && window.customerData.custom_attributes.preferred_payment_method.value != null) {
                preferendMethod.method = window.customerData.custom_attributes.preferred_payment_method.value;
                availablePaymentMethods.some(function (payment) {
                    if (payment.method == preferendMethod.method) {
                        preferendMethod.title = payment.title;
                    }
                });
            }
            if(selectedPaymentMethod == null && availablePaymentMethods.length > 1) {
                registry.get('checkout.steps.confirm-info-step' , function (singleConfirm){
                    singleConfirm.paymentMethodName(preferendMethod.title);
                    var formattedSurchargeFee =  priceUtils.formatPrice(
                        0,window.checkoutConfig.priceFormat
                    );

                    if(typeof window.paymentFee[preferendMethod.method] != "undefined") {
                        formattedSurchargeFee =  priceUtils.formatPrice(
                            window.paymentFee[preferendMethod.method],window.checkoutConfig.priceFormat
                        );
                    }
                    singleConfirm.formattedSurchargeFee(formattedSurchargeFee);
                });
                registry.get('checkout.steps.multiple-checkout-order-confirmation' , function (multipleConfirm){
                    multipleConfirm.paymentMethodName(preferendMethod.title);
                    var formattedSurchargeFee =  priceUtils.formatPrice(
                        0,window.checkoutConfig.priceFormat
                    );

                    if(typeof window.paymentFee[preferendMethod.method] != "undefined") {
                        formattedSurchargeFee =  priceUtils.formatPrice(
                            window.paymentFee[preferendMethod.method],window.checkoutConfig.priceFormat
                        );
                    }
                    multipleConfirm.formattedSurchargeFee(formattedSurchargeFee);
                });
                quote.paymentMethod(preferendMethod);
                selectedPaymentMethod = preferendMethod.method;
            }
            if(preferendMethod.method == '') {
                registry.get('checkout.steps.billing-step.payment' , function (el){
                    el.isSavePreferredMethodAvailable(false);
                });
            }
            if (selectedPaymentMethod) {
                availablePaymentMethods.some(function (payment) {
                    if (payment.method == selectedPaymentMethod) {
                        payment.paygent_option = checkoutData.getPaygentOption();
                        selectPaymentMethodAction(payment);
                    }
                });
            }
        },

        resolveBillingAddress: function () {
            var selectedBillingAddress = checkoutData.getSelectedBillingAddress(),
                newCustomerBillingAddressData = checkoutData.getNewCustomerBillingAddress(),
                shippingAddress = quote.shippingAddress();

            if (selectedBillingAddress) {
                if (selectedBillingAddress == 'new-customer-address' && newCustomerBillingAddressData) {
                    selectBillingAddress(createBillingAddress(newCustomerBillingAddressData));
                } else {
                    addressList.some(function (address) {
                        if (selectedBillingAddress == address.getKey()) {
                            selectBillingAddress(address);
                        }
                    });
                }
            } else {
                this.applyBillingAddress()
            }
        },
        applyBillingAddress: function () {
            if (quote.billingAddress()) {
                selectBillingAddress(quote.billingAddress());
                return;
            }
            var shippingAddress = quote.shippingAddress();
            if (shippingAddress
                && shippingAddress.canUseForBilling()
                && (shippingAddress.isDefaultShipping() || !quote.isVirtual())) {
                //set billing address same as shipping by default if it is not empty
                selectBillingAddress(quote.shippingAddress());
            }
        }
    }
});
