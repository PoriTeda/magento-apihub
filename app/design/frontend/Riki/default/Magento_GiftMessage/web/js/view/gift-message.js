/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
/*global define*/
define([
        'uiComponent',
        'Magento_GiftMessage/js/model/gift-message',
        'Magento_GiftMessage/js/model/gift-options',
        'Magento_GiftMessage/js/action/gift-options',
        'Magento_GiftWrapping/js/model/gift-wrapping',
        'uiRegistry'
    ],
    function (Component, giftMessage, giftOptions, giftOptionsService, giftWrapping, uiRegistry) {
        "use strict";
        return Component.extend({
            formBlockVisibility: null,
            resultBlockVisibility: null,
            model: {},
            modelGiftWrapping: {},
            initialize: function() {
                var self = this;
                this._super()
                    .observe('formBlockVisibility')
                    .observe({'resultBlockVisibility': false});

                this.itemId = this.itemId || 'orderLevel';
                var model = new giftMessage(this.itemId);
                this.modelGiftWrapping = new giftWrapping(this.itemId);
                giftOptions.addOption(model);
                this.model = model;
                self.formBlockVisibility(true);

                this.model.getObservable('isClear').subscribe(function(value) {
                    if (value == true) {
                        self.formBlockVisibility(false);
                        self.model.getObservable('alreadyAdded')(true);
                    }
                });

                this.isResultBlockVisible();
            },
            isResultBlockVisible: function() {
                var self = this;
                if (this.model.getObservable('alreadyAdded')()) {
                    this.resultBlockVisibility(true);
                }
                this.model.getObservable('additionalOptionsApplied').subscribe(function(value) {
                    if (value == true) {
                        self.resultBlockVisibility(true);
                    }
                });
            },
            getObservable: function(key) {
                return this.model.getObservable(key);
            },
            toggleFormBlockVisibility: function() {
                if (!this.model.getObservable('alreadyAdded')()) {
                    this.formBlockVisibility(!this.formBlockVisibility());
                }
            },
            editOptions: function() {
                this.resultBlockVisibility(false);
                this.formBlockVisibility(true);
            },
            deleteOptions: function() {
                giftOptionsService(this.model, true);
            },
            hideFormBlock: function() {
                this.formBlockVisibility(false);
                if (this.model.getObservable('alreadyAdded')()) {
                    this.resultBlockVisibility(true);
                }
            },
            getWrappingItems: function() {
                var allWrappingItems = this.modelGiftWrapping.getWrappingItems(),
                    availableDesignIds = '';
                uiRegistry.get(this.name + '.giftWrapping' , function (obj){
                    availableDesignIds = obj.availableDesignIds;
                });
                if(typeof availableDesignIds == 'string' && availableDesignIds != '')
                    availableDesignIds = availableDesignIds.split(',');

                var a = _.filter(allWrappingItems, function (item) {
                    return item.id;
                });
                return a;
            },
            hasActiveOptions: function() {
                if(this.getWrappingItems().length > 0) {
                    var regionData = this.getRegion('additionalOptions');
                    var options = regionData();
                    for (var i = 0; i < options.length; i++) {
                        if (options[i].isActive()) {
                            return true;
                        }
                    }
                }
                return false;
            },
            isActive: function() {
                switch (this.itemId) {
                    case 'orderLevel':
                        return this.model.getConfigValue('isOrderLevelGiftOptionsEnabled') == true && this.getWrappingItems().length > 0;
                    default:
                        return this.model.getConfigValue('isItemLevelGiftOptionsEnabled') == true && this.getWrappingItems().length > 0;
                }
            },
            submitOptions: function() {
                giftOptionsService(this.model);
            }
        });
    }
);
