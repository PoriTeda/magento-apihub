/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

define([
    'uiComponent',
    'Magento_Checkout/js/model/payment/renderer-list'
], function (Component, rendererList) {
    'use strict';

    rendererList.push(
        {
            type: 'worldpay',
            component: 'Magento_Worldpay/js/view/payment/method-renderer/worldpay'
        }
    );

    /** Add view logic here if needed */
    return Component.extend({});
});
