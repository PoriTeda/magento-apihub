/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
define(
    [
        "jquery",
        'ko',
        'Riki_Subscription/js/model/profile',
        'Riki_Subscription/js/model/course'
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
                return course.getAllowChangePaymentMethod();
            },
            getSelectedPaymentMethod : function () {
                return profile.getSelectedPaymentMethod();
            }
        };
    }
);
