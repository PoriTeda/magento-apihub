/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
define(
    ['ko'],
    function (ko) {
        'use strict';
        var dataSetting = window.subscriptionConfig.course_setting;
        var courseData = window.subscriptionConfig.course_data;
        return {
            getMiniumOrderQty: function(){
                return 1;
            },
            getAllowChangeAddress: function(){
                return parseInt(dataSetting.is_allow_change_address);
            },
            getAllowChangeProduct: function(){
                return parseInt(dataSetting.is_allow_change_product);
            },
            getAllowChangeQty: function () {
                return parseInt(dataSetting.is_allow_change_qty);
            },
            getAllowSKipNextDelivery: function () {
                return parseInt(dataSetting.is_allow_skip_next_delivery);
            },
            getAllowChangeNextDeliveryDate: function(){
                /*Always allow admin user change delivery_date*/
                return true;
            },
            getAllowChangeFrequency: function() {
                return dataSetting.is_allow_change_next_delivery;
            },
            getAllowChangePaymentMethod: function(){
                return parseInt(dataSetting.is_allow_change_payment_method);
            },
            getName: function(){
                return courseData.course_name;
            },
            getHanpukaiDeliveryDateAllowed: function () {
                return parseInt(dataSetting.hanpukai_delivery_date_allowed);
            },
            getHanpukaiDeliveryDateFrom: function () {
                return dataSetting.hanpukai_delivery_date_from;
            },
            getHanpukaiDeliveryDateTo: function () {
                return dataSetting.hanpukai_delivery_date_to;
            },
            getNextDeliveryDateCalculationOption: function () {
                return courseData.next_delivery_date_calculation_option;
            }
        };
    }
);
