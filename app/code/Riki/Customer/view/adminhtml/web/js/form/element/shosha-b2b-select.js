define([
    'Magento_Ui/js/form/element/select'
], function (SelectElement) {
    return SelectElement.extend({
        defaults: {
            imports: {
                update: window.adminhtml_shoshacustomer_acl
            }
        },
        initialize: function () {
            this._super();
            if(!window.adminhtml_shoshacustomer_acl){
                this.visible(false);
            }
        },
        update: function (value) {
            this.visible(value);
        }
    });
});