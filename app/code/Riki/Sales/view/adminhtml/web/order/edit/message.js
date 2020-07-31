/**
 * @category    Mage
 * @package     Magento_Sales
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
define([
    "jquery",
    "jquery/ui",
    'Magento_Ui/js/modal/modal',
    'Magento_Ui/js/modal/confirm',
    "mage/translate"
], function($, ui, modal, confirm, $t){
    "use strict";
    $.widget('mage.orderEditDialog', {
        options: {
            url:     null,
            message: null,
            modal:  null
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
                modalClass: 'edit-order-popup',
                title: $.mage.__('Edit Order'),
                buttons: [{
                    text: $.mage.__('Ok'),
                    'class': 'action-primary',
                    click: function(){
                        if(localStorage.getItem('number_create_order_tab') > 0) {
                            confirm({
                                content: $t('You are creating order in another tab. Do you want to clear old data and create again?'),
                                actions: {
                                    confirm: function() {
                                        self.redirect();
                                    }
                                }
                            });
                        } else {
                            self.redirect();
                        }
                    }
                }]
            });
        }
    });

    return $.mage.orderEditDialog;
});
