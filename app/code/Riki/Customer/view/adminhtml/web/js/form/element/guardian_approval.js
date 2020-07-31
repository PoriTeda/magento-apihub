/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

define([
    'underscore',
    'uiRegistry',
    'Magento_Ui/js/form/element/boolean',
    'Riki_Customer/js/lib/moment',
    'jquery'
], function (_, registry, Boolean, moment, $) {
    'use strict';

    return Boolean.extend({
        defaults: {
            imports: {
                update: '${ $.parentName }.dob:value'
            }
        },

        /**
         * @param {String} value
         */
        initialize: function () {
            this._super();
            this.visible(false);
        },
        onUpdate: function () {
            var dob = registry.get(this.parentName + '.dob');
            var birthday = moment(dob.value());
            var sixteenYearsAgo = moment(window.current_date).subtract(16,"years");
             if(!sixteenYearsAgo.isAfter(birthday)) {
                if(this.value() == true){
                    dob.error(false);
                    this.error(false);
                }else{
                    dob.error($.mage.__('The customer is between 13 and 16 years but the guardian approved'));
                }
            }
        }
    });
});
