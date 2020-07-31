/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

define([
    'underscore',
    'Riki_Customer/js/lib/dateFormat',
    'uiRegistry',
    'Magento_Ui/js/form/element/date',
    'Riki_Customer/js/lib/moment',
    'jquery'
], function (_, dateFormat, registry, Date, moment, $) {
    'use strict';

    return Date.extend({
        defaults: {
            imports: {
                update: '${ $.parentName }.dob:value'
            }
        },
        initConfig: function () {
            this._super();
            if(window.current_locale == 'ja_JP'){
                this.dateFormat = 'YYYY/MM/DD';
            }
            return this;
        },
        update:function (dob) {
            if(window.current_locale == 'ja_JP'){
                var jpDate = dateFormat(dob,'yyyy/mm/dd');
                this.value(jpDate);
            }
            var guardianApproval = registry.get(this.parentName + '.GARDIAN_APPROVAL');
            if(typeof guardianApproval != 'undefined'){
                var birthday = moment(dob);
                var thirteenYearsAgo = moment(window.current_date).subtract(13,"years").add(1,"days");
                var sixteenYearsAgo = moment(window.current_date).subtract(16,"years");
                if(!sixteenYearsAgo.isAfter(birthday)) {
                    if(guardianApproval.value() == true){
                        guardianApproval.visible(true);
                    }
                }
                if(!thirteenYearsAgo.isAfter(birthday)){
                    guardianApproval.visible(false);
                }
            }
        },

        /**
         * @param {String} value
         */
        validate: function () {
            var guardianApproval = registry.get(this.parentName + '.GARDIAN_APPROVAL'),
                dob = this.value(),
                isValid = true;
            if(dob == ""){
                this.error($.mage.__('This is a required field.'));
                isValid = false;
                return {
                    valid: isValid,
                    target: this
                }
            }
            var birthday = moment(dob);
            var thirteenYearsAgo = moment(window.current_date).subtract(13,"years").add(1,"days");
            var sixteenYearsAgo = moment(window.current_date).subtract(16,"years").add(1,"days");

            if(!thirteenYearsAgo.isAfter(birthday)){
                guardianApproval.visible(false);
                this.error($.mage.__('The customer is less than 13 years old and can\'t be registered'));
                isValid = false;
            }else if(!sixteenYearsAgo.isAfter(birthday)) {
                guardianApproval.visible(true);
                guardianApproval.validation['checked'] = true;
                guardianApproval.required(true);
                guardianApproval.validate();
                if(guardianApproval.value() == true){
                    isValid = true;
                }else{
                    this.error($.mage.__('The customer is between 13 and 16 years but the guardian approved'));
                    isValid = false;
                }
            }

            if (isValid) {
                guardianApproval.visible(false);
                return this._super();
            } else {
                if(!thirteenYearsAgo.isAfter(birthday)){
                    guardianApproval.visible(false);

                }else if(!sixteenYearsAgo.isAfter(birthday)){
                    guardianApproval.visible(true);
                }
                return {
                    valid: isValid,
                    target: this
                }
            }
        }
    });
});
