define([
    'jquery',
    'ko',
    'uiComponent',
    'mage/translate'
], function (
    $,
    ko,
    Component,
    $t
) {
    'use strict';
    return Component.extend({
        currentSection: ko.observable(''),
        currentSectionName: ko.observable($t('Search subscription product by category button')),
        scrolling: ko.observable(false),

        initialize: function () {
            var self = this;
            this._super();
            this.checkOffset();
            $(window).scroll(function(){
                self.checkOffset();
            });

            return this;
        },

        checkOffset: function() {
            if(!this.scrolling()) {
                var top = $(document).scrollTop() + $(window).height()/4,
                    self = this,
                    optionValue = "";

                $('.multiple-products-block:visible').each(function () {
                    var section = $(this),
                        postion = section.offset().top - top;

                    if(postion <= 0) {
                        optionValue = section.find('h2.title').attr('id');
                        self.currentSectionName($("option[value='" + optionValue + "']").text());
                    }
                });

                if(optionValue !== ""){
                    this.currentSection(optionValue);
                }

                if(top  <= $(window).height()/4) {
                    self.currentSectionName($t('Search subscription product by category button'));
                    this.currentSection("");
                }
            }
        },
        /**
         * auto scroll to category's section when selected navigation
         */
        navigationScroll: function(viewModel) {
            var optionValue = viewModel.currentSection();
            viewModel.scrolling(true);
            if(optionValue !== '') {

                viewModel.currentSectionName($("option[value='" + optionValue + "']").text());
                var sectionToScroll = $('#' + optionValue).offset().top - 50;
                $('body, html').animate({
                    scrollTop: sectionToScroll
                }, '500', function () {
                    viewModel.scrolling(false);
                });
            }else {
                $('body, html').animate({
                    scrollTop: 0
                }, '500', function () {
                    viewModel.currentSectionName($t('Search subscription product by category button'));
                    viewModel.scrolling(false);
                });
            }
        }

    });
});