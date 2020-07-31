define([
    "jquery",
    "jquery/ui",
    'Riki_Preorder/js/product/preorder'

], function($) {
    $.widget('mage.rikiPreorderGrouped', $.mage.rikiPreorder, {
        options: {
            map: {},
            preorderNoteTemplate: ''
        },
        _create: function(){
          //super._create();
            this._saveOriginal();
            var self = this;
            $.each(this.options.map, function(key, value){
                $('.qty input[name=super_group\\['+key+'\\]]').change(function(){
                   if(this.value > 0) {
                       self.options.addToCartLabel = value.cartLabel;
                       self.options.preOrderNote = value.note;
                       self.enable();
                   } else {
                       self.disable();
                   }
                });
                $('.grouped .price-box.price-final_price[data-product-id='+key+']').append(self.options.preorderNoteTemplate.replace('{preorderNote}', value.note));

            });
        },
        _changeLabels: function() {
               $.mage.catalogAddToCart.prototype.options.addToCartButtonTextDefault = this.options.addToCartLabel;
               this.options.addToCartButton.html(this.options.addToCartLabel);

        }
    }

    );
});
