define([
    "jquery",
    "jquery/ui",
    'Magento_Ui/js/modal/modal',
    "mage/translate"
], function($){
    "use strict";
    $.widget('mage.approvalPoint', {
        options: {
            url:     null,
            message: null,
            modal:  null,
            title: $.mage.__('Reject shopping point')
        },

        /**
         * @protected
         */
        _create: function () {
            this._prepareDialog();
        },

        /**
         * Show modal
         */
        showDialog: function() {
            this.options.dialog.html(this.options.message).modal('openModal');
        },

        /**
         * Redirect to edit page
         */
        redirect: function() {
            window.location = this.options.url;
        },

        /**
         * Prepare modal
         * @protected
         */
        _prepareDialog: function() {
            var self = this;

            this.options.dialog = $('<div class="ui-dialog-content ui-widget-content"></div>').modal({
                type: 'popup',
                modalClass: 'order-approve-point-popup',
                title: self.options.title,
                buttons: [{
                    text: $.mage.__('Ok'),
                    'class': 'action-primary',
                    click: function(){
                        self.redirect();
                    }
                }]
            });
        }
    });

    return $.mage.approvalPoint;
});
