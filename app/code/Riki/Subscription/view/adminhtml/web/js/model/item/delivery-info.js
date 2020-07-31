/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
/*jshint browser:true jquery:true*/
/*global alert*/
define([
    'ko'
], function( ko ) {
    /**
     * @param addressData
     * Returns new address object
     */
    return function (deliveryInfo , code ) {
        return {
           code: code,
           next_delivery_date: ko.observable(deliveryInfo.delivery_date.next_delivery_date),
           timeslot_id: ko.observable(deliveryInfo.delivery_date.time_slot),
           name: deliveryInfo.name,
           address: ko.observableArray(deliveryInfo.info),
           address_html: ko.observable(deliveryInfo.address_html),
           calendar_period: deliveryInfo.calendar_period,
           restrict_date:  deliveryInfo.restrict_date,
           is_exist_back_order_not_allow_choose_dd: deliveryInfo.is_exist_back_order_not_allow_choose_dd
        }
    }
});
