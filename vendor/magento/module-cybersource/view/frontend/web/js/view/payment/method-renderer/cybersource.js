/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

define([
    'jquery',
    'Magento_Payment/js/view/payment/iframe'
], function ($, Component) {
    'use strict';

    return Component.extend({
        defaults: {
            template: 'Magento_Cybersource/payment/cybersource-form'
        },
        placeOrderHandler: null,
        validateHandler: null,

        /**
         * @param {Function} handler
         */
        setPlaceOrderHandler: function (handler) {
            this.placeOrderHandler = handler;
        },

        /**
         * @param {Function} handler
         */
        setValidateHandler: function (handler) {
            this.validateHandler = handler;
        },

        /**
         * @returns {Object}
         */
        context: function () {
            return this;
        },

        /**
         * @returns {Boolean}
         */
        isShowLegend: function () {
            return true;
        },

        /**
         * @returns {String}
         */
        getCode: function () {
            return 'cybersource';
        },

        /**
         * @returns {Boolean}
         */
        isActive: function () {
            return true;
        }
    });
});
