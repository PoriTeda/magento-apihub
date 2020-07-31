/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
/*global define*/
define(
    [
        'jquery',
        'Riki_Subscription/js/model/profile',
        'Riki_Subscription/js/model/emulator-order'
    ],
    function ($, profile , orderData ) {
        'use strict';
        return function (selectedValue) {
            profile.profile_type(selectedValue);
        };
    }
);
