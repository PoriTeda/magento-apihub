/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
/*global define*/
define(
    [
        "ko",
        'jquery',
        'Riki_Subscription/js/model/profile',
    ],
    function (ko , $, profile ) {
        'use strict';
        return function (component , newQty) {
            if(!_.isUndefined($("#confirmation-product-cart-qty-" + component.item_id))){
                $("#confirmation-product-cart-qty-" + component.item_id).text(
                    component.getFinalQty( parseInt(newQty) , component.unit_qty , component.unit_case)
                );
            }
            profile.profileHasChanged(true);
        };
    }
);
