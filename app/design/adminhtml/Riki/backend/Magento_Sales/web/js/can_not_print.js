define(
    [
        'jquery',
        'ko',
        'Magento_Ui/js/form/form',
        'Magento_Ui/js/modal/alert',
        "Magento_Ui/js/modal/modal",
        "mage/translate",
        'mage/validation'
    ],
    function($, ko, Component, alert, modal, $t) {
        'use strict';
        return Component.extend({

            initialize: function() {
                var self = this;
                this._super();
                return this;
            },
            /** Provide login action */
            printPopup: function() {
                var self = this;
                var url = this.url;
                var options = {
                    'type': 'popup',
                    'title': $t('Issue a receipt'),
                    'modalClass': 'print-order',
                    'parentModalClass': '_has-modal-custom _has-auth-shown',
                    'responsive': true,
                    'innerScroll': true,
                    'responsiveClass': 'custom-slide',
                    'overlayClass': 'modals-overlay'
                };
                var popup = modal(options, $('#dialog-form'));
                $('#dialog-form').modal('openModal');
            }
        });
    }
);
