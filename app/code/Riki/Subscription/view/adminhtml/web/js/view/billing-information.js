/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
/*jshint browser:true jquery:true*/
/*global alert*/
define([
        "jquery",
        'ko',
        'uiComponent',
        'Magento_Ui/js/modal/alert',
        'Riki_Subscription/js/model/profile',
        'Riki_Subscription/js/model/customer/address' ,
        "jquery/ui",
        "mage/translate",
        "mage/mage",
        "mage/validation"
    ], function (
        $,
        ko,
        Component,
        alert,
        profile ,
        address ,
        mage ,
        $t
    ) {
        "use strict";

        return Component.extend({
            defaults: {
                template: 'Riki_Subscription/billing-information'
            },
            /** Initialize observable properties */
            initObservable: function () {
                this.billingAddressData = this.getBillingAddressData();
                return this;
            },
            getFormattedAddress: function (addressData) {
                return addressData.inline_address;
            },
            getHasBillingInformation: function () {
                return window.subscriptionConfig.has_billing_address;
            },
            getBillingAddressData: function () {
                if(this.getHasBillingInformation()){
                    return address(window.subscriptionConfig.billing_information);
                }else{
                    return {};
                }
            }
        });
    }
);