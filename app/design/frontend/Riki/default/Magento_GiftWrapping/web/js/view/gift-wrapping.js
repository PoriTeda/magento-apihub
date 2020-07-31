/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
/*global define*/
define([
        'jquery',
        'uiComponent',
        'underscore',
        'Magento_GiftWrapping/js/model/gift-wrapping-collection',
        'Magento_GiftWrapping/js/model/gift-wrapping',
        'Magento_Catalog/js/price-utils',
        'Magento_GiftMessage/js/model/gift-options',
        'Magento_Checkout/js/model/full-screen-loader',
        'Magento_Ui/js/model/messageList',
        'mage/url'
    ],
    function ($, Component, _, giftWrappingCollection, giftWrapping, priceUtils, giftOptions, fullScreenLoader, messageList, urlBuilder) {
        "use strict";

        return Component.extend({
            isVisibleInfoBlock: null,
            model: {},
            displayArea: 'additionalOptions',
            levelIdentifier: '',
            defaults: {
                availableDesignIds: []
            },
            initialize: function() {
                this._super()
                    .observe('isVisibleInfoBlock');
                this.levelIdentifier = this.itemId || 'orderLevel';

                this.model = new giftWrapping(this.levelIdentifier);
                giftWrappingCollection.addOption(this.model);

                giftOptions.options.subscribe(
                    function (changes) {
                        _.each(changes, function (change) {
                            if (change.status === 'added') {
                                this.extendGiftMessageModel();
                            }
                        }, this);
                    }, this, 'arrayChange');
                this.extendGiftMessageModel();
                this.applyWrapping();
            },
            applyWrapping: function() {
                var wrappingId = this.getAppliedWrappingId();
                var messageModel = giftWrappingCollection.getOptionByItemId(this.levelIdentifier);
                if (wrappingId) {
                    this.setActiveItem(wrappingId);
                }

                if (messageModel && (this.isExtraOptionsApplied() || wrappingId)) {
                    messageModel.getObservable('message-' + this.levelIdentifier, 'additionalOptionsApplied')(true);
                }
            },
            isExtraOptionsApplied: function () {
                return this.model.isExtraOptionsApplied();
            },
            getAppliedWrappingId: function() {
                return this.model.getAppliedWrappingId();
            },
            extendGiftMessageModel: function() {
                var giftMessage = giftOptions.getOptionByItemId(this.levelIdentifier);
                if (giftMessage) {
                    giftMessage.additionalOptions.push(this.model);
                }
            },
            getWrappingItems: function() {
                var allWrappingItems = this.model.getWrappingItems(),
                    availableDesignIds = this.availableDesignIds;
                if(typeof availableDesignIds == 'string' && availableDesignIds != '')
                    availableDesignIds = availableDesignIds.split(',');

                var a = _.filter(allWrappingItems, function (item) {
                    return _.indexOf(availableDesignIds, item.id) != -1;
                });
                return a;
            },
            isActive: function() {
                switch (this.levelIdentifier) {
                    case 'orderLevel':
                        return this.model.getConfigValue('allowForOrder')
                            && (this.getWrappingItems().length > 0
                                || this.model.getConfigValue('isAllowGiftReceipt')
                                || this.model.getConfigValue('isAllowPrintedCard')
                            );
                        break;
                    default:
                        return this.model.getConfigValue('allowForItems')
                            && this.getWrappingItems().length > 0;
                }
            },
            showAppliedBlock: function() {
                if (this.getAppliedWrappingId()) {
                    return true;
                }
                if (this.levelIdentifier == 'orderLevel' && this.isExtraOptionsApplied()) {
                    return true;
                }
            },
            getObservable: function(key) {
                return this.model.getObservable('wrapping-' + this.levelIdentifier, key);
            },
            isAllowGiftReceipt: function() {
                return this.levelIdentifier == 'orderLevel' && this.model.getConfigValue('isAllowGiftReceipt') == true;
            },
            isAllowPrintedCard: function() {
                return this.levelIdentifier == 'orderLevel' && this.model.getConfigValue('isAllowPrintedCard') == true;
            },
            isDisplayWrappingBothPrices: function() {
                return this.model.getConfigValue('displayWrappingBothPrices') == true;
            },

            /**
             * Get printed card price display settings from configuration.
             * @returns {Boolean}
             */
            isDisplayCardBothPrices: function () {
                return this.model.getConfigValue('displayCardBothPrices');
            },
            getPrintedCardPrice: function() {
                return priceUtils.formatPrice(this.model.getPrintedCardPrice(), this.model.getPriceFormat());
            },
            getPrintedCardPriceWithTax: function() {
                return priceUtils.formatPrice(
                    this.model.getPrintedCardPriceWithTax(),
                    this.model.getPriceFormat()
                );
            },
            getPrintedCardPriceWithoutTax: function() {
                return priceUtils.formatPrice(
                    this.model.getPrintedCardPriceWithoutTax(),
                    this.model.getPriceFormat()
                );
            },
            isHighlight: function(id) {
                return this.model.isHighlight(this.levelIdentifier, id);
            },
            setActiveItem: function(id) {
                this.model.setActiveItem(id);
                this.updateInfoBlock();
            },
            uncheckWrapping: function() {
                this.isVisibleInfoBlock(false);
                this.getObservable('activeWrappingLabel')(null);
                this.getObservable('activeWrappingImageSrc')(null);
                this.getObservable('activeWrappingPrice')(null);
                this.getObservable('activeWrappingPriceWithoutTax')(null);
                this.getObservable('activeWrappingPriceWithTax')(null);
                this.model.uncheckWrapping();
            },
            updateInfoBlock: function() {
                this.isVisibleInfoBlock(true);
                var wrappingInfo = this.model.getActiveWrappingInfo(this.levelIdentifier);
                if (wrappingInfo && wrappingInfo.id) {
                    this.getObservable('activeWrappingLabel')(wrappingInfo.label);
                    this.getObservable('activeWrappingImageSrc')(wrappingInfo.path);
                    this.getObservable('activeWrappingPrice')(
                        priceUtils.formatPrice(wrappingInfo.price, this.model.getPriceFormat())
                    );
                    this.getObservable('activeWrappingPriceWithoutTax')(
                        priceUtils.formatPrice(wrappingInfo.priceExclTax, this.model.getPriceFormat())
                    );
                    this.getObservable('activeWrappingPriceWithTax')(
                        priceUtils.formatPrice(wrappingInfo.priceInclTax, this.model.getPriceFormat())
                    );
                }
            },
            formatPrice: function(price) {
                return priceUtils.formatPrice(
                    price,
                    this.model.getPriceFormat()
                );
            },
            updateGiftWrapping: function(item_id) {
                var gw_id = $('select[name="gift_wrapping_' + item_id +'"]').val();
                this._ajax(urlBuilder.build('multicheckout/update/wrapping'), {
                    cart_id: window.checkoutConfig.quoteData.entity_id,
                    item_id: item_id,
                    gw_id: gw_id
                });
            },
            generateRandomString: function (chars, length) {
                var result = '';
                length = length > 0 ? length : 1;

                while (length--) {
                    result += chars[Math.round(Math.random() * (chars.length - 1))];
                }

                return result;
            },
            _ajax: function(url, data) {
                var formKey = $.mage.cookies.get('form_key');
                if (!formKey) {
                    formKey = this.generateRandomString('0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ', 16);
                    $.mage.cookies.set('form_key', formKey);
                }
                $.extend(data, {
                    'form_key': formKey
                });
                $.ajax({
                    url: url,
                    data: data,
                    type: 'post',
                    global: false,
                    dataType: 'json',
                    context: this,
                    beforeSend: function() {
                        $('body').trigger('processStart');
                    }
                })
                    .done(function(response) {
                        if (response.success) {
                            window.location.reload(true);
                        } else {
                            var msg = response.error_message;
                            if (msg) {
                                messageList.addErrorMessage({'message': msg});
                                $('body').trigger('processStop');
                            }
                        }
                    })
                    .fail(function(error) {
                        console.log(JSON.stringify(error));
                    });
            }
        });
    }
);
