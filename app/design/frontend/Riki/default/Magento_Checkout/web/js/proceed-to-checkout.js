/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

define([
        'jquery'
    ],
    function ($) {
        'use strict';

        return function (config, element) {
            $(element).click(function (event) {
                event.preventDefault();

                location.href = config.checkoutUrl;
            });
        };
    }
);
