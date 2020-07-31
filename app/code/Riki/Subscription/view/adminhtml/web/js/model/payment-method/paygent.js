/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
define(
    [
        "jquery",
        'ko',
        'Riki_Subscription/js/model/payment-method',
        'Riki_Subscription/js/model/course',
        'Riki_Subscription/js/model/profile'
    ],
    function (
        $,
        ko,
        profile,
        course
    ) {
        'use strict';
        var paymentMethodList = window.subscriptionConfig.payment_method;
        var flatFrequencyArray = ko.observableArray([]) ;
        return {
            getAllowedPaymentMethod: function(){
                return paymentMethodList;
            },
            getIsAllowChangePaymentMethod: function(){
                return parseInt(course.getAllowChangePaymentMethod()) == 1;
            },
            getSelectedPaymentMethod : function () {
                return profile.getSelectedPaymentMethod();
            }
        };
    }
);
