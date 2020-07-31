/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
/*global define*/
define(
    [
        'Magento_Checkout/js/view/summary/abstract-total'
    ],
    function (Component) {
        "use strict";
        return Component.extend({
            defaults: {
                template: 'Magento_Checkout/summary/point-used'
            }
        });
    }
);
