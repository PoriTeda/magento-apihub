define([
    "jquery",
    'uiRegistry',
    "jquery/ui",
    'Magento_Ui/js/modal/modal',
    "mage/translate"
], function($, registry){
    "use strict";
    window.block_riki_loyalty_reward  = registry.get('customer_form.areas.block_riki_loyalty_reward.block_riki_loyalty_reward');
    $.widget('mage.reducePoint', {
        options: {
            url:     null,
            message: $.mage.__('Are you sure?'),
            modal:  null,
            title: $.mage.__('Delete shopping point')
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
        deletePoint: function() {
            var url = this.options.url,
                postData = {form_key: FORM_KEY},
                self = this;
            $.ajax({
                type: 'POST',
                url: url,
                data: postData,
                dataType: 'json',
                showLoader: true
            }).success(function (obj) {
                self.options.dialog.modal('closeModal');
                if (obj.error) {
                    alert(obj.msg);
                } else {
                    window.block_riki_loyalty_reward.loadData();
                }
            });
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
                        self.deletePoint();
                    }
                }]
            });
        }
    });

    return $.mage.reducePoint;
});
