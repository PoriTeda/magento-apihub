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
            $.mage.__('A valid WBS Code/Account Code must be specified before the Promotion or Coupon can be activated')
        ],
        'required-wbs-code': [
            function (value, element) {
                return !this.optional(element);
            },
            $.mage.__('WBS code is mandatory when order type is "Free of change - Free samples"')
        ],
        'validate-select-subscription': [
            function (value, element) {
                return this.optional(element) || value != -1;
            },
            $.mage.__('This is a required field.')
        ]
    }, function (i, rule) {
        rule.unshift(i);
        $.validator.addMethod.apply($.validator, rule);
    });
}));