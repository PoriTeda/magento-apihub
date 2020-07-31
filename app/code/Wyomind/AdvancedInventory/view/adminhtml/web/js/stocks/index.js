/**
 * Copyright © 2016 Wyomind. All rights reserved.
 * See LICENSE.txt for license details.
 */


var InventoryManager = {
    debug: true,
    log: function (method, arguments) {
        if (this.debug)
            console.log("InventoryManager says " + method + "()", arguments);
    },
    saveStocks: function (url, id) {

        this.log('saveStocks', arguments);
        ids = new Array;
        if (id != "all") {
            ids.push(id);
        } else {
            jQuery('.GlobalQty').each(function () {
                ids.push(jQuery(this).attr("id").replace("GlobalQty_", ""));
            })
        }

        inventory = {};
        ids.each(function (id) {
            tr = InventoryManager.getRow(id)

            status = jQuery('#GlobalQty_' + id).attr('multistock');

            if (tr.find('.StockStatus').length)
                is_in_stock = tr.find('.StockStatus')[0].value;
            else
                is_in_stock = false;
            if (status === 'enabled') {
                pos_wh = {};
                qty = null;



                tr.find(".PosQty").each(function () {

                    data = jQuery(this).attr('id').split('_');
                    qty = 0;

                    if (jQuery(this).find('INPUT').eq(0).length)
                        qty = jQuery(this).find('INPUT').eq(0).val();
                    pos_wh[data[2]] = {qty: qty}

                })

                inventory[id] = {multistock: true, pos_wh: pos_wh, qty: null, is_in_stock: is_in_stock};

            } else
                inventory[id] = {multistock: false, pos_wh: null, qty: tr.find('.GlobalQty INPUT').eq(0).val(), is_in_stock: is_in_stock};

        })


        jQuery.ajax({
            url: url,
            type: 'post',
            data: {data: JSON.stringify(inventory)},
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

        if (id !== "all") {
            this.resetAction(id);
        }
    },
    updateQty: function () {

        this.log('updateQty', arguments);
        qty = 0;
        jQuery('#advancedinventory_stocks TABLE TR DIV[visibility!=hidden] INPUT.validate-number').each(function () {
            qty += parseInt(jQuery(this).val());
        });
        jQuery('#inventory_qty').val(qty);
    },
    updateStocks: function (tr) {

        this.log('updateStocks', arguments);
        backorderable = false;
        multistock = tr.find(".GlobalQty").eq(0).attr("multistock") == "enabled";
        if (multistock) {

            qty = 0;
            tr.find(".PosQty").each(function () {
                if (jQuery(this).next().next().length)
                    backorderable = jQuery(this).next().next().hasClass("backorderable");
            });

            tr.find(".PosQty INPUT").each(function () {

                qty += Math.round(jQuery(this).val());
            });
            tr.find('.GlobalQty')[0].innerHTML = qty;


        } else {
            qty = tr.find(".GlobalQty INPUT").eq(0).val();

            backorderable = tr.find(".GlobalQty").eq(0).next().hasClass("backorderable");

        }
        if (InventoryManager.autoUpdateStockStatus) {
            if (qty - tr.find('.StockStatus').eq(0).attr('min') > 0 || backorderable) {
                tr.find('.StockStatus').eq(0).val(1);
            } else {
                tr.find('.StockStatus').eq(0).val(0);
            }
        }


    },
    enableMultiStock: function (type, from) {

        this.log('enableMultiStock', arguments);


        status = jQuery('#GlobalQty_' + from).attr('multistock');

        if (status === 'enabled') {
            msg = this.enableMsg;

            jQuery('#GlobalQty_' + from).html("<input class='keydown inventory_input' type='text' value='" + jQuery('#GlobalQty_' + from).html() + "' />");
            jQuery('#GlobalQty_' + from).attr('multistock', 'disabled')
            jQuery('#GlobalQty_' + from).next().show();
            this.getRow(from).find('.PosQty').each(function () {
                jQuery(this).html(InventoryManager.nd)
                jQuery(this).next().next().hide();
            })
        } else {
            msg = this.disableMsg;
            qty = jQuery('#GlobalQty_' + from).find('INPUT').eq(0).val();

            jQuery('#GlobalQty_' + from).text(qty);
            jQuery('#GlobalQty_' + from).attr('multistock', 'enabled')
            jQuery('#GlobalQty_' + from).next().hide();
            g = 0;
            this.getRow(from).find('.PosQty').each(function () {
                data = jQuery(this).next().attr('data').evalJSON()
                if (g == 0)
                    q = qty;
                else
                    q = 0;
                if (data.manage_stock) {
                    html = "<input type='text' class='keydown inventory_input' value='" + q + "' / >";
                } else {
                    html = InventoryManager.void;
                }
                jQuery(this).html(html)
                jQuery(this).next().next().show();
                g++;
            })
        }
        this.updateStocks(this.getRow(from))
        this.getRow(from).find('select').eq(1).find('OPTION:selected').eq(0).html(msg)
        this.resetAction(from);



    },
    disableMultiStock: function () {

        this.log('disableMultiStock', arguments);
    },
    evalEvent: function (elt, event) {

        this.log('evalEvent', arguments);
    },
    keydown: function (e, elt) {

        this.log('keydown', arguments);

        if (e.keyCode == 38) {
            elt.val(parseNumber(elt.val()) + 1);

        }
        if (e.keyCode == 40) {
            elt.val(parseNumber(elt.val()) - 1);

        }
        if (e.keyCode == 13) {

            if (elt.parents('TR').eq(0).find('SELECT.admin__control-select OPTION').length) {
                action = elt.parents('TR').eq(0).find('SELECT.admin__control-select OPTION').eq(1).val().evalJSON();
                if (action.hasOwnProperty("params")) {
                    InventoryManager.saveStocks(action.params.url,action.params.id);
                }
            }
        }
    },
    resetAction: function (id) {

        this.log('resetAction', arguments);
        action = this.getRow(id);
        if (action.find('SELECT').length)
            action.find('SELECT')[1].selectedIndex = 0;

    },
    getRow: function (id) {

        this.log('getRow', arguments);
        return jQuery('#GlobalQty_' + id).parents().eq(1)
    },
    closePopup: function () {
        jQuery('#ai-overlay').remove();
    }
}



window.onload = function () {
    require(["jquery", "mage/mage"], function ($) {
        $(function () {
            jQuery('INPUT.keydown').live("keydown", function (e) {
                InventoryManager.keydown(e, jQuery(this));
                if (jQuery(this).attr('onChange')) {
                    InventoryManager.evalEvent(jQuery(this), 'onChange');
                }
            });
            jQuery('INPUT.inventory_input').live("keydown", function (e) {
                InventoryManager.updateStocks(jQuery(this).parents('TR').eq(0));
            });
            jQuery('INPUT.inventory_input').live("change", function (e) {
                i = jQuery(this);
                if (isNaN(i.val())) {
                    i.val(0);
                }
                InventoryManager.updateStocks(jQuery(this).parents('TR').eq(0));
            });
            if (jQuery("#content")) {
                jQuery("#ai-scroller").css({'width': jQuery("#content").width() - 380 + "px"});
            }

        });
    });
}
