define([
    'Magento_Ui/js/form/element/textarea',
    'uiRegistry',
    'underscore',
    'jquery'
], function (Element, registry, _, $) {
    return Element.extend({
        defaults: {
            imports: {
                update: '${ $.parentName }.AMB_STOP_DATE:value'
            }
        },
        update: function (value) {
            this.visible(value);

            if(!value){
                return;
            }
            if (value == "") {
                this.error(false);
                this.validation = _.omit(this.validation, 'required-entry');

            } else if(this.value() ==""){
                this.validation['required-entry'] = true;
                this.error($.mage.__('Please enter resignment reason'));
            } else if(this.value()!=""){
                this.error(false);
                this.validation = _.omit(this.validation, 'required-entry');
            }

            this.required(!!(value != ""));
        }
        
    });
});