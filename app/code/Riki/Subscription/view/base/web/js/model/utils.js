/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
define(
    [
        'jquery',
        'ko',
        'Magento_Catalog/js/price-utils',
        'mage/translate'
    ],
    function (
        $,
        ko,
        priceUtils,
        $t
    ) {
        'use strict';
        const priceFormat = !!window.subscriptionConfig ? window.subscriptionConfig.price_format : {
            decimalSymbol: ".",
            groupLength: 3,
            groupSymbol: ",",
            integerRequired: 1,
            pattern: "%s" + $t('Yen'),
            precision: "0",
            requiredPrecision: "0"
        };
        return {
            getFormattedPrice: function (price) {
                return priceUtils.formatPrice(price, priceFormat);
            },
        };
    }
);
