/*jshint jquery:true*/
(function (factory) {
    if (typeof define === 'function' && define.amd) {
        define(
            [
            "jquery",
            "Magento_Ui/js/lib/validation/validator",
            "mage/translate"
            ], factory
        );
    } else {
        factory(jQuery);
    }
}(function ($, validator, $t) {
    "use strict";

    validator.addRule(
        'validate-custom-postal-code', function (value) {
            return /^\d{3}-\d{4}$/.test(value) || /^\d{7}$/.test(value);
        }, $t('Your Postcode must be in the format 000-0000')
    );
}));