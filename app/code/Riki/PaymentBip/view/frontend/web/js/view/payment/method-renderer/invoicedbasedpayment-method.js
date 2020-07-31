/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
/*browser:true*/
/*global define*/
define(
    [
        'Magento_Checkout/js/view/payment/default',
        'Magento_Customer/js/model/customer'
    ],
    function (Component, customer) {
        'use strict';

        return Component.extend({
            defaults: {
                template: 'Riki_PaymentBip/payment/invoicedbasedpayment'
            },

            /** Returns send check to info */
            getMailingAddress: function() {
                return window.checkoutConfig.payment.checkmo.mailingAddress;
            },
            isAvailable: function () {
                if (typeof customer.customerData.custom_attributes.b2b_flag != 'undefined' && typeof customer.customerData.custom_attributes.shosha_business_code != 'undefined') {
                    if (customer.customerData.custom_attributes.b2b_flag.value == '1' && customer.customerData.custom_attributes.shosha_business_code.value != '') {
                        return true;
                    }
                }
                return false;
            }

        });
    }
);
