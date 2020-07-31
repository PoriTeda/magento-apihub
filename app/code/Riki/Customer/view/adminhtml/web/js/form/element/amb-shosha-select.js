define([
    'Magento_Ui/js/form/element/select'
], function (SelectElement) {
    return SelectElement.extend({
        initialize: function () {
            this._super();
            if(!window.adminhtml_shoshacustomer_acl){
                this.visible(false);
            }
        }
    });
});