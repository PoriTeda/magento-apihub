/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
/*jshint browser:true jquery:true*/
/*global alert*/
define([
        "jquery",
        "ko",
        "uiComponent",
        "mage/mage",
        "mage/translate",
        'Magento_Customer/js/customer-data',
        "Riki_Subscription/js/model/profile-list",
        "Riki_Subscription/js/model/utils"
    ], function (
        $,
        ko,
        Component,
        mage,
        $t,
        customerData,
        profileList,
        utils
    ) {
        "use strict";

        return Component.extend({
            defaults: {
                template: "Magento_Catalog/profile-information"
            },
            /** Initialize observable properties */
            initObservable: function () {
                this._super();
                this.profiles = customerData.get('profiles');
                this.productId = window.productId;
                return this;
            },
            formatCurrency: function(value){
                return utils.getFormattedPrice(value);
            },
            getDeliveryTo: function(profileId) {
                if (this.deliveryTypes && this.deliveryTypes[profileId]) {
                    return $t(this.deliveryTypes[profileId]);
                }
            },
            trans: function (str) {
                return $t(str);
            }
        });
    }
);