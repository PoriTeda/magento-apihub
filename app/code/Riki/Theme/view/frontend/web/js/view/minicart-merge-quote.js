define([
    'jquery',
    'Magento_Customer/js/customer-data',
    'Riki_Theme/js/cart-data-model',
    'domReady!'
], function ($, customerData, cartDataModel) {
    $(document).ready(function () {
        if ($("#riki-block-minicart").length) {
            console.log("__run_merge_minicart__");
            cartDataModel.mergeQuote().isCartReady().whenChangeQty(false, true);
        }
    });
});