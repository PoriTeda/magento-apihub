/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
define(
    ['ko'],
    function (ko) {
        'use strict';
        var orderData = window.subscriptionConfig.order_data;
        return {
            paymentFee: ko.observable(orderData.fee),
            gw_amount: ko.observable(orderData.gw_amount),
            subtotal_incl_tax: ko.observable(orderData.subtotal_incl_tax),
            discount: ko.observable(orderData.discount_amount),
            shipping_fee_incl_tax: ko.observable(orderData.shipping_incl_tax),
            tax_amount: ko.observable(orderData.tax_amount),
            grand_total: ko.observable(orderData.grand_total),
            tentative_point_money: ko.observable(orderData.tentative_point_money),
            bonus_point_amount: ko.observable(orderData.bonus_point_amount),
            getOrderId: function () {
                return orderData.entity_id;
            },
            getIncrementId: function () {
                return orderData.increment_id;
            },
            getSubTotalInclTax: function(){
                return this.subtotal_incl_tax();
            },
            getDiscount: function () {
                return this.discount();
            },
            getShippingFeeInclTax: function(){
                return this.shipping_fee_incl_tax();
            },
            getPaymentFee: function () {
                return this.paymentFee();
            },
            getUsedPointAmount: function(){
                if(orderData.used_point_amount > 0){
                    return orderData.used_point_amount;
                }else{
                    return 0;
                }
            },
            getTaxAmount: function () {
                return this.tax_amount();
            },
            getGrandTotal: function(){
                return this.grand_total();
            },
            getTentativePointMoney: function () {
                return this.tentative_point_money();
            },
            getBonusPointAmount: function () {
                return this.bonus_point_amount();
            },
            getFreeGifts: function() {
                console.log(orderData);
            }
        };
    }
);
