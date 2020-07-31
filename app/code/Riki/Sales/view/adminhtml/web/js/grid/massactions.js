/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

define([
    'Magento_Ui/js/grid/massactions',
    'jquery'
], function (MagentoMassactions, $) {
    'use strict';

    return MagentoMassactions.extend({
        modal: function () {
            $('#popup-reason-cancel-in-grid').modal('openModal');
        }
    });
});
