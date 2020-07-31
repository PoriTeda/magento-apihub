define([
    'jquery',
    'ko',
    'mage/url',
    'uiComponent',
    'Magento_Ui/js/modal/modal',
    'mage/translate',
    "Riki_Theme/js/cart-data-model",
    'Riki_SubscriptionPage/js/view/qty',
    "Riki_SubscriptionPage/js/view/price",
    'Riki_SubscriptionPage/js/view/toolbar',
    'Riki_SubscriptionPage/js/view/product-detail',
    "underscore",
    "uiRegistry",
    "domReady!"
], function (
    $,
    ko,
    urlBuilder,
    Component,
    modal,
    $t,
    cartDataModel,
    qty,
    price,
    toolBar,
    productDetail,
    _
) {
    'use strict';


    return Component.extend(_.extend({}, {
        isGridMode: ko.observable(true),

        initialize: function (config) {
            this._super();

            // change viewMode
            toolBar.prototype.bindingToolbar(this.isGridMode, $(".multiple-products-main"));

            // binding qty function
            this.initData();
            cartDataModel.mergeQuote( true)
                .isCartReady(true)
                .whenChangeQty(false, true);

            return this;
        },

        moveToTop: function(data, e){
            e.preventDefault();
            $('html,body').animate({
                scrollTop: 0
            }, 700);
        },

        initData: function() {
            const formElement = $('#form-validate');

            formElement.find('.multiple-category-row-item').each(function () {
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
        },

    }, productDetail.prototype.extendObject(), toolBar.prototype.extendObject()));
});