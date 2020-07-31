define([
    'jquery',
    'ko',
    'Magento_Customer/js/customer-data',
    'underscore',
    'Riki_Subscription/js/model/utils',
    "Riki_Theme/js/sync-cart-data",
    'mage/translate',
    'Magento_Ui/js/model/messageList',
    'uiRegistry',
], function ($, ko, customerData, _, priceUtils, syncCartData, $t, messageList, uiRegistry) {
    const cacheKey = "m_cart_data";
    const _getCartData = function (reset) {
        // IE11 compatible
        if (reset === undefined) {
            reset = false;
        }
        customerData.init();
        let _cartData = customerData.get(cacheKey)();
        if (reset || _.isEmpty(_cartData)) {
            let _cartData = {
                data_id: cacheKey,
                pageType: false,
                pageId: false,
                subtotal: 0,
                totalQty: 0,
                products: [],
                quoteProducts: [],
                quoteProductsStorage: [],
            };

            _saveCartData(_cartData);

            return _getCartData();
        } else {
            return _cartData;
        }
    };
    const _saveCartData = function (data) {
        return customerData.set(cacheKey, data)
    };


    const cartDataModel = {
        multipleClickTime: 0,

        cartData: {
            pageType: null,
            pageId: null,
            subtotal: null,
            totalQty: null,
            products: null,
            quoteProducts: null,
            quoteProductsStorage: null
        },

        currentPageType: null,
        currentPageId: null,
        realCartPageType: null,
        realCartPageId: null,
        debounceChange: null,
        isReady: false,
        isSubscribedQuoteItems: false,   // prevent update loop when subscribe cart data
        isHanpukai: false,
        isUpdatingFromCustomerData: false,
        allowCheckQtyChange: ko.observable(false), // only check qty is same sub-page when quote was merged
        hasQuoteItems: false,
        isUpdatingInSpot: false,

        getCartSubtotal: function () {
            if (!this.cartData.subtotal) {
                this.cartData.subtotal = ko.observable(_getCartData()['subtotal']);
            }
            return this.cartData.subtotal;
        },
        getCartTotalQty: function () {
            if (!this.cartData.totalQty) {
                this.cartData.totalQty = ko.observable(_getCartData()['totalQty']);
            }
            return this.cartData.totalQty;
        },

        getCartProducts: function () {
            if (!this.cartData.products) {
                this.cartData.products = [];
            }
            return this.cartData.products;
        },

        getProductsInCart: function () {
            if (!this.cartData.quoteProducts) {
                this.cartData.quoteProducts = ko.observableArray(_getCartData()['quoteProducts']);
            }
            return this.cartData.quoteProducts;
        },

        getStorageProducts: function () {
            if (!this.cartData.quoteProductsStorage) {
                this.cartData.quoteProductsStorage = _getCartData()['quoteProductsStorage'];
            }
            return this.cartData.quoteProductsStorage;
        },

        setCurrentPageType: function (pageType) {
            this.currentPageType = pageType;

            return this;
        },

        setCurrentPageId: function (pageId) {
            this.currentPageId = pageId;

            return this;
        },

        getCartPageType: function () {
            if (this.cartData.pageType === null) {
                this.cartData.pageType = _getCartData()['pageType'];
            }
            return this.cartData.pageType;
        },

        getCartPageId: function () {
            if (this.cartData.pageId === null) {
                this.cartData.pageId = _getCartData()['pageId'];
            }
            return this.cartData.pageId;
        },

        isAllowMergeCartLocal: function () {
            if (cartDataModel.currentPageType === "subscription") {
                if (cartDataModel.currentPageType !== cartDataModel.getCartPageType() || cartDataModel.currentPageId !== cartDataModel.getCartPageId()) {
                    return false;
                }
            }
            return true;
        },

        isAllowMergeRealcart: function () {
            if (cartDataModel.currentPageType === "subscription") {
                if (cartDataModel.currentPageType != cartDataModel.realCartPageType || cartDataModel.currentPageId !== cartDataModel.realCartPageId) {
                    return false;
                }
            }
            return cartDataModel.isSameSubPageWithRealCart();
        },

        /**
         * Merge old quote from local storage to current page
         */
        mergeQuote: function (isSpotPage, isHanpukai, pageType, pageId) {
            var self = this;

            if (pageType !== undefined) {
                this.currentPageType = pageType;
            }
            if (pageId !== undefined) {
                this.currentPageId = pageId;
            }
            if (isSpotPage === undefined) {
                isSpotPage = false;
            }
            if (isHanpukai === undefined) {
                isHanpukai = false;

            }
            if (isHanpukai) {
                cartDataModel.cartData.products = null;
            }
            if (typeof customerData.get("cart") === "function" && !cartDataModel.isSubscribedQuoteItems) {
                const realCartData = customerData.get("cart")();

                const _debounceUpdateQuoteQtyFn = _.debounce(function (newQty, item, whenSuccess, whenFail) {
                    //console.log("__update_quote_item_server__qty: " + newQty);
                    syncCartData._updateItemQty([
                        $("#minicart_qty_select_" + item['id'] + "_" + item['catId']),
                        $("#minicart_qty_delete_" + item['id'] + "_" + item['catId']),
                        $("#minicart_qty_minus_" + item['id'] + "_" + item['catId']),
                        $("#minicart_qty_plus_" + item['id'] + "_" + item['catId']),
                        $("#minicart_qty_remove_" + item['id'] + "_" + item['catId']),
                    ], {
                        item: item,
                        item_id: item['item_id'],
                        item_qty: newQty
                    }, whenSuccess, whenFail);
                    if(window.isSpotPage) {
                        cartDataModel.isUpdatingInSpot = false;
                    }
                }, 500);

                const minicartSubscribeChange = _.debounce(function (realCartData) {
                    if (realCartData.hasOwnProperty("rikiCourseId") && !!realCartData["rikiCourseId"]) {
                        cartDataModel.realCartPageType = "subscription";
                        cartDataModel.realCartPageId = realCartData["rikiCourseId"];
                    } else {
                        cartDataModel.realCartPageType = null;
                        cartDataModel.realCartPageId = null;
                    }
                    cartDataModel.hasQuoteItems = false;
                    cartDataModel.isUpdatingFromCustomerData = true;
                    const _currentTime = +new Date();
                    const quoteItems = realCartData.hasOwnProperty("m-minicart-data") ? realCartData["m-minicart-data"] : [];
                    cartDataModel.isSubscribedQuoteItems = true;

                    if (!_.isEmpty(quoteItems)) {
                        cartDataModel.hasQuoteItems = true;
                        _.each(quoteItems, function (item) {
                            if (item['free_item'] == true && item['is_riki_machine'] != "1") {
                                return;
                            }

                            // Check if item is in current subscription course
                            let product = _.find(cartDataModel.cartData.products, function (p) {
                                return p['item_id'] == item['item_id'] ||
                                    (!p.hasOwnProperty('is_multiple_campaign')
                                        && (p['id'] == item['id'] || p['id'] == item['entity_id'])
                                        && (p['type'] == "main" && item['is_addition'] != 1)
                                        && ((p['type'] == "machine" && item['is_riki_machine'] == 1) || (p['type'] == "main" && item['is_riki_machine'] == 0))
                                        && cartDataModel.isAllowMergeRealcart());

                            });

                            if (product) {
                                //console.log("__merge_realcart_product_in__");
                                product['quoteItemUpdatedTime'] = _currentTime;
                                if (!product['unitQty']) {
                                    product['unitQty'] = item['unit_qty'];
                                }
                                var _qty = product['qtySelected']();
                                if (!isNaN(item['currentQty'])) {
                                    _qty = parseInt(item['currentQty']);
                                    if (item.hasOwnProperty("case_display") && item["case_display"] === 'cs') {
                                        _qty = parseInt(parseInt(item['currentQty']) / parseInt(item['unit_qty']) + "");
                                    }
                                }
                                if (_qty !== product['qtySelected']()) {
                                    product['qtySelected'](_qty);
                                }
                                product['isInRealCart'] = true;
                                product['item_id'] = item['item_id'];
                                product['giftWrappingSelected'] = item['gw_id'];
                                // set gift_wrapping form quote into input hidden
                                var gw_id = (item['gw_id']) ? item['gw_id'] : -1;
                                $("#gift_wrapping_" + product['id'] + "_" + product['catId']).val(gw_id);
                                product['free_item'] = item['free_item'];
                                if (item['is_riki_machine'] == "1") {
                                    product['free_item'] = false;
                                    product.qty(1);
                                }
                            } else {
                                //console.log("__merge_realcart_product_out__");
                                product = item;
                                if (!product['unitQty']) {
                                    product['unitQty'] = item['unit_qty'];
                                }
                                if (!product.hasOwnProperty("id") && !product.hasOwnProperty("entity_id")) {
                                    return;
                                }
                                product['id'] = !!product['id'] ? product['id'] : product['entity_id'];
                                product['isInRealCart'] = true;
                                product['catId'] = 0;
                                product['quoteItemUpdatedTime'] = _currentTime;
                                product['disabled'] = 0;

                                if (product['is_riki_machine'] == 1) {
                                    product['type'] = 'machine';
                                } else if (product['free_product'] == 1) {
                                    product['type'] = 'free_product';
                                } else {
                                    product['type'] = 'main';
                                }

                                product['qtySelected'] = cartDataModel.numberObservable(0, product['maxQty']);
                                product['qty'] = cartDataModel.numberObservable(0);
                                product['qtyCase'] = cartDataModel.numberObservable(0);

                                product.qtySelected.subscribe(function (v) {
                                    var qty = v;
                                    if (product.hasOwnProperty("case_display") && product["case_display"] === 'cs') {
                                        qty = qty * product['unit_qty'];
                                        product.qtyCase(qty);
                                    }
                                    product.qty(qty);
                                });

                                product["finalPriceNumber"] = ko.observable(product['price_incl_tax'] * (product["case_display"] === 'cs' ? product['unit_qty'] : 1));
                                product['subtotalNumber'] = ko.pureComputed(function () {
                                    return product['finalPriceNumber']() * product.qtySelected();
                                });
                                product['subtotal'] = ko.pureComputed(function () {
                                    return priceUtils.getFormattedPrice(product['subtotalNumber']());
                                });

                                product['finalPrice'] = ko.pureComputed(function () {
                                    return priceUtils.getFormattedPrice(product['finalPriceNumber']());
                                });

                                cartDataModel.getCartProducts().push(product);
                                if (product.hasOwnProperty("case_display") && product["case_display"] === 'cs') {
                                    product['qtySelected'](parseFloat(product['currentQty']) / parseFloat(product['unit_qty']));
                                } else {
                                    product['qtySelected'](product['currentQty']);
                                }

                                product['qty'].subscribe(function () {
                                    cartDataModel.whenChangeQty(isHanpukai);
                                });

                                product['giftWrappingSelected'] = product['gw_id'];
                                product['free_product'] = product['free_product'];
                                // reduce send ajax when initializing quote product
                                product['isReady'] = true;
                            }

                            if (product['isSubscribedChangeQty'] !== true) {
                                //console.log("__init_subscribe_change_update_sv__");
                                product['isSubscribedChangeQty'] = true;
                                product['qty'].subscribe(function (newQty) {
                                    if (cartDataModel.isUpdatingFromCustomerData === false && product['isInRealCart'] === true) {
                                        //console.log("__qty_change_update_server__");
                                        if(window.isSpotPage) {
                                            cartDataModel.isUpdatingInSpot = true;
                                        }
                                        _debounceUpdateQuoteQtyFn(newQty, product, function () {
                                        }, function () {
                                            // customerData.reload(['messages'], true);
                                            // window.alert($t("Something went wrong, please reload page."));
                                        });
                                    }
                                });
                            }
                        });
                    }

                    _.each(cartDataModel.cartData.products, function (item) {
                        if (item['isInRealCart'] === true && item['quoteItemUpdatedTime'] < _currentTime) {
                            item.qtySelected(0);
                            item['isInRealCart'] = false;
                        }
                    });

                    setTimeout(function () {
                        cartDataModel.isUpdatingFromCustomerData = false;
                    }, 1000);

                    // update real cart data to minicart
                    cartDataModel.whenChangeQty(isHanpukai, true);
                    cartDataModel.allowCheckQtyChange(true);
                }, 100);

                minicartSubscribeChange(realCartData);
                customerData.get("cart").subscribe(minicartSubscribeChange);

                // merge
                if (!isHanpukai) {
                    if (this.isAllowMergeCartLocal()) {
                        var productConfig = customerData.get('multiple-category-campaign')()['selected_products'];
                        self.mergeItems(productConfig);
                        customerData.get('multiple-category-campaign').subscribe(function (v) {
                            productConfig = v['selected_products'];
                            self.mergeItems(productConfig);
                        });
                    }
                }
            }


            return this;
        },

        mergeItems: function (productConfig) {
            var self = this;
            _.each(_.extend(cartDataModel.getStorageProducts(), self.parseSelectedProducts(productConfig)), function (lcP) {
                const p = _.find(cartDataModel.cartData.products, function (_p) {
                    return _p["id"] == lcP["id"] && _p["catId"] == lcP["catId"] && !lcP['isInRealCart'];
                });
                if (p && lcP.hasOwnProperty("qtySelected")) {
                    //console.log("__merge_storage_item__");
                    p.qtySelected(lcP.qtySelected);
                    p.giftWrappingSelected = lcP.giftWrappingSelected;
                    p.giftWrappingSelectedPrice = lcP.giftWrappingSelectedPrice;
                }
            });
        },
        isCartReady: function (isReady) {
            // IE11 compatible
            if (isReady === undefined) {
                isReady = true;
            }
            this.isReady = isReady;

            return this;
        },

        isSameSubPageWithRealCart: function () {
            if (this.realCartPageType === "subscription") {
                if (this.currentPageId !== this.realCartPageId) {
                    return false;
                }
            }

            return true;
        },

        validateChangeQtySubpage: function () {
            if (cartDataModel.allowCheckQtyChange() && (!cartDataModel.isSameSubPageWithRealCart() || (cartDataModel.hasQuoteItems && cartDataModel.currentPageType === "subscription" && cartDataModel.currentPageId != cartDataModel.realCartPageId))) {
                const msg = $t("Only one subscription allowed in the shopping cart");
                syncCartData.showErrorMessage(msg);
                uiRegistry.set('mbAddToCartTmp', false);
                return false;
            }

            return true;
        },

        whenChangeQty: function (isHanpukai, forceRunImmediately) {
            // IE11 compatible
            if (isHanpukai === undefined) {
                this.isHanpukai = isHanpukai;
            }
            if (!this.isReady) {
                return;
            }

            //console.log("__when_qty_change__");
            if (forceRunImmediately === true) {
                this._calculateQtyChange();
            } else {
                this._debounceChangeQty(this.isHanpukai);
            }

            return this;
        },

        _calculateQtyChange: function () {
            //console.log("__reCalculate__");
            let items = [];
            _.each(cartDataModel.cartData.products, function (p) {
                if (p.qtySelected() > 0 && !p.hasOwnProperty('is_multiple_campaign')) {
                    items.push(p);
                }
            });
            let cartData = _getCartData();
            cartData['quoteProductsStorage'] = ko.toJS(items);
            cartData.pageId = this.currentPageId;
            cartData.pageType = this.currentPageType;
            _saveCartData(cartData);

            cartDataModel.getProductsInCart()(items);

            cartDataModel.calculateTotalQty();
            cartDataModel.calculateSubtotal(cartDataModel.isHanpukai);
        },

        _debounceChangeQty: function (isHanpukai) {
            // IE11 compatible
            if (isHanpukai === undefined) {
                isHanpukai = false;
            }
            cartDataModel.isHanpukai = isHanpukai;
            if (this.debounceChange == null) {
                this.debounceChange = _.debounce(function () {
                    cartDataModel._calculateQtyChange();
                }, 130);
            }
            return this.debounceChange();

        },

        calculateTotalQty: function () {
            let total = 0;

            _.each(this.cartData.products, function (product) {
                if (!product.hasOwnProperty('type') || product["free_item"] == true
                    || !!product.hasOwnProperty('is_multiple_campaign')) {
                    return;
                }
                total += parseInt(product["qtySelected"]());
            });

            this.getCartTotalQty()(total);
            return total;
        },

        calculateSubtotal: function (isHanpukai) {
            // IE11 compatible
            if (isHanpukai === undefined) {
                isHanpukai = false;
            }
            let total = 0;

            if (isHanpukai === false) {
                _.each(this.getCartProducts(), function (product) {
                    if (!!product.hasOwnProperty('type') && parseFloat(product['qty']()) > 0
                        && !product.hasOwnProperty('is_multiple_campaign')) {
                        total += parseFloat(product["subtotalNumber"]());
                    }
                });
            }
            this.getCartSubtotal()(total);
            return total;
        },

        parseSelectedProducts: function (productConfig) {
            var id, quantity, selectedItems = [], catId;
            var campaign_id = $('input[name="campaign_id"]').val();
            if (campaign_id !== undefined && campaign_id.length > 0) {
                if (productConfig !== undefined && (productConfig.length > 0 || Object.keys(productConfig).length > 0)) {
                    Object.keys(productConfig).forEach(function (key) {
                        id = key.split("_")[0];
                        catId = key.split("_")[1];
                        quantity = productConfig[key].split(":")[0];
                        selectedItems.push({
                            id: id, catId: catId, currentQty: quantity, type: 'main',
                            is_riki_machine: '0', free_item: false, entity_id: id,
                            item_id: id, gw_id: null, is_multiple_campaign: true,
                            qtySelected: quantity
                        });
                    });
                    var actionElem = $('.action.to-subscription');
                    actionElem.removeClass('disabled');
                    actionElem.prop('disabled', false);
                }
            }
            return selectedItems;
        },

        resetCart: function () {
            _getCartData(true);
        },

        numberObservable: function (initialValue, maxValue, minValue, validateFunction) {
            // IE11 compatible
            if (maxValue === undefined) {
                maxValue = false;
            }
            if (minValue === undefined) {
                minValue = 0;
            }
            const _actual = ko.observable(initialValue);

            return ko.dependentObservable({
                read: function () {
                    return _actual();
                },
                write: function (newValue) {
                    if (typeof validateFunction === "function") {
                        if (!validateFunction.call(this, newValue)) {
                            return;
                        }
                    }
                    if (isNaN(newValue) || newValue < minValue || parseFloat(newValue) === _actual()) {
                        return;
                    }
                    _actual((!!maxValue && parseFloat(newValue) >= maxValue) ? maxValue : parseFloat(newValue));
                }
            });
        }
    };

    return cartDataModel;
});