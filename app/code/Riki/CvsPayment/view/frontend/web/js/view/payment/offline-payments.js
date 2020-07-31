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
                type: 'cvspayment',
                component: 'Riki_CvsPayment/js/view/payment/method-renderer/cvspayment'
            }
        );
        /**
         * Add view logic here if needed
         */
        return Component.extend({});
    }
);