define(['jquery', 'Magento_Ui/js/modal/modal', 'mage/translate'], function($, modal, $t) {
    'use strict';
    return function (config, element) {
        var aboutSelectableDate = $('#about-selectable-date');
        /** Show popup About Selectable Date*/
        $('a.about-selectable-date').on('click', function (evt) {
            evt.preventDefault();
            var options = {
                type: 'popup',
                responsive: false,
                innerScroll: false,
                clickableOverlay: false,
                modalClass: 'small about-selectable-date-popup',
                buttons: [{
                    text: $.mage.__('OK'),
                    click: function (event) {
                        this.closeModal(event);
                    }
                }]
            };
            var popup = modal(options, aboutSelectableDate);
            aboutSelectableDate.modal('openModal');
        });
    }
});