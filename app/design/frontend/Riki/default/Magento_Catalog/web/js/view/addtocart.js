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
        "mage/mage",
        "mage/translate",
        "Magento_Ui/js/modal/modal",
        'Riki_Theme/js/cart-data-model',
        'Riki_SubscriptionPage/js/view/qty',
        "Riki_SubscriptionPage/js/view/price",
        'Riki_Subscription/js/model/utils',
        'uiRegistry',
        'Magento_Customer/js/customer-data',
        'Riki_Subscription/js/model/profile-list',
        'Riki_Theme/js/sync-cart-data',
        'Magento_Catalog/js/catalog-add-to-cart',
        'domReady!'
    ], function (
        $,
        ko,
        Component,
        mage,
        $t,
        modal,
        cartDataModel,
        qty,
        price,
        priceUtils,
        uiRegistry,
        customerData,
        profileList,
        syncCartData
    ) {
        "use strict";

        return Component.extend({
            /** Initialize observable properties */
            profiles: ko.observableArray(),
            selectedOption: ko.observable(1),
            subtotalNumber: cartDataModel.getCartSubtotal(),
            subtotal: ko.observable(0),
            totalQty: cartDataModel.getCartTotalQty(),
            products: cartDataModel.getCartProducts(),
            toProfileQtySelected: ko.observable(0),
            customer: ko.observableArray(),
            currentProduct: {},

            initObservable: function() {
                var self = this;
                customerData.reload('customer');
                customerData.reload('profiles');
                this._super();
                this.initFormValidation();
                this.initViewModel();
                this.customer = customerData.get('customer');
                let selectedRadioInput = document.querySelector('input[name="radio"]:checked').value;
                this.selectedOption.subscribe(function(v){
                    if (v == 1){
                        $('.to-subscription').addClass('hidden');
                        $('.button-order').removeClass('hidden');
                        self.toProfileQtySelected(0);
                    } else if (v == 2){
                        $('.to-subscription').removeClass('hidden');
                        $('.button-order').addClass('hidden');
                        self.toProfileQtySelected(0);
                        // Clear quote on current PDP
                        self.currentProduct['qtySelected'](0);
                    }
                })
                // Reassign value for selected option when user click back on browser
                if(parseInt(selectedRadioInput, 10) !== this.selectedOption()) {
                    this.selectedOption(parseInt(selectedRadioInput, 10));
                }
                cartDataModel.mergeQuote(true)
                    .isCartReady(true)
                    .whenChangeQty(false, true);
                this.profiles = customerData.get('profiles');
                // Set temporary so validateChangeQtySubpage won't show error right away
                cartDataModel.allowCheckQtyChange(false);
                this.bindButton();
                return this;
            },
            openPopup: function () {
                var self = this;
                var options = '';
                this.verifyQty();
                var popupElement = $('#choose-subscription');

                if (this.profiles && this.profiles().profiles && this.profiles().profiles.items) {
                    options = {
                        title: $t('Add in next delivery'),
                        type: 'popup',
                        responsive: false,
                        innerScroll: true,
                        modalClass: 'choose-subscription',
                        buttons: [{
                            text: $t('Confirmation'),
                            click: function () {
                                self.submitForm();
                            }
                        }]
                    };
                } else {
                    options = {
                        type: 'popup',
                        responsive: false,
                        innerScroll: true,
                        modalClass: 'choose-subscription',
                        buttons: [{
                            text: $t('Close'),
                            click: function () {
                                this.closeModal();
                            }
                        }]
                    };
                }
                modal(options, popupElement);
                popupElement.modal('openModal');
            },
            closePopup: function () {
                var popupElement = $('#choose-subscription');
                popupElement.modal('closeModal');
            },
            validateForm: function (form) {
                return $(form).validation() && $(form).validation('isValid');
            },
            submitForm: function () {
                var self = this;
                this.verifyQty();
                $('#profile_id-error-clone').html('');
                if (this.validateForm('#form-choose-subscription')) {
                    var qty = self.toProfileQtySelected();
                    $('#form-choose-subscription').find('#product_qty').val(qty).end().submit();
                    this.closePopup();
                } else {
                    $('#profile_id-error-clone').html($t('This is a required field.'));
                }
                return false;
            },
            changeOption: function(item, e){
                this.selectedOption($(e.target).val());
                return true;
            },
            updateQuoteQty: function (newQty, item, whenSuccess, whenFail) {
                if(!cartDataModel.isUpdatingInSpot) {
                    syncCartData._updateItemQty([
                        $("#minicart_qty_select_" + item['id'] + "_" + item['catId']),
                        $("#minicart_qty_delete_" + item['id'] + "_" + item['catId']),
                        $("#minicart_qty_minus_" + item['id'] + "_" + item['catId']),
                        $("#minicart_qty_plus_" + item['id'] + "_" + item['catId']),
                        $("#minicart_qty_remove_" + item['id'] + "_" + item['catId']),
                        $("#minus-delete-pdp"), $("#minus-pdp"), $("#plus-pdp"), $(".qty-select-pdp"),
                        $(".first-option"), $(".second-option")
                    ], {
                        item: item,
                        item_id: item['item_id'],
                        product_id: item['id'],
                        item_qty: newQty
                    }, whenSuccess, whenFail);
                }
            },
            initViewModel: function(){
                var self = this;
                var trContainer = $('.product-info-main');
                var viewModel = {};
                viewModel['imageUrl'] = window.productImageUrl;
                viewModel['name'] = window.productName;
                viewModel['catId'] = window.productCategoryId;
                viewModel['id'] = window.productId;
                viewModel['disabled'] = 0;
                viewModel['caseDisplay'] = 'ea';
                viewModel['unitQty'] = 1;
                viewModel['gift_wrapping'] = window.giftWrapping;
                viewModel['giftWrappingSelected'] = -1;
                viewModel['free_item'] = false;
                viewModel['type'] = 'main';
                viewModel['delivery_type'] = window.deliveryType;
                viewModel['updateFail'] = ko.observable(false);
                viewModel['lastQtySelected'] = 0;

                qty.prototype.assignProductQtyData(viewModel, trContainer);
                price.assignPriceData(viewModel, trContainer);
                viewModel['qtySelected'].subscribe(function(value){
                    if(!this.updateFail()) {
                        this.lastQtySelected = value;
                    }
                }, viewModel, "beforeChange");
                viewModel['qtySelected'].subscribe(function (newValue) {
                    if (isNaN(newValue)) {
                        newValue = 0;
                    }
                    var qty = parseInt(newValue);
                    if(newValue != 0){
                        uiRegistry.set('mbAddToCartTmp', true);
                    }
                    if (this.caseDisplay === 'cs') {
                        this.qtyCase(parseInt(newValue));
                        qty = qty * this.unitQty;
                    }
                    if(this.lastQtySelected != newValue) {
                        if (self.selectedOption() == 1 && !this.updateFail()) {
                            self.updateQuoteQty(parseInt(newValue), viewModel, function () {
                            }, function () {
                            });
                        }
                    }
                    this.qty(qty);
                }, viewModel);
                viewModel.updateFail.subscribe(function(newValue){
                    if(newValue){
                        this.qtySelected(this.lastQtySelected);
                        this.updateFail(false);
                    }
                }, viewModel);
                viewModel['finalPrice'] = ko.pureComputed(function () {
                    if (typeof this.tierPriceNumber == 'function') {
                        return priceUtils.getFormattedPrice(this.tierPriceNumber());
                    } else {
                        return priceUtils.getFormattedPrice(this.finalPriceNumber());
                    }
                }, viewModel);
                self.products.push(viewModel);
                self.currentProduct = viewModel;
            },
            verifyQty: function(){
                if($('#qty').val() == 0 && $('#qty_select').val() != 0){
                    if($('#case_display').val() == 'cs'){
                        $('#qty').val($('#qty_select').val() * $('#unit_qty').val());
                    } else{
                        $('#qty').val($('#qty_select').val());
                    }
                }
                $('#qty_cs_double_check').val($('#qty').val());
                return true;
            },
            toProfileQtyChange: function(data, event, v){
                // only execute by human action
                if (event.originalEvent !== undefined)
                {
                    var self = this;
                    this.toProfileQtySelected(parseInt(self.toProfileQtySelected()) + parseInt(v));
                    return this;
                }
            },
            initFormValidation: function(){
                $('#product_addtocart_form').mage('validation', {
                    radioCheckboxClosest: '.nested',
                    submitHandler: function (form) {
                        var widget = $(form).catalogAddToCart({
                            bindSubmit: false
                        });

                        widget.catalogAddToCart('submitForm', $(form));

                        return false;
                    }
                });
            },
            bindButton: function(){
                $('.tocart').attr('data-bind', "click: verifyQty, css: {disabled: currentProduct.qtySelected() == 0}");
                $('.to-subscription.user-is-login').attr('data-bind', "click: openPopup, css:{disabled: toProfileQtySelected() == 0}");
                $('.to-subscription.user-is-guest').attr('data-bind', "css:{disabled: toProfileQtySelected() == 0}");
            }
        });
    }
);