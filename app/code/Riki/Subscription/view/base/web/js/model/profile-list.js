/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
define(
    [
        "jquery",
        "ko",
        'Magento_Customer/js/customer-data',
    ],
    function (
        $,
        ko,
        customerData
    ) {
        'use strict';
        var itemsData = customerData.get('profiles')();
        return {
            getItemsData: function () {
                if (itemsData && itemsData.profiles && itemsData.profiles.items) {
                    return itemsData.profiles.items;
                }
                return [];
            }
        };
    }
);
