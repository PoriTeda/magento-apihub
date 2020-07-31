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
        'validate-number': [
            function (v) {
                return $.mage.isEmptyNoTrim(v) || (!isNaN($.mage.parseNumber(v)) && /^\s*-?\d*(\.\d*)?\s*$/.test(v));
            },
            $.mage.__('Please enter a valid number in this field.')
        ],'validate-wbs-code': [
            function (value, element) {
                return this.optional(element) || /^AC-\d{8}$/.test(value);
            },
            $.mage.__('Please use a valid WBS Code')
        ],
        'validate-greater-than-min-amount' : [
            function () {
                if ($("#cou_order_total_amount_option").val() == 2) {
                    return true;
                }
                var minAmount = $("#cou_oar_minimum_amount_threshold").val();
                var maxAmount = $("#cou_oar_maximum_amount_threshold").val();
                if(maxAmount != '' && minAmount != '') {
                    return (parseInt(maxAmount) > parseInt(minAmount));
                } else {
                    return true;
                }
            },
            $.mage.__('"Order total Maximum amount threshold" must be greater than "Minimum amount"')
        ],
        'validate-greater-than-min-amount-grid' : [
            function (val, element) {
                var wrapId = $(element).attr('id');
                var orderFromId = wrapId.replace('order_to', 'order_from');
                var orderFromValue = $('#'+ orderFromId).val();
                if(val!='') {
                    if(parseInt(val) >= parseInt(orderFromValue)) {
                        return true;
                    } else {
                        return false;
                    }
                } else {
                    return true;
                }
            },
            $.mage.__('"To order time" must be greater or equal to "From order time"')
        ],
        'validate-less-than-maximum' : [
            function (val, element) {
                var maximumAmount = $('#cou_oar_maximum_amount_threshold').val();
                if(val!='' && maximumAmount!='') {
                    if(parseInt(val) < parseInt(maximumAmount)) {
                        return true;
                    } else {
                        return false;
                    }
                } else {
                    return true;
                }
            },
            $.mage.__('"Minimum amount" of this option must be less than "Maximum amount"')
        ],
        'validate-sequence-overlap-minimum-amount' : [
            function (v,element) {
                var fieldName = 'minimum_amount';
                var elId = getIndexNumber($(element).attr('id'), fieldName);
                return validateOverLapOrderTime(elId, fieldName);
            },
            $.mage.__('"From order time" has been overlapped ')
        ],
        'validate-sequence-overlap-maximum-qty' : [
            function (v,element) {
                var fieldName = 'maximum_qty';
                var elId = getIndexNumber($(element).attr('id'), fieldName);
                return validateOverLapOrderTime(elId, fieldName);
            },
            $.mage.__('"From order time" has been overlapped ')
        ]
    }, function (i, rule) {
        rule.unshift(i);
        $.validator.addMethod.apply($.validator, rule);
    });

    function getIndexNumber(strNumber, fieldName) {
        var currentIndex = strNumber.replace(fieldName + '_', '');
        return currentIndex.replace('_order_from', '');
    }
    function validateOverLapOrderTime(elId, fieldName) {
        var indexEl = 0;
        var previousToOrderTime = 0;
        var firstItem = 0;
        var secondResult = true;
        var classFieldName = fieldName.replace('_', '-');
        $('.validate-sequence-overlap-' + classFieldName).each(function (ind, el){
            var tempId =  getIndexNumber($(el).attr('id'), fieldName);
            if (elId == tempId && indexEl == 0) {
                firstItem = 1;
            }
            else {
                var currentFromOrderTime = parseInt($('#' + fieldName + '_' + tempId + '_order_from').val());
                if (
                    previousToOrderTime > 0 &&
                    currentFromOrderTime - previousToOrderTime <= 0
                ) {
                    secondResult = false;
                }
                if (previousToOrderTime === '' && currentFromOrderTime > 0) {
                    secondResult = false;
                }
            }
            indexEl++;
            previousToOrderTime = $('#' + fieldName + '_' + tempId + '_order_to').val();
        });
        if(firstItem ==1) {
            return true;
        } else {
            return secondResult;
        }
    }
}));