/*jshint jquery:true*/
(function (factory) {
    if (typeof define === 'function' && define.amd) {
        define([
            "jquery",
            "mage/translate",
            "mage/validation"
        ], factory);
    } else {
        factory(jQuery);
    }
}(function ($, $t) {
    "use strict";

    $.each({
        'validate-katakana': [
            function (value) {
                return !/([^\u30a0-\u30ff０-９\sー])+/.test(value);
            },
            $t('Please enter full-width katakana character.')
        ],
        'validate_double_byte_last_name': [
            function (value) {
                return !/([^\u4e00-\u9faf\u30a0-\u30ff\u3040-\u309f\u3400-\u4dbf\u3000-\u303f\uff01-\uff5e０-９Ａ-ｚ\（\）\：\s―])+/.test(value);
            },
            $t('Please enter with double-byte last name character.')
        ],
        'validate_double_byte_first_name': [
            function (value) {
                return !/([^\u4e00-\u9faf\u30a0-\u30ff\u3040-\u309f\u3400-\u4dbf\u3000-\u303f\uff01-\uff5e０-９Ａ-ｚ\（\）\：\s―])+/.test(value);
            },
            $t('Please enter with double-byte first name character.')
        ],
        'validate_double_byte_last_kanatana_name': [
            function (value) {
                return !/([^\u30a0-\u30ff０-９\sー])+/.test(value);
            },
            $t('Please enter with double-byte last kanatana name character.')
        ],
        'validate_double_byte_first_kanatana_name': [
            function (value) {
                return !/([^\u30a0-\u30ff０-９\sー])+/.test(value);
            },
            $t('Please enter with double-byte first kanatana name character.')
        ],
        'validate_double_byte_required': [
            function (value) {
                return !/([^\u4e00-\u9faf\u30a0-\u30ff\u3040-\u309f\u3400-\u4dbf\u3000-\u303f\uff01-\uff5e０-９Ａ-ｚ\（\）\：\s―])+/.test(value);
            },
            $t('Please enter with double-byte characters.')
        ],
        'validate-phone-number': [
            function (value) {
                if(value != '') {
                    var len = value.match(/\d/g);
                    if(len != null && (len.length > 11 || len.length < 10)){
                        return false;
                    }
                    return /(^\d+(-|\d)+)$/.test(value);
                }
                return true;
            },
            $t('Please enter a valid phone number. For example 123-456-7890.')
        ],
        'max_text_length_custom': [
            function (value, params) {
                return value.length <= +params.maxLength;
            },
            $t('Please enter less or equal than {0} symbols.')
        ],
        'required-entry-last-name' : [
            function(value) {
                return !(value === '' || (value == null) || (value.length === 0) || /^\s+$/.test(value));
            }, $.mage.__('Last name is a required field.')
        ],
        'required-entry-first-name' : [
            function(value) {
                return !(value === '' || (value == null) || (value.length === 0) || /^\s+$/.test(value));
            }, $.mage.__('First name is a required field.')
        ],
        'required-entry-last-name-katakana' : [
            function(value) {
                return !(value === '' || (value == null) || (value.length === 0) || /^\s+$/.test(value));
            }, $.mage.__('Last name katakana is a required field.')
        ],
        'required-entry-first-name-katakana' : [
            function(value) {
                return !(value === '' || (value == null) || (value.length === 0) || /^\s+$/.test(value));
            }, $.mage.__('First name katakana is a required field.')
        ],
        'required-postal-code' : [
            function(value) {
                return !(value === '' || (value == null) || (value.length === 0) || /^\s+$/.test(value));
            }, $.mage.__('Postal code is a required field.')
        ],
        'required-entry-city' : [
            function(value) {
                return !(value === '' || (value == null) || (value.length === 0) || /^\s+$/.test(value));
            }, $.mage.__('City is a required field.')
        ],
        'required-entry-telephone' : [
            function(value) {
                return !(value === '' || (value == null) || (value.length === 0) || /^\s+$/.test(value));
            }, $.mage.__('Telephone is a required field.')
        ],
        'required-entry-region' : [
            function (value) {
                return ((value !== "none") && (value != null) && (value.length !== 0));
            },
            $.mage.__('Prefectures is a required field.')
        ],
        'validate-postal-code-format': [
            function (value) {
                return /^\d{3}-\d{4}$/.test(value) || /^\d{7}$/.test(value);
            },
            $t('The corresponding address was not found.')
        ],
        'validate-double_byte': [
            function (value) {
                return !/([^\u4e00-\u9faf\u30a0-\u30ff\u3040-\u309f\u3400-\u4dbf\u3000-\u303f\uff01-\uff5e０-９Ａ-ｚ\（\）\：\s―])+/.test(value);
            },
            $t('Please enter with double-byte characters.')
        ]
    }, function (i, rule) {
        rule.unshift(i);
        $.validator.addMethod.apply($.validator, rule);
    });
}));