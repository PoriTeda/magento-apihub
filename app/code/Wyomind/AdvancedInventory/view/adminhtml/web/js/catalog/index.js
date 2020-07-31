/**
 * Copyright © 2016 Wyomind. All rights reserved.
 * See LICENSE.txt for license details.
 */

require([
    'jquery',
    "mage/mage",
    'Wyomind_AdvancedInventory/js/catalog/jstree.min'
], function ($) {
    $(function () {

        jQuery('.treeview').live("click", function (e) {
            id = jQuery(this).attr("identifier");
            url = jQuery(this).attr("url")
            jQuery(this).remove();
            console.log(jQuery(this).attr("identifier"), jQuery(this).attr("url"))
            jQuery("#" + id).jstree({
                'core': {
                    'data': {
                        'url': url,
                        'data': function (node) {
                            return {'level': node.id};
                        }
                    }
                }
            });
        })
    })
});