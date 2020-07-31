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
        'Riki_Subscription/js/model/item/delivery-info'
    ],
    function ($, profile , itemList , deliveryInfo ) {
        'use strict';
        return function (deliveryInfoItem) {
            /* get item list data */
            var itemDeliveryInfo = _.find( itemList.getItemsData()() , function( obj ) {
               return deliveryInfoItem.address_id == obj.address_id ;
            });
            if( itemDeliveryInfo.info.timeslot_id() !=  deliveryInfoItem.info.timeslot_id() ){
                profile.profileHasChanged(true);
            }
            itemDeliveryInfo.info.timeslot_id( deliveryInfoItem.info.timeslot_id() );
        };
    }
);
