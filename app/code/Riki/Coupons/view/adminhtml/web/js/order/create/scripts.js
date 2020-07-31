/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
require(
    [
        "jquery"
    ],
    function($) {
        'use strict';

        var getAllCoupons = function () {
            var coupon = $F('coupons:code');
            if (coupon) {
                coupon = [coupon];
            } else {
                coupon = [];
            }

            $('#multi-coupon-list').find('.action-remove').each(
                function () {
                    if ($(this).data('coupon-code')) {
                        coupon.push($(this).data('coupon-code'));
                    }
                }
            );

            return coupon;
        };

        window.getCouponsForApply = function () {
            return getAllCoupons().join(',');
        };

        window.getCouponsForRemove = function(element) {
            var coupon = $(element).data('coupon-code'),
                coupons = getAllCoupons();

            var index = coupons.indexOf(coupon);
            if (index > -1) {
                coupons.splice(index, 1);
            }

            return coupons.join(',');
        }
    }
);
