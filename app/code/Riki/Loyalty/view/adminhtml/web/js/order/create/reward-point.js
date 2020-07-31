define([
    'jquery',
    'uiRegistry',
    'Bluecom_PaymentFee/js/order/create/scripts',
    'mage/validation'
], function ($, registry) {
    'use strict';
    window.rewardOptions = {};
    $.widget('mage.rewardPoint', {
        options: {
            ajaxUrl: null
        },
        _create: function () {
            window.rewardOptions = this.options;
        }
    });
    AdminOrder.prototype.redeemPoint = function(option, cartId) {
        $('#reward-point-error').hide();
        option = $.mage.parseNumber(option);
        switch (option) {
            case 0:
            case 1:
                $('#point-redeem-amount').hide();
                $('[name=select-point-amount]').prop('disabled', true);
                break;
            case 2:
                $('#point-redeem-amount').show();
                $('[name=select-point-amount]').prop('disabled', false);
                var pointAmount = $('[name=select-point-amount]').val();
                pointAmount = $.mage.parseNumber(pointAmount);
                var numberValid = !isNaN(pointAmount) && pointAmount > 0;
                if (!numberValid) {
                    $('#reward-point-error').html($.mage.__('(Tent) You have input an invalid number')).show();
                    return false;
                }
                var pointBalance = $.mage.parseNumber(window.pointBalance);
                if (pointAmount > pointBalance) {
                    var messageError = $.mage.__('Your balance is %1').replace('%1', window.pointBalanceFormatted);
                    $('#reward-point-error').html(messageError).show();
                    return false;
                }
                var cartTotalValue = $.mage.parseNumber(window.cartTotal);
                if (pointAmount > cartTotalValue) {
                    var messageError = $.mage.__('The amount also need %1').replace('%1', window.cartTotalFormatted);
                    $('#reward-point-error').html(messageError).show();
                    return false;
                }
                break;
            default:
                break;
        }
        var self = this;
        var usedPoints =  $('[name=select-point-amount]').val();
        $.ajax({
            type: 'POST',
            url: this.loadBaseUrl +'block/reward_redeem',
            data: {
                'reload_reward_point':true,
                'cart_id':cartId,
                'option':option,
                'used_points' : usedPoints
            },
            dataType: 'html',
            showLoader: true
        }).complete(
            function () {
                self.loadArea(['billing_method', 'totals'], true);
            }
        );
    };
    return $.mage.rewardPoint;
});
