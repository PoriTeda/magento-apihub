/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

/**
 * @deprecated since version 2.2.0
 */
/* eslint-disable strict, no-undef*/
require([
    'jquery',
    'prototype',
    'mage/adminhtml/events'
], function (jQuery) {

    /**
     * Data changed.
     */
    function dataChanged() {
        jQuery('#save_publish_button').removeClass('no-display').show();
        jQuery('#publish_button').hide();
    }
    jQuery('[data-role=cms-revision-form-changed]').on('change', dataChanged);
    varienGlobalEvents.attachEventHandler('tinymceChange', dataChanged);
});
