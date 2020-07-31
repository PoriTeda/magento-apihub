define(
    ['jquery'],
    function ($) {
        'use strict';
        $(document).ready(function () {
            $('.btnpagetop .btn-top').click(function (e) {
                e.preventDefault();
                $('html,body').animate({
                    scrollTop: 0
                }, 700);
            });

            $(window).scroll(function() {
                if ($(this).scrollTop()) {
                    $('.btnpagetop').fadeIn();
                } else {
                    $('.btnpagetop').fadeOut();
                }
            });

            $(window).scroll(function() {
                if($(window).width() > 1024){
                    var scroll = $(window).scrollTop();
                    if (scroll >= 100) {
                        $(".opc_sidebar-fixed").css('top' , '55px');
                    } else {
                        $(".opc_sidebar-fixed").css('top' , '115px');
                    }
                }
            });

            /** Auto close popup when click outside popup */
            $(document).on('click touchstart', 'aside.modal-popup', function (e) {
                if (e.target !== this) {
                    return;
                }
                $('.modals-wrapper .modals-overlay').trigger('click');
            });

            /** Auto scroll up if the page have message */
            if($('.page.messages > .messages').length > 0 && $('.page.messages > .messages > div.message').length > 0) {
                var scrollTopVal = $('.page.messages').offset().top - ($('.page.messages').offset().top - $('#maincontent .columns').offset().top + $('#maincontent .columns').offset().top);
                $('body, html').animate({ scrollTop: scrollTopVal });
            }
        });
    });
