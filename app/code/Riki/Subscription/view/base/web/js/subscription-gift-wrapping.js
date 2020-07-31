/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
/*global define*/
define(['jquery'],
    function ($) {
        'use strict';

        // Gift wrapping
        $('.action-gift').click(function (e) {
            e.preventDefault();
            var gift_content = $(this).next();
            var gift_wrapping = gift_content.find('.gift-wrapping');
            var gift_message = gift_content.find('.gift-message');
            var message_content = gift_content.find('.message-content');

            $(this).toggleClass('_active');
            gift_content.toggleClass('_active');
            if (!message_content.length) {
                gift_wrapping.removeClass('has-message');
                gift_message.removeClass('has-message');
            }
        });

        // Choose Gift wrapping
        $('.gift-wrap-img-thumb label').click(function () {
            var gift_wrapping_thumb = $(this).parent();
            var gift_wrapping_list = $(this).parent().next();
            var id = '#' + $(this).attr('data-show-desc');
            gift_wrapping_thumb.find('label').removeClass('_active');
            gift_wrapping_list.find('.gift-wrapping-item').removeClass('_active');
            $(this).addClass('_active');
            $(id).addClass('_active');
        });

        // Remove Gift wrapping
        $('.gift-wrapping-title .action-remove').click(function () {
            var id = $(this).attr('data-remove-gift-wrap');
            var data_no_gift = '#' + $(this).attr('data-no-gift');
            var label = 'label[data-show-desc="' + id + '"]';
            var gift_wrapping_item = '#' + id;
            $(label).removeClass('_active');
            $(gift_wrapping_item).removeClass('_active');
            $(data_no_gift).prop('checked', true);
        });

        // Edit Gift Message
        $('.message-content .edit-message').click(function () {
            var mess_content = $(this).parent();
            var gift_wrap = $(this).parent().parent().find('.gift-wrapping');
            var gift_message = $(this).parent().parent().find('.gift-message');

            mess_content.addClass('has-message');
            gift_wrap.removeClass('has-message');
            gift_message.removeClass('has-message');
        });

        // Cancel Gift Message
        $('.cancel-message').click(function (e) {
            e.preventDefault();
            var gift_message = $(this).parent().parent().parent().parent();
            var gift_wrap = $(this).parent().parent().parent().parent().parent().find('.gift-wrapping');
            var mess_content = $(this).parent().parent().parent().parent().parent().find('.message-content');
            var gift_content = gift_wrap.parent();
            var action_gift = gift_content.prev();
            var message_content = gift_content.find('.message-content');
            mess_content.removeClass('has-message');
            gift_wrap.addClass('has-message');
            gift_message.addClass('has-message');
            if (!message_content.length) {
                gift_content.removeClass('_active');
                action_gift.removeClass('_active');
            }
        });
    }
);
