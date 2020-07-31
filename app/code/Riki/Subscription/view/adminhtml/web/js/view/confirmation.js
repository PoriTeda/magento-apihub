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
        "Magento_Ui/js/modal/modal",
        'Magento_Ui/js/modal/alert',
        'Riki_Subscription/js/model/profile',
        'Riki_Subscription/js/model/course',
        'Riki_Subscription/js/view/billing-information',
        'Riki_Subscription/js/model/emulator-order',
        'Riki_Subscription/js/model/item-list',
        'Riki_Subscription/js/model/utils',
        'Riki_Subscription/js/model/payment-method',
        'uiRegistry',
        "jquery/ui",
        "mage/translate",
        "mage/mage",
        "mage/validation"
    ], function (
        $,
        ko,
        Component,
        modal,
        alert,
        profile ,
        course,
        billing,
        order ,
        itemList ,
        utils ,
        paymentMethod ,
        uiRegistry ,
        mage ,
        $t
    ) {
        "use strict";

        var customerAddressData = window.subscriptionConfig.addresses_data;
        var product_out_off_stock = window.subscriptionConfig.product_out_off_stock;
        var product_stock_level = window.subscriptionConfig.product_stock_level;
        var totalPoint = window.subscriptionConfig.balance;
        var user_reward_setting = [
            {  label: $t('Not use point') , value: 0 },
            {  label: $t('Automatically use all points') , value: 1 },
            {  label: $t('Automatically redeem a specified maximum number of points') , value: 2 },
        ];

        return Component.extend({
            defaults: {
                template: 'Riki_Subscription/confirmation',
            },
            /** Initialize observable properties */
            initObservable: function () {
                var self = this;
                var billingViewModel = billing();
                this._super();


                this.profileHasChanged = profile.profileHasChanged;
                this.wasDisengaged = profile.wasDisengaged();
                this.billingAddressData = billingViewModel.getBillingAddressData();
                this.totalPoint = totalPoint;

                /* control setting */
                uiRegistry.get(this.parentName , function (component) {
                    self.generateNextOrderAction = component.generateNextOrderAction;
                    self.updateAllChangesAction = component.updateAllChangesAction;
                    self.confirmAction = component.confirmAction;
                    self.rewardUserRedeem = component.rewardUserRedeemValue;
                    self.rewardUserSettingValue = component.rewardUserSettingValue;
                });
                this.profile = profile;
                this.orderData = order;
                this.itemData = itemList.getItemsData();
                this.customerAddressesData = customerAddressData;
                this.userRewardPointSetting = user_reward_setting;
                this.freeGifts = window.subscriptionConfig.free_gifts;
                this.profileTmp = profile.getProfileHaveTmp();
                this.next_delivery_date =  profile.next_delivery_date;
                this.product_out_off_stock = product_out_off_stock;
                this.product_stock_level = product_stock_level;
                this.stockPointIsSelected = window.subscriptionConfig.stock_point_is_selected;
                this.isStockPointProfile = window.subscriptionConfig.is_stock_point_profile;

                return this;
            },
            getTimeSlotText: function (timeslotId) {
                var timeSlotObject = _.find(window.subscriptionConfig.timeslot_data , function (obj) {
                   return obj.value ==  timeslotId();
                });
                if (!_.isUndefined(timeSlotObject)) {
                    return timeSlotObject.label;
                }
                return '';
            },
            getAddressText: function (addressId) {
                var self = this;
                var objAddress = _.find(self.customerAddressesData , function (obj) {
                    return obj.address_id == addressId;
                });

                if (!_.isUndefined(objAddress)) {
                    return objAddress.address_data;
                }
                return '';
            },
            getPaymentMethodObj: function (paymentMethodCode) {
                var undefinedPayment = {
                    label : null
                };
                if (typeof paymentMethodCode == 'undefined') {
                    return undefinedPayment;
                }
                var paymentMethodObj = _.find(paymentMethod.getAllowedPaymentMethod() , function (obj) {
                    return obj.value == paymentMethodCode;
                });
                if (typeof paymentMethodObj != 'undefined') {
                    return paymentMethodObj;
                }
                return undefinedPayment;
            },
            getFormatedAddress: function (addressInfomation) {
                var formattedAddressString = '';
                $.each(addressInfomation , function ( index , value ) {
                    formattedAddressString += value + '<br/>';
                });
                return formattedAddressString;
            },
            getHasBillingInformation: function () {
                return billing().getHasBillingInformation();
            },
            returnToEditPage: function (component , event) {
                uiRegistry.get(this.parentName , function (component) {
                    component.generateNextOrderAction(false);
                    component.updateAllChangesAction(false);
                    component.confirmAction(false);
                });
            },
            generateOrderAndUpdateProfile: function (component , event) {

                var self = this;
                var modalElement = $('#shopping_point_setting');
                var options = {
                    type: 'popup',
                    responsive: true,
                    innerScroll: true,
                    title:  $t('Apply shopping point to create order.'),
                    closed: function () {
                        self.generateNextOrderAction(false);
                        self.updateAllChangesAction(false);
                        self.confirmAction(false);
                    },
                    buttons: [{
                        text: $t('Generate Order'),
                        click: function () {
                            uiRegistry.get(self.parentName , function (component) {
                                component.saveProfileAction('generate_order_confirm');
                                $('body').trigger('processStart');
                                component.submitForm();
                            });
                        }
                    }]
                };
                var popup = modal(options, modalElement);
                modalElement.modal('openModal');
            },
            updateAllChanges: function (component , event) {
                uiRegistry.get(component.parentName , function (component) {
                    component.saveProfileAction('confirm');
                    $('body').trigger('processStart');
                    component.submitForm();
                });
            },
            translate: function (neededString) {
                return $t(neededString);
            },
            getFinalQty: function (qty,unitQty,unitCase) {
                if ('CS' == unitCase) {
                    return qty/unitQty;
                } else {
                    return qty;
                }
            },
            generateOrder: function (component , event) {
                var self = this;
                var modalElement = $('#shopping_point_setting');
                var options = {
                    type: 'popup',
                    responsive: true,
                    innerScroll: true,
                    title:  $t('Apply shopping point to create order.'),
                    closed: function () {
                        self.generateNextOrderAction(false);
                        self.updateAllChangesAction(false);
                        self.confirmAction(false);
                    },
                    buttons: [{
                        text: $t('Generate Order'),
                        click: function () {
                            uiRegistry.get(self.parentName , function (component) {
                                component.generateNextOrderAction(true);
                                component.formPostUrl(window.subscriptionConfig.generate_order_url);
                                $('body').trigger('processStart');
                                component.submitForm();
                            });
                        }
                    }]
                };
                var popup = modal(options, modalElement);
                modalElement.modal('openModal');
            },
            getPriceFormatted: function (price) {
                return utils.getFormattedPrice(price);
            },
            getPoints: function (point) {
                return point + ' ' + $t('Points');
            },
            skipSeasonalProduct: function (from,to) {
                return $t('Skipped ') + from + ' ~ ' + to ;
            },
            getStrToTime: function (date) {
                if (date == 'now') {
                    return new Date().getTime();
                }
                return new Date(date).getTime();
            },
            getNextDeliveryDate: function (item_delivery_date) {
                if (item_delivery_date() == null) {
                    return profile.next_delivery_date;
                }
                return item_delivery_date;
            },
            getOrderStatus: function (product_id) {
                var productOutOffStock = this.product_out_off_stock;
                if ($.inArray(product_id,productOutOffStock) > -1) {
                return $t("Not delivered yet");
                }
                return null;

            },
            getStrToTimeOfDeliveryDate:function (item_delivery_date) {
                if (item_delivery_date() == null) {
                    return new Date(profile.next_delivery_date).getTime();
                }
                return new Date(item_delivery_date()).getTime();
            },
            getStockLevel: function (product_id) {
                var productStockLevel = this.product_stock_level;
                if (productStockLevel[product_id]) {
                    return productStockLevel[product_id];
                }
                return null;

            },
            getProfileWareHouse: function () {
                var warehouseId = window.subscriptionConfig.profileData.specified_warehouse_id;

                if (warehouseId === null) {
                    warehouseId = 0;
                }

                var warehouseOptions = window.subscriptionConfig.warehouseOptions;

                var warehouseName = warehouseOptions[0];

                $.map(warehouseOptions, function (value , key) {
                    if (key == warehouseId) {
                        warehouseName = value;
                    }
                });

                return warehouseName;
            },
            isDayOfWeekAndIntervalUnitMonth: function(){
                var frequencyUnit = profile.frequency_unit();
                var nextDeliveryDateCalculationOption = course.getNextDeliveryDateCalculationOption();

                if (nextDeliveryDateCalculationOption === 'day_of_week' && (frequencyUnit === 'month' || frequencyUnit === 'months')) {
                    return true;
                }

                return false;
            },
            getDayOfWeek: function(date) {
                var d = new Date(date);
                var dayOfWeek = new Array(7);
                dayOfWeek[0] = $t('Sunday');
                dayOfWeek[1] = $t('Monday');
                dayOfWeek[2] = $t('Tuesday');
                dayOfWeek[3] = $t('Wednesday');
                dayOfWeek[4] = $t('Thursday');
                dayOfWeek[5] = $t('Friday');
                dayOfWeek[6] = $t('Saturday');

                return dayOfWeek[d.getDay()];
            },
            calculateNthWeekdayOfMonth: function(date){
                var nthweekdayOfMonth = new Array(5);
                nthweekdayOfMonth[1] = $t('1st');
                nthweekdayOfMonth[2] = $t('2nd');
                nthweekdayOfMonth[3] = $t('3rd');
                nthweekdayOfMonth[4] = $t('4th');
                nthweekdayOfMonth[5] = $t('Last');

                if (!isNaN(date)) {
                    return nthweekdayOfMonth[date];
                } else {
                    var d = new Date(date);
                    return nthweekdayOfMonth[Math.ceil(d.getDate() / 7)];
                }
            },
            getDeliveryMessage: function(code){
                var itemData = this.itemData(),
                    deliveryMessage = '',
                    nthWeekdayOfMonth = '',
                    dayOfWeek = '',
                    lang = $('html').attr('lang');
                if (itemData.length) {
                    for (var i = 0; i < itemData.length; i++) {
                        if (itemData[i].info.code == code) {
                            if (itemData[i].info.next_delivery_date() != '') {
                                if (itemData[i].info.next_delivery_date() == profile.next_delivery_date()
                                    && profile.getNthWeekdayOfMonth() != null
                                    && profile.getDayOfWeek() != null
                                ) {
                                    nthWeekdayOfMonth = this.calculateNthWeekdayOfMonth(profile.getNthWeekdayOfMonth());
                                    dayOfWeek = $t(profile.getDayOfWeek());
                                } else {
                                    nthWeekdayOfMonth += this.calculateNthWeekdayOfMonth(itemData[i].info.next_delivery_date());
                                    dayOfWeek = this.getDayOfWeek(itemData[i].info.next_delivery_date());
                                }

                                if (lang == 'ja-JP') {
                                    deliveryMessage = nthWeekdayOfMonth + dayOfWeek + $t('every');
                                } else {
                                    deliveryMessage = $t('every') + ' ' + nthWeekdayOfMonth + ' ' + dayOfWeek;
                                }
                            }
                            break;
                        }
                    }
                }
                return deliveryMessage;
            },
        });
    });