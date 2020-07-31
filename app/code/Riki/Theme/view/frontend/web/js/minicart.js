define([
    'jquery',
    'ko',
    'underscore',
    'mage/url',
    'uiComponent',
    'mage/translate',
    'Riki_Theme/js/cart-data-model',
    'Riki_SubscriptionPage/js/view/qty',
    'Riki_Subscription/js/model/utils',
    'uiRegistry',
    'Magento_Customer/js/customer-data',
    'Magento_Ui/js/model/messageList'
], function (
    $,
    ko,
    _,
    urlBuilder,
    Component,
    $t,
    cartDataModel,
    qty,
    priceUtils,
    uiRegistry,
    customerData,
    messageList
) {
    var btCheckoutText = ko.observable($t('Proceed to order process'));
    'use strict';
    return Component.extend({
        subtotalNumber: ko.observable(priceUtils.getFormattedPrice(cartDataModel.getCartSubtotal()())),
        productsInCart: cartDataModel.getProductsInCart(),

        setBtCheckoutText: function (text) {
            btCheckoutText(text);
            return this;
        },

        getBtCheckoutText: function () {
            return btCheckoutText();
        },

        getCartTotalQty: function () {
            return cartDataModel.getCartTotalQty();
        },

        initialize: function () {
            this._super();
            const self = this;
            cartDataModel.getCartSubtotal().subscribe(function (v) {
                self.subtotalNumber(priceUtils.getFormattedPrice(v));
            });
            cartDataModel.getProductsInCart().subscribe(function (products) {
                _.each(products, function (product) {
                    self.generateSelectOption(product);
                });

                if (_.isArray(products) && products.length === 0) {
                    self.hideCart();
                }

                self._changeStatusCheckoutButton(products);
            });

            $("body").on("show_error_message", function () {
                self.hideCart();
            });

        },

        qtyIncrement: function (v) {
            let newQty = v.qtySelected() + 1;
            if (newQty <= 0 || isNaN(newQty)) {
                newQty = 0;
            }
            v.qtySelected(newQty);
        },

        qtyDecrement: function (v) {
            let newQty = v.qtySelected() - 1;
            if (newQty <= 0 || isNaN(newQty)) {
                newQty = 0;
            }
            v.qtySelected(newQty);
        },

        removeProduct: function (v) {
            v.qtySelected(0);
        },

        displayCart: function () {
            const self = this;
            $('body').addClass('disable-scroll');
            if (cartDataModel.getProductsInCart()().length === 0) {
                return;
            }

            cartDataModel.allowCheckQtyChange(false);

            setTimeout(function () {
                _.each(cartDataModel.getProductsInCart()(), function (product) {
                    self.generateSelectOption(product);
                });

                _.each(cartDataModel.getCartProducts(), function (product) {
                    const _currentQty = product.qtySelected();
                    if(_currentQty > 0) {
                        product.qtySelected.notifySubscribers(_currentQty);
                    }
                });

                $('.minicart_content').addClass('active');
                $('.minicart-screen').addClass('active');

                setTimeout(function () {
                    cartDataModel.allowCheckQtyChange(true);
                }, 1200);
            }, 100);
        },

        hideCart: function () {
            const minicartContent = $('.minicart_content');
            const minicartScreen = $('.minicart-screen');
            minicartContent.removeClass('active');
            minicartScreen.removeClass('active');
            $('body').removeClass('disable-scroll');
        },

        generateSelectOption: function (product) {
            const minicartQtyElem = $("#minicart_qty_select_" + product['id'] + "_" + product['catId']);
            if (minicartQtyElem.length > 0) {
                qty.prototype.generateSelectOption(product, minicartQtyElem);
            }
        },

        submitCart: function () {
            this.hideCart();
            this.verifyQty();
            var campaign_id = $('input[name="campaign_id"]').val();
            var riki_course_id = $('input[name="riki_course_id"]').val();
            var riki_multiple_cat = $('input[name="riki_multiple_cat"]').val();
            var product_addtocart_detail_page = $('input[name="product_addtocart_detail_page"]').val();

            //case campaign page
            if (this.checkRealCart() && campaign_id !== undefined && campaign_id.length > 0) {
                window.location.assign(urlBuilder.build('checkout/#single_order_confirm'));
                return false;
            }

            //case spot detail page
            if (product_addtocart_detail_page !== undefined && product_addtocart_detail_page.length > 0) {
                window.location.assign(urlBuilder.build('checkout/#single_order_confirm'));
                return false;
            }

            // case only real cart
            if (this.checkRealCart() && (uiRegistry.get('mbAddToCartTmp') == undefined || uiRegistry.get('mbAddToCartTmp') === false)) {
                window.location.assign(urlBuilder.build('checkout/#single_order_confirm'));
                return false;
            }
            // case submit form
            $('#form-validate').submit();
        },
        clearCart: function () {
            this.hideCart();
            cartDataModel.resetCart();
            if (this.checkRealCart()) {
                $('#form-validate-delete-cart').submit();
            } else {
                window.location.reload(true);
                return false;
            }
        },

        _changeStatusCheckoutButton: function (productInCart) {
            // IE11 compatible
            if (productInCart === undefined) {
                productInCart = false;
            }
            let isEnable = true;
            if (productInCart === false) {
                productInCart = cartDataModel.getProductsInCart();
            }
            if (_.isArray(productInCart) && productInCart.length === 0) {
                isEnable = false;
            }
            this.checkButonCheckoutMultipleCat(isEnable);
            this.checkButonCheckoutSubscriptionPage(isEnable);

        },
        checkButonCheckoutMultipleCat: function (isEnable) {
            var riki_multiple_product = $("#riki_multiple_product").val();
            var cartData = customerData.get("cart")();
            var quoteItems = cartData.hasOwnProperty("m-minicart-data") ? cartData["m-minicart-data"] : [];
            if (!_.isEmpty(quoteItems) && riki_multiple_product != undefined && parseInt(cartData.rikiCourseId) > 0) {
                isEnable = false;
            }
            if (isEnable) {
                $(".riki-category-multiple-tocart").removeClass("disabled");
            } else {
                $(".riki-category-multiple-tocart").addClass("disabled");
            }
        },
        checkButonCheckoutSubscriptionPage: function (isEnable) {
            var riki_course_id = $("#riki_course_id").val();
            var cartData = customerData.get("cart")();
            var quoteItems = cartData.hasOwnProperty("m-minicart-data") ? cartData["m-minicart-data"] : [];
            if (!_.isEmpty(quoteItems) && riki_course_id != undefined && parseInt(cartData.rikiCourseId) != parseInt(riki_course_id)) {
                isEnable = false;
            }
            if (isEnable) {
                $(".riki-subscription-tocart").removeClass("disabled");
            } else {
                $(".riki-subscription-tocart").addClass("disabled");
            }
        },

        deliveryTypeIs: function (pr, type) {
            const product = _.find(this.productsInCart(), function (p) {
                return pr.id == p.id;
            });
            return !!product && product.hasOwnProperty("delivery_type") && product.delivery_type === type;
        },

        hasGiftWrapping: function (pr) {
            const product = _.find(this.productsInCart(), function (p) {
                return (pr.id == p.id && pr.catId == p.catId);
            });
            return !!product && product.hasOwnProperty("gift_wrapping") && !!product.gift_wrapping;
        },

        getPriceFormatted: function (price) {
            return priceUtils.getFormattedPrice(price());
        },
        /**
         * @return Array
         * Convert list gift wrapping from string to
         * array object with details
         */
        getOptionGiftWrapping: function (gift_wrapping) {
            var list = [];
            var allWrappingItems = window.getDesignsInfo;
            var availableDesignIds = gift_wrapping.split(',');
            _.filter(allWrappingItems, function (item) {
                if (_.indexOf(availableDesignIds, item.id) != -1) {
                    var obj = {};
                    obj.id = item.id;
                    obj.label = item.label;
                    obj.price = item.price;
                    obj.priceAfterFormat = priceUtils.getFormattedPrice(item.price);
                    list.push(obj);
                }
            });
            return list;
        },
        /** Update GiftWrapping when select **/
        updateGiftWrapping: function (product_id, catId) {
            uiRegistry.set('mbAddToCartTmp', true);
            var gw_id = $('select[name="gift_wrapping_' + product_id + '_' + catId + '"]').val();
            var price = $('#gift_wrapping_' + product_id + '_' + catId + '_' + gw_id).data("price");
            let productList = [];
            _.each(cartDataModel.getProductsInCart()(), function (product) {
                if (product.id == product_id && product.catId == catId) {
                    product.giftWrappingSelected = gw_id;
                    product.giftWrappingSelectedPrice = price;
                }
                productList.push(product);
            });
            cartDataModel.whenChangeQty(false);
            if(window.isSpotPage){
                $("#gift_wrapping_" + product_id).val(gw_id);
                var params = {
                    "item_id": product_id,
                    "gw_id": gw_id,
                };
                $("body").trigger("processStart");
                $.ajax({
                    url: window.minicartCheckout.updateGiftWrapping,
                    method: 'POST',
                    data: params
                }).done(function (response) {
                    if(response.msg) {
                        messageList.addErrorMessage({'message': msg});
                        const dataPlaceHolderElem = $('[data-placeholder="messages"]');

                        dataPlaceHolderElem.html(msg);
                        if (msg !== "") {
                            $("body").trigger("show_error_message");
                            dataPlaceHolderElem.addClass("message error");
                        } else {
                            dataPlaceHolderElem.removeClass("message error");
                        }
                    }
                    $("body").trigger("processStop");
                });
            } else {
                $("#gift_wrapping_" + product_id + "_" + catId).val(gw_id);
            }
        },
        hasFreeGiftWrapping: function (gift_wrapping) {
            var allWrappingItems = window.getDesignsInfo;
            var availableDesignIds = gift_wrapping.split(',');
            var check = false;
            check = _.find(allWrappingItems, function (item) {
                if (_.indexOf(availableDesignIds, item.id) != -1 && parseInt(item.basePrice) === 0) {
                    return item.id;
                }
            });
            return check;
        },
        getPriceGiftWrapping: function (getProductsInCart) {
            var giftWrappingTotal = 0;
            _.each(getProductsInCart, function (product) {
                if (typeof product.giftWrappingSelected != undefined && product.giftWrappingSelected > 0 && typeof product.giftWrappingSelectedPrice != undefined) {
                    giftWrappingTotal = giftWrappingTotal + parseInt(product.giftWrappingSelectedPrice);
                }

            });
            return giftWrappingTotal;
        },
        checkRealCart: function () {
            var data = customerData.get("cart")();
            var quoteItems = data.hasOwnProperty("m-minicart-data") ? data["m-minicart-data"] : [];
            if (!_.isEmpty(quoteItems)) {
                return true;
            }
            return false;
        },
        checkRealCartHanpukai: function () {
            var cartData = customerData.get("cart")();
            var rikiHanpukaiQty = cartData.rikiHanpukaiQty;
            if (!_.isEmpty(rikiHanpukaiQty) && parseInt(rikiHanpukaiQty) > 0) {
                return true;
            }
            return false;
        },
        urlRemoveCart: function () {
            var updatePost = urlBuilder.build('checkout/cart/updatePost');
            return updatePost;
        },
        getRikiCourseName: function () {
            var cartData = customerData.get("cart")();
            var rikiCourseName = cartData.rikiCourseName;
            if (!_.isEmpty(rikiCourseName)) {
                return rikiCourseName;
            }
            return null;
        },
        getRikiHanpukaiQty: function () {
            var cartData = customerData.get("cart")();
            var rikiHanpukaiQty = cartData.rikiHanpukaiQty;
            if (!_.isEmpty(rikiHanpukaiQty) && parseInt(rikiHanpukaiQty) > 0) {
                return parseInt(rikiHanpukaiQty);
            }
            return 1;
        },
        getTotalQty: function () {
            if (this.checkRealCartHanpukai()) {
                var cartData = customerData.get("cart")();
                var rikiHanpukaiQty = cartData.rikiHanpukaiQty;
                if (!_.isEmpty(rikiHanpukaiQty) && parseInt(rikiHanpukaiQty) > 0) {
                    return parseInt(rikiHanpukaiQty);
                }
                return 1;
            } else {
                return  this.getCartTotalQty();
            }
        },
        /**
         * Get min sale qty
         * @param min_sale_qty
         * @returns {Number}
         */
        getMinSaleQty: function (min_sale_qty) {
            var qty = 0;
            if (typeof min_sale_qty == 'undefined' || min_sale_qty == '') {
                qty = 1;
            } else {
                qty = min_sale_qty;
            }
            return parseInt(qty);
        },

        /**
         * Get max sale qty
         * @param max_sale_qty
         * @returns {Number}
         */
        getMaxSaleQty: function (max_sale_qty) {
            var qty = 0;
            if (typeof max_sale_qty == 'undefined' || max_sale_qty == '') {
                qty = 99;
            } else {
                if (max_sale_qty > 99) {
                    qty = 99;
                } else {
                    qty = max_sale_qty;
                }
            }
            return parseInt(qty);
        },
        updateQtyHanpukai: function () {
            $('#update-hanpukai').submit();
        },
        getCurentUrl: function () {
            return window.location.href;
        },
        getUrlCheckoutCart: function () {
            var urlCheckoutCart = urlBuilder.build('checkout/cart/');
            if (this.checkRealCart()) {
                return urlCheckoutCart;
            }
            return '#';
        },
        getSubtotalHanpukaiPage: function () {
            if(!_.isEmpty(customerData.get('cart')()['m-minicart-data'])) {
                return customerData.get('cart')().subtotal;
            } else {
                return '<span class="price">0円</span>'
            }
        },
        getTotalQtyHanpukaiPage: function () {
            var cartData = customerData.get("cart")();
            if (this.checkRealCartHanpukai()) {
                var rikiHanpukaiQty = cartData.rikiHanpukaiQty;
                if (!_.isEmpty(rikiHanpukaiQty) && parseInt(rikiHanpukaiQty) > 0) {
                    return parseInt(rikiHanpukaiQty);
                }
                return 1;
            } else {
                var totalQty = 0;
                var minicart_data = customerData.get("cart")()['m-minicart-data'];
                _.each(minicart_data, function(item){
                    if(item.case_display === 'cs'){
                        totalQty += parseInt(item.currentQty) / parseInt(item.unit_qty);
                    } else{
                        totalQty += parseInt(item.currentQty);
                    }
                });
                return totalQty;
            }
        },
        getOrderType: function() {
            if(this.getRikiCourseName() === null){
                return $t("Order type SPOT");
            } else if (this.checkRealCartHanpukai()){
                return $t("Order type Hanpukai");
            } else{
                return $t("Order type Subscription");
            }
        },
        verifyQty: function(){
            if(window.isSpotPage) {
                if ($('#qty').val() == 0 && $('#qty_select').val() != 0) {
                    if ($('#case_display').val() == 'cs') {
                        $('#qty').val($('#qty_select').val() * $('#unit_qty').val());
                    } else {
                        $('#qty').val($('#qty_select').val());
                    }
                }
                $('#qty_cs_double_check').val($('#qty').val());
            }
        }
    });
});