define([
    'jquery',
    'jquery/ui',
    'Magento_Ui/js/modal/modal',
    'Mirasvit_FraudCheck/js/lib/slider'
], function ($, ui, modal) {
    'use strict';

    $.widget('fc.ScoreEdit', {
        _create: function () {
            this.initImportance();
            this.initIsActive();

            this.initSlider();

            this.reload();
        },

        initImportance: function () {
            var self = this;

            $("[data-importance]>[data-value]").on('click', function (e) {
                var $target = $(e.currentTarget);
                var val = $target.attr('data-value');

                $target.parent().attr('data-importance', val);
                $('input', $target.parent()).val(val);

                self.reload();
            }).on('mouseenter', function (e) {
                var $target = $(e.currentTarget);
                var val = $target.attr('data-value');

                $target.parent().attr('data-hover', val);
            });

            $("[data-importance]").on('mouseleave', function (e) {
                var $target = $(e.currentTarget);

                $target.attr('data-hover', 0);
            });
        },

        initIsActive: function () {
            var self = this;

            $("[data-is-active]").on('click', function (e) {
                var $target = $(e.currentTarget);
                var val = $target.attr('data-is-active');

                if (val == 1) {
                    $target.attr('data-is-active', 0);
                    $target.addClass('fa-toggle-off')
                        .removeClass('fa-toggle-on');

                    $target.parent().parent().addClass('inactive')
                        .removeClass('active');
                } else {
                    $target.attr('data-is-active', 1);
                    $target.removeClass('fa-toggle-off')
                        .addClass('fa-toggle-on');

                    $target.parent().parent().addClass('active')
                        .removeClass('inactive');
                }

                $('input', $target.parent()).val($target.attr('data-is-active'));

                self.reload();
            });
        },

        reload: function () {
            $("[target=preview_iframe]").remove();

            var $form = $('<form/>')
                .attr('action', this.options.previewUrl)
                .attr('method', 'post')
                .attr('target', 'preview_iframe')
                .css('display', 'none')
                .html($('<textarea>')
                    .attr('name', 'data')
                    .text($('#edit_form').serialize()));

            $('body').append($form);

            $form.submit();
        },

        initSlider: function () {
            $('#slider').slider({
                min: 0,
                max: 100,
                step: 1,
                range: false,
                tooltips: true,
                handles: [{
                    value: 0,
                    type: "accept"
                }, {
                    value: $("[data-status=accept]").val(),
                    type: "review"
                }, {
                    value: $("[data-status=review]").val(),
                    type: "reject"
                }],
                // display type names
                showTypeNames: true,
                typeNames: {
                    'accept': 'Accept',
                    'review': 'Review',
                    'reject': 'Reject'
                },
                // slide callback
                slide: function (e, ui) {
                    $("[data-status=accept]").val(ui.values[1]);
                    $("[data-status=review]").val(ui.values[2]);
                }
            });
        }
    });

    return $.fc.ScoreEdit;
});