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
                type: 'npatobarai',
                component: 'Riki_NpAtobarai/js/view/payment/method-renderer/npatobarai-method'
            }
        );
        return Component.extend({});
    }
);