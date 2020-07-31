
require([
    'Magento_Rma/rma'
], function () {
    window.AdminRma.prototype.addOrderItemToGrid = function (orderItem, className, hasUserAttributes) {
        var fieldsProduct = [
            'product_name',
            'product_sku',
            'qty_ordered',
            'unit_case',
            'qty_requested',
            'order_item_id'
        ];
        var tableRma = $('rma_items_grid_table');

        var newRmaItemId = "new" + this.newRmaItemId;

        var tbody = tableRma.down('tbody.newRma');
        if (!tbody) {
            tbody = new Element('tbody').addClassName('newRma');
        }

        var row = new Element('tr', {id: 'id_' + newRmaItemId, 'class': className ? 'even' : 'odd'});

        fieldsProduct.each(function(el,i) {
            var column = new Element('td',{class:'col-'+ el});
            var data = '';
            if (orderItem[el]) {
                data = orderItem[el];
                if (el == 'unit_case') {
                    column.insert('<input type="hidden" name="items[' + newRmaItemId + '][unit_case]" value="'+ data +'"/>');
                    if (data != orderItem['unit_case_ordered']) {
                        data += '<br><p class="unit-case">'+ orderItem['unit_case_ordered'] +'(' + orderItem['unit_qty_ordered'] + ' ' + data + ')</p>'
                    }
                }
            } else if (el == 'order_item_id') {
                column.addClassName('hidden');
                column.insert('<input type="hidden" name="items[' + newRmaItemId + '][order_item_id]" value="'+orderItem['item_id']+'"/>');
            } else {
                data = $('rma_properties_' + el);
                if (data) {
                    data = $(data).cloneNode(true);
                    data.name = 'items[' + newRmaItemId + '][' + data.name + ']';
                    data.id   = data.id + '_' + newRmaItemId;
                    data.addClassName('required-entry');
                }
            }
            column.insert(data);
            row.insert(column);
        });
        var column = new Element('td',{class:'col-actions'});
        var deleteLink = new Element('a', {href:'#'});
        Event.observe(deleteLink, 'click', this.deleteRow.bind(this));
        deleteLink.insert($$('label[for="rma_properties_delete_link"]').first().innerHTML);
        column.insert(deleteLink);
        row.insert(column);
        tableRma.insert(tbody.insert(row));

        this.getAjaxData(this.newRmaItemId, true);
        this.callLoadProductsCallback();
        this.newRmaItemId++;
    };
    window.AdminRma.prototype.getOrderItem = function(idElement) {
        var data = Array();
        var rowOrder = jQuery(idElement).parents('tr:first');
        data['item_id'] = idElement.value;
        data['product_name'] = jQuery('.col-product_name', rowOrder).text().trim();
        data['product_sku'] = jQuery('.col-sku', rowOrder).text().trim();
        data['qty_ordered'] = jQuery('.col-qty', rowOrder).text().trim();
        data['unit_case'] = jQuery('.col-unit_case', rowOrder).text().trim();
        data['unit_case_ordered'] = jQuery('.col-unit_case_ordered', rowOrder).text().trim();
        data['unit_qty_ordered'] = jQuery('.col-unit_qty_ordered', rowOrder).text().trim();
        return data;
    };

    var _hidePopups = window.AdminRma.prototype.hidePopups;
    window.AdminRma.prototype.hidePopups = function () {
        jQuery('button[onclick="rma.addSelectedProduct()"]').show();
        _hidePopups.apply(this);
    };

    var _showBundleItems = window.AdminRma.prototype.showBundleItems;
    window.AdminRma.prototype.showBundleItems = function (event) {
        jQuery('button[onclick="rma.addSelectedProduct()"]').hide();
        _showBundleItems.apply(this, [event]);
    };

    var _showPopup  = window.AdminRma.prototype.showPopup;
    window.AdminRma.prototype.showPopup = function (divId) {
        jQuery('#' + divId).show();
        _showPopup.apply(this, [divId]);
    };

    var _bundleStoreState = window.AdminRma.prototype.bundleStoreState;
    window.AdminRma.prototype.bundleStoreState = function (itemId) {
        _bundleStoreState.apply(this, [itemId]);

        if (this.bundleArray[itemId]) {
            for (var i in this.bundleArray[itemId]) {
                var bi = this.bundleArray[itemId][i];
                if (bi.hasOwnProperty('item_id')) {
                    var unitDefault = jQuery('#checkbox_rma_bundle_item_unit_' + itemId + '_'+ bi.item_id),
                        unitCase = jQuery('#checkbox_rma_bundle_item_unit_case_ordered_' + itemId + '_'+ bi.item_id),
                        unitQty = jQuery('#checkbox_rma_bundle_item_unit_qty_ordered' + itemId + '_'+ bi.item_id);
                    if (unitDefault.length) {
                        bi.unit_case = unitDefault.prop('value');
                    }
                    if (unitCase.length) {
                        bi.unit_case_ordered = unitCase.prop('value');
                    }
                    if (unitQty.length) {
                        bi.unit_qty_ordered = unitQty.prop('value');
                    }
                    this.bundleArray[itemId][i] = bi;
                }
            }
        }
    }
});