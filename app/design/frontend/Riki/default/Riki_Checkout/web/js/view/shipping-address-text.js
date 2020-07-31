define(
    [
        'ko',
        'jquery',
        'uiComponent'
    ],
    function (
        ko,
        $,
        Component
    ) {
        'use strict';
        return Component.extend({
            defaults: {
                template: 'Riki_Checkout/shipping-address-text'
            },
            checkMultiple: function() {
                if( window.location.href.indexOf('multicheckout') >= 0){
                    return true;
                }
                return  false;
            }
        });
    }
);