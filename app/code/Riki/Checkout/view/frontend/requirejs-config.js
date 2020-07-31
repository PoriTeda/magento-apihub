/* required js config */
var config = {
    'map': {
        '*': {
            'Magento_Checkout/js/model/quote': 'Riki_Checkout/js/model/quote/plugin',
            'sidebar': 'Riki_Checkout/js/riki-sidebar'
        }
    },
    config: {
        mixins: {
            'Magento_Ui/js/lib/validation/rules': {
                'Riki_Checkout/js/lib/validation/plugin': true
            },
            'Magento_Checkout/js/checkout-data': {
                'Riki_Checkout/js/checkout-data/mixin': true
            }
        }
    }
};