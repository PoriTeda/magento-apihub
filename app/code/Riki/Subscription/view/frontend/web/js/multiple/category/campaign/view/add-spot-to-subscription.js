/*jshint browser:true jquery:true*/
/*global alert*/
define([
        "jquery",
        "ko",
        "uiComponent",
        "mage/mage",
        "mage/translate",
        "Magento_Ui/js/modal/modal",
        "Magento_Customer/js/customer-data",
        "Riki_SubscriptionPage/js/view/toolbar",
        "Riki_SubscriptionPage/js/view/product-detail",
        "Riki_Theme/js/minicart",
        "uiRegistry",
        "underscore",
        "domReady!"
    ], function (
    $,
    ko,
    Component,
    mage,
    $t,
    modal,
    customerData,
    toolBar,
    productDetail,
    minicart,
    registry,
    _
    ) {
        "use strict";

        return Component.extend(_.extend({}, {
            isGridMode: ko.observable(true),
            /** Initialize observable properties */
            initObservable: function () {
                var self = this;
                this._super();

                toolBar.prototype.bindingToolbar(this.isGridMode, $(".multiple-products-main"));

                this.customer = customerData.get('customer');
                var dataCart = customerData.get("cart")();
                var quoteItems = dataCart.hasOwnProperty("m-minicart-data") ? dataCart["m-minicart-data"] : [];
                if (!_.isEmpty(quoteItems)) {
                    $('.action.to-subscription').addClass('disabled');
                }
                this.options = {
                    title: $t('Add in next delivery'),
                    type: 'popup',
                    responsive: false,
                    innerScroll: true,
                    modalClass: 'choose-subscription',
                    buttons: [{
                        text: $t('Confirmation'),
                        attr: {'disabled': 'disabled'},
                        click: function () {
                            self.submitForm();
                        }
                    }]
                };

                this.currentSection = ko.observable('');
                this.currentSectionName = ko.observable($t('Search subscription product by category button'));
                this.scrolling = ko.observable(false);
                productDetail.prototype.productId.subscribe(function(v){
                    this.productId = v;
                });
                this.checkOffset();
                $(window).scroll(function () {
                    self.checkOffset();
                });

                window['changeDefaultSubmit'] = function () {
                    self.openPopup();
                };
                minicart.prototype.setBtCheckoutText($t("Proceed to Checkout"));
                return this;
            },
            openPopup: function () {
                // Redirect if customer not login
                if (!this.isCustomerLogin(this.customer())) {
                    registry.get('multipleCategoryCampaignSelectedProducts', function (selectedProduct) {
                        var deferrer = selectedProduct.ajaxSaveSelectedProducts();

                        // disable button
                        $('.action.to-subscription').addClass('disabled');

                        deferrer.done(function () {
                            window.location = window.multileCategoryCampaignConfig.login_url;
                        }).fail(function () {
                            alert($t('Something went wrong.'))
                        }).always(function () {
                            $('.action.to-subscription').removeClass('disabled');
                        });
                    });
                } else {
                    var popupElement = $('#choose-subscription');
                    modal(this.options, popupElement);
                    registry.get('multipleCategoryCampaignSelectedProducts', function (selectedProduct) {
                        var deferrer = selectedProduct.ajaxSaveSelectedProducts();

                        // disable button
                        $('.action.to-subscription').addClass('disabled');

                        deferrer.done(function () {
                            $('.action.to-subscription').removeClass('disabled');
                        }).fail(function () {
                            alert($t('Something went wrong.'))
                        }).always(function () {
                            $('.action.to-subscription').removeClass('disabled');
                        });
                    });
                    popupElement.modal('openModal');
                    if (window.multileCategoryCampaignConfig.enableButonSubmit) {
                        $('.choose-subscription button').removeAttr('disabled');
                    }
                }
            },
            closePopup: function () {
                var popupElement = $('#choose-subscription');
                popupElement.modal('closeModal');
            },
            validateForm: function (form) {
                return $(form).validation() && $(form).validation('isValid');
            },
            submitForm: function () {
                $('#profile_id-error-clone').html('');
                if (this.validateForm('#form-choose-subscription')) {
                    var profileId = $('input[name=profile_id]:checked').val();
                    $('#form-validate').find('#riki_profile_id').val(profileId).end().submit();
                    this.closePopup();
                    $('.action.to-subscription').addClass('disabled');
                } else {
                    $('#profile_id-error-clone').html($t('This is a required field.'));
                }
                return false;
            },
            isCustomerLogin: function (customerData) {
                if (customerData.email != undefined) {
                    return true;
                }
                return false;
            },
            checkOffset: function () {
                if (!this.scrolling()) {
                    var top = $(document).scrollTop() + $(window).height() / 4,
                        self = this,
                        optionValue = "";

                    $('.multiple-products-block:visible').each(function () {
                        var section = $(this),
                            postion = section.offset().top - top;

                        if (postion <= 0) {
                            optionValue = section.find('h2.title').attr('id');
                            self.currentSectionName($("option[value='" + optionValue + "']").text());
                        }
                    });

                    if (optionValue !== "") {
                        this.currentSection(optionValue);
                    }

                    if (top <= $(window).height() / 4) {
                        self.currentSectionName($t('Search subscription product by category button'));
                        this.currentSection("");
                    }
                }
            },
            /**
             * auto scroll to category's section when selected navigation
             */
            navigationScroll: function (viewModel) {
                var optionValue = viewModel.currentSection();
                viewModel.scrolling(true);
                if (optionValue !== '') {
                    viewModel.currentSectionName($("option[value='" + optionValue + "']").text());
                    var sectionToScroll = $('#' + optionValue).offset().top - 50;
                    $('body, html').animate({
                        scrollTop: sectionToScroll
                    }, '500', function () {
                        viewModel.scrolling(false);
                    });
                } else {
                    $('body, html').animate({
                        scrollTop: 0
                    }, '500', function () {
                        viewModel.currentSectionName($t('Search subscription product by category button'));
                        viewModel.scrolling(false);
                    });
                }
            },
            moveToTop2: function(data, e){
                e.preventDefault();
                $('html,body').animate({
                    scrollTop: 0
                }, 700);
            }
        }, toolBar.prototype.extendObject(), productDetail.prototype.extendObject()));
    }
);
