/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
/*global define*/
define([
    'jquery',
    'ko',
    'uiComponent',
    'Magento_Checkout/js/action/select-shipping-address',
    'Magento_Checkout/js/model/quote',
    'Magento_Checkout/js/model/shipping-address/form-popup-state',
    'Magento_Checkout/js/checkout-data',
    'Magento_Customer/js/customer-data'
], function($, ko, Component, selectShippingAddressAction, quote, formPopUpState, checkoutData, customerData) {
    'use strict';
    var countryData = customerData.get('directory-data');

    return Component.extend({
        defaults: {
            template: 'Magento_Checkout/shipping-address/address-renderer/default'
        },

        initObservable: function () {
            this._super();
            this.isSelected = ko.computed(function() {
                var isSelected = false;
                var shippingAddress = quote.shippingAddress();
                if (shippingAddress) {
                    isSelected = shippingAddress.getKey() == this.address().getKey();
                }
                return isSelected;
            }, this);

            return this;
        },

        getCountryName: function(countryId) {
            return (countryData()[countryId] != undefined) ? countryData()[countryId].name : "";
        },

        /** Set selected customer shipping address  */
        selectAddress: function() {
            selectShippingAddressAction(this.address());
            checkoutData.setSelectedShippingAddress(this.address().getKey());

            //set selected shipping address and billing address are the same
            quote.billingAddress(quote.shippingAddress());
        },

        editAddress: function() {
            formPopUpState.isVisible(true);
            this.showPopup();

        },
        showPopup: function() {
            $('[data-open-modal="opc-new-shipping-address"]').trigger('click');
        },
        getRikiName: function() {
            return (this.address().getType() == 'new-customer-address') ? this.address().customAttributes.riki_nickname : this.address().customAttributes.riki_nickname.value;
        },
        getApartment: function() {
            var apartment = '';
            if(typeof this.address().customerId == 'undefined') {
                if(!(typeof this.address().customAttributes == 'undefined'))
                    apartment = this.address().customAttributes.apartment;
            }else {
                if(!(typeof this.address().customAttributes.apartment == 'undefined'))
                    apartment = this.address().customAttributes.apartment.value;
            }
            return apartment;
        }
    });
});
