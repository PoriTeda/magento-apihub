define([
    "jquery",
    "jquery/ui"
], function($) {
    "use strict";

    $.widget('Riki.validateDelayPayment', {
        options :{
            delayPaymentElement : "#cou_is_delay_payment",
            frequencySelectElement : "#cou_frequency_ids > option",
            shoppingPointDeductionElement: "",
            capturedAmountCalculationOptionElement : "div.field-captured_amount_calculation_option",
            paymentDelayTimeElement : "div.field-payment_delay_time",
            totalAmountThresholdElement : "div.field-total_amount_threshold",
            isAutoBoxElement : "div.field-is_auto_box",
            paymentIdsElement: "#cou_payment_ids > option",
            lastOrderTimeIsDelayPaymentElement: 'div.field-last_order_time_is_delay_payment',
            allowedFrequencies: []
        },
        _create: function() {

            this._initValidateDelayPayment($(this.options.delayPaymentElement).val());
            this.element.on('change', $.proxy(function (e) {
                this._initValidateDelayPayment($(e.target).val());
            }, this));
        },
        _initValidateDelayPayment: function (value) {
            var $capturedAmountCalculationOptionElement = $(this.options.capturedAmountCalculationOptionElement);
            var $shoppingPointDeduction = $(this.options.shoppingPointDeductionElement);
            var $paymentDelayTime = $(this.options.paymentDelayTimeElement);
            var $totalAmountThreshold = $(this.options.totalAmountThresholdElement);
            var $isAutoBoxElm = $(this.options.isAutoBoxElement);
            var $paymentIds = $(this.options.paymentIdsElement);
            var $frequencySelect = $(this.options.frequencySelectElement);
            var frequencyAllow = this.options.allowedFrequencies;
            var paymentDelayTimeInput = $(this.options.paymentDelayTimeElement + ' #cou_payment_delay_time');
            var $lastOrderTimeIsDelayPaymentInput = $(this.options.lastOrderTimeIsDelayPaymentElement);

            if (value == 1) {
                $capturedAmountCalculationOptionElement.css('display', '');
                $shoppingPointDeduction.css('display', '');
                $paymentDelayTime.css('display', '');
                $totalAmountThreshold.css('display', '');
                $isAutoBoxElm.css('display', '');
                $lastOrderTimeIsDelayPaymentInput.css('display', '');

                $frequencySelect.each(function() {
                    if (frequencyAllow.indexOf(this.value) == -1) {
                        $(this).attr('disabled', 'disabled');
                    }
                });
            } else {
                /** remove disable options */
                $paymentIds.removeAttr('disabled');
                $frequencySelect.removeAttr('disabled');
                $capturedAmountCalculationOptionElement.css('display', 'none');
                $shoppingPointDeduction.css('display', 'none');
                $paymentDelayTime.css('display', 'none');
                $totalAmountThreshold.css('display', 'none');
                $isAutoBoxElm.css('display', 'none');
                $lastOrderTimeIsDelayPaymentInput.css('display', 'none');

                var valuetime = paymentDelayTimeInput.val();
                if (!valuetime) {
                    paymentDelayTimeInput.val(0);
                }
            }
        }
    });
    return $.Riki.validateDelayPayment;
});