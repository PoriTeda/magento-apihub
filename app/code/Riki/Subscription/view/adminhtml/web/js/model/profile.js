/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
define(
    [
        "jquery",
        'underscore',
        'ko',
        'Riki_Subscription/js/model/frequency',
        "mage/translate",
        'Riki_Subscription/js/model/utils'
    ],
    function (
        $,
        _,
        ko,
        frequency,
        $t,
        utils
    ) {
        'use strict';
        var profileData = window.subscriptionConfig.profileData;
        return {
            profileHasChanged: ko.observable(window.subscriptionConfig.subscription_profile_has_changed),
            frequency_unit: ko.observable(profileData.frequency_unit),
            frequency_interval: ko.observable(profileData.frequency_interval),
            current_frequency: ko.observable(profileData.current_frequency),
            next_delivery_date: ko.observable(profileData.next_delivery_date),
            paymentmethod: ko.observable(profileData.payment_method),
            profile_type: ko.observable(null),
            skip_next_delivery: ko.observable(profileData.skip_next_delivery != '0'),
            earn_point_on_order: ko.observable(parseInt(profileData.earn_point_on_order)),
            paygent_save_prederred: ko.observable(profileData.paygent_save_prederred),
            getProfileId: function () {
                return profileData.profile_id;
            },
            getStatus: function () {
                return profileData.status;
            },
            getOrderTimes: function () {
                return profileData.order_times;
            },
            getCourseName: function () {
                return profileData.course_name;
            },
            getCourseId: function () {
                return profileData.course_id;
            },
            getCourseCode: function () {
                return profileData.course_code;
            },
            getSelectedFrequency: function () {
                return profileData.selected_frequency;
            },
            getCurrentFrequency: function () {
                return profileData.current_frequency;
            },
            getSelectedPaymentMethod: function () {
                return profileData.payment_method;
            },
            getEarnPointOnOrder: function () {
                return this.earn_point_on_order;
            },
            getSkipNextDelivery: function () {
                return parseInt(profileData.skip_next_delivery);
            },
            getProfileHaveTmp: function () {
                return window.subscriptionConfig.have_tmp;
            },
            wasDisengaged: function () {
                return profileData.disengagement_date != null
                    && profileData.disengagement_reason != null
                    && profileData.disengagement_user != null
            },
            getSalesValueCount: function () {
                return profileData.sales_value_count;
            },
            getSalesCount: function () {
                return profileData.sales_count;
            },
            getNewPaygent: function () {
                if (profileData.new_paygent === null || profileData.new_paygent == false) {
                    return false;
                } else {
                    return true;
                }
            },
            getDayOfWeek: function () {
                return profileData.day_of_week;
            },
            getNthWeekdayOfMonth: function () {
                return profileData.nth_weekday_of_month;
            },
            getDisengagedDate: function () {
                return profileData.disengagement_date
            },
            getDisengagedUserName: function () {
                return profileData.disengagement_user
            },
            getMonthlyFeeLabel: function () {
                return profileData.monthly_fee_label;
            },
            getTotalMaxAmountThreshold: function () {
                return !_.isEmpty(profileData.total_max_amount_threshold)
                    ? utils.getFormattedPrice(profileData.total_max_amount_threshold) : '';
            },
            getTotalMinAmountThreshold: function () {
                let str = '';
                let totalMinAmount = profileData.total_min_amount_threshold.amount;
                let option = profileData.total_min_amount_threshold.option;
                switch (option) {
                    case "0":
                        str += totalMinAmount ? $t('Only apply for the second order') + ' / ' + utils.getFormattedPrice(totalMinAmount) : $t('Only apply for the second order');
                        break;
                    case "1":
                        str += totalMinAmount ? $t('Apply for all orders') + ' / ' + utils.getFormattedPrice(totalMinAmount) : $t('Apply for all orders');
                        break;
                    case "2":
                        if ($.isArray(totalMinAmount)) {
                            $.each(totalMinAmount, function (key, value) {
                                if (value.to_order_time === '') {
                                    str += $t('From') + ' ' + value.from_order_time + ' / ' + utils.getFormattedPrice(value.amount) + '<br>';
                                } else {
                                    str += $t('From') + ' ' + value.from_order_time + ' ' + $t('to') + ' ' + value.to_order_time + ' / ' + utils.getFormattedPrice(value.amount) + '<br>';
                                }
                            });
                        }
                        break;
                }

                return str;
            },
            getMaxQtyRestriction: function () {
                let str = '';
                let maxQty = profileData.maximum_qty_restriction.qty;
                let option = profileData.maximum_qty_restriction.option;

                switch (option) {
                    case "1":
                        str += maxQty ? $t('Only apply for the first order') + ' / ' + maxQty : $t('Only apply for the first order');
                        break;
                    case "2":
                        str += maxQty ? $t('Only apply for the second order') + ' / ' + maxQty : $t('Only apply for the second order');
                        break;
                    case "3":
                        if ($.isArray(maxQty)) {
                            $.each(maxQty, function (key, value) {
                                if (value.to_order_time === '') {
                                    str += $t('From') + ' ' + value.from_order_time + ' / ' + value.qty + '<br>';
                                } else {
                                    str += $t('From') + ' ' + value.from_order_time + ' ' + $t('to') + ' ' + value.to_order_time + ' / ' + value.qty + '<br>';
                                }
                            });
                        }
                        break;
                }

                return str;
            },
            getNavigationPath: function () {
                return window.subscriptionConfig.course_data.navigation_path;
            },
            getTermsOfUse: function () {
                return profileData.terms_of_use;
            },
            getTermsOfUseDownloadUrl: function () {
                return profileData.terms_of_use_download_url;
            }
        };
    }
);
