/*jshint browser:true jquery:true*/
/*global FORM_KEY*/
define([
    'jquery',
    'uiRegistry',
    'jquery/ui',
    'Riki_Rule/js/validation/rules',
    'Magento_Ui/js/modal/modal',
    'mage/translate',
    'mage/backend/tree-suggest',
    'mage/backend/validation'
], function ($, registry) {
    'use strict';

    window.block_riki_loyalty_reward  = registry.get('customer_form.areas.block_riki_loyalty_reward.block_riki_loyalty_reward');

    $.widget('mage.newPointPopup', {
        _create: function () {
            var widget = this;
            var newRewardsForm = $('#new_rewards_form');
            newRewardsForm.mage('validation', {
                errorPlacement: function (error, element) {
                    error.insertAfter(element.is('#new_rewards_parent') ?
                        $('#new_rewards_parent-suggest').closest('.mage-suggest') :
                        element);
                }
            }).on('highlight.validate', function (e) {
                var options = $(this).validation('option');
                if ($(e.target).is('#new_rewards_parent')) {
                    options.highlight($('#new_rewards_parent-suggest').get(0),
                        options.errorClass, options.validClass || '');
                }
            });
            this.element.modal({
                type: 'slide',
                modalClass: 'mage-new-rewards-dialog form-inline',
                title: $.mage.__('Add\\Deduct Points'),
                opened: function () {
                    $('#new_rewards_amount').val('');
                    $('#booking_point_wbs').val('');
                    $('#booking_point_account').val('');
                    $('#expiration_period').val('');
                    $('#new_rewards_amount').focus();
                    $('#new_rewards_comment').val('');
                },
                buttons: [{
                    text: $.mage.__('Apply'),
                    class: 'action-primary',
                    click: function (e) {
                        if (!newRewardsForm.valid()) {
                            return;
                        }
                        newRewardsForm.find('div:first').html('');
                        var thisButton = $(e.currentTarget);
                        thisButton.prop('disabled', true);
                        var postData = {
                            amount: $('#new_rewards_amount').val(),
                            comment: $('#new_rewards_comment').val(),
                            booking_point_wbs: $('#booking_point_wbs').val(),
                            booking_point_account: $('#booking_point_account').val(),
                            expiration_period: $('#expiration_period').val(),
                            form_key: FORM_KEY,
                            customer_id: widget.options.customerId,
                            customer_code: widget.options.customerCode,
                            return_session_messages_only: 1
                        };

                        $.ajax({
                            type: 'POST',
                            url: widget.options.saveRewardUrl,
                            data: postData,
                            dataType: 'json',
                            context: $('body')
                        }).success(function (data) {
                            if (data.error) {
                                newRewardsForm.find('div:first').html(data.html);
                            } else {
                                $(widget.element).modal('closeModal');
                            }
                        }).complete(
                            function () {
                                thisButton.prop('disabled', false);
                                window.block_riki_loyalty_reward.loadData();
                            }
                        );
                    }
                }]
            });
        }
    });

    return $.mage.newPointPopup;
});
