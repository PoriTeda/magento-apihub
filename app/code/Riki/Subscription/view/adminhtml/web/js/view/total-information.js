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
        'Riki_Subscription/js/model/course',
        'Riki_Subscription/js/model/utils',
        'Riki_Subscription/js/model/emulator-order',
        'Riki_Subscription/js/action/select-profile-setting',
        'uiRegistry',
        "jquery/ui",
        "mage/translate",
        "mage/mage",
        "mage/validation"
    ], function (
        $,
        ko,
        Component,
        alert,
        profile ,
        course ,
        utils ,
        order,
        selectProfileSettingAction,
        uiRegistry,
        mage ,
        $t
    ) {
        "use strict";
        var is_hanpukai = ko.observable(window.subscriptionConfig.is_hanpukai);
        var allow_change_earn_point = window.subscriptionConfig.allow_change_earn_point;
        return Component.extend({
            defaults: {
                template: 'Riki_Subscription/payment-information'
            },
            /** Initialize observable properties */
            initObservable: function () {
                this._super();
                var self = this;
                this.earnPointOnOrder = profile.getEarnPointOnOrder();
                this.wasDisengaged = profile.wasDisengaged();
                this.earnPointOnOrder.subscribe(function (newValue) {
                    profile.profileHasChanged(true);
                });
                this.allowChangeEarnPoint = allow_change_earn_point;
                this.isHanpukai = is_hanpukai;
                this.skip_next_delivery = profile.skip_next_delivery;
                this.skip_next_delivery.subscribe(function (newValue) {
                    profile.profileHasChanged(true);
                });
                this.isAllowSkipNextDelivery = course.getAllowSKipNextDelivery();
                this.orderData = order;
                this.warehouseOptions = this.getWarehouseOptions();
                this.profileWarehouseId = window.subscriptionConfig.profileData.specified_warehouse_id;
                /* control setting */
                uiRegistry.get(this.parentName , function (component) {
                    self.isDisabledAll = component.isDisabledAll;
                });
                this.stockPointIsSelected = window.subscriptionConfig.stock_point_is_selected;
                this.isStockPointProfile = window.subscriptionConfig.is_stock_point_profile;
                return this;
            },
            getPriceFormatted: function (price) {
                return utils.getFormattedPrice(price);
            },
            selectProfileType: function (component , event) {
                selectProfileSettingAction(event.target.value);
                return true;
            },
            getPoints: function (point) {
                return point? point : 0 + ' ' + $t('Points');
            },
            addCoupon: function () {
                var couponCode = $.trim($('#profile_add_coupon_code').val());

                if (couponCode == "") {
                    return false;
                }

                $('#profile_coupon_error_message').removeClass('mage-error');

                $('body').trigger('processStart');

                $.ajax({
                    url: window.subscriptionConfig.addCouponUrl,
                    method: 'POST',
                    dataType : 'json',
                    data: { couponCode: couponCode},
                    async:true,
                    success:function (result) {
                        if (result.error) {
                            $('body').trigger('processStop');
                            $('#profile_coupon_error_message').html(result.errorMessage);
                            $('#profile_coupon_error_message').addClass('mage-error');
                            $('#profile_coupon_error_message').show();
                        } else {
                            $('#form-submit-profile').find('input[name=save_profile]').val('coupon_add');
                            $('#form-submit-profile').submit();
                        }
                    },
                    error:function () {
                        $('body').trigger('processStop');
                    }
                });
            },
            deleteCoupon: function (data) {

                var couponCode = data;

                $('body').trigger('processStart');

                $.ajax({
                    url: window.subscriptionConfig.deleteCouponUrl,
                    method: 'POST',
                    dataType : 'json',
                    data: { couponCode: couponCode},
                    async:true,
                    success:function (result) {
                        $('#form-submit-profile').find('input[name=save_profile]').val('coupon_delete');
                        $('#form-submit-profile').submit();
                    },
                    error:function () {
                        $('#form-submit-profile').find('input[name=save_profile]').val('coupon_delete');
                        $('#form-submit-profile').submit();
                    }
                });
            },
            getWarehouseOptions: function () {
                var options = ko.observableArray([]);
                $.map(window.subscriptionConfig.warehouseOptions , function (value , key) {
                    options.push({
                        id: key,
                        text: value
                    })
                });
                return options;
            },
            changeProfileWarehouse: function () {
                var warehouseId = $('#specified_warehouse_id').val();
                $('body').trigger('processStart');

                $.ajax({
                    url: window.subscriptionConfig.changeWarehouseUrl,
                    method: 'POST',
                    dataType : 'json',
                    data: { warehouseId: warehouseId},
                    async:true,
                    success:function (result) {
                        if (result.error) {
                            $('body').trigger('processStop');
                            $('#change_warehouse_message').html(result.message);
                            $('#change_warehouse_message').addClass('mage-error');
                        } else {
                            $('#form-submit-profile').find('input[name=save_profile]').val('change_warehouse');
                            $('#form-submit-profile').submit();
                        }
                    },
                    error:function () {
                        $('body').trigger('processStop');
                    }
                });
            }
        });
    });
