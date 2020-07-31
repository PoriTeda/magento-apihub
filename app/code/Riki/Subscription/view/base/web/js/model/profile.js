/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
define(
    [
        "jquery",
        'ko',
        'Riki_Subscription/js/model/frequency'
    ],
    function (
        $,
        ko,
        frequency
    ) {
        'use strict';
        var profileData = window.subscriptionConfig.profileData;
        return {
            profileHasChanged: ko.observable(false),
            frequency_unit: ko.observable(profileData.frequency_unit),
            frequency_interval: ko.observable(profileData.frequency_interval),
            paymentmethod: ko.observable(profileData.payment_method),
            profile_type: ko.observable(null),
            skip_next_delivery: ko.observable(profileData.skip_next_delivery != '0'),
            earn_point_on_order: ko.observable(parseInt(profileData.earn_point_on_order)),
            paygent_save_prederred: ko.observable(profileData.paygent_save_prederred),
            getProfileId: function() {
                return profileData.profile_id;
            },
            getStatus: function(){
                return profileData.status;
            },
            getOrderTimes: function () {
                return profileData.order_times;
            },
            getCourseName: function(){
                return profileData.course_name;
            },
            getSelectedFrequency: function(){
                return profileData.selected_frequency;
            },
            getSelectedPaymentMethod: function () {
                return profileData.payment_method;
            },
            getEarnPointOnOrder: function () {
                return this.earn_point_on_order;
            },
            getSkipNextDelivery: function(){
                return parseInt(profileData.skip_next_delivery);
            },
            getProfileHaveTmp: function(){
                return window.subscriptionConfig.have_tmp;
            },
            wasDisengaged: function(){
                return profileData.disengagement_date != null
                    && profileData.disengagement_reason != null
                    && profileData.disengagement_user != null
            }
        };
    }
);
