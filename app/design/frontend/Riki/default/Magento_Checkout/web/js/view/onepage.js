define([
    'jquery',
    'ko',
    'uiComponent',
    'mage/url'
], function ($, ko, Component,urlBuilder) {
    'use strict';
    return Component.extend({
        initialize: function () {
            this._super();
            return this;
        },
        urlLogout:function () {
            return  urlBuilder.build('customer/account/logout')
        }
    });
});
