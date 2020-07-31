define([
    "jquery",
    "jquery/ui"
], function ($) {

    $.widget('mage.amsmtpConfig', {
        options: {
            selectors: {},
            providers: [],
            successClass: 'success',
            failClass: 'fail',
            loadingClass: 'loading',
            configFormId: 'config-edit-form'
        },

        elements: {
        },
        
        detailsElement: null,

        _create: function () {
            var self = this;
            for (var field in this.options.selectors) {
                this.elements[field] = $(this.options.selectors[field]);
            }

            this.detailsElement = this.element.find('[data-role=details]');

            this.detailsElement.find('[data-role=toggle]').click(function () {
                $(this).parent('[data-role=details]').toggleClass('collapsed');
            });

            var fillTrigger = $(this.element).find('[data-role="amsmtp-fill-button"]');

            if (fillTrigger) {
                fillTrigger.click($.proxy(this.fill, this))
            }

            var checkTrigger = $(this.element).find('[data-role="amsmtp-check-button"]');

            if (checkTrigger) {
                this.checkTrigger = checkTrigger;
                // checkTrigger.click($.proxy(this.check, this))

                checkTrigger.click(function(e){
                    e.preventDefault();
                    $('#amsmtp_smtp_test_email').toggleClass('required-entry');
                    $('#' + self.options.configFormId).attr('action', self.options.check_url).submit();
                    $('#amsmtp_smtp_test_email').toggleClass('required-entry');
                });
            }

            this.toggleNoticeVisibility();
            $('.amsmtp-enable').on('click', this.toggleNoticeVisibility);
        },

        fill: function () {
            var index = +this.elements.provider.val();
            var provider = this.options.providers[index];

            this.elements.server.val(provider.server);
            this.elements.port.val(provider.port);
            this.elements.auth.val(provider.auth);
            this.elements.encryption.val(provider.encryption);
        },

        toggleNoticeVisibility: function () {
            if ($('.amsmtp-enable').val() == '0') {
                $('.amsmtp-notice').show();
            } else {
                $('.amsmtp-notice').hide();
            }
        }
    });

    return $.mage.amsmtpConfig;
});
