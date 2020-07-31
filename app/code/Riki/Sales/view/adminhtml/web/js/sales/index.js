
InventoryManager.updateAssignation = function (url, id) {

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

            if (typeof data.message != "undefined") {
                alert(data.message);
            } else {
                jQuery("#assignation_column_" + id).eq(0).html(data);
                jQuery('INPUT[type=text].keydown').each(function () {
                    jQuery(this).next().next().next().next().val(jQuery(this).val())
                })
            }
        }
    });
};

InventoryManager.autoUpdateAssignation = function (url, id) {

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

            if (typeof data.message != "undefined") {
                alert(data.message);
            } else {
                place_ids = [];
                InventoryManager.clearAll();
                if (typeof data.inventory.items != "undefined") {
                    jQuery.each(data.inventory.items, function (item_id, item) {
                        jQuery.each(item.pos, function (place_id, pos) {
                            if (place_ids.indexOf(place_id) == -1) {
                                place_ids.push(place_id); }
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
            }
        }
    });
};




