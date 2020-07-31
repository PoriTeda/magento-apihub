define([
    'Magento_Ui/js/form/element/abstract',
    'jquery',
    'uiRegistry',
    'mage/translate',
    'underscore'
], function (AbstractElement, $, registry, _) {
    return AbstractElement.extend({
        defaults: {
            imports: {
                update: '${ $.parentName }.email_2_type:value'
            }
        },
        update: function(email_2_type_value){
            var email_1_type = registry.get('customer_form.areas.customer.customer.email_1_type');
            var email_2_type = registry.get('customer_form.areas.customer.customer.email_2_type');
            if(window.is_edit_customer){
                if(email_2_type_value == 9){
                    this.disabled(true);
                }else{
                    this.disabled(false);
                }
            }
            if((typeof email_1_type != 'undefined') && (typeof email_2_type != 'undefined')  ){
                if(email_2_type.value() == email_1_type.value()){
                    if(this.value() != ""){
                        email_2_type.error($.mage.__('Email 1 type and  Email 2 type must be different.'));
                    }else{
                        email_1_type.error(false);
                        email_2_type.error(false);
                    }
                }
            }
        },
        validate: function () {
            var result = this._super();
            var email_1_type = registry.get('customer_form.areas.customer.customer.email_1_type');
            var email_2_type = registry.get('customer_form.areas.customer.customer.email_2_type');
            if((typeof email_2_type.value() != "undefined") && email_2_type.value() == email_1_type.value()){
                email_2_type.error($.mage.__('Email 1 type and  Email 2 type must be different.'));
                result.valid = false;
                // if(this.value() != ""){
                //     email_2_type.error($.mage.__('Email 1 type and  Email 2 type must be different.'));
                //     result.isValid = false;
                // }else{
                //     email_1_type.error(false);
                //     email_2_type.error(false);
                //     result.isValid = true;
                // }
            }
           return result;
        }
    });
});