define(['jquery', 'Magento_Checkout/js/checkout-data'], function($, checkoutData) {
    'use strict';
    return function (config, element) {
        $(window).on('beforeunload', function(){
            checkoutData.setFocusContent($(window).scrollTop());
        });
        $(document).ready(function () {
            if(checkoutData.getFocusContent()) {
                $('html,body').animate({
                    scrollTop: checkoutData.getFocusContent()
                }, 0);
                checkoutData.setFocusContent(false);
            }
        });
    }
});