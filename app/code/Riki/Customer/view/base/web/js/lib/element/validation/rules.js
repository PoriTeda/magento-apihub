/*jshint jquery:true*/
(function (factory) {
    if (typeof define === 'function' && define.amd) {
        define([
            "jquery",
            "Magento_Ui/js/lib/validation/validator",
            "mage/translate",
            "mage/validation"
        ], factory);
    } else {
        factory(jQuery);
    }
}(function ($, validator) {
    "use strict";

    validator.addRule('validate-katakana', function (value) {
         return !/([^\u30a0-\u30ff０-９\s－])+/.test(value);
    }, $.mage.__('Please enter full-width katakana character.'));

    validator.addRule('validate_double_byte_last_name', function (value) {
        return !/([^\u4e00-\u9faf\u30a0-\u30ff\u3040-\u309f\u3400-\u4dbf\u3000-\u303f\uff01-\uff5e０-９Ａ-ｚ\（\）\：\s―])+/.test(value);
    }, $.mage.__('Please enter with double-byte last name character.'));

    validator.addRule('validate_double_byte_first_name', function (value) {
        return !/([^\u4e00-\u9faf\u30a0-\u30ff\u3040-\u309f\u3400-\u4dbf\u3000-\u303f\uff01-\uff5e０-９Ａ-ｚ\（\）\：\s―])+/.test(value);
    }, $.mage.__('Please enter with double-byte first name character.'));

    validator.addRule('validate_double_byte_last_kanatana_name', function (value) {
        return !/([^\u30a0-\u30ff０-９\sー])+/.test(value);
    }, $.mage.__('Please enter with double-byte last kanatana name character.'));

    validator.addRule('validate_double_byte_first_kanatana_name', function (value) {
        return !/([^\u30a0-\u30ff０-９\sー])+/.test(value);
    }, $.mage.__('Please enter with double-byte first kanatana name character.'));

    validator.addRule('validate_double_byte_required', function (value) {
        return !/([^\u4e00-\u9faf\u30a0-\u30ff\u3040-\u309f\u3400-\u4dbf\u3000-\u303f\uff01-\uff5e０-９Ａ-ｚ\（\）\：\s―])+/.test(value);
    }, $.mage.__('Please enter with double-byte characters.'));

    validator.addRule('validate-phone-number', function (value) {
        if(value != '') {
            var len = value.match(/\d/g);
            if(len != null && (len.length > 11 || len.length < 10)){
                return false;
            }
            return /(^\d+(-|\d)+)$/.test(value);
        }
        return true;
    }, $.mage.__('Please enter a valid phone number. For example 123-456-7890.'));

    validator.addRule('validate-shosha-business-code', function (value) {
        if(value != '') {
            var len = value.match(/\d/g);

            if(len != null && (len.length !=  10)){
                return false;
            }

            return /^\d{10}$/.test(value);
        }
        return true;
    }, $.mage.__('Please enter a valid business code. It requires 10 digits'));

    validator.addRule('validate-commission', function (value) {
        if(value != '') {
            var len = value.match(/\d/g);

            return /^\d+(\.\d+)?$/.test(value);
        }
        return true;
    }, $.mage.__('Please enter a valid commission'));

    validator.addRule('required-entry-last-name', function (value) {
        return !(value === '' || (value == null) || (value.length === 0) || /^\s+$/.test(value));
    }, $.mage.__('Last name is a required field.'));

    validator.addRule('required-entry-first-name', function (value) {
        return !(value === '' || (value == null) || (value.length === 0) || /^\s+$/.test(value));
    }, $.mage.__('First name is a required field.'));

    validator.addRule('required-entry-last-name-katakana', function (value) {
        return !(value === '' || (value == null) || (value.length === 0) || /^\s+$/.test(value));
    }, $.mage.__('Last name katakana is a required field.'));

    validator.addRule('required-entry-first-name-katakana', function (value) {
        return !(value === '' || (value == null) || (value.length === 0) || /^\s+$/.test(value));
    }, $.mage.__('First name katakana is a required field.'));

    validator.addRule('required-postal-code', function (value) {
        return !(value === '' || (value == null) || (value.length === 0) || /^\s+$/.test(value));
    }, $.mage.__('Postal code is a required field.'));

    validator.addRule('required-entry-city', function (value) {
        return !(value === '' || (value == null) || (value.length === 0) || /^\s+$/.test(value));
    }, $.mage.__('City is a required field.'));

    validator.addRule('required-entry-telephone', function (value) {
        return !(value === '' || (value == null) || (value.length === 0) || /^\s+$/.test(value));
    }, $.mage.__('Telephone is a required field.'));

    validator.addRule('required-entry-region', function (value) {
        return ((value !== "none") && (value != null) && (value.length !== 0));
    }, $.mage.__('Prefectures is a required field.'));

    validator.addRule('validate-postal-code-format', function (value) {
        return /^\d{3}-\d{4}$/.test(value) || /^\d{7}$/.test(value);
    }, $.mage.__('The corresponding address was not found.'));

    validator.addRule('validate-double_byte', function (value) {
        return !/([^\u4e00-\u9faf\u30a0-\u30ff\u3040-\u309f\u3400-\u4dbf\u3000-\u303f\uff01-\uff5e０-９Ａ-ｚ\（\）\：\s―])+/.test(value);
    }, $.mage.__('Please enter with double-byte characters.'));
    validator.addRule('validate-company-fax-number', function (value) {
        if (value != '') {
            var len = value.match(/\d/g);
            if (len != null && (len.length > 12 || len.length < 10)) {
                return false;
            }
            return /(^\d+(-|\d)+)$/.test(value);
        }
        return true;
    }, $.mage.__('Please enter a valid Company fax number. For example 123-456-7890.'));


}));
