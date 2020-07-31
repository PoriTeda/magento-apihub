/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

var config = {
    map: {
        '*': {
            'Magento_AdvancedCheckout/addbysku':'Riki_Subscription/js/addbysku',
            'AddCourse' : 'Riki_Subscription/js/subprofile'
        }
    },
    config: {
        mixins: {
            'Magento_Ui/js/form/element/ui-select': {
                'Riki_Subscription/js/element/ui-select-mixin': true
            }
        }
    }
};
