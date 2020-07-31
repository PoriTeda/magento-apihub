define([
    'Magento_Ui/js/form/element/boolean'
], function (DateElement) {
    return DateElement.extend({
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