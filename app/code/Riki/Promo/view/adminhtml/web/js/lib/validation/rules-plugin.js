define([
    'jquery',
    'underscore',
    'Magento_Ui/js/lib/validation/utils',
    'mage/translate',
    'jquery/validate',
    'jquery/ui'
], function ($, _, utils) {
    'use strict';

    return function (validationRules) {

        var newRules = _.mapObject({
            'validate-wbs-code': [
                function (value) {
                    return utils.isEmpty(value) || /^AC-\d{8}$/.test(value);
                },
                $.mage.__('A valid WBS Code/Account Code must be specified before the Promotion or Coupon can be activated')
            ],
            'validate-select-subscription': [
                function (value) {
                    return utils.isEmpty(value) || value != -1;
                },
                $.mage.__('This is a required field.')
            ]
        }, function (data) {
            return {
                handler: data[0],
                message: data[1]
            };
        });

        $.extend(validationRules, newRules);

        return validationRules;
    };
});