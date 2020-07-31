/*jshint jquery:true*/
(function (factory) {
    if (typeof define === 'function' && define.amd) {
        define([
            "jquery",
            "mage/validation",
            "mage/translate"
        ], factory);
    } else {
        factory(jQuery);
    }
}(function ($) {
    "use strict";

    $.each({
        'validate-color-code': [
            function (value, element) {
                
                return this.optional(element) || /^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/.test(value);
            },
            $.mage.__('A valid Color code must be format by #. Example: #00FF33 ')
        ]
    }, function (i, rule) {
        rule.unshift(i);
        $.validator.addMethod.apply($.validator, rule);
    });
}));