require([
    "jquery",
    "Magento_Catalog/catalog/product/composite/configure"
], function($) {
    if (window.ProductConfigure) {
        var _showWindow = window.ProductConfigure.prototype._showWindow;
        window.ProductConfigure.prototype._showWindow = function () {
            if ($('[data-readonly=' + this.current.itemId + ']').length) {
                $('#product_composite_configure_input_qty').prop('readonly', true);
            } else {
                $('#product_composite_configure_input_qty').prop('readonly', false);
            }
            _showWindow.apply(this);
        };
    }
});