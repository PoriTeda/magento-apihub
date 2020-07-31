define([
    'Magento_Ui/js/form/element/select'
], function (Select) {
    return Select.extend({
        initialize: function () {
            this._super();
            if(!window.is_edit_customer){
                this.visible(false);
            }
        }
    });
});