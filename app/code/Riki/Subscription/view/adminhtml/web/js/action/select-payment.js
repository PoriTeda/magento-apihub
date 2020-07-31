/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
/*global define*/
define(
    [
        'jquery',
        'Riki_Subscription/js/model/profile',
        'Riki_Subscription/js/model/emulator-order'
    ],
    function ($, profile , orderData ) {
        'use strict';
        return function (selectedValue) {

            if(!_.isUndefined(selectedValue)){
                var paymentCode = selectedValue.value;
                var price = parseFloat(selectedValue.params.price);
                profile.paymentmethod(paymentCode);
                var oldPaymentFee = parseFloat(orderData.getPaymentFee());
                var grandTotal = parseFloat(orderData.getGrandTotal());
                orderData.paymentFee(price);

                /* re-calculate grand total */
                var newGrandTotal =  ( grandTotal - oldPaymentFee ) + price;
                orderData.grand_total(newGrandTotal);
                profile.profileHasChanged(true);
            }
        };
    }
);
