/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
/*global define*/
define(
    [
        'jquery',
        'Riki_Subscription/js/model/profile',
        'Riki_Subscription/js/model/item-list',
        'Riki_Subscription/js/model/emulator-order'
    ],
    function ($, profile , itemList , orderData ) {
        'use strict';
        return function ( itemId , gwId , giftObject ) {
            /* get item list data */
            var itemListData = itemList.getItemsData()();
            for(var i=0 ; i < itemListData.length ; i++){
                var deliveryGroup = itemListData[i];
                var item = _.find(deliveryGroup.items , function(obj){
                    return obj.item_id == itemId;
                });
                if(!_.isUndefined(item)){
                    var oldGwFee = 0;
                    if(item.gift_object()) {
                        oldGwFee = item.gift_object().price_incl_tax;
                    }
                    item.gw_id(gwId);
                    item.gift_object(giftObject);

                    /* re-calculator gw fee and grand total again */
                    if(item.gift_object()) {
                        var newGwFee = ( orderData.gw_amount() - oldGwFee ) + parseFloat(giftObject.price_incl_tax) ;
                        var newGrandTotal = ( orderData.grand_total() - oldGwFee ) + parseFloat(giftObject.price_incl_tax) ;
                    }else{
                        var newGwFee = orderData.gw_amount() - oldGwFee ;
                        var newGrandTotal = orderData.grand_total() - oldGwFee ;
                    }

                    if(_.isNaN(newGwFee)){
                        console.log(newGwFee);
                    }

                    orderData.gw_amount(newGwFee);
                    orderData.grand_total(newGrandTotal);

                    profile.profileHasChanged(true);
                    return ;
                }
            }
        };
    }
);
