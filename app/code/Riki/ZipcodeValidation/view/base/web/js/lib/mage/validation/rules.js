/*jshint jquery:true*/
(function (factory) {
    if (typeof define === 'function' && define.amd) {
        define(
            [
            "jquery",
            "mage/translate",
            "mage/validation"
            ], factory
        );
    } else {
        factory(jQuery);
    }
}(function ($, $t) {
    "use strict";

    $.each(
        {
            'validate-custom-postal-code': [
            function (value) {
                return /^\d{3}-\d{4}$/.test(value) || /^\d{7}$/.test(value);
            },
            $t('Your Postcode must be in the format 000-0000')
            ]
        }, function (i, rule) {
            rule.unshift(i);
            $.validator.addMethod.apply($.validator, rule);
        }
    );
}));