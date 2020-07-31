/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
define([
    'ko',
    'jquery',
    'uiComponent',
    'Magento_Customer/js/customer-data',
    'underscore',
    'jquery/jquery-storageapi'
], function (ko, $, Component, customerData, _) {
    'use strict';

    return Component.extend({
        defaults: {
            cookieMessages: [],
            messages: []
        },
        initialize: function () {
            this._super();
            var self = this;
            var scrollTopVal = $('.page.messages').offset().top - ($('.page.messages').offset().top - $('#maincontent .columns').offset().top + $('#maincontent .columns').offset().top);
            this.cookieMessages = $.cookieStorage.get('mage-messages');
            if(this.cookieMessages === null || this.cookieMessages === ''){
                this.cookieMessages = [];
            }
            this.cookieMessages.forEach(function(message){
                message.text = message.text.replace(/&lt;strong&gt;/g,"<strong>");
                message.text = message.text.replace(/&lt;\/strong&gt;/g,"</strong>");
            });
            if(this.cookieMessages != null && this.cookieMessages.length > 0) {
                $('body, html').animate({ scrollTop: scrollTopVal });
            }

            this.messages = customerData.get('messages').extend({disposableCustomerData: 'messages'});
            this.messages.subscribe(function(messages) {
                if(typeof messages.messages != 'undefined' && messages.messages.length > 0) {
                    $('body, html').animate({ scrollTop: scrollTopVal });
                }
            });

            if (!_.isEmpty(this.messages().messages)) {
                customerData.set('messages', {});
            }

            $.cookieStorage.set('mage-messages', '');
        }
    });
});
