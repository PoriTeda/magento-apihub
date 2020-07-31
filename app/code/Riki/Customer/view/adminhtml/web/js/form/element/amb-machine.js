define([
    'Magento_Ui/js/form/element/select',
    'jquery',
    'uiRegistry',
    'mage/translate'
], function (SelectElement, $, registry) {
    return SelectElement.extend({
        defaults: {
            imports: {
                update: '${ $.parentName }.amb_type:value'
            }
        },
        update: function (value) {
            this.visible(value);
            var stop_date = registry.get('customer_form.areas.customer.customer.AMB_STOP_DATE');
            if(typeof stop_date != "undefined"){
                if(stop_date.value() !=""){
                    if(this.value() != 0 || this.value() != 91){
                        this.error($.mage.__('Must choose 0(no) or 91(returning) on Machine rental status'));
                    }else{
                        this.error(false);
                    }
                }else{
                    this.error(false);
                }
            }
        },
        validate: function () {
            var result = this._super();
            var stop_date = registry.get('customer_form.areas.customer.customer.AMB_STOP_DATE');
            if(stop_date.value() !=""){
                if(this.value() == 0 || this.value() == 91){
                    this.error(false);
                    result.valid = true;
                }else{
                    result.valid = false;
                    this.error($.mage.__('Must choose 0(no) or 91(returning) on Machine rental status'));
                }
            }else{
                this.error(false);
            }
            return result;
        }
    });
});