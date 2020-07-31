/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

define([
    'jquery',
    'underscore',
    'mage/template'
], function ($, _, mageTemplate) {
    'use strict';

    return {
        /**
         * @param {Object} response
         * @return {*|jQuery}
         */
        build: function (response) {
            var formTmpl =
                '<form action="<%= data.action %>" method="POST" hidden enctype="application/x-www-form-urlencoded">' +
                    '<% _.each(data.fields, function(val, key){ %>' +
                    '<input value="<%= val %>" name="<%= key %>" type="hidden">' +
                    '<% }); %>' +
                    '</form>',
                inputs = {},
                tmpl, index, hiddenFormTmpl;

            for (index in response.fields) { //eslint-disable-line guard-for-in
                inputs[response.fields[index]] = response.values[index];
            }

            hiddenFormTmpl = mageTemplate(formTmpl);
            tmpl = hiddenFormTmpl({
                data: {
                    action: response.action,
                    fields: inputs
                }
            });

            return $(tmpl).appendTo($('[data-container="body"]'));
        }
    };
});
