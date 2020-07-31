define([
    'Magento_Ui/js/form/element/select',
    'jquery',
    'uiRegistry',
    'mage/translate'
], function (SelectElement, $, registry) {
    return SelectElement.extend({
        update: function(){
            var email_1_type = registry.get('customer_form.areas.customer.customer.email_1_type');
            if(this.value() == email_1_type.value()){
                    this.error($.mage.__('Email 1 type and  Email 2 type must be different.'));
            }else{
                this.error(false);
            }
        },
        validate: function () {
            var  isValid = true;
            var email = registry.get('customer_form.areas.customer.customer.email');
            var email_2= registry.get('customer_form.areas.customer.customer.email_2');
            var email_1_type = registry.get('customer_form.areas.customer.customer.email_1_type');
            if((typeof this.value()!= "undefined") && this.value() == email_1_type.value()){
                this.error($.mage.__('Email 1 type and  Email 2 type must be different.'));
                isValid = false;
            }else {
                email_1_type.error(false);
                this.error(false);
                isValid = true;
            }

            return {
                valid: isValid,
                target: this
            }
        }
    });
});