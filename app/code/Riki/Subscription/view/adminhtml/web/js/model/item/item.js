/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
/*jshint browser:true jquery:true*/
/*global alert*/
define(['ko'], function(ko) {
    /**
     * @param addressData
     * Returns new address object
     */
    return function (itemData) {
        var findGiftObject = function(){
            var giftObject = _.find(itemData.gw_data.items , function (obj) {
                return obj.wrapping_id == itemData.gw_id
            });
            if(!_.isUndefined(giftObject)){
                return giftObject;
            }else{
                return false;
            }
        };
        var is_skip =itemData.is_skip;
        return {
            amount: itemData.amount,
            gw_message_id: itemData.gift_message_id,
            name: itemData.name,
            price: itemData.price,
            has_gw: itemData.has_gw_data,
            has_message: itemData.has_gift_message,
            gift_message_data: itemData.gift_message_data,
            gw_id: ko.observable(itemData.gw_id),
            gw_data: itemData.gw_data.items,
            gift_object: ko.observable(findGiftObject()),
            item_id: itemData.productcat_id,
            gift_toggle: ko.observable(itemData.gw_id != '0' || itemData.gw_id != null ),
            qty: itemData.qty,
            unit_case: itemData.unit_case,
            unit_qty: itemData.unit_qty ,
            product_data: itemData.product_data,
            productcart_data: itemData.productcart_data,
            is_free_gift: itemData.is_free_gift,
            allow_seasonal_skip : itemData.allow_seasonal_skip,
            seasonal_skip_optional : itemData.seasonal_skip_optional,
            allow_skip_from : itemData.allow_skip_from,
            allow_skip_to : itemData.allow_skip_to,
            is_skip : ko.observable((itemData.allow_seasonal_skip == 1)?(itemData.seasonal_skip_optional == 1?itemData.is_skip:1): 0 ),
            skip_from : ko.observable((itemData.skip_from)?itemData.skip_from:itemData.allow_skip_from),
            skip_to : ko.observable((itemData.skip_to)?itemData.skip_to:itemData.allow_skip_to),
            is_addition : itemData.is_addition,
            getFinalQty: function(qty,unitQty,unitCase){
                if('CS' == unitCase){
                    return qty/unitQty;
                }
                else{
                    return qty;
                }
            }
        }
    }
});
