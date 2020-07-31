/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

define([
    'jquery',
    'uiComponent',
    'underscore'
], function ($, Class) {
    'use strict';

    return Class.extend({
        defaults: {
            formSelector: '#edit_form',
            active: false,
            scriptLoaded: false,
            imports: {
                onActiveChange: 'active'
            }
        },

        /** @inheritdoc */
        initObservable: function () {
            this._super()
                .observe('active scriptLoaded');
            $(this.formSelector).off('changePaymentMethod.' + this.code)
                .on('changePaymentMethod.' + this.code, this.changePaymentMethod.bind(this));

            return this;
        },

        /**
         * @param {*} isActive
         */
        onActiveChange: function (isActive) {
            this.disableEventListeners();

            if (isActive) {
                window.order.addExcludedPaymentMethod(this.code);

                if (!this.scriptLoaded()) {
                    this.loadScript();
                }
                this.enableEventListeners();
            }
        },

        /**
         * Enable event listeners.
         */
        enableEventListeners: function () {
            $(this.formSelector).on('invalid-form.validate.' + this.code, this.invalidFormValidate.bind(this))
                .on('afterValidate.beforeSubmit', this.beforeSubmit.bind(this));
        },

        /**
         * Disable event listeners.
         */
        disableEventListeners: function () {
            $(self.formSelector).off('invalid-form.validate.' + this.code)
                .off('afterValidate.beforeSubmit');
        },

        /**
         * @return {Object}
         */
        loadScript: function () {
            var state = this.scriptLoaded;

            $('body').trigger('processStart');
            require([this.cryptUrl], function () {
                state(true);
                $('body').trigger('processStop');
            });

            return this;
        },

        /**
         * @param {*} event
         * @param {*} method
         * @return {Object}
         */
        changePaymentMethod: function (event, method) {
            this.active(method === this.code);

            return this;
        },

        /**
         * @return {Object}
         */
        invalidFormValidate: function () {
            return this;
        },

        /**
         * @return {Object}
         */
        beforeSubmit: function () {
            return this;
        }

    });
});
