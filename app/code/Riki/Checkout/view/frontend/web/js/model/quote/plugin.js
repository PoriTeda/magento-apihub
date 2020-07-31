/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
define(
    ['ko'],
    function (ko) {
        'use strict';
        var billingAddress = ko.observable(null);
        var quoteItemDdateInfo = ko.observableArray([]);
        var shippingAddress = ko.observable(null);
        var shippingMethod = ko.observable(null);
        var paymentMethod = ko.observable(null);
        var question = ko.observable(null);
        var quoteData = window.checkoutConfig.quoteData;
        var basePriceFormat = window.checkoutConfig.basePriceFormat;
        var priceFormat = window.checkoutConfig.priceFormat;
        var storeCode = window.checkoutConfig.storeCode;
        var totalsData = window.checkoutConfig.totalsData;
        var totals = ko.observable(totalsData);
        var collectedTotals = ko.observable({});
        return {
            totals: totals,
            shippingAddress: shippingAddress,
            shippingMethod: shippingMethod,
            billingAddress: billingAddress,
            paymentMethod: paymentMethod,
            question: question,
            quoteItemDdateInfo : quoteItemDdateInfo,
            amrewards_point:  ko.observable(window.checkoutConfig.quoteData.amrewards_point) ,
            guestEmail: null,

            getQuoteId: function() {
                return quoteData.entity_id;
            },
            isVirtual: function() {
                return !!Number(quoteData.is_virtual);
            },
            getPriceFormat: function() {
                return priceFormat;
            },
            getBasePriceFormat: function() {
                return basePriceFormat;
            },
            getItems: function() {
                return window.checkoutConfig.quoteItemData;
            },
            /**
             *  get quote item info by id
             * @param itemId
             * @returns object|bool
             */
            getItemById: function(itemId){
                var self = this;
                var info = {
                    name: null,
                    id: null,
                    point:1
                };
              if( this.getItems() != null && this.getItems().length  ){
                  $.each(this.getItems() , function(index , quoteItem){
                      if( typeof quoteItem.item_id != "undefined" && quoteItem.item_id != null ){
                          if( quoteItem.item_id == itemId  ){
                              info.name = quoteItem.getName();
                              info.id = quoteItem.item_id;
                          }
                      }
                  });
              }
              if( info.id != null ){
                return info;
              } else{
                return false;
              }
            },
            getTotals: function() {
                return totals;
            },
            setTotals: function(totalsData) {
                if (_.isObject(totalsData.extension_attributes)) {
                    _.each(totalsData.extension_attributes, function(element, index) {
                        totalsData[index] = element;
                    });
                }
                totals(totalsData);
                this.setCollectedTotals('subtotal_with_discount', parseFloat(totalsData.subtotal_with_discount));
            },
            setPaymentMethod: function(paymentMethodCode) {
                paymentMethod(paymentMethodCode);
            },
            getPaymentMethod: function() {
                return paymentMethod;
            },
            getStoreCode: function() {
                return storeCode;
            },
            setCollectedTotals: function(code, value) {
                var totals = collectedTotals();
                totals[code] = value;
                collectedTotals(totals);
            },
            getCalculatedTotal: function() {
                var total = 0.;
                _.each(collectedTotals(), function(value) {
                    total += value;
                });
                return total;
            }
        };
    }
);
