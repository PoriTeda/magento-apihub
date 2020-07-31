/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

define([
    'jquery',
    'Magento_Payment/js/view/payment/cc-form',
    'Magento_Checkout/js/model/payment/additional-validators'
], function ($, ccFormComponent, additionalValidators) {
    'use strict';

    return ccFormComponent.extend({
        defaults: {
            template: 'Magento_Eway/payment/eway-direct-form',
            active: false,
            scriptLoaded: false,
            imports: {
                onActiveChange: 'active'
            }
        },
        placeOrderHandler: null,
        validateHandler: null,

        /** @inheritdoc */
        initObservable: function () {
            this._super()
                .observe('active scriptLoaded');

            return this;
        },

        /**
         * @param {*} handler
         */
        setPlaceOrderHandler: function (handler) {
            this.placeOrderHandler = handler;
        },

        /**
         * @param {*} handler
         */
        setValidateHandler: function (handler) {
            this.validateHandler = handler;
        },

        /**
         * @return {Object}
         */
        context: function () {
            return this;
        },

        /**
         * @return {Boolean}
         */
        isShowLegend: function () {
            return true;
        },

        /**
         *
         * @return {String}
         */
        getCode: function () {
            return 'eway';
        },

        /**
         * @return {Boolean}
         */
        isActive: function () {
            var active = this.getCode() === this.isChecked();

            this.active(active);

            return active;
        },

        /**
         * @param {*} isActive
         */
        onActiveChange: function (isActive) {
            if (isActive && !this.scriptLoaded()) {
                this.loadScript();
            }
        },

        /**
         * Load script.
         */
        loadScript: function () {
            var state = this.scriptLoaded;

            $('body').trigger('processStart');
            require([this.getUrl()], function () {
                state(true);
                $('body').trigger('processStop');
            });
        },

        /**
         * @return {String}
         */
        getUrl: function () {
            return window.checkoutConfig.payment[this.getCode()].cryptUrl;
        },

        /**
         * @return {String}
         */
        getEncryptKey: function () {
            return window.checkoutConfig.payment[this.getCode()].encryptKey;
        },

        /**
         * @return {Object}
         */
        getData: function () {
            var isEncrypt = window.eCrypt && this.isActive();

            return {
                'method': this.item.method,
                'additionalData': {
                    'cc_type': this.creditCardType(),
                    'cc_exp_year': this.creditCardExpYear(),
                    'cc_exp_month': this.creditCardExpMonth(),

                    'cc_number': isEncrypt ?
                        window.eCrypt.encryptValue(this.creditCardNumber(), this.getEncryptKey()) : '',
                    'cc_cid': isEncrypt ?
                        window.eCrypt.encryptValue(this.creditCardVerificationNumber(), this.getEncryptKey()) : '',
                    'cc_ss_start_month': this.creditCardSsStartMonth(),
                    'cc_ss_start_year': this.creditCardSsStartYear(),
                    'cc_ss_issue': this.creditCardSsIssue()
                }
            };
        },

        /**
         * Place order.
         */
        placeOrder: function () {
            if (this.validateHandler() && additionalValidators.validate()) {
                this.isPlaceOrderActionAllowed(false);
                this._super();
            }
        }
    });
});
