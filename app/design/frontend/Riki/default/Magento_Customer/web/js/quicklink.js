define([
    'jquery',
    'Riki_Theme/js/view/navigation-profile'
], function ($, leftMenu) {
    'use strict';

    $('#quicklink-nav').click(function(e){
        e.preventDefault();
        $('.menu-quicklink__content').toggleClass("active");
        $('.menu-quicklink__screen').addClass("active");
    });

    $('.menu-quicklink__screen').click(function(e){
        e.preventDefault();
        $(this).toggleClass("active");
        $('.menu-quicklink__content').removeClass("active");
    });

    $('.menu-quicklink__close').click(function(e){
        e.preventDefault();
        $('.menu-quicklink__screen').removeClass("active");
        $('.menu-quicklink__content').removeClass("active");
    });

    $('.quicklink_subprofile').click(function(e){
        e.preventDefault();
        leftMenu.prototype.openMenu(null, e);
    })
});
