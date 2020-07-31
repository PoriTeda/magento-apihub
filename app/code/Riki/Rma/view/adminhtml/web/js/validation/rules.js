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
}(function ($) {
    "use strict";

    $.each(
        {
            'adjustment-not-negative-value': [
                function (value) {
                    return $.validator.methods['not-negative-amount'].call(this, value);
                },
                $.mage.__('Negative value is not allowed, please change your adjustment.')
            ]
        }, function (i, rule) {
            rule.unshift(i);
            $.validator.addMethod.apply($.validator, rule);
        }
    );
}));