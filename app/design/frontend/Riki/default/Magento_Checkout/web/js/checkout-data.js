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
    'Magento_Customer/js/customer-data'
], function ($, storage) {
    'use strict';

    var cacheKey = 'checkout-data';

    var getData = function () {
        return storage.get(cacheKey)();
    };

    var saveData = function (checkoutData) {
        storage.set(cacheKey, checkoutData);
    };

    if ($.isEmptyObject(getData())) {
        var checkoutData = {
            'selectedShippingAddress': null,
            'shippingAddressFromData' : null,
            'newCustomerShippingAddress' : null,
            'selectedShippingRate' : null,
            'selectedPaymentMethod' : null,
            'selectedBillingAddress' : null,
            'billingAddressFormData' : null,
            'newCustomerBillingAddress' : null,
            'shippingAddressAction' : null,
            'paygentOption': null,
            'warningMessage': null,
            'errorMessage': false,
            'focusContent': false,
            'backSteps': 1
        };
        saveData(checkoutData);
    }

    return {
        setSelectedShippingAddress: function (data) {
            var obj = getData();
            obj.selectedShippingAddress = data;
            saveData(obj);
        },

        getSelectedShippingAddress: function () {
            return getData().selectedShippingAddress;
        },

        setShippingAddressFromData: function (data) {
            var obj = getData();
            obj.shippingAddressFromData = data;
            saveData(obj);
        },

        getShippingAddressFromData: function () {
            return getData().shippingAddressFromData;
        },

        setNewCustomerShippingAddress: function (data) {
            var obj = getData();
            obj.newCustomerShippingAddress = data;
            saveData(obj);
        },

        getNewCustomerShippingAddress: function () {
            return getData().newCustomerShippingAddress;
        },

        setSelectedShippingRate: function (data) {
            var obj = getData();
            obj.selectedShippingRate = data;
            saveData(obj);
        },

        getSelectedShippingRate: function() {
            return getData().selectedShippingRate;
        },

        setSelectedPaymentMethod: function (data) {
            var obj = getData();
            obj.selectedPaymentMethod = data;
            saveData(obj);
        },

        getSelectedPaymentMethod: function() {
            return getData().selectedPaymentMethod;
        },

        setSelectedBillingAddress: function (data) {
            var obj = getData();
            obj.selectedBillingAddress = data;
            saveData(obj);
        },

        getSelectedBillingAddress: function () {
            return getData().selectedBillingAddress;
        },

        setBillingAddressFromData: function (data) {
            var obj = getData();
            obj.billingAddressFromData = data;
            saveData(obj);
        },

        getBillingAddressFromData: function () {
            return getData().billingAddressFromData;
        },

        setNewCustomerBillingAddress: function (data) {
            var obj = getData();
            obj.newCustomerBillingAddress = data;
            saveData(obj);
        },

        getNewCustomerBillingAddress: function () {
            return getData().newCustomerBillingAddress;
        },

        getValidatedEmailValue: function () {
            var obj = getData();
            return (obj.validatedEmailValue) ? obj.validatedEmailValue : '';
        },

        setValidatedEmailValue: function (email) {
            var obj = getData();
            obj.validatedEmailValue = email;
            saveData(obj);
        },

        getInputFieldEmailValue: function () {
            var obj = getData();
            return (obj.inputFieldEmailValue) ? obj.inputFieldEmailValue : '';
        },

        setInputFieldEmailValue: function (email) {
            var obj = getData();
            obj.inputFieldEmailValue = email;
            saveData(obj);
        },

        getShippingAddressAction: function () {
            return getData().shippingAddressAction;
        },

        setShippingAddressAction: function (data) {
            var obj = getData();
            obj.shippingAddressAction = data;
            saveData(obj);
        },

        getPaygentOption: function () {
            return getData().paygentOption;
        },

        setPaygentOption: function (data) {
            var obj = getData();
            obj.paygentOption = data;
            saveData(obj);
        },

        getWarningMessage: function () {
            return getData().warningMessage;
        },

        setWarningMessage: function (data) {
            var obj = getData();
            obj.warningMessage = data;
            saveData(obj);
        },

        getErrorMessage: function () {
            return getData().errorMessage;
        },

        setErrorMessage: function (data) {
            var obj = getData();
            obj.errorMessage = data;
            saveData(obj);
        },

        getFocusContent: function () {
            return getData().focusContent;
        },

        setFocusContent: function (data) {
            var obj = getData();
            obj.focusContent = data;
            saveData(obj);
        },

        getBackSteps: function () {
            return getData().backSteps;
        },

        setBackSteps: function (data) {
            var obj = getData();
            obj.backSteps = data;
            saveData(obj);
        }
    }
});
