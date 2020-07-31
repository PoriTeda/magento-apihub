define([
    "jquery",
    "amasty_slick",
    "jquery/ui"
], function ($) {

    $.widget('mage.ampromoPopup', {
        options: {
            autoOpen: false,
            slickSettings: {}
        },

        isSlickInitialized: false,

        _create: function () {
            $(this.element).mousedown($.proxy(function (event) {
                if ($(event.target).data('role') == 'ampromo-overlay') {
                    event.stopPropagation();
                    this.hide();
                }
            }, this));

            $('[data-role=ampromo-popup-hide]').click($.proxy(this.hide, this));

            if (this.options.autoOpen) {
                this.show();
            }
        },

        hide: function () {
            $(this.element).fadeOut();
        },

        show: function () {
            if (!this.isSlickInitialized) {
                // Hack for "slick" library
                $(this.element).show();
                $('[data-role=ampromo-gallery]').slick(this.options.slickSettings);
                $(this.element).hide();

                this.isSlickInitialized = true;
            }

            $(this.element).fadeIn();
        }
    });

    return $.mage.ampromoPopup;
});
