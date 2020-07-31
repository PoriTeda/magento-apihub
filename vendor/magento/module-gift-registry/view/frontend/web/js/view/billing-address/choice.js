/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

/**
 * @deprecated since version 2.2.0
 */
define(['uiComponent'], function (Component) {
    'use strict';

    return Component.extend({
        defaults: {
            template: 'Magento_GiftRegistry/billing-address/choice'
        },
        giftRegistryId: window.checkoutConfig.giftRegistry.id,
        hasGiftRegistryInCart: window.checkoutConfig.giftRegistry.available && window.checkoutConfig.giftRegistry.id,

        /**
         * @return {Object}
         */
        getAdditionalData: function () {
            return {
                'gift_registry_id': this.giftRegistryId
            };
        }
    });
});
