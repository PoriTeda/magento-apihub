define([
    'Magento_Ui/js/form/element/abstract'
], function (AbstractElement) {
    return AbstractElement.extend({
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