/**
 * Copyright © 2016 Wyomind. All rights reserved.
 * See LICENSE.txt for license details.
 */


var InventoryManager = {
    debug: true,
    canSave: false,
    data: {},
    log: function (method, arguments) {
        if (this.debug)
            console.log("InventoryManager says " + method + "()", arguments);
    },
    viewAssignation: function (elt, url) {

        jQuery(elt).parents('TD').eq(0).off('click');

        jQuery.ajax({
            url: url,
            type: 'get',
            showLoader: true,
            error: function () {
                alert('An error occurred!');
            },
            success: function (data) {
                content = "<div id='ai-overlay'><div id='ai-details'></div></div>"
                jQuery("#html-body").append(content);
                jQuery('#ai-details').html(data);
                return true;
            }
        });
    },
    closePopup: function () {
        jQuery('#ai-overlay').remove();

    },
    validateStockData: function () {

        this.log('validateStockData', arguments);

        jQuery('.ai-stock-inner TBODY TR').each(function () {
            if (jQuery(this).find('INPUT[type=text]').length > 0) {
                total = 0;
                qty = jQuery(this).attr("qty");
                jQuery(this).find('INPUT[type=text]').each(function () {
                    total += InventoryManager.parseInt(jQuery(this).val());
                })

                if (total != qty) {
                    jQuery(this).addClass('alert')

                } else {
                    jQuery(this).removeClass('alert')

                }
            }
        })
        if (this.canSave) {

            if (jQuery('.ai-stock-inner TBODY TR.alert').length > 0) {
                jQuery('#assignation_button').addClass('disabled');
                jQuery('#assignation_button').attr("disabled", true);
            } else {
                jQuery('#assignation_button').removeClass('disabled');
                jQuery('#assignation_button').attr("disabled", false);
            }
        }
        var disabled = jQuery(":input:disabled").removeAttr('disabled');
        InventoryManager.data = jQuery("#ai-scroller").serialize();
        disabled.attr('disabled', 'disabled');


    },
    updateRemainingStock: function (elt) {

        e = jQuery(elt);
        this.log('updateRemainingStock', arguments);
        qty = InventoryManager.parseInt(e.parents().eq(1).attr("qty"));
        total = 0;
        e.parents().eq(1).find('INPUT[type=text]').each(function () {
            total += InventoryManager.parseInt(jQuery(this).val());
        })
        origin = InventoryManager.parseInt(e.next().next().val());
        if ((total > qty || isNaN(e.val()))) {
            e.val(origin);
        }


        if (e.val() < 0 || e.val() == '')
            e.val(0);
        else if (!e.parents().eq(0).prev().hasClass('multistock_disabled')) {
            maximum = InventoryManager.parseInt(e.next().next().next().val());

            if ((e.val() <= maximum) || e.parents().eq(0).prev().hasClass('backorder'))
                e.parent().prev().html(maximum - e.val());
            else if (maximum < 0) {
                e.val(origin);
                e.parent().prev().html(maximum);
            } else {
                e.val(maximum);
                e.parent().prev().html(0);
            }

            e.next().next().val(e.val());

        }
        if (e.val() > 0)
            e.addClass('valid');
        else
            e.removeClass('valid');


        this.validateStockData()
    },
    updateAssignation: function (url, id) {

        this.log('updateAssignation', arguments);

        this.validateStockData();
        jQuery.ajax({
            url: url,
            method: 'post',
            data: {data: InventoryManager.data},
            showLoader: true,
            error: function () {
                alert('An error occurred!');
            },
            success: function (data) {

                jQuery("#assignation_column_" + id).eq(0).html(data);
                jQuery('INPUT[type=text].keydown').each(function () {
                    jQuery(this).next().next().next().next().val(jQuery(this).val())
                })

            }
        });



    },
    clearAll: function () {
        jQuery("INPUT[type=text].keydown").each(function () {
            jQuery(this).val(0);
            InventoryManager.updateRemainingStock(jQuery(this))
        })
        jQuery("INPUT[type=radio].assigned_to").removeAttr('checked');

        //InventoryManager.updateAssignation(url, id)

    },
    autoUpdateAssignation: function (url, id) {

        this.log('autoUpdateAssignation', arguments);


        jQuery.ajax({
            url: url,
            method: 'post',
            data: {data: InventoryManager.data},
            showLoader: true,
            error: function () {
                alert('An error occurred!');
            },
            success: function (data) {
                place_ids = [];
                InventoryManager.clearAll();
                if (typeof data.inventory.items != "undefined") {

                    jQuery.each(data.inventory.items, function (item_id, item) {
                        jQuery.each(item.pos, function (place_id, pos) {
                            if (place_ids.indexOf(place_id) == -1)
                                place_ids.push(place_id);
                            jQuery("INPUT#inventory_" + item_id + "_" + place_id).val(pos.qty_assigned);
                            InventoryManager.updateRemainingStock(jQuery("INPUT#inventory_" + item_id + "_" + place_id))
                        })
                    });
                    if (place_ids.length == 1) {
                        jQuery("#radio_" + place_ids[0]).prop("checked", true);
                    }


                    // InventoryManager.updateAssignation(url2, id)

                } else {
                    alert('Unable to find a location to assign!');
                }
                console.log(data.log)



            }
        });



    },
    keydown: function (e, elt) {

        this.log('keydown', arguments);

        if (e.keyCode == 38) {
            elt.val(InventoryManager.parseInt(elt.val()) + 1);

        }
        if (e.keyCode == 40) {
            elt.val(InventoryManager.parseInt(elt.val()) - 1);

        }


    },
    number_format: function (number, decimals, dec_point, thousands_sep) {
        // Strip all characters but numerical ones.
        number = (number + '').replace(/[^0-9+\-Ee.]/g, '');
        var n = !isFinite(+number) ? 0 : +number,
                prec = !isFinite(+decimals) ? 0 : Math.abs(decimals),
                sep = (typeof thousands_sep === 'undefined') ? ',' : thousands_sep,
                dec = (typeof dec_point === 'undefined') ? '.' : dec_point,
                s = '',
                toFixedFix = function (n, prec) {
                    var k = Math.pow(10, prec);
                    return '' + Math.round(n * k) / k;
                };
        // Fix for IE parseFloat(0.55).toFixed(0) = 0;
        s = (prec ? toFixedFix(n, prec) : '' + Math.round(n)).split('.');
        if (s[0].length > 3) {
            s[0] = s[0].replace(/\B(?=(?:\d{3})+(?!\d))/g, sep);
        }
        if ((s[1] || '').length < prec) {
            s[1] = s[1] || '';
            s[1] += new Array(prec - s[1].length + 1).join('0');
        }
        return s.join(dec);
    },
    parseInt: function (value) {
        return parseInt(value)
        //return InventoryManager.number_format(value, 4, '.');
    }

}
require(["jquery", "mage/mage"], function ($) {
    $(function () {


        jQuery(document).on("keydown", "INPUT.keydown", function (e) {
            InventoryManager.keydown(e, jQuery(this))
            InventoryManager.updateRemainingStock(this, false)
        })


        jQuery(document).on("change","INPUT.assigned_to", function (e) {
            radio = jQuery(this);
            i = 0;
            jQuery('#ai-scroller TABLE TR').each(function () {
                if (i) {
                    qty = jQuery(this).attr('qty')
                    ii = 0;
                    jQuery(this).find("INPUT.keydown").each(function () {
                        maximum = jQuery(this).next().next().next().val();
                        if (ii == radio.val()) {
                            if (jQuery(this).parents().eq(0).prev().hasClass("backorder")) {
                                jQuery(this).val(InventoryManager.parseInt(qty));
                            } else if (InventoryManager.parseInt(qty) > InventoryManager.parseInt(maximum)) {
                                jQuery(this).val(InventoryManager.parseInt(maximum));
                            } else {
                                jQuery(this).val(InventoryManager.parseInt(qty));
                            }
                        } else {
                            jQuery(this).val(0);
                        }

                        ii++;
                    });
                }
                i++;
            })
            jQuery('#ai-scroller TABLE TR').each(function () {
                jQuery(this).find("INPUT.keydown").each(function () {
                    InventoryManager.updateRemainingStock(this)
                })
            })
        });
        if (jQuery("#container"))
            jQuery("#ai-scroller").css({'width': jQuery("#container").width() - 380 + "px"});

    })
});




