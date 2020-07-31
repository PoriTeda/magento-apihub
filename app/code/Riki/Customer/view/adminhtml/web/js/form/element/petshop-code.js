define([
    'Magento_Ui/js/form/element/abstract',
    'jquery',
    'uiRegistry',
    'mage/translate'
], function (AbstractElement, $, registry, mt) {
    
    return AbstractElement.extend({
        defaults: {
            imports: {
                update: '${ $.parentName }.PETSHOP_CODE:value'
            }
        },
        update: function (value) {
            var byte = this.countByte(value);
            if(byte > 10){
                this.error($.mage.__('Value is lager than 10 byte'));
                this.source.set('params.invalid', true);
            }
            if(byte >=0 && byte <=10){
                this.error(false);
            }
        },
        validate: function () {
          var result = this._super();
          var byte =   this.countByte(this.value());
            if (byte > 10){
                this.error($.mage.__('Value is lager than 10 byte'));
                this.source.set('params.invalid', true);
                result.valid = false;
            }
            if(byte >=0 && byte <=10){
                result.valid = true;
                this.error(false);
            }
            return result;
        },
        countByte: function (value) {
            var m = encodeURIComponent(value).match(/%[89ABab]/g);
            return value.length + (m ? m.length : 0);
        }
        
    });
});