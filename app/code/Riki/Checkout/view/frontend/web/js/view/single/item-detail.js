define(
    [
        'jquery', 'ko', 'uiComponent', 'mage/translate', 'uiRegistry'
    ], function ($, ko, Component, $t, registry) {
        'use strict';

        return Component.extend({
            defaults: {
                template: 'Riki_Checkout/single/item-detail'
            },

            deliveryTypes: ko.observableArray([]),
            dateBlock: null,

            initialize: function() {
                this._super();

                var self = this;

                registry.get('deliveryTypes', function (deliveryTypes) {
                    self.deliveryTypes(deliveryTypes());
                });

                registry.get('checkout.steps.shipping-step.shippingAddress.delivery_date', function (deliveryDate) {
                    self.dateBlock = deliveryDate;
                });

                return this;
            },

            getDeliveryTypes: function () {
                 return this.deliveryTypes;
            },

            updateItemQty: function (item_id,unit_case,unit_qty, adjustQty) {
                this.dateBlock.updateItemQty(item_id,unit_case,unit_qty, adjustQty)
            },

            updateHanpukaiQty: function(value) {
                this.dateBlock.updateHanpukaiQty(value);
            },

            removeItem: function (item_id,product_id) {
                this.dateBlock.removeItem(item_id,product_id)
            },

            updateGiftWrapping: function (item_id) {
                this.dateBlock.updateGiftWrapping(item_id);
            },
            getMinSaleQty: function (min_sale_qty) {
                return this.dateBlock.getMinSaleQty(min_sale_qty);
            },
            getMaxSaleQty : function (max_sale_qty) {
                return this.dateBlock.getMaxSaleQty(max_sale_qty);
            }
        });
    }
);