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
        'validate-wbs-code': [
            function (value, element) {
                return this.optional(element) || /^AC-\d{8}$/.test(value);
            },
            $.mage.__('Please use a valid WBS Code')
        ]
    }, function (i, rule) {
        rule.unshift(i);
        $.validator.addMethod.apply($.validator, rule);
    });
}));