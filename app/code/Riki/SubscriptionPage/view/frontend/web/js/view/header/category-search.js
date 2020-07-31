define([
    'jquery',
    'ko',
    'mage/url',
    'uiComponent',
    'mage/translate',
    "uiRegistry",
    "domReady!"
], function (
    $,
    ko,
    urlBuilder,
    Component,
    $t
) {
    'use strict';
    return Component.extend({
        currentSectionValue: ko.observable(''),
        currentSectionName: ko.observable($t('Search subscription product by category button')),
        isScrolling: ko.observable(false),

        initialize: function () {
            const self = this;
            this.checkOffsetShowCurrentCategory();
            $(window).scroll(function () {
                self.checkOffsetShowCurrentCategory();
            });

            this._super();
        },

        checkOffsetShowCurrentCategory: function () {
            if (!this.isScrolling()) {
                this.isScrolling(true);
                let top = $(document).scrollTop() + $(window).height() / 4,
                    self = this,
                    optionValue = "";

                $('.m-category-section:visible').each(function () {
                    var section = $(this),
                        postion = (section.offset().top - top) - 40;
                    if (postion <= 0) {
                        optionValue = section.find('h2.title').attr('id');
                        const text =$("option[value='" + optionValue + "'].option-category-sub").text();
                        if(!!text){
                            self.currentSectionName(text);
                        }

                    }
                });

                if (optionValue !== "") {
                    this.currentSectionValue(optionValue);
                }

                if (top <= $(window).height() / 4) {
                    self.initDefaultValueSelection();
                }
                this.isScrolling(false);
            }
        },
        navigationScrollToCategory: function (viewModel) {
            if (viewModel.isScrolling()) {
                return;
            }
            const self = this;
            const optionValue = viewModel.currentSectionValue();
            viewModel.isScrolling(true);
            if (optionValue !== '') {
                const newText = $("option[value='" + optionValue + "']").html();
                viewModel.currentSectionName(newText);
                var sectionToScroll = $('#' + optionValue).offset().top - 100;
                $('body, html').animate({
                    scrollTop: sectionToScroll
                }, '500', function () {
                    viewModel.isScrolling(false);
                });
            } else {
                $('body, html').animate({
                    scrollTop: 0
                }, '500', function () {
                    self.initDefaultValueSelection();
                    viewModel.isScrolling(false);
                });
            }
        },
        initDefaultValueSelection: function () {
            this.currentSectionName($t('Search subscription product by category button'));
            this.currentSectionValue("");
        }

    });
});