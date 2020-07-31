/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
/*global define*/
define(
    [
        'jquery',
        'Riki_Subscription/js/model/emulator-order'
    ],
    function ($,emulateData ) {
        'use strict';
        return function (orderData) {
            emulateData.paymentFee(orderData.fee);
            emulateData.gw_amount(orderData.gw_amount);
            emulateData.subtotal_incl_tax(orderData.subtotal_incl_tax);
            emulateData.discount(orderData.discount_amount);
            emulateData.shipping_fee_incl_tax(orderData.shipping_incl_tax);
            emulateData.tax_amount(orderData.tax_amount);
            emulateData.grand_total(orderData.grand_total);
            emulateData.tentative_point_money(orderData.tentative_point_money);
            emulateData.bonus_point_amount(orderData.bonus_point_amount);
        };
    }
);
