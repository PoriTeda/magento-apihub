/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
/*global define*/
define(
    [
        'jquery',
        'Riki_Subscription/js/model/item-list',
        'Riki_Subscription/js/model/item/item',
        'Riki_Subscription/js/model/profile'
    ],
    function ($, itemList , item, profile ) {
        'use strict';
        return function (productCartItemId , dateValue) {
            /* get item list data */
            var item = _.find( itemList.getItemsData()() , function( obj ) {
                _.find( obj.items , function(item){
                   if (item.item_id == productCartItemId) {
                       item.skip_from(dateValue);
                   }
                });
            });
            profile.profileHasChanged(true);
        };
    }
);
