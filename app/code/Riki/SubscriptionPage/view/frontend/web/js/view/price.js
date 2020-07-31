define([
    'jquery',
    'ko',
    'Riki_Theme/js/cart-data-model',
    'underscore',
    'Riki_Subscription/js/model/utils'
], function ($, ko, cartDataModel, _, priceUtils) {
    return {
        assignPriceData: function (productViewModel, container, isHanpukai) {
            // IE11 compatible
            if(isHanpukai === undefined) {
                isHanpukai = false;
            }
            let finalPriceItem = container.find('[data-price-type="finalPrice"]').attr('data-price-amount');

            productViewModel["finalPriceNumber"] = ko.observable(0);
            productViewModel['subtotalNumber'] = ko.pureComputed(function () {
                return productViewModel['finalPriceNumber']() * productViewModel.qtySelected();
            });
            productViewModel['subtotal'] = ko.pureComputed(function () {
                return priceUtils.getFormattedPrice(productViewModel['subtotalNumber']());
            });

            productViewModel['finalPrice'] = ko.pureComputed(function () {
                return priceUtils.getFormattedPrice(productViewModel['finalPriceNumber']());
            });

            if (finalPriceItem) {
                productViewModel["finalPriceNumber"](finalPriceItem);
                const productId = productViewModel["id"];
                const tierPriceObjItem = window['tierPriceObj_' + productId];

                productViewModel["qty"].subscribe(function (qty) {
                    // console.log("_qty_change_calculate_tier_price_" + qty);
                    productViewModel["finalPriceNumber"](finalPriceItem);
                    if (typeof tierPriceObjItem !== 'undefined' && tierPriceObjItem.hasTierPrice) {
                        var tierPrice = null;
                        _.each(tierPriceObjItem.tierPriceItem, function (_tierPrice) {
                            if (qty >= _tierPrice['qty']) {
                                tierPrice = tierPrice === null || tierPrice.price > _tierPrice.price ? _tierPrice : tierPrice;
                            }
                        });

                        if (productViewModel.hasOwnProperty("caseDisplay") && productViewModel["caseDisplay"] === 'cs') {
                            if (tierPrice && tierPrice.hasOwnProperty("price") && parseFloat(finalPriceItem) > tierPrice.price * productViewModel['unitQty']) {
                                productViewModel["finalPriceNumber"](Math.floor(tierPrice.price) * productViewModel['unitQty']);
                            }
                        } else {
                            if (tierPrice && tierPrice.hasOwnProperty("price") && parseFloat(finalPriceItem) > tierPrice.price) {
                                productViewModel["finalPriceNumber"](tierPrice.price);
                            }
                        }

                    }
                    cartDataModel.whenChangeQty(isHanpukai);
                });

                productViewModel["finalPriceNumber"].subscribe(function () {
                    cartDataModel.whenChangeQty(isHanpukai);
                });
            } else {
                throw new Error("can_not_find_price");
            }
        }
    };
});