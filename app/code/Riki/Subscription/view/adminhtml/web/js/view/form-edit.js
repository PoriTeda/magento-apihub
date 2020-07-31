/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
/*jshint browser:true jquery:true*/
/*global alert*/
define([
        "jquery",
        'ko',
        'mage/storage',
        'mage/url',
        'uiComponent',
        'uiRegistry',
        'Magento_Ui/js/modal/alert',
        "Magento_Ui/js/modal/modal",
        'Riki_Subscription/js/model/profile',
        'Riki_Subscription/js/model/course',
        "jquery/ui",
        "mage/translate",
        'Riki_Subscription/js/action/update_grandtotal',
        "Magento_Ui/js/modal/confirm",
        "mage/mage",
        "mage/validation"
    ], function (
        $,
        ko,
        storage,
        urlBuilder,
        Component,
        uiRegistry,
        alert,
        modal,
        profile ,
        course ,
        mage ,
        $t,
        updateGrandTotal,
        confirm
    ) {
        "use strict";

        var generate_order_action = ko.observable(false);
        var update_all_changes = ko.observable(false);
        var confirm_action = ko.observable(false);
        var form_post_url = window.subscriptionConfig.save_url;
        var is_disabled_all = ko.observable(window.subscriptionConfig.is_disabled_all);
        var has_no_product =(window.subscriptionConfig.delivery_info.length == 0);
        var profileData = window.subscriptionConfig.profileData;
        var simulate_profile_data_url = window.subscriptionConfig.simulate_profile_data_url;

        return Component.extend({
            defaults: {
                template: 'Riki_Subscription/form-edit',
            },
            /** Initialize observable properties */
            initObservable: function () {
                var self = this;
                this._super();
                this.courseName = ko.observable(profile.getCourseName());
                this.wasDisengaged = profile.wasDisengaged();
                this.profileStatus = profile.getStatus() == 1;
                this.isCompleted = profile.getStatus() == 2;
                this.hasNoProduct = has_no_product;
                this.profile = profile;
                this.saveProfileAction = ko.observable('confirm');
                this.formPostUrl = ko.observable(form_post_url);
                this.generateNextOrderAction = generate_order_action;
                this.updateAllChangesAction = update_all_changes;
                this.confirmAction = confirm_action;
                this.isAllowChangeProduct = course.getAllowChangeProduct();
                this.profileHasChanged = profile.profileHasChanged;
                this.rewardUserRedeemValue = ko.observable(window.subscriptionConfig.reward_user_redeem);
                this.rewardUserSettingValue = ko.observable(window.subscriptionConfig.reward_user_setting);
                this.isDisabledAll = is_disabled_all;
                this.formkey = FORM_KEY;
                this.totalPoint = window.subscriptionConfig.balance;
                this.orderData = window.subscriptionConfig.order_data;
                this.disableGenerateOrderButton = (profile.getProfileHaveTmp()==1)?(profile.wasDisengaged()?1:0):1;
                this.allowSubscriptionStockPoint = window.subscriptionConfig.allow_stock_point;
                this.stockPointUrlPost = window.subscriptionConfig.stock_point_url_post;
                this.stockPointIsSelected = window.subscriptionConfig.stock_point_is_selected;
                this.stockPointDataPost = window.subscriptionConfig.stock_point_data_post;
                this.removeStockPointUrl = window.subscriptionConfig.remove_stock_point_url;
                this.isStockPointProfile = window.subscriptionConfig.is_stock_point_profile;
                this.isStockPointProfileModel = window.subscriptionConfig.is_stock_point_profile_model;
                this.disableButtonSP = window.subscriptionConfig.disable_two_button_stock_point;
                this.returnUrl = window.subscriptionConfig.return_url;
                return this;
            },
            addProductToCourse: function( element , event ){
                var hiddenElement = $('#add-products');
                var options = {
                    type: 'popup',
                    responsive: true,
                    innerScroll: true,
                    modalClass: 'add-product-popup',
                    title: $t('Add product'),
                    buttons: [{
                        text: $t('Add'),
                        click: function() {
                            this.closeModal();
                            profileProductAdd.productGridAddSelected();
                        }
                    }]
                };
                var popup = modal(options,hiddenElement );
                hiddenElement.modal('openModal');
            },
            addAdditionalProductToCourse: function( element , event ){
                var hiddenElement = $('#add-additional-products');
                var options = {
                    type: 'popup',
                    responsive: true,
                    innerScroll: true,
                    modalClass: 'add-additional-product-popup',
                    title: $t('Add additional product'),
                    buttons: [{
                        text: $t('Add'),
                        click: function() {
                            this.closeModal();
                            profileAdditionalProductAdd.productGridAddSelected();
                        }
                    }]
                };
                var popup = modal(options,hiddenElement );
                hiddenElement.modal('openModal');
            },
            getSaveUrl: function(){

            },
            /* Validation Form*/
            validateForm: function (form) {
                return $(form).validation() && $(form).validation('isValid');
            },
            submitForm: function(component){
                if(this.generateNextOrderAction()){
                    var totalPoint = this.totalPoint;
                    var rewardUserSettingValue = this.rewardUserSettingValue();
                    var rewardUserRedeemValue = this.rewardUserRedeemValue();
                    var message = null;
                    if(rewardUserSettingValue == 2){
                        if(parseInt(rewardUserRedeemValue) > parseInt(totalPoint)){
                            message = $t('Your balance is')+" "+totalPoint+$t('JPY');
                        }
                        if(parseInt(rewardUserRedeemValue) > parseInt(this.orderData.grand_total + this.orderData.used_point_amount)){
                            message = $t('The amount also need')+" "+parseInt(this.orderData.grand_total + this.orderData.used_point_amount)+$t('JPY');
                        }
                        if (isNaN(parseInt(rewardUserRedeemValue))){
                            message = $t('(Tent) You have input a invalid number');
                        }
                    }
                    if(message != null){
                        this.confirmAction(false);
                        this.updateAllChangesAction(false);
                        this.generateNextOrderAction(false);
                        $('#point_error_message').html(message).addClass('message message-error');
                        $('body').trigger('processStop');
                        return false;
                    }
                    if(profile.profileHasChanged())
                    {
                        if (!this.validateForm('#form-submit-profile')) {
                            this.confirmAction(false);
                            this.updateAllChangesAction(false);
                            this.generateNextOrderAction(false);
                            $('body').trigger('processStop');
                            return false;
                        }
                        return $('#form-submit-profile').submit();
                    }else{
                        return $('#form-submit-profile').submit();
                    }
                }

                if (!this.validateForm('#form-submit-profile')) {
                    this.confirmAction(false);
                    this.updateAllChangesAction(false);
                    this.generateNextOrderAction(false);
                    $('body').trigger('processStop');
                    return false;
                }
                /*Your source code*/
                $('#form-submit-profile').submit();
            },
            generateNextOrder: function(component , event){
                if(component.hasNoProduct){
                    if($('#messages').length > 0){
                        var messageContainer = $('#messages').find('.messages');
                    }else{
                        var outerMessageContainer = document.createElement('div');
                        var messageContainer = document.createElement('div');
                        $(outerMessageContainer).attr('id',"messages");
                        $(messageContainer).addClass('messages');
                        $(outerMessageContainer).append(messageContainer);
                        $('.page-content').prepend(outerMessageContainer);
                    }
                    var newMessage = document.createElement('div');
                    $(newMessage).html(
                        '<div data-ui-id="messages-message-error">' + $t('Subscription profile must have at least one product.') +'</div>'
                    );
                    $(newMessage).addClass('message message-error error');
                    $(newMessage).attr('data-role','message');
                    /* append */
                    messageContainer.append(newMessage);
                    /* bind event auto hide */
                    setTimeout(function () {
                        $('[data-role=message]').hide('blind', {}, 500)
                    }, 5000);
                    return false;
                }

                component.updateAllChangesAction(false);
                component.generateNextOrderAction(true);
                component.confirmAction(true);
            },
            updateAllChanges: function (component , event) {
                var selectedFrequency = 0;
                if ($('#frequency_id')) {
                    selectedFrequency = $('#frequency_id').val();
                }

                if (component.hasNoProduct || selectedFrequency <= 0) {
                    if ($('#messages').length > 0) {
                        var messageContainer = $('#messages').find('.messages');
                    } else {
                        var outerMessageContainer = document.createElement('div');
                        var messageContainer = document.createElement('div');
                        $(outerMessageContainer).attr('id',"messages");
                        $(messageContainer).addClass('messages');
                        $(outerMessageContainer).append(messageContainer);
                        $('.page-content').prepend(outerMessageContainer);
                    }

                    var newMessage = document.createElement('div');

                    if (component.hasNoProduct) {
                        var errorMessage = $t('Subscription profile must have at least one product.');
                    } else {
                        var errorMessage = $t('Please select frequency.');
                    }

                    $(newMessage).html(
                        '<div data-ui-id="messages-message-error">' + errorMessage +'</div>'
                    );
                    $(newMessage).addClass('message message-error error');
                    $(newMessage).attr('data-role','message');
                    /* append */
                    messageContainer.append(newMessage);
                    /* bind event auto hide */
                    setTimeout(function () {
                        $('[data-role=message]').hide('blind', {}, 500)
                    }, 5000);
                    return false;
                }

                if (!this.validateForm('#form-submit-profile')) {
                    return false;
                }
                component.updateAllChangesAction(true);
                component.generateNextOrderAction(false);
                component.confirmAction(true);
                this.simulateProfile(this);
            },
            disengageSubscription: function(){
                var popupElm = $('#disengage-modal-content');
                var options = {
                    type: 'popup',
                    responsive: true,
                    innerScroll: true,
                    modalClass: 'disengage-popup',
                    title: $t('Disengage Subscription'),
                    buttons: [
                        {
                        text: $t('OK'),
                        click: function () {
                            $('#disengage-form').submit();
                        }
                    },
                        {
                            text: $t('Cancel'),
                            click: function () {
                                this.closeModal();
                            }
                        }
                    ]
                };
                var popup = modal(options, popupElm);
                popupElm.modal('openModal');
            },
            addPenaltyFeeProductToCourse: function( element , event ){
                var hiddenElement = $('#penalty-fee-products');
                var options = {
                    type: 'popup',
                    responsive: true,
                    innerScroll: true,
                    modalClass: 'add-penalty-fee-product-popup',
                    title: $t('Add Penalty Fee'),
                    buttons: [{
                        text: $t('Cancel'),
                        click: function () {
                            this.closeModal();
                        }
                    }]
                };
                var popup = modal(options,hiddenElement );
                hiddenElement.modal('openModal');
            },
            disengageWithoutPenaltyFee: function( element , event ){
                var hiddenElement = $('#disengage-without-penalty-confirm');
                var options = {
                    type: 'popup',
                    responsive: true,
                    innerScroll: true,
                    modalClass: 'disengage-without-penalty-confirm-popup',
                    title: $t('Disengage Profile Confirm'),
                    buttons: [
                        {
                            text: $t('OK'),
                            click: function () {
                                $('#disengage-without-penalty-form').submit();
                            }
                        },{
                        text: $t('Cancel'),
                        click: function () {
                            this.closeModal();
                        }
                    }
                    ]
                };
                var popup = modal(options,hiddenElement );
                hiddenElement.modal('openModal');
            },

            simulateProfile: function ( element , event) {
                var url = simulate_profile_data_url;
                var formData = $('#form-submit-profile').serialize();
                return $.ajax({
                    url: url,
                    dataType: 'json',
                    data: formData,
                    context: self.element,
                    showLoader : true
                }).done($.proxy(function(data) {
                    if(data.status == true) {
                        updateGrandTotal(data.message);
                    } else {
                        if (!_.isUndefined(data.message)) {
                            this.updaterErrorMessage(data.message);
                        }
                    }
                }, this));
            },
            updaterErrorMessage: function(message) {
                this.confirmAction(false);
                this.updateAllChangesAction(false);
                this.generateNextOrderAction(false);
                if ($('#messages').length > 0) {
                    $('#messages').find('.messages').each(function () {
                        if (!$(this).children().hasClass('message-warning') || !$(this).children().hasClass('message-notice')) {
                            $(this).html('');
                        }
                    });
                }
                var divErrorMsg = $('<div/>', {class: 'message message-error error', text: message});
                var divMsg = $('<div/>', {class: 'messages'}).append(divErrorMsg);
                $('#messages').append(divMsg);

                $('body').trigger('processStop');

            },
            deleteAllProductCart: function() {
                uiRegistry.get('subscription-form-edit.items-information' , function(component){
                    component.deleteAllProductCart();
                });
            },
            redirectUrlStockPoint: function(component ,event) {
                var self = this;
                $('body').trigger('processStart');
                var shippingAddress = $('.can-create-new-address').val();
                $.ajax({
                    url: window.subscriptionConfig.stock_point_address_data,
                    method: 'POST',
                    dataType : 'json',
                    data: {
                        'profile_id': profileData.profile_id,
                        'shipping_address_id': shippingAddress,
                        'return_url': this.returnUrl
                    },
                    success:function (data) {
                        if (data.result == true) {
                            $('#formStockPoint .reqdata').val(data.data);
                            $('#formStockPoint').submit();
                        } else {
                            $('body').trigger('processStop');
                            if (!_.isUndefined(data.message)) {
                                self.updaterErrorMessage(data.message);
                            }
                        }
                    },
                    error:function () {
                        $('body').trigger('processStop');
                    }
                });
            },
            removeStockPoint: function(component , event) {
                var remove_url = this.removeStockPointUrl;
                var profileId = profileData.profile_id;
                    confirm({
                        content: $t("Are you sure you want remove stock point ?"),
                        actions: {
                            confirm: function () {
                                return $.ajax({
                                    url: remove_url,
                                    dataType: 'json',
                                    data: {
                                        id: profileId,
                                    },
                                    context: self.element,
                                    showLoader : true
                                }).done($.proxy(function (data) {
                                    window.location.href = window.subscriptionConfig.editUrl;
                                }, this));
                            },
                            cancel: function () {
                                return false;
                            }
                        }
                    });
                    return false;
            }
        });
    }
);