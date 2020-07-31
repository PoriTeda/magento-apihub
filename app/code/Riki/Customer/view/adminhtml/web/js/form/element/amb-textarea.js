define([
    'Magento_Ui/js/form/element/textarea'
], function (TextAreaElement) {
    return TextAreaElement.extend({
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