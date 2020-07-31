/*jshint browser:true jquery:true*/
/*global alert*/
define([
        "jquery",
        "ko",
        "uiComponent",
        "mage/url",
        "Riki_Theme/js/cart-data-model",
        "Riki_SubscriptionPage/js/view/qty",
        "Riki_SubscriptionPage/js/view/price",
        "Riki_SubscriptionPage/js/view/product-detail",
        "Magento_Customer/js/customer-data",
        "underscore"
    ], function (
    $,
    ko,
    Component,
    urlBuilder,
    cartDataModel,
    qty,
    price,
    productDetail,
    customerData,
    _
    ) {
        "use strict";
        return Component.extend(_.extend({}, {
            isFirstLoad: false,
            initialize: function () {
                this._super();

                this.initData();
                return this;
            },

            ajaxSaveSelectedProducts: function () {
                // this.subscription.dispose();
                const serviceUrl = urlBuilder.build('subscriptions/multiple_category/saveselectedproduct');
                const deffered = $.Deferred();
                const self = this;
                let data = {};

                _.each(cartDataModel.getCartProducts(), function (p) {
                    if (p.qty() > 0) {
                        data[self.createKey(p["id"], p["catId"])] = p.qtySelected() + ":" + p.qtyCase();
                    }
                });

                if (_.isEmpty(data)) {
                    setTimeout(function () {
                        deffered.reject();
                    });
                }

                $.ajax({
                    url: serviceUrl,
                    method: 'POST',
                    data: data,
                    global: false
                }).done(function () {
                    deffered.resolve();
                }).fail(function () {
                    deffered.reject();
                });

                return deffered.promise();
            },
            /**
             * create key common for element
             * @param productId
             * @param categoryId
             * @returns {string}
             */
            createKey: function (productId, categoryId) {
                var campaignId = window.multileCategoryCampaignConfig.campaign_id;
                return productId + "_" + categoryId + "_" + campaignId;
            },

            initData: function() {
                const formElement = $('#form-validate');

                formElement.find('.multiple-campaign-row-item').each(function () {
                    const divContainer = $(this),
                        viewModel = {};

                    viewModel['catId'] = divContainer.data('category-id');
                    viewModel['id'] = divContainer.data('product-id');
                    viewModel['imageUrl'] = divContainer.find('img.product-image-photo').attr('data-src');
                    viewModel['name'] = divContainer.find('img.product-image-photo').attr('alt');
                    viewModel['disabled'] = 0;
                    viewModel['type'] = 'main';
                    viewModel['giftWrappingSelected'] = -1;
                    viewModel['free_item'] = false;
                    viewModel['is_multiple_campaign'] = true;

                    qty.prototype.assignProductQtyData(viewModel, divContainer);
                    price.assignPriceData(viewModel, divContainer);

                    cartDataModel.getCartProducts().push(viewModel);
                });

                cartDataModel.getCartTotalQty().subscribe(function (v) {
                    if (v > 0) {
                        $('button.submit').removeAttr('disabled');
                    } else {
                        $('button.submit').attr('disabled', 'disabled');
                    }
                });

                cartDataModel.mergeQuote().isCartReady().whenChangeQty(false,true);
            },

        }, productDetail.prototype.extendObject()));
    }
);
