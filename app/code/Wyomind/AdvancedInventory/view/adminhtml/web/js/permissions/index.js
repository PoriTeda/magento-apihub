/**
 * Copyright © 2016 Wyomind. All rights reserved.
 * See LICENSE.txt for license details.
 */
var AdvancedInventoryPermissions = {
    debug: false,
    log: function (method, arguments) {
        if (this.debug)
            console.log("AdvancedInventoryPermissions says " + method + "()", arguments);
    },
    save: function () {

        this.log('save', arguments);
        jQuery.ajax({
            url: AdvancedInventoryPermissions.url,
            type: 'POST',
            data: {permissions: JSON.stringify(this.permissions)},
            showLoader: true,
            success: function (data) {

                if (typeof data != "object") {
                    alert("Error : " + data);
                } else if (data.error === true)
                    alert("Error : " + data.message);


            },
            error: function () {
                alert(data);
            }

        });
    },
    reinit: function () {
        this.log('reinit', arguments);
        document.reload();
    },
    update_permissions: function () {
        this.log('update_permissions', arguments);
        this.permissions = {};
        // update permissions from selected checkboxes

        jQuery('TABLE.data-grid').eq(1).find('INPUT[type=checkbox]').each(function () {

            if (jQuery(this).prop("checked") && !jQuery(this).prop("disabled")) {
                var user_id = jQuery(this).attr('id').split('_')[0];
                var store_id = jQuery(this).attr('id').split('_')[1];
                if (typeof AdvancedInventoryPermissions.permissions[user_id] == "undefined")
                    AdvancedInventoryPermissions.permissions[user_id] = new Array();
                AdvancedInventoryPermissions.permissions[user_id].push(store_id);
                AdvancedInventoryPermissions.log('this.permissions', AdvancedInventoryPermissions.permissions);
            }
        });

    }
};



require(["jquery", "mage/mage"], function ($) {

    $(document).ready(function () {

        if (AdvancedInventoryPermissions.permissions != '*') {

            for (user_id in AdvancedInventoryPermissions.permissions) {
				
                if (jQuery("#" + user_id + '_all')) {
                    var all = false;

                    jQuery.each(AdvancedInventoryPermissions.permissions[user_id], function(permission) {
						
                        if (AdvancedInventoryPermissions.permissions[user_id][permission] == "all") {
                            all = true;
							
                        } else {
	
                            jQuery("#" + user_id + '_' + AdvancedInventoryPermissions.permissions[user_id][permission]).attr("checked", true);
                        }
                    })
				
		
                    if (all) {
						
                        jQuery("#" + user_id + '_all').attr("checked", true);

                        var tr = jQuery("#" + user_id + '_all').parents('tr').eq(0);
                        var children = tr.find('input.store_cbx');

                        children.each(function () {
                            jQuery(this).attr("checked", true)
                            jQuery(this).attr("disabled", true)
                        });
                    }
                }
            }
        }



        AdvancedInventoryPermissions.update_permissions();


        jQuery('TABLE.data-grid').eq(1).find('INPUT[type=checkbox]').each(function () {

            AdvancedInventoryPermissions.log('onload', jQuery(this));
            // checkboxes observers
            jQuery(this).click(function () {
                AdvancedInventoryPermissions.log('click', jQuery(this));
                var id = jQuery(this).attr('id');
                var checked = jQuery(this).prop('checked');
                if (id.indexOf('all') != -1) { // click on all checkbox

                    var tr = jQuery(this).closest('tr');
                    var children = tr.find('INPUT.store_cbx');

                    children.each(function () {
                        jQuery(this).prop('checked', checked);
                        jQuery(this).prop('disabled', checked);
                    });

                }
                AdvancedInventoryPermissions.update_permissions();
            });

        });



    });

});

