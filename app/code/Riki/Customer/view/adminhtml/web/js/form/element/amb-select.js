define([
    'Magento_Ui/js/form/element/select'
], function (SelectElement) {
    return SelectElement.extend({
        defaults: {
            imports: {
                update: '${ $.parentName }.amb_type:value'
            }
        },
        update: function (value) {
            this.visible(value);
        }
    });
});