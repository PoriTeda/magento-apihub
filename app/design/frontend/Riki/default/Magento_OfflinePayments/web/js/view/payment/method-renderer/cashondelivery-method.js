/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
/*browser:true*/
/*global define*/
define(
    [
        'Magento_Checkout/js/view/payment/default',
        'Magento_Checkout/js/model/quote',
        'Magento_Customer/js/model/customer',
        'uiRegistry'
    ],
    function (Component, quote, customer, uiRegistry) {
        'use strict';
        return Component.extend({
            defaults: {
                template: 'Magento_OfflinePayments/payment/cashondelivery'
            },

            /** Returns payment method instructions */
            getInstructions: function() {
                return window.checkoutConfig.payment.instructions[this.item.method];
            },
            isAvailable: function () {
                var deliveryTypes = uiRegistry.get('deliveryTypes');
                if (typeof deliveryTypes == 'function') {
                    deliveryTypes = deliveryTypes();
                    if (typeof deliveryTypes == 'object' && deliveryTypes.length > 1) {
                        return false;
                    }
                }

                if(quote.billingAddress() != null && quote.shippingAddress() != null) {

                    /*flag to check multi checkout or single checkout { true / false }*/
                    var isMultiCheckout = false;

                    var quoteDdateAddress = quote.quoteItemDdateInfo();
                    if (typeof  quoteDdateAddress.addressDdateInfo != 'undefined') {
                        isMultiCheckout = true;
                    }

                    /*hide COD for multi checkout*/
                    if (isMultiCheckout == true) {
                        return false;
                    }

                    /* find riki_type_address { home/ company/shipping } */
                    var rikiAddressType = 'home';

                    if(typeof quote.shippingAddress().customerId == 'undefined') {
                        if( !(typeof quote.shippingAddress().customAttributes == 'undefined')
                            && !(typeof quote.shippingAddress().customAttributes.riki_type_address == 'undefined') ){
                            if( quote.shippingAddress().customAttributes.riki_type_address.value != "" ){
                                rikiAddressType = quote.shippingAddress().customAttributes.riki_type_address.value;
                            }
                        }

                    }else {
                        if( !(typeof quote.shippingAddress().customAttributes.riki_type_address == 'undefined') ){
                            if( quote.shippingAddress().customAttributes.riki_type_address.value != "" ){
                                rikiAddressType = quote.shippingAddress().customAttributes.riki_type_address.value;
                            }
                        }
                    }

                    if( rikiAddressType == 'home' ){
                        /*show COD for address type is home*/
                        return true;
                    } else if ( rikiAddressType == 'company' ){
                        /*flag to check ambassador customer { true/ false }*/
                        var isAmbassador = false;
                        /*Array of customer membership*/
                        var customerMembership = [];

                        if (customer.customerData.custom_attributes.membership) {
                            customerMembership = customer.customerData.custom_attributes.membership.value;
                        } else {
                            customerMembership = [];
                        }

                        if (customerMembership.indexOf(3) != -1) {
                            isAmbassador = true;
                        }

                        /*show COD for address type is company and customer type isAmbassador*/
                        if( isAmbassador === true ){
                            return true;
                        }
                    }

                    /*dont show COD for remaining case*/
                    return false;
                }
            },
            getPaymentFeeValue: function(methodCode) {
                return parseFloat(window.paymentFee[methodCode]);
            }
        });
    }
);
