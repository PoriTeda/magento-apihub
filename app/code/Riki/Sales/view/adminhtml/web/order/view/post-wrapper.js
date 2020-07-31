/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

define([
    'jquery',
    'Magento_Ui/js/modal/confirm',
    'Magento_Ui/js/modal/modal',
    'mage/translate'
], function ($, confirm, modal) {
    'use strict';

    /**
     * @param {String} url
     * @returns {Object}
     */
    function getForm(url) {
        return $('<form>', {
            'action': url,
            'method': 'POST'
        }).append($('<input>', {
            'name': 'form_key',
            'value': window.FORM_KEY,
            'type': 'hidden'
        }));
    }

    $('#order-view-cancel-button').click(function () {
        if ($('#order-view-cancel-button').hasClass('order_exported')) {
            var msg = $.mage.__('This order is in processing.%s Contact to the manager to stop transferring to warehouse, and you try to cancel this order again if it is OK to stop this shipping.%s In addition, you can NOT cancel for LOHACO order if this order status is &quot;IN_PROCESSING&quot;.');
        } else {
            var msg = $.mage.__('Are you sure you want to cancel this order?');
        }

        var url = $('#order-view-cancel-button').data('url'),
            point = $('#order-view-cancel-button').attr('data-message_point');
        if(point !='undefined' && point > 0){
            msg+= '<br/>'+$.mage.__('If you cancel the order, you will lose %1 Do you really want to cancel?').replace('%1', point);
        }
        confirm({
            'content': msg.replace(/%s/g, '<br\>'),
            'actions': {

                /**
                 * 'Confirm' action handler.
                 */
                confirm: function () {
                    $('#popup-reason-cancel').modal('openModal');
                }
            },
            buttons: [{
                text: $.mage.__('NO'),
                class: 'action-secondary action-dismiss',

                /**
                 * Click handler.
                 */
                click: function (event) {
                    this.closeModal(event);
                }
            }, {
                text: $.mage.__('OK'),
                class: 'action-primary action-accept',

                /**
                 * Click handler.
                 */
                click: function (event) {
                    this.closeModal(event, true);
                }
            }]
        });

        return false;
    });

    $('#order-view-hold-button').click(function () {
        var url = $('#order-view-hold-button').data('url');

        getForm(url).appendTo('body').submit();
    });

    $('#order-view-unhold-button').click(function () {
        var urlApprove = $('#order-view-unhold-button').data('urlapprove');
        if(typeof urlApprove !== 'undefined' && urlApprove !== false) {
            var pointConfirmForm = $('#frm-point-confirm');
            var options = {
                type: 'popup',
                responsive: true,
                innerScroll: true,
                title: $.mage.__('Please process point on this order')
            };
            var popup = modal(options, pointConfirmForm);
            pointConfirmForm.modal('openModal');
        }else {
            var url = $('#order-view-unhold-button').data('url');
            getForm(url).appendTo('body').submit();
        }

    });
});
