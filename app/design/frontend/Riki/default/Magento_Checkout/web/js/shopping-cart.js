/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
/*jshint browser:true jquery:true*/
define([
    "jquery",
    "Magento_Checkout/js/checkout-data",
    "Magento_Ui/js/modal/alert",
    "Magento_Ui/js/modal/confirm",
    "jquery/ui",
    'mage/translate'
], function ($, checkoutData, alert, confirm) {
    "use strict";

    $.widget('mage.shoppingCart', {
        _create: function () {
            $(document).ready(function () {
                var form = $('form.form-cart');
                var that = this;
                form[0].reset();
                if (checkoutData.getFocusContent()) {
                    $('html,body').animate({
                        scrollTop: checkoutData.getFocusContent()
                    }, 0);
                    checkoutData.setFocusContent(false);
                }
                if (window.location.href != document.referrer) {
                    checkoutData.setBackSteps(1);
                }

                $('#empty_cart_button').on('click',function (e) {
                    e.preventDefault();
                    confirm({
                        title: $.mage.__('Attention'),
                        content: '<h4>' + $.mage.__('Are you sure you want to delete all products?') + '</h4>',
                        actions: {
                            confirm: function () {
                                $('#empty_cart_button').attr('name', 'update_cart_action_temp');
                                $('#update_cart_action_container')
                                    .attr('name', 'update_cart_action').attr('value', 'empty_cart');
                                form.submit();
                            },
                            cancel: function () {
                                return false;
                            }
                        }
                    });
                });
            });

            $(this.options.emptyCartButton).on('click', $.proxy(function () {
                $(this.options.emptyCartButton).attr('name', 'update_cart_action_temp');
                $(this.options.updateCartActionContainer)
                    .attr('name', 'update_cart_action').attr('value', 'empty_cart');
            }, this));

            $('a.enter-coupon').on('click', function (e) {
                e.preventDefault();
                $('html,body').animate({
                    scrollTop: $('#discount-coupon-form').offset().top - 100
                }, 700);
            });

            $('form.form-cart').on('submit', function (e) {
                countBackSteps();
            });

            $('.action.back').on('click', function (e) {
                e.preventDefault();
                window.history.go(-(checkoutData.getBackSteps()));
            });

            $(window).scroll(function () {
                var curPoint = $(window).scrollTop();
                var stopPoint = $('.check-offset').offset().top;
                var windowHeight = $(window).height();
                var additionHeight = $('.actions-toolbar-floating').outerHeight();
                if ((curPoint + windowHeight - additionHeight - 25) >= (stopPoint)) {
                    if (!$('body').hasClass('remove-sticky')) {
                        $('body').addClass('remove-sticky');
                    }
                } else {
                    $('body').removeClass('remove-sticky');
                }
            });

            var items = $.find("[data-role='cart-item-qty']");
            for (var i = 0; i < items.length; i++) {
                $(items[i]).on('keypress', $.proxy(function (event) {
                    var keyCode = (event.keyCode ? event.keyCode : event.which);
                    if (keyCode == 13) {
                        $(this.options.emptyCartButton).attr('name', 'update_cart_action_temp');
                        $(this.options.updateCartActionContainer)
                            .attr('name', 'update_cart_action').attr('value', 'update_qty');

                    }
                }, this));
            }
            $(this.options.continueShoppingButton).on('click', $.proxy(function () {
                location.href = this.options.continueShoppingUrl;
            }, this));


            //Riki google tag
            function RikiGoogleTag() {
            }

            RikiGoogleTag.prototype = {

                //get data google tag
                getDatagoogleTag: function () {
                    var cartStorage = localStorage.getItem('googleTagAddToCartStorage');
                    if (cartStorage != null) {
                        return JSON.parse(cartStorage);
                    }
                    return null;
                },

                //remove from cart single
                convertItemDataLayer: function (objectData) {
                    var dataRemove = {};
                    for (var index in objectData) {
                        if (objectData.hasOwnProperty(index)) {
                            if (index != 'sku' && index != 'qty') {
                                dataRemove[index] = objectData[index];
                            }
                        }
                    }
                    return dataRemove;
                },

                compareDataChangeSingle: function (elementCurrent) {
                    var parentItem = elementCurrent.closest('.parent-item-cart');

                    var productId = 0;
                    if (typeof (parentItem.attr('data-cart-product-id')) != "undefined") {
                        productId = parseInt(parentItem.attr('data-cart-product-id'));
                    }

                    var qtyOld = 0;
                    if (typeof (parentItem.find('.data-remove-qty')) != "undefined") {
                        qtyOld = parseInt(parentItem.find('.data-remove-qty').val());
                    }

                    var newQty = parseInt(elementCurrent.val());

                    var qtyRemove = 0;
                    if (qtyOld - newQty >= 0) {
                        qtyRemove = qtyOld - newQty;
                    }

                    var data = {
                        'productId': productId,
                        'qtyOld': qtyOld,
                        'newQty': newQty,
                        'qtyRemove': qtyRemove
                    };
                    return data;
                },

                pushDataLayer: function (productId, quantity) {
                    var dataProductRemove = this.getDatagoogleTag();
                    if (dataProductRemove && dataProductRemove.hasOwnProperty(productId)) {
                        var dataProductItemsRemove = this.convertItemDataLayer(dataProductRemove[productId]);
                        if (quantity >= 0) {
                            dataProductItemsRemove['quantity'] = quantity;
                        }
                        dataLayer.push({
                            'event': 'removeFromCart',
                            'currencyCode': dlCurrencyCode,
                            'ecommerce': {
                                'remove': {
                                    'actionField': {},
                                    'products': [
                                        dataProductItemsRemove
                                    ]
                                }
                            }
                        });
                    }
                },

                pushDataLayerMultiple: function (productId, quantity) {
                    var dataProductRemove = this.getDatagoogleTag();
                    if (dataProductRemove && dataProductRemove.hasOwnProperty(productId)) {
                        var dataProductItemsRemove = this.convertItemDataLayer(dataProductRemove[productId]);
                        dataProductItemsRemove['quantity'] = quantity;
                        dataLayer.push({
                            'event': 'removeFromCart',
                            'currencyCode': dlCurrencyCode,
                            'ecommerce': {
                                'remove': {
                                    'actionField': {},
                                    'products': [
                                        dataProductItemsRemove
                                    ]
                                }
                            }
                        });
                    }
                },

                dataLayerHanpukai: function (currentQty) {
                    var dataProductRemove = this.getDatagoogleTag();
                    var hanpukaiItemsRemove = [];
                    if (dataProductRemove) {
                        for (var index in dataProductRemove) {
                            if (currentQty > 0) {
                                dataProductRemove[index]['quantity'] = currentQty;
                            }
                            hanpukaiItemsRemove.push(dataProductRemove[index]);
                        }
                        dataLayer.push({
                            'event': 'removeFromCart',
                            'currencyCode': dlCurrencyCode,
                            'ecommerce': {
                                'remove': {
                                    'actionField': {},
                                    'products': hanpukaiItemsRemove
                                }
                            }
                        });
                    }
                }
            }

            var rikiGoogleTag = new RikiGoogleTag();
            //change quantity hanpukai
            jQuery('#cart-hanpukai-change-all-qty-dt').on('change', function () {
                var item = jQuery(this);
                var dataCompare = rikiGoogleTag.compareDataChangeSingle(item);
                if (dataCompare.newQty <= dataCompare.qtyOld) {
                    rikiGoogleTag.dataLayerHanpukai(dataCompare.qtyRemove);
                }
                return true;
            });

            //remove product cart quantity
            jQuery('.has-row-span-hanpukai button').on('click', function () {
                rikiGoogleTag.dataLayerHanpukai(0);
                return true;
            });

            //change quantity
            jQuery('select[data-role="cart-item-qty"]').on('change', function () {
                $('body').trigger('processStart');
                checkoutData.setFocusContent($(window).scrollTop());
                var item = jQuery(this);
                var dataCompare = rikiGoogleTag.compareDataChangeSingle(item);
                if (dataCompare.newQty <= dataCompare.qtyOld) {
                    if (!isNaN(dataCompare.productId) && dataCompare.productId != null) {
                        rikiGoogleTag.pushDataLayer(dataCompare.productId, dataCompare.qtyRemove);
                    }
                }
                item.closest('.parent-item-cart').find('.data-remove-qty').val(item.value);
                item.closest('.parent-item-cart').find('.is-changed-qty').val(true);
                jQuery('form.form-cart').submit();
            });

            //remove product
            jQuery('.trackingDeleteProductCart .action-delete').on('click', function () {
                var item = jQuery(this);
                countBackSteps();
                var dataCompare = rikiGoogleTag.compareDataChangeSingle(item);
                if (!isNaN(dataCompare.productId) && dataCompare.productId != null) {
                    rikiGoogleTag.pushDataLayer(dataCompare.productId);
                }
                return true;
            });

            window.countBackSteps = function () {
                checkoutData.setBackSteps(checkoutData.getBackSteps() + 1);
            }

        }
    });
    return $.mage.shoppingCart;
});
