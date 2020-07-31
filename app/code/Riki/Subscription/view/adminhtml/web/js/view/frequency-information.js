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
        'Riki_Subscription/js/action/select-frequency' ,
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
        selectFrequencyAction ,
        uiRegistry,
        mage ,
        $t
    ) {
        "use strict";
        return Component.extend({
            defaults: {
                template: 'Riki_Subscription/frequency-information',
            },
            /** Initialize observable properties */
            initObservable: function () {
                var self = this;
                this._super();
                this.frequencyData = frequency.getFrequencies();
                this.currentFrequency = profile.getCurrentFrequency();
                this.selectedFrequency = profile.getSelectedFrequency();
                this.isAllowChangeNextDeliveryDate = course.getAllowChangeNextDeliveryDate();
                this.courseName =  course.getName();
                this.isStockPointProfile = window.subscriptionConfig.is_stock_point_profile;
                this.stockPointIsSelected = window.subscriptionConfig.stock_point_is_selected;
                this.frequencyExistInCourse = window.subscriptionConfig.frequencyExistInCourse;
                this.canShowFrequencyBox = window.subscriptionConfig.canShowFrequencyBox;
                /* control setting */
                uiRegistry.get(this.parentName , function(component){
                    self.isDisabledAll = component.isDisabledAll;
                });
                return this;
            },
            selectFrequency: function(component , event){
                var self = this;
                var selectedValue = _.find(self.frequencyData() , function(obj){
                    return obj.id == event.target.value;
                });
                return selectFrequencyAction(selectedValue.text);
            }
        });
    }
);