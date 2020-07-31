/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
/*jshint browser:true jquery:true*/
/*global alert*/
define([
        "jquery",
        'ko',
        'uiComponent',
        'Magento_Ui/js/modal/alert',
        'Riki_Subscription/js/model/profile',
        'Riki_Subscription/js/model/utils',
        'Riki_Subscription/js/model/emulator-order',
        "jquery/ui",
        "mage/translate",
        "mage/mage",
        "mage/validation"
    ], function (
        $,
        ko,
        Component,
        alert,
        profile,
        utils,
        order,
        mage,
        $t
    ) {
        "use strict";

        return Component.extend({
            defaults: {
                template: 'Riki_Subscription/basic-information',
            },
            /** Initialize observable properties */
            initObservable: function () {
                /** @var profile Riki_Subscription/js/model/profile  */
                if(window.subscriptionConfig.have_tmp){
                    var profileData = window.subscriptionConfig.profileData;
                    this.profileId = profileData.main_profile_id;
                }
                else{
                    this.profileId = ko.observable(profile.getProfileId());
                }

                this.orderTimes = ko.observable(profile.getOrderTimes());
                this.courseName = ko.observable(profile.getCourseName());
                this.courseId = profile.getCourseId();
                this.courseCode = profile.getCourseCode();
                this.orderData = order;
                this.sales_value_count = profile.getSalesValueCount();
                this.sales_count = profile.getSalesCount();
                this.monthlyFeeLabel = profile.getMonthlyFeeLabel();
                this.is_invalid_lead_time_on_both_wh = ko.observable(window.subscriptionConfig.is_invalid_lead_time_on_both_wh);
                this.invalid_lead_time_message = window.subscriptionConfig.invalid_lead_time_message;
                this.disengagedDate = ko.observable(profile.getDisengagedDate());
                this.disengagedUserName = ko.observable(profile.getDisengagedUserName());
                this.wasDisengaged = ko.observable(profile.wasDisengaged());
                this.disengagementReasons = window.subscriptionConfig.disengagement_reasons;
                this.questionnaireData = window.subscriptionConfig.questionnaire_data;
                this.deliveryTimesOfSubscription = ko.observable(window.subscriptionConfig.delivery_times_of_subscription);
                this.waitingOOSOrders = ko.observable(window.subscriptionConfig.waiting_oos_delivery);
                this.timesOfCancelOrder = ko.observable(window.subscriptionConfig.times_of_cancel_order);
                this.totalMaxAmountThreshold = profile.getTotalMaxAmountThreshold();
                this.totalMinAmountThreshold = profile.getTotalMinAmountThreshold();
                this.maxQtyRestriction = profile.getMaxQtyRestriction();
                this.navigationPath = profile.getNavigationPath();
                this.termsOfUse = profile.getTermsOfUse();
                this.termsOfUseDownloadUrl = profile.getTermsOfUseDownloadUrl();
                return this;
            },
            getStatus: function(){
                if(profile.getStatus() == 1 ){
                    return $t("Active");
                }else{
                    if(profile.getStatus() == 2 ){
                        return $t('Completed');
                    }else {
                        return $t("Disengaged");
                    }
                }
            },
            getPriceFormatted: function(price){
                return utils.getFormattedPrice(price);
            }
        });
    }
);