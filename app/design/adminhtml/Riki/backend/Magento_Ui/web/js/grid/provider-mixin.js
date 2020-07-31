define([
    'jquery',
    'Magento_Ui/js/modal/alert',
    'mage/translate'
], function ($, alert, $t) {
    'use strict';

    var mixin = {
        onError: function (xhr) {
            if (xhr.statusText === 'abort') {
                return;
            }

            alert({
                content: $t('Something went wrong.'),
                buttons: [{
                    text: $.mage.__('OK'),
                    class: 'action-primary action-accept',
                    click: function () {
                        this.closeModal(true);
                        location.reload();
                    }
                }]
            });
        }
    };

    return function (target) {
        return target.extend(mixin);
    };
});
