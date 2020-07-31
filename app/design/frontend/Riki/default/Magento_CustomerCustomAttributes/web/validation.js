/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
define([
    'jquery',
    'mage/mage',
    'Riki_Customer/js/lib/mage/validation/rules'
], function ($) {
    'use strict';

    return function (config) {
        var dataForm = $('#form-validate');

        if (config.hasUserDefinedAttributes) {
            dataForm = dataForm.mage('fileElement', {});
        }
        dataForm.mage('validation', config);

        if (config.disableAutoComplete) {
            dataForm.find('input:text').attr('autocomplete', 'off');
        }
    };
});
