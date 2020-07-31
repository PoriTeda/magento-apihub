define([
    "jquery",
    "mage/translate",
    "prototype",
    "Magento_Catalog/catalog/product/composite/configure"
], function (jQuery, $t) {

    window.AddPreProduct = Class.create();

    AddPreProduct.prototype = {
        /**
         * Constructor
         *
         * @param order            Instance of AdminOrder
         * @param hasItem
         */
        initialize: function (order) {
            this.order = order;

            // abstract admin sales instance
            function adminSalesInstance(addPreproductObject) {
                this.order = addPreproductObject.order;
            }

            function adminCheckout()
            {
                this.controllerRequestParameterNames = {customerId: 'customer', storeId: 'store'};
            }

            // admin sales instance for order creation
            adminOrder.prototype = new adminSalesInstance(this);
            adminOrder.prototype.constructor = adminOrder;
            function adminOrder() {
                var preproductAreaId = this.order.getAreaId('preproduct_area');

                this.controllerRequestParameterNames = {customerId: 'customerId', storeId: 'storeId'};
                if(typeof this.order.itemsArea !== 'undefined') {
                    this.order.itemsArea.preproductButton = new ControlButton($t('Add Pre-order Products'));
                    this.order.itemsArea.preproductButton.onClick = function () {
                        $(preproductAreaId).show();
                        var el = this;
                        window.setTimeout(function () {
                            el.remove();
                        }, 10);
                    };
                    this.order.itemsArea.onLoad = this.order.itemsArea.onLoad.wrap(function (proceed) {
                        proceed();
                        if ($(preproductAreaId) && !$(preproductAreaId).visible()) {
                            this.addControlButton(this.preproductButton);
                        }
                    });
                }
                if(typeof this.order.dataArea !== 'undefined') {
                    this.order.dataArea.onLoad();
                }
            }

            // Strategy
            if (this.order instanceof (window.AdminOrder || Function)) {
                this._provider = new adminOrder();
            } else {
                this._provider = new adminCheckout();
            }
            this.controllerRequestParameterNames = this._provider.controllerRequestParameterNames;
        }, /**
         * Submit configured products to quote
         */
        productGridAddSelected: function () {
            if (this.order.productGridShowButton) Element.show(this.order.productGridShowButton);
            var area = ['preproduct_area', 'items', 'shipping_method', 'totals', 'giftmessage', 'billing_method'];
            // prepare additional fields and filtered items of products
            var fieldsPrepare = {};
            var itemsFilter = [];
            var products = this.order.gridProducts.toObject();

            for (var productId in products) {
                itemsFilter.push(productId);
                var paramKey = 'item['+productId+']';
                var paramKeyOriginal = 'item['+productId+']';
                for (var productParamKey in products[productId]) {

                    if('case_display' == productParamKey || 'unit_qty' == productParamKey){
                        var paramKeyCaseDisplay = paramKeyOriginal+'['+productParamKey+']';
                        fieldsPrepare[paramKeyCaseDisplay] = products[productId][productParamKey];
                    }
                    else
                    if('qty' == productParamKey){
                        var paramKeyCaseDisplay = paramKeyOriginal+'['+productParamKey+']';
                        if('cs' == products[productId]['case_display']){
                            fieldsPrepare[paramKeyCaseDisplay] = products[productId][productParamKey] * products[productId]['unit_qty'];
                        }
                        else{
                            fieldsPrepare[paramKeyCaseDisplay] = products[productId][productParamKey];
                        }

                    }
                    else{
                        paramKey += '['+productParamKey+']';
                        fieldsPrepare[paramKey] = products[productId][productParamKey];
                    }
                }
            }

            if(itemsFilter.length){
                fieldsPrepare['is_preproduct'] = 1;
            }

            this.order.productConfigureSubmit('product_to_add', area, fieldsPrepare, itemsFilter);
            productConfigure.clean('quote_items');
            this.order.hideArea('preproduct_area');
            this.order.gridProducts = $H({});
        }
    };

});
