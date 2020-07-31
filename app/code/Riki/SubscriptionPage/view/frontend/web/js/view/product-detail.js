define([
    'underscore',
    'jquery',
    'mage/url',
    'ko',
    'uiClass',
    'Magento_Ui/js/modal/modal',
    'Riki_Theme/js/cart-data-model',
    'mage/translate',
    'Riki_Subscription/js/model/utils',
    'mage/gallery/gallery',
    'uiRegistry',
    "domReady!",
], function (
    _,
    $,
    urlBuilder,
    ko,
    Component,
    modal,
    cartDataModel,
    $t,
    priceUtils,
    Gallery,
    uiRegistry
) {
    const productPopupData = [];
    var popupDetailModal;
    var isHanpukai;
    return Component.extend({
        data: {
            productData: null
        },
        productId: ko.observable(null),
        productDetailData: {
            url: ko.observable("#"),
            name: ko.observable(""),
            saleAbleText: ko.observable($t("Stock: Your order is being accepted.")),
            price: ko.observable(""),
            tierPrice: ko.observable(""),
            descriptions: {
                desc_explanation: ko.observable(""),
                desc_ingredient: ko.observable(""),
                desc_allergen_mandatory: ko.observable(""),
                desc_explanation_recom: ko.observable(""),
                desc_content: ko.observable(""),
                desc_nutrition: ko.observable(""),
                desc_supplemental_info: ko.observable(""),
            }
        },
        isAssigned: false,

        initialize: function (config) {
            isHanpukai = config['isHanpukai'];
            delete config['isHanpukai'];
            productPopupData.push(
                config
            );
            this.assignProductDetailDataTo(config);
        },

        assignProductDetailDataTo: function (data) {
            const self = this;
            const productData = _.first(_.values(data));
            if (productData.hasOwnProperty("id")) {
                cartDataModel.getProductsInCart().subscribe(function () {
                    // improve performance
                    if(self.isAssigned){
                        return;
                    }
                    _.map(cartDataModel.getCartProducts(), function (p) {
                        if (p['id'] == productData['id'] && p['type'] != "machine") {
                            p['delivery_type'] = productData['delivery_type'];
                            p['gift_wrapping'] = productData['gift_wrapping'];

                            self.isAssigned = true;
                        }
                    });
                });
            }
        },

        openDetailPopup: function (productId) {
            const self = this;
            if (!popupDetailModal) {
                const options = {
                    type: 'popup',
                    responsive: true,
                    innerScroll: true,
                    modalClass: 'see-detail-modal',
                    buttons: [
                        {
                            text: $.mage.__('Add to Cart'),
                            class: 'btn_popup_add_to_cart hidden',
                            click: function () {
                                this.closeModal();
                            }
                        }]
                };

                popupDetailModal = modal(options, $('#popup-mpdal'));
            }

            this.productId(productId);
            var productData = _.find(productPopupData, function (p) {
                return p.hasOwnProperty(productId);
            });
            if (productData) {
                productData = productData[productId];

            } else {
                throw new Error("can_get_data_product_detail");
            }

            const productCartData = _.find(cartDataModel.getCartProducts(), function (p) {
                return p['id'] == productId;
            });

            // tier price
            var tierPriceHtml = null;

            // get tier price for product subscription page
            var tierPriceNode = $(".subscription-row-item[data-product-id='"+ productId +"']").find('.prices-tier');

            // get tier price for subscription multiple category
            if (tierPriceNode.length == 0)
            {
                tierPriceNode = $(".multiple-campaign-row-item[data-product-id='"+ productId +"']").find('.prices-tier');
            }

            // get tier price for catalog multiple view
            if (tierPriceNode.length == 0)
            {
                tierPriceNode = $(".multiple-category-row-item[data-product-id='"+ productId +"']").find('.prices-tier');
            }

            if(tierPriceNode.length && tierPriceNode.is(":visible")) {
                tierPriceNode.find('*').each(function () {
                    $(this).addClass('popup-mpdal-tierprice');
                });
                tierPriceHtml = tierPriceNode.html();
            }


            this.productDetailData.name(productData['name']);
            this.productDetailData.url(productData['thumbnail_image_url']);
            this.productDetailData.price(priceUtils.getFormattedPrice(productCartData.finalPriceNumber()));
            this.productDetailData.tierPrice(tierPriceHtml);

            _.each(this.productDetailData.descriptions, function (v, k) {
                if (!productData["descriptions"][k]) {
                    self.productDetailData.descriptions[k](null);
                } else {
                    self.productDetailData.descriptions[k](productData["descriptions"][k]);
                }
            });

            const saleText = !!productData['stockText'] ? productData['stockText'] : "";
            this.productDetailData.saleAbleText(saleText);

            // get image gallery
            var params = {
                "product_id": productId
            };
            var resp = null;
            $.ajax({
                url: urlBuilder.build('subscription-page/ajax/productGallery'),
                method: 'POST',
                data: params,
                showLoader: true
            }).done(function (response) {
                if(response){
                    resp = response;
                    popupDetailModal.openModal();

                    Gallery({
                        data: JSON.parse(resp.data),
                        options: resp.options,
                        breakpoints: JSON.parse(resp.breakpoints),
                        fullscreen: resp.fullscreen
                    }, $("#detail-gallery"));
                }
            });
        },

        popupAddToCart: function () {
            const self = this;
            const product = _.find(cartDataModel.getCartProducts(), function (p) {
                return p['id'] == self.productId || p['id'] == self.productId();
            });
            if (product) {
                var campaign_id = $('input[name="campaign_id"]').val();
                uiRegistry.set('mbAddToCartTmp', true);
                if (campaign_id !== undefined && campaign_id.length > 0) {
                    $('.action.to-subscription').removeClass('disabled');
                    $('.action.to-subscription').prop('disabled', false);
                }
                product.qtySelected(product.qtySelected() + 1);
            }
            popupDetailModal.closeModal();
        },

        extendObject: function () {
            const self = this;
            return {
                openDetailPopup: self.openDetailPopup,
                popupAddToCart: self.popupAddToCart,
                productDetailData: self.productDetailData,
                productId: self.productId
            }
        }
    });
});