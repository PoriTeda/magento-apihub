/**
 * Copyright © 2016 Wyomind. All rights reserved.
 * See LICENSE.txt for license details.
 */

if (typeof InventoryManager == "undefined") {
    InventoryManager = {};
}

function toInt(version) {
    var ipl = 0;
    version.split('.').forEach(function (octet) {
        ipl <<= 8;
        ipl += parseInt(octet);
    });
    return(ipl >>> 0);
}



require(["jquery", "mage/mage", "mage/translate"], function ($) {
    $(function () {

        // wait for the page to be fully loaded
        var observe = setInterval(function () {
            if (jQuery('[name=product\\[inventory\\]\\[mage\\]]').length >= 1 || jQuery('[name=product\\[quantity_and_stock_status\\]\\[qty\\]').length >= 1) {

                // Mage version
                var mageVersion = jQuery('[name=product\\[inventory\\]\\[mage\\]]').val();
                if (mageVersion == undefined) {
                    mageVersion = "2.0";
                }
                var pid = jQuery('[name=product\\[inventory\\]\\[product_id\\]]');

                if (pid.val() == "") {
                    pid.parent().parent().parent().css({"display": "none"});
                    clearInterval(observe);
                    return;
                }

                if (toInt(mageVersion) >= toInt("2.1")) { // Mage >= 2.1

                    InventoryManager = {
                        debug: false,
                        autoUpdateStockStatus: 0,
                        log: function (method, arguments) {
                            if (this.debug) {
                                console.log("InventoryManager says " + method + "()", arguments);
                            }
                        },
                        updateQty: function () {
                            this.log('updateQty', arguments);
                            var qty = 0;
                            jQuery('div[data-index=wyomind_advanced_inventory] [name*=\\]\\[qty\\]]').each(function () {
                                qty += parseInt(jQuery(this).val());
                            });
                            jQuery('[name=product\\[quantity_and_stock_status\\]\\[qty\\]]')[0].value = qty;
                            jQuery('[name=product\\[quantity_and_stock_status\\]\\[qty\\]]')[1].value = qty;
                        },
                        enableMultiStock: function () {

                            this.log('enableMultiStock', arguments);

                            if (jQuery("[name=product\\[inventory\\]\\[multistock\\]]")[0].value == 0) { // multi stock disabled

                                jQuery('[name=product\\[quantity_and_stock_status\\]\\[qty\\]]')[0].disabled = false;
                                jQuery('[name=product\\[quantity_and_stock_status\\]\\[qty\\]]')[1].disabled = false;
                                jQuery('[name=product\\[stock_data\\]\\[backorders\\]]')[0].disabled = false;
                                jQuery('[name=product\\[quantity_and_stock_status\\]\\[is_in_stock\\]]')[0].disabled = false;
                                jQuery('[name=product\\[stock_data\\]\\[use_config_backorders\\]]')[0].disabled = false;

                                jQuery('[name*=product\\[inventory\\]\\[pos_wh\\]]').parent().parent().css({display: 'none'});
                                jQuery('[name*=product\\[inventory\\]\\[pos_wh\\]][type=checkbox]').parent().parent().parent().css({display: 'none'});

                            } else { // multistock enabled
                                jQuery('[name=product\\[quantity_and_stock_status\\]\\[qty\\]]')[0].disabled = true;
                                jQuery('[name=product\\[quantity_and_stock_status\\]\\[qty\\]]')[1].disabled = true;
                                jQuery('[name=product\\[stock_data\\]\\[backorders\\]]')[0].disabled = true;
                                jQuery('[name=product\\[stock_data\\]\\[use_config_backorders\\]]')[0].disabled = true;
                                if (InventoryManager.autoUpdateStockStatus === "1") {
                                    jQuery('[name=product\\[quantity_and_stock_status\\]\\[is_in_stock\\]]')[0].disabled = true;
                                    jQuery('[name=product\\[quantity_and_stock_status\\]\\[is_in_stock\\]]')[1].disabled = true;
                                } else {
                                    jQuery('[name=product\\[quantity_and_stock_status\\]\\[is_in_stock\\]]')[0].disabled = false;
                                    jQuery('[name=product\\[quantity_and_stock_status\\]\\[is_in_stock\\]]')[1].disabled = false;
                                }

                                jQuery('[name*=product\\[inventory\\]\\[pos_wh\\]]').parent().parent().css({display: 'block'});
                                jQuery('[name*=product\\[inventory\\]\\[pos_wh\\]][type=checkbox]').parent().parent().parent().css({display: 'block'});

                                InventoryManager.updateQty();
                            }


                        },
                        showDetails: function (elt) {
                            this.log('showDetails', arguments);
                            var display = (jQuery(elt).val() === "1" && jQuery(document.getElementsByName("product[inventory][multistock]")[0]).val() === "1" ? "block" : "none");
                            jQuery(elt).parent().parent().next().css({"display": display});
                            jQuery(elt).parent().parent().next().next().css({"display": display});
                            jQuery(elt).parent().parent().next().next().next().css({"display": display});
                            if (jQuery(document.getElementsByName("product[inventory][multistock]")[0]).val() === "1") {
                                InventoryManager.updateQty();
                            }
                        },
                        updateConfig: function (elt) {
                            jQuery(elt).parent().parent().parent().prev().find("select").prop('disabled', jQuery(elt).prop('checked'));
                        }
                    };

                    InventoryManager.autoUpdateStockStatus = jQuery('[name=product\\[inventory\\]\\[auto_update_stock_status\\]]').val();

                    InventoryManager.enableMultiStock();

                    // initialize display
                    jQuery("div[data-index=wyomind_advanced_inventory] [name*=\\]\\[use_config_setting_for_backorders\\]]").each(function () {
                        InventoryManager.updateConfig(jQuery(this));
                    });
                    jQuery("div[data-index=wyomind_advanced_inventory] [name*=\\]\\[manage_stock\\]]").each(function () {
                        InventoryManager.showDetails(jQuery(this));
                    });


                    // enable/disable multi stock
                    jQuery("[name=product\\[inventory\\]\\[multistock\\]").on('change', function () {
                        InventoryManager.enableMultiStock();
                    });
                    // display details ?
                    jQuery("div[data-index=wyomind_advanced_inventory] [name*=\\]\\[manage_stock\\]]").on('change', function () {
                        InventoryManager.showDetails(jQuery(this));
                    });
                    // qty changed
                    jQuery("div[data-index=wyomind_advanced_inventory] [name*=\\]\\[qty\\]]").on('change', function () {
                        InventoryManager.updateQty();
                    });
                    // checkbox use config ?
                    jQuery("div[data-index=wyomind_advanced_inventory] [name*=\\]\\[use_config_setting_for_backorders\\]]").on('change', function () {
                        InventoryManager.updateConfig(jQuery(this));
                    });

                } else {// Magento 2.0

                    InventoryManager.debug = false;
                    InventoryManager.log = function (method, arguments) {
                        if (this.debug) {
                            console.log("InventoryManager says " + method + "()", arguments);
                        }
                    };
                    InventoryManager.updateQty = function () {

                        this.log('updateQty', arguments);
                        qty = 0;
                        jQuery('#advancedinventory_stocks  DIV.pointofsale[visibility!=hidden] INPUT.validate-number').each(function () {
                            qty += parseInt(jQuery(this).val());
                        });
                        jQuery('[name=product\\[quantity_and_stock_status\\]\\[qty\\]]').val(qty);
                        jQuery('#inventory_qty').val(qty);
                        jQuery('#original_inventory_qty').val(qty);
                    };
                    InventoryManager.enableMultiStock = function () {

                        this.log('enableMultiStock', arguments);
                        
                        if (jQuery("#multistock").val() === "0") {
                            jQuery('#qty').parent().parent().show();
                            jQuery('#inventory_qty').prop({disabled: false});
                            jQuery("#inventory_backorders").parent().parent().show();

                            jQuery("#inventory_stock_availability").prop({disabled: false});


                        } else {
                            jQuery('#qty').parent().parent().hide();
                            jQuery('#inventory_qty').prop({disabled: true});
                            jQuery("#inventory_backorders").parent().parent().hide();
                            if (InventoryManager.autoUpdateStockStatus == 1) {
                                jQuery("#inventory_stock_availability").prop({disabled: true});
                            }
                            InventoryManager.updateQty();
                        }

                        jQuery('#advancedinventory_stocks').css({display: (jQuery("#multistock").val() === "0") ? 'none' : 'block'});


                    };
                    InventoryManager.showDetails = function (elt) {

                        this.log('showDetails', arguments);
                        jQuery(elt).next().css({"display": (jQuery(elt).val() === "1" ? "block" : "none")});
                        jQuery(elt).next().css('visibility', (jQuery(elt).val() === '0') ? 'hidden' : 'visible');
                        InventoryManager.updateQty();
                    };
                }

                clearInterval(observe);
            }
        }, 1000);
    });
});
