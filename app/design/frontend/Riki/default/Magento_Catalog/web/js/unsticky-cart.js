define(['jquery', 'mage/translate', 'Magento_Checkout/js/checkout-data'], function($, $t, checkoutData) {
    'use strict';
    return function (config, element) {
        $(window).scroll(function(){
            var curPoint = $(window).scrollTop();
            var stopPoint = $('.check-offset').offset().top;
            var windowHeight = $(window).height();
            var additionHeight = $('.actions-toolbar').outerHeight();
            if((curPoint + windowHeight - additionHeight) >= (stopPoint)) {
                if(!$('body').hasClass('remove-sticky')) {
                    $('body').addClass('remove-sticky')
                }
            }else {
                $('body').removeClass('remove-sticky');
            }
        });
        /** reset form and element to fix issue of back-forward cache on safari */
        $(window).bind("pageshow", function(event) {
            if (event.originalEvent.persisted) {
                window.location.reload()
            }
        });
        $('a.enter-coupon').on('click', function (e) {
            e.preventDefault();
            $('html,body').animate({
                scrollTop: $('#block-discount').offset().top - 100
            }, 700);
        });
    }
});