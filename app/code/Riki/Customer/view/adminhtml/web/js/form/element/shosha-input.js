define([
    'Magento_Ui/js/form/element/abstract'
], function (AbstractElement) {
    return AbstractElement.extend({
        defaults: {
            imports: {
                update: '${ $.parentName }.b2b_flag:value'
            }
        },
        update: function (value) {
            this.visible(value);
            if(window.adminhtml_shoshacustomer_acl) {
                if (value == 0) {
                    this.value('');
                }
            }
            if(!window.adminhtml_shoshacustomer_acl){
                this.visible(false);
            }
        }
    });
});