define([
    "jquery",
    'Magento_Ui/js/modal/alert',
    'Magento_Ui/js/modal/confirm',
    "mage/translate",
    "prototype",
    "Magento_Catalog/catalog/product/composite/configure",
    'Magento_Ui/js/lib/view/utils/async'
], function (jQuery, alert, confirm, $t) {

    window.AddGilletteProduct = Class.create();

    AddGilletteProduct.prototype = {
        /**
         * Constructor
         *
         * @param order            Instance of AdminOrder
         * @param hasItem
         */
        initialize: function (order) {
            this.order = order;
            // abstract admin sales instance
            function adminSalesInstance(addGilletteproductObject) {
                this.order = addGilletteproductObject.order;
            }

            function adminCheckout()
            {
                this.controllerRequestParameterNames = {customerId: 'customer', storeId: 'store'};
            }

            // admin sales instance for order creation
            adminOrder.prototype = new adminSalesInstance(this);
            adminOrder.prototype.constructor = adminOrder;
            function adminOrder() {
                var preproductAreaId = this.order.getAreaId('gillette');

                this.controllerRequestParameterNames = {customerId: 'customerId', storeId: 'storeId'};
                if(typeof this.order.itemsArea !== 'undefined') {
                    this.order.itemsArea.gilletteproductButton = new ControlButton($t('Add Gillette Products'));
                    this.order.itemsArea.gilletteproductButton.onClick = function () {
                        var interval = setInterval(function () {
                            jQuery('#sales_order_create_gilletteprod_grid tr').trigger('click');
                            clearInterval(interval);
                        }, 500);
                        $(preproductAreaId).show();
                        var el = this;
                        window.setTimeout(function () {
                            el.remove();
                        }, 10);
                    };
                    this.order.itemsArea.onLoad = this.order.itemsArea.onLoad.wrap(function (proceed) {
                        proceed();
                        if ($(preproductAreaId) && !$(preproductAreaId).visible()) {
                            this.addControlButton(this.gilletteproductButton);
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
        },

        productGridRowClick: function (grid, event) {
            var trElement = Event.findElement(event, 'tr');
            var isInputQty = Event.element(event).tagName == 'INPUT' && Event.element(event).type == 'text';
            var isInputCheckbox = Event.element(event).tagName == 'INPUT' && Event.element(event).type == 'checkbox';
            var checkbox = Element.select(trElement, 'input[type="checkbox"]');
            var inputQty = Element.select(trElement, 'input[type="text"]');
            if (trElement && !isInputQty) {
                if(checkbox[0] && !checkbox[0].disabled){
                    var checked = isInputCheckbox ? checkbox[0].checked : !checkbox[0].checked;
                    if(isInputCheckbox)
                    {
                        jQuery(trElement).trigger('click');
                    }else{
                        if(!isInputQty )
                        {
                            if (!isInputQty) {
                                if(checked)
                                {
                                    inputQty[0].disabled=false;
                                }else{
                                    inputQty[0].disabled=true;
                                    //inputQty[0].value = 1;
                                }
                            }
                        }
                        grid.setCheckboxChecked(checkbox[0], checked);
                    }
                }
            }
        },

        productGridCheckboxCheck: function (grid, element, checked) {
            if(checked){
                if(element.inputElements) {
                    //this.gridData.set(element.value, {});
                    this.gridProducts.set(element.value, {});
                    var product = this.gridProducts.get(element.value);
                    for(var i = 0; i < element.inputElements.length; i++) {
                        element.inputElements[i].disabled = false;
                        //this.gridData.get(element.value)[element.inputElements[i].name] = element.inputElements[i].value;

                        var input = element.inputElements[i];
                        if (input.checked || input.name != 'giftmessage') {
                            product[input.name] = input.value;
                        } else if (product[input.name]) {
                            delete(product[input.name]);
                        }
                    }
                }
            }
            else{
                if(element.inputElements){
                    for(var i = 0; i < element.inputElements.length; i++) {
                        element.inputElements[i].disabled = true;
                    }
                }
                // this.gridData.unset(element.value);
                this.gridProducts.unset(element.value);
            }

            grid.reloadParams = {'products[]': this.gridProducts.keys()};
        },

        /**
         * Submit configured products to quote
         */
        productGridAddSelected: function () {
            if (this.order.productGridShowButton) Element.show(this.order.productGridShowButton);
            var area = ['gillette', 'items', 'shipping_method', 'totals', 'giftmessage', 'billing_method'];
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

            this.order.productConfigureSubmit('product_to_add', area, fieldsPrepare, itemsFilter);
            productConfigure.clean('quote_items');
            this.order.hideArea('gillette');
            this.order.gridProducts = $H({});
        }
    };

});
