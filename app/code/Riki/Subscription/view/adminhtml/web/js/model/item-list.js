/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
define(
    [
        "jquery",
        'ko',
        'Riki_Subscription/js/model/item/item',
        'Riki_Subscription/js/model/item/delivery-info'
    ],
    function (
        $,
        ko ,
        deliveryItem,
        deliveryInfo
    ) {
        'use strict';
        var itemsData = window.subscriptionConfig.delivery_info;
        var timeslot_data = window.subscriptionConfig.timeslot_data;
        return {
            flatItemData: ko.observableArray([]),
            getItemsData: function () {
                var self = this;
                if(this.flatItemData().length > 0){
                    return this.flatItemData;
                }
                $.map(itemsData , function(item , key){
                    for(var i=0 ; i < Object.keys(item).length ; i++ ){
                        var productCartData = [];
                        var code = Object.keys(item)[i];
                        $.each(item[code].product , function(key , productData){
                            productCartData.push(deliveryItem(productData))
                        });
                        self.flatItemData.push({
                            info: deliveryInfo(item[code] , code),
                            items: productCartData ,
                            address_id: key
                        });
                    }
                });
                return this.flatItemData;
            },
            getAllTimeSlotData: function () {
                return timeslot_data;
            }
        };
    }
);
