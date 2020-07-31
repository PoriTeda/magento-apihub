define([
    'jquery',
    'ko',
    'uiComponent',
    'mage/url',
    'mage/translate'
], function ($, ko, Component, urlBuilder, $t) {
    'use strict';
    return Component.extend({
        defaults: {
            template: 'Magento_Checkout/checkout-title'
        },
        getUrlCheckout: function() {
            if( window.location.href.indexOf('multicheckout') >= 0){
              return urlBuilder.build('checkout/#single_order_confirm');
            }
            return urlBuilder.build('multicheckout/#multiple_order_confirm');
        },
        getLabelCheckout : function() {
            if( window.location.href.indexOf('multicheckout') >= 0){
                return $t('Change to single address');
            }
            return $t('Change to multiple address');
        }
    });
});
