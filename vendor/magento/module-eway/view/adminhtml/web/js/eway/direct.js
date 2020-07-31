/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

define([
    'jquery',
    'Magento_Eway/js/eway',
    'underscore'
], function ($, Eway) {
    'use strict';

    return Eway.extend({
        /**
         * @param {*} value
         * @return {*}
         */
        encryptValue: function (value) {
            return window.eCrypt.encryptValue(value, this.encryptKey);
        },

        /**
         * @return {Object}
         */
        invalidFormValidate: function () {
            $('#' + self.code + '_cc_number').val('');
            $('#' + self.code + '_cc_cid').val('');

            return this;
        },

        /**
         * @param {jQuery.Event} event
         * @return {Object}
         */
        beforeSubmit: function (event) {
            var form = $(event.target),
                ccNumberField = form.find('#' + this.code + '_cc_number_encrypt'),
                ccCidField = form.find('#' + this.code + '_cc_cid_encrypt'),
                ccNumber =  this.encryptValue(ccNumberField.val()),
                ccCid = this.encryptValue(ccCidField.val());

            form.find('#' + this.code + '_cc_number').val(ccNumber);
            form.find('#' + this.code + '_cc_cid').val(ccCid);

            ccNumberField.val('');
            ccCidField.val('');

            return this;
        }
    });
});
