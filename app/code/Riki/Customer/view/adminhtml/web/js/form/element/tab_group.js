define([
    'Magento_Ui/js/form/components/tab_group',
    'jquery',
    'uiRegistry',
    'Magento_Ui/js/modal/alert',
    'underscore',
    'mage/translate'
], function (MagentoTabGroup, $, registry, alert, _, $t) {
    return MagentoTabGroup.extend({
        onValidate: function () {
            this._super();

            var addresses = registry.get('customer_form.areas.address.address.address_collection');
            var isHasAddress = addresses.elems().length;

            var isSetHome,isSetCompany = false;
            _.each(registry.get('customer_form.areas.address.address.address_collection').elems(), function (v) {
                var addressType = registry.get(v.name + '.riki_type_address');

                if("home" ==addressType.value() ){
                    isSetHome = true;

                }
                if("company"== addressType.value()){
                    isSetCompany = true;
                }
            });
            var isAmb = registry.get('customer_form.areas.customer.customer.amb_type').value();
            if(isAmb != 1 && isAmb != 9 && isSetCompany){
                alert({
                    content: $t($.mage.__("There are invalid data from Customer Address Type field. 'Company' is only applied for Ambassador."))
                });
                this.source.set('params.invalid', true);
            }
            // if((isAmb == 1 && !isSetCompany)|| (isAmb == 9 && !isSetCompany)){
            //     alert({
            //         content: $t($.mage.__("Please add 1 Address with type 'Company'"))
            //     });
            //     this.source.set('params.invalid', true);
            // }
            if (!isHasAddress) {
                alert({
                    content: $t($.mage.__('Please add customer address'))});
                this.source.set('params.invalid', true);
            }
            if (isHasAddress && !isSetHome) {
                alert({
                    content: $t($.mage.__("Please add 1 Address with type 'Home'"))});
                this.source.set('params.invalid', true);
            }


        }
    });
});