/**
 * Copyright © 2016 Wyomind. All rights reserved.
 * See LICENSE.txt for license details.
 */

require([
    'jquery',
    'Magento_Customer/js/customer-data',
    'Riki_Theme/js/cart-data-model',
    'domReady!'
], function ($, customerData, cartDataModel) {
    setTimeout(function () {
        cartDataModel.mergeQuote().isCartReady().whenChangeQty(false, true);
    },5);
});