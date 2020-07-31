define([
    'Magento_Ui/js/form/element/select',
    'jquery',
    'uiRegistry',
    'mage/translate'
], function (SelectElement, $, registry) {
    return SelectElement.extend({
        update: function(){
            var email_2_type = registry.get('customer_form.areas.customer.customer.email_2_type');
            if(email_2_type.value() == this.value()){
                    this.error($.mage.__('Email 1 type and  Email 2 type must be different.'));
                   email_2_type.error($.mage.__('Email 1 type and  Email 2 type must be different.'));
            }else{
                this.error(false);
            }
        },
        validate: function () {
            var isValid = true;
            var email = registry.get('customer_form.areas.customer.customer.email');
            var email_2= registry.get('customer_form.areas.customer.customer.email_2');
            var email_2_type = registry.get('customer_form.areas.customer.customer.email_2_type');
            if((typeof this.value()!= "undefined") && email_2_type.value() == this.value()){
                this.error($.mage.__('Email 1 type and  Email 2 type must be different.'));
                isValid = false;
            }else {
                this.error(false);
                email_2_type.error(false);
                isValid = true;
            }
            if (isValid) {
                return this._super();
            } else {
                return {
                    valid: isValid,
                    target: this
                }
            }
        }
    });
});