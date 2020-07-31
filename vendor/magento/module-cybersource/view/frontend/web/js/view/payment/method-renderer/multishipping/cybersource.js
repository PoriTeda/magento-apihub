/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

define([
    'jquery',
    'Magento_Cybersource/js/view/payment/method-renderer/cybersource'
], function ($, Component) {
    'use strict';

    return Component.extend({
        defaults: {
            template: 'Magento_Cybersource/payment/multishipping/cybersource-form'
        }
    });
});
