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

            testVal: ko.observable(),

            initialize: function(config) {
                var self = this;
                this._super();
                this.testVal(config.customVars);
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
                    'overlayClass': 'modals-overlay',
                    'buttons': [{
                        text: $.mage.__('Issue receipt'),
                        class: 'print action submit primary',
                        click: function () {
                            var printDataArray = $('#print-order-form').serialize();
                            if($('#print-order-form').validation()
                                && $('#print-order-form').validation('isValid')
                            ) {
                                this.closeModal();
                                $('#print-order-form')[0].reset();
                                window.open(url+'?'+printDataArray,'_blank');
                            }
                        }
                    }]
                };
                var popup = modal(options, $('#dialog-form'));
                $('#dialog-form').modal('openModal');
            }
        });
    }
);
