define([
    'jquery',
    'mage/url',
    'mage/mage',
    'jquery/ui'
], function($, urlBuilder) {
    "use strict";

    $.widget('mage.rewardSetting', {
        options: {
            formId: "reward-point-setting",
            messageId: "reward-point-message"
        },
        _create: function() {
            var self = this,
            formContainer = $('#' + this.options.formId);
            var msgId = $('#' + this.options.messageId);
            formContainer.mage('validation', {
                errorPlacement: function(error, element) {
                    var msgErr = msgId;
                    msgErr.addClass("message-error error message").html(error[0].textContent);
                },
                submitHandler: function (form) {
                    self.ajaxSubmit($(form));
                }
            });
        },
        alertMessage: function(message) {
            var container = $('#'+this.options.messageId);
            container.html(message);
        },
        ajaxSubmit: function(form) {
            var self = this;
            $.ajax({
                url: form.attr('action'),
                data: form.serialize(),
                type: 'post',
                dataType: 'json',
                beforeSend: function () {
                    form.find('button[type=submit]').toggleClass('disabled');
                },
                success: function (data) {
                    var messageClass = (data.err) ? 'message-error error message' : 'message-success success message',
                        message = '';
                    message += '<div class="'+ messageClass +'">';
                    message += '<div>'+ data.msg +'</div>';
                    message += '</div>';
                    self.alertMessage(message);
                    form.find('button[type=submit]').toggleClass('disabled');
                }
            });
        }
    });

    return $.mage.rewardSetting;
});