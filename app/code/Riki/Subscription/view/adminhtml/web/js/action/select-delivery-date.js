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
        return function (addressId , dateValue) {
            /* get item list data */
            var itemDeliveryInfo = _.find( itemList.getItemsData()() , function( obj ) {
                return obj.address_id == addressId ;
            });
            itemDeliveryInfo.info.next_delivery_date( dateValue );
            profile.profileHasChanged(true);
        };
    }
);
