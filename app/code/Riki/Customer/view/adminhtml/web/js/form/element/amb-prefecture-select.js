define([
    'Magento_Ui/js/form/element/select',
    'jquery',
    'mage/url',
    'underscore'
], function (AbstractElement, $, mageUrl,_) {
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