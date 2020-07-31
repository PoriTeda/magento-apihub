/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
define(
    [
        'jquery',
        'ko',
        'Magento_Catalog/js/price-utils'
    ],
    function (
        $ ,
        ko ,
        priceUtils
    ) {
        'use strict';
        var priceFormat = window.subscriptionConfig.price_format;
        return {
            getFormattedPrice: function (price) {
                return priceUtils.formatPrice(price, priceFormat);
            },
        };
    }
);
