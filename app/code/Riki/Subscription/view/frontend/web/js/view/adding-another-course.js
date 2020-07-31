/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
/*jshint browser:true jquery:true*/
/*global alert*/
define([
        "jquery",
        "ko",
        "uiComponent",
        'Magento_Ui/js/modal/modal',
        "mage/translate"
    ], function (
    $,
    ko,
    Component,
    modal,
    $t
    ) {
        "use strict";
        var popUp = null;
        var profileId = null;
        var hasAdditionalCategoriesProducts = null;
        var loadAdditionalCategoriesProductsUrl = null;
        return Component.extend({
            popUpFormElement: '#add-another-course-popup',
            isFormPopUpVisible: ko.observable(false),
            initObservable: function () {
                this.hasAdditionalCategoriesProducts = ko.observable(false);
                return this;
            },
            initialize: function (config) {
                var self = this;
                this.profileId = config.profile_id;
                this.loadAdditionalCategoriesProductsUrl = config.load_additional_categories_products_url;
                this._super();
                this.isFormPopUpVisible.subscribe(function (value) {
                    if (value) {
                        self.getPopUp().openModal();
                    }
                });

                this.loadAdditionalCategoriesProducts();
            },
            showFormPopUp: function () {
                this.isFormPopUpVisible(true);
            },

            getPopUp: function () {
                var self = this;
                if (!popUp) {
                    self.options = {
                        type: 'popup',
                        responsive: false,
                        innerScroll: false,
                        modalClass: 'add-another-course subscription-view-index',
                        title: $t('Add another course'),
                        buttons: [{
                            text: $t('Add products (altogether)'),
                            click: function () {
                                var self1 = this;
                                var formElement = $('#form-submit-profile');
                                var profileId = parseInt(formElement.find('#profile_id').val());
                                var urlAddProductCart = $('#riki_url_add_product_profile').val();

                                var dataAddToCart = [];
                                var productAddition = [];
                                var mainProduct = [];
                                $(".add-more-prod-at-subscription input[name=product_id]").each(function (key, item) {
                                    var productMainId = $(item).val();
                                    mainProduct.push(productMainId);
                                });

                                $('#add-another-course-popup .item ').each(function (key, item) {
                                    var isCase = $(this).data('quantity-type');
                                    var productCatId = $(item).find('.name input').attr('name');
                                    if (productCatId) {
                                        productCatId = productCatId.match(/[0-9]+_[0-9]+/g);

                                        var dataProduct = [];
                                        var productId = $(item).find('.name input').val();
                                        var unitCase = $(item).find('.qty #case_display_' + productCatId).val();
                                        var unitQty = $(item).find('.qty #unit_qty_value_' + productCatId).val();
                                        var qtyCaseSelectedValue = 0;
                                        var qtySelectedValue = 0;

                                        //Is Case
                                        if(isCase == 1){
                                            qtyCaseSelectedValue = $(item).find('.qty_case').val();
                                            qtySelectedValue = qtyCaseSelectedValue * unitQty;
                                        }
                                        else{ //Normal quantity
                                            qtySelectedValue = $(item).find('.qty #qty_' + productCatId).val();
                                        }

                                        if (qtySelectedValue == 0 && qtyCaseSelectedValue == 0) {
                                            return;
                                        }

                                        var isAdditional = 1;
                                        if (mainProduct.indexOf(productId) > -1) {
                                            isAdditional = 0;
                                        }

                                        if (unitCase == 'ea') {
                                            qtyCaseSelectedValue = qtySelectedValue;
                                            unitQty = 1;
                                        }

                                        productAddition.push(productId);
                                        dataProduct = {
                                            'product_additional_id': productId,
                                            'product_additional_qty': qtySelectedValue,
                                            'product_case': qtyCaseSelectedValue,
                                            'unit_case': unitCase,
                                            'unit_qty': unitQty,
                                            'is_addition': isAdditional
                                        };
                                        dataAddToCart.push(dataProduct);
                                    }

                                });

                                var payload = JSON.stringify({'profile_id': profileId, 'products': dataAddToCart});

                                $('body').trigger('processStart');
                                return $.ajax({
                                    url: urlAddProductCart,
                                    data: payload,
                                    type: 'POST',
                                    dataType: 'json',
                                    context: this,
                                    success: function (response) {
                                        if (response.success == true) {
                                            var formElement = $('#form-submit-profile');
                                            $('<input />').attr({
                                                type: 'hidden',
                                                id: 'is_added',
                                                name: 'is_added',
                                                value: productAddition.toString()
                                            }).appendTo(formElement);
                                            return formElement.submit();
                                        } else {
                                            self1.closeModal();
                                            $('body').trigger('processStop');
                                            self1.closeModal();
                                        }
                                    }
                                })
                            }
                        }],
                        closed: function () {
                            self.isFormPopUpVisible(false);
                        }
                    };
                    popUp = modal(self.options, $(self.popUpFormElement));
                }
                return popUp;
            },

            submitForm: function () {
                var self = this;
                self.getPopUp().closeModal();
            },

            loadAdditionalCategoriesProducts: function () {
                var self = this;
                $.ajax({
                    url: self.loadAdditionalCategoriesProductsUrl,
                    type: 'POST',
                    global: false,
                    data: {profile_id: self.profileId},
                    dataType: 'json',
                    success: function (response) {
                        if (response) {
                            ko.utils.setHtml($('#add-another-course-popup')[0],JSON.parse(response));
                            $('#add-another-course-popup').trigger('contentUpdated');
                            self.hasAdditionalCategoriesProducts(true);
                        }
                    }
                })
            }
        });
    }
);