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
            type: 'eway',
            component: 'Magento_Eway/js/view/payment/method-renderer/' +
                window.checkoutConfig.payment.eway.connectionType
        }
    );

    /**
     * Add view logic here if needed
     */
    return Component.extend({});
});
