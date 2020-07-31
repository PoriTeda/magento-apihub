/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

define([
    'jquery',
    'Magento_Customer/js/customer-data',
    'Magento_GoogleTagManager/js/google-analytics-universal',
    'Magento_GoogleTagManager/js/google-analytics-universal-cart',
    'underscore',
    'jquery/ui'
], function ($, customerData, GoogleAnalyticsUniversal, GoogleAnalyticsUniversalCart, _) {
    'use strict';

    $.widget('mage.gtmCart', {
        options: {
            dlCurrencyCode: window.dlCurrencyCode || '',
            dataLayer: window.dataLayer || [],
            staticImpressions: window.staticImpressions || [],
            staticPromotions: window.staticPromotions || [],
            updatedImpressions: window.updatedImpressions || [],
            updatedPromotions: window.updatedPromotions || [],
            cookieAddToCart: '',
            cookieRemoveFromCart: window.cookieRemoveFromCart || '',
            temporaryEventStorage: [],
            blockNames: [],
            events: {
                AJAX_ADD_TO_CART: 'ajax:addToCart',
                AJAX_REMOVE_FROM_CART: 'ajax:removeFromCart'
            },
            actions: {}
        },

        /**
         * @inheritdoc
         *
         * @private
         */
        _create: function () {
            this.googleAnalyticsUniversalCart = new GoogleAnalyticsUniversalCart({
                dlCurrencyCode: this.options.dlCurrencyCode,
                dataLayer: this.options.dataLayer,
                cookieAddToCart: this.options.cookieAddToCart,
                cookieRemoveFromCart: this.options.cookieRemoveFromCart
            });
            this.googleAnalyticsUniversal = new GoogleAnalyticsUniversal({
                blockNames: this.options.blockNames,
                dlCurrencyCode: this.options.dlCurrencyCode,
                dataLayer: this.options.dataLayer,
                staticImpressions: this.options.staticImpressions,
                staticPromotions: this.options.staticPromotions,
                updatedImpressions: this.options.updatedImpressions,
                updatedPromotions: this.options.updatedPromotions
            });
            this.cartItemsCache = [];
            this._initActions();
            this._setListeners();
            this._setCartDataListener();
            this.googleAnalyticsUniversal.updatePromotions();
            this.googleAnalyticsUniversal.updateImpressions();
            this.googleAnalyticsUniversalCart.parseAddToCartCookies();
            this.googleAnalyticsUniversalCart.parseRemoveFromCartCookies();
            this.googleAnalyticsUniversalCart.subscribeProductsUpdateInCart();
            this.googleAnalyticsUniversalCart.listenMinicartReload();
            this.options.dataLayer.push({
                'ecommerce': {
                    'impressions': 0,
                    'promoView': 0
                }
            });
        },

        /**
         * Initialize actions callback function.
         *
         * @private
         */
        _initActions: function () {
            var events = this.options.events;

            this.options.actions[events.AJAX_ADD_TO_CART] = function (product) {
                this.googleAnalyticsUniversal.addToCart(
                    product['product_sku'],
                    product['product_name'],
                    product['product_price_value'],
                    product.qty
                );
            }.bind(this);

            this.options.actions[events.AJAX_REMOVE_FROM_CART] = function (product) {
                this.googleAnalyticsUniversal.removeFromCart(
                    product['product_sku'],
                    product['product_name'],
                    product['product_price_value'],
                    product.qty
                );
            }.bind(this);
        },

        /**
         * Finds and returns product by sku.
         *
         * @param {String} productId - product id.
         * @return {Object} product data.
         */
        getProductById: function (productId) {
            /**
             * Product search criteria.
             *
             * @param {Object} item
             * @return {Boolean}
             */
            var searchCriteria = function (item) {
                    return item['product_id'] === productId;
                },
                productFromCache = _.find(this.cartItemsCache, searchCriteria),
                productFromCart = _.find(customerData.get('cart')().items, searchCriteria);

            if (!productFromCache) {
                return _.extend({}, productFromCart, {
                    qty: 1
                });
            }

            if (productFromCache && productFromCart) {
                return _.extend({}, productFromCache, {
                    qty: productFromCart.qty - productFromCache.qty
                });
            }

            return productFromCache || productFromCart;
        },

        /**
         * Sets event to temporary storage.
         * When the cart data was updated this event will be executed.
         *
         * @param {String} type - Event type.
         * @param {Array} productIds - list of product ids.
         *
         * @private
         */
        _setToTemporaryEventStorage: function (type, productIds) {
            this.options.temporaryEventStorage.push({
                type: type,
                productIds: productIds
            });
        },

        /**
         * Sets listener to the cart data.
         *
         * @private
         */
        _setCartDataListener: function () {
            customerData.get('cart').subscribe(function (data) {
                if (this.options.temporaryEventStorage.length) {
                    this._executeEvents();
                }

                this.cartItemsCache = data.items.slice();
            }.bind(this));
        },

        /**
         * Sets listener to the cart.
         *
         * @private
         */
        _executeEvents: function () {
            var product;

            this.options.temporaryEventStorage.forEach(function (item, index) {
                item.productIds.forEach(function (productId) {
                    product = this.getProductById(productId);

                    if (!_.isUndefined(product['product_sku'])) {
                        this.options.actions[item.type](product);
                    }

                    this.options.temporaryEventStorage.splice(index, 1);
                }.bind(this));
            }.bind(this));
        },

        /**
         * Sets listener to cart events.
         *
         * @private
         */
        _setListeners: function () {
            /**
             * Wrapper function for handler.
             *
             * @param {Function} callback
             * @param {String} type - action type
             * @param {Object} event - jQuery event
             * @param {Object} eventData - event data
             * @param {String} eventData.productId - product id
             */
            var handlerWrapper = function (callback, type, event, eventData) {
                    callback.call(this, type, eventData.productIds);
                },
                opt = this.options;

            $(document)
                .on(
                    opt.events.AJAX_ADD_TO_CART,
                    handlerWrapper.bind(this, this._setToTemporaryEventStorage, opt.events.AJAX_ADD_TO_CART)
                )
                .on(
                    opt.events.AJAX_REMOVE_FROM_CART,
                    handlerWrapper.bind(this, this._setToTemporaryEventStorage, opt.events.AJAX_REMOVE_FROM_CART)
                );
        }
    });

    return $.mage.gtmCart;
});
