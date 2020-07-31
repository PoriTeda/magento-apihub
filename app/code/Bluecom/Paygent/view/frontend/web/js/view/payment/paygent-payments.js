/*browser:true*/
/*global define*/
define(
    [
        'uiComponent',
        'Magento_Checkout/js/model/payment/renderer-list'
    ],
    function (
        Component,
        rendererList
    ) {
        'use strict';
        rendererList.push(
            {
                type: 'paygent',
                component: 'Bluecom_Paygent/js/view/payment/method-renderer/paygent-method'
            }
        );
        /** Add view logic here if needed */
        return Component.extend({});
    }
);