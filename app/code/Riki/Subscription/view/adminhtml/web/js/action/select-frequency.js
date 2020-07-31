/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
/*global define*/
define(
    [
        'jquery',
        'Riki_Subscription/js/model/profile',
        'Riki_Subscription/js/model/frequency'
    ],
    function ($, profile , frequency) {
        'use strict';
        return function (selectedValue) {
            var interval = selectedValue.substr(0,selectedValue.indexOf(' '));
            var unit = selectedValue.substr(selectedValue.indexOf(' ') + 1);
            profile.frequency_unit(unit);
            profile.frequency_interval(interval);
            profile.profileHasChanged(true);
        };
    }
);
