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
        'Riki_Subscription/js/model/frequency',
        'Riki_Subscription/js/model/course' ,
        'Riki_Subscription/js/model/payment-method' ,
        'Riki_Subscription/js/model/utils',
        'Riki_Subscription/js/action/select-payment',
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
        frequency,
        course,
        paymentMethod,
        utils,
        selectPaymentAction ,
        uiRegistry,
        mage ,
        $t
    ) {
        "use strict";

        var creditcardImage = window.subscriptionConfig.paygent_img;
        var isUsedPaygent = window.subscriptionConfig.is_used_paygent;

        return Component.extend({
            defaults: {
                template: 'Riki_Subscription/payment-information'
            },
            isNewPaygent: ko.observable(),
            /** Initialize observable properties */
            initObservable: function () {
                this._super();
                var self = this;
                this.paymentMethod = paymentMethod.getAllowedPaymentMethod();
                this.isAllowChangePaymentMethod =  paymentMethod.getIsAllowChangePaymentMethod();
                this.selectedPaymentMethod = paymentMethod.getSelectedPaymentMethod();
                this.paygentImage = creditcardImage;
                this.new_paygent = profile.getNewPaygent();
                this.isUsedPaygent = isUsedPaygent;
                this.isTmpProfile = profile.getProfileHaveTmp();
                this.wasDisengaged = profile.wasDisengaged();
                this.profile = profile;
                this.isStockPoint = window.subscriptionConfig.stock_point_is_selected || window.subscriptionConfig.is_stock_point_profile
                this.showIvr = !window.subscriptionConfig.is_stock_point_profile_model;
                if(this.isNotPaygentPaymentMethod(this.selectedPaymentMethod) == 0) {
                    if (this.new_paygent == true) {
                        this.isNewPaygent('1');
                    }

                    if (this.new_paygent == false || this.new_paygent == null) {
                        this.isNewPaygent('0');
                    }
                }else {
                    this.isNewPaygent('-1');
                }
                /* control setting */
                uiRegistry.get(this.parentName , function(component){
                    self.isDisabledAll = component.isDisabledAll;
                });
                /* process logic for is Disable all */

                if(this.selectedPaymentMethod  == 'cvspayment'
                    || this.selectedPaymentMethod  == 'invoicedbasedpayment'
                ){
                    this.isDisabledAll = ko.observable(true);
                }

                return this;
            },
            getProfileData: function () {
                return profile;
            },
            translate: function (neededString) {
                return $t(neededString);
            },
            formatCurrency: function(value){
                return utils.getFormattedPrice(value);
            },
            selectPaymentMethod: function(component , event){

                var targetId = event.target.id;

                if(targetId == 'new_paygent'){

                    $('#new_paygent_ipt').val('1');

                    if($('#ivr_now')){
                        $('#ivr_now').show();
                        $('#ivr_now').on('click', $.proxy(function (e) {
                            e.preventDefault();
                            uiRegistry.get('subscription-form-edit' , function (component){
                                component.generateNextOrder(component);
                            });
                            return false;
                        }, this));
                    }
                }else{
                    if($('#ivr_now')){
                        $('#ivr_now').hide();
                    }

                    $('#new_paygent_ipt').val('0');
                }

                selectPaymentAction(this);
                return true;
            },
            isNotPaygentPaymentMethod : function(paymentMethodCode){
                if( paymentMethodCode != 'paygent'){
                    return 1;
                }
                else{
                    return 0;
                }
            },
            isUsedPaygent : function () {
                return this.isUsedPaygent;
            }
        });
    }
);