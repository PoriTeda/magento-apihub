/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

define([
    'underscore',
    'uiRegistry',
    'Magento_Ui/js/form/element/date'
], function (_, registry, Date) {
    'use strict';

    return Date.extend({
        defaults: {
            imports: {
                update: '${ $.parentName }.amb_type:value'
            }
        },

        /**
         * @param {String} value
         */
        update: function (value) {
            this.visible(value);

            if (value != 9) {
                this.error(false);
                this.validation = _.omit(this.validation, 'required-entry');
            } else if(value ==9){
                this.validation['required-entry'] = true;
            } else if(this.value() !=""){
                this.error(false);
                this.validation = _.omit(this.validation, 'required-entry');
            }
            this.required(!!(value ==9));

            if(typeof this.value() == 'undefined'){
                return;
            }
            var resignment_reason = registry.get(this.parentName + '.AMB_STOP_REASON');
            if(typeof resignment_reason !== 'undefined') {
                if (this.value() == "") {
                    resignment_reason.error(false);
                    resignment_reason.validation = _.omit(this.validation, 'required-entry');
                } else if(this.value() != ""){
                    resignment_reason.validation['required-entry'] = true;
                } else if(resignment_reason.value() !=""){
                    resignment_reason.error(false);
                    resignment_reason.validation = _.omit(this.validation, 'required-entry');
                }
                resignment_reason.required(!!(this.value()!=""))
            }
        },
        validate: function (){
            var result = this._super();
            var machine_barista = registry.get('customer_form.areas.customer.customer.status_machine_NBA');
            if(typeof machine_barista != "undefined"){
                if(this.value() == ""){
                    machine_barista.error(false);
                }
            }
            return result;
        }
    });
});
