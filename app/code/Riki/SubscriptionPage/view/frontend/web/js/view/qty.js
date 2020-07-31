define([
    'jquery',
    'ko',
    'uiComponent',
    'underscore',
    'Riki_Theme/js/cart-data-model',
    'uiRegistry'
], function ($, ko, Component, _, cartDataModel, uiRegistry) {

    return Component.extend({
        productId: null,
        categoryId: null,
        isHanpukai: false,
        products: cartDataModel.getCartProducts(),
        maxQty: null,
        minQty: null,
        isDisable: null,


        initialize: function (config) {
            this._super();
            this.productId = config['qtyData']['productId'];
            this.categoryId = config['qtyData']['categoryId'];
            this.isHanpukai = config['qtyData']['isHanpukai'];
            this.maxQty = config['qtyData']['maxQty'];
            this.minQty = config['qtyData']['minQty'];
            this.isDisable = config['qtyData']['isDisable'];
        },

        assignProductQtyData: function (productViewModel, container) {
            const self = this;
            if(!window.isSpotPage) {
                const mbQtyElem = container.find('#mb_qty_input_' + productViewModel['id'] + '_' + productViewModel['catId']);
                if (mbQtyElem.length) {
                    productViewModel['maxQty'] = parseInt(mbQtyElem[0].getAttribute("data-quantity"));
                    productViewModel['minQty'] = parseInt(mbQtyElem[0].getAttribute("data-minimum"));
                    productViewModel['disabled'] = !!mbQtyElem.prop("disabled");
                }
            } else {
                productViewModel['maxQty'] = window.productMaxQty;
                productViewModel['minQty'] = window.productMinQty;
                productViewModel['disabled'] = !!container.find('#qty').prop("disabled");
            }

            productViewModel.qtySelected = !productViewModel.hasOwnProperty("qtySelected") ? cartDataModel.numberObservable(0, productViewModel['maxQty'],0,function(){
                return productViewModel['isInRealCart'] !== true && !productViewModel.hasOwnProperty('is_multiple_campaign') ? cartDataModel.validateChangeQtySubpage() : true;
            }) : productViewModel.qtySelected;
            productViewModel.qty = !productViewModel.hasOwnProperty("qty") ? ko.observable(0) : productViewModel.qty;
            productViewModel.qtyCase = !productViewModel.hasOwnProperty("qtyCase") ? ko.observable(0) : productViewModel.qtyCase;
            productViewModel.giftWrappingSelected = !productViewModel.hasOwnProperty("giftWrappingSelected") ? cartDataModel.observable(-1) : productViewModel.giftWrappingSelected;

            var qtyElement, qtyCaseElement, caseDisplayElement, unitQtyElement;
            if(window.isSpotPage){
                qtyElement = container.find('#qty');
            } else{
                qtyElement = container.find('#qty_' + productViewModel['id'] + '_' + productViewModel['catId']);
            }
            if (qtyElement.length) {
                qtyElement[0].setAttribute('data-bind', 'value: getProduct().qty');
            }

            if(window.isSpotPage){
                qtyCaseElement = container.find('#qty_case');
            } else {
                qtyCaseElement = container.find('#qty_case_' + productViewModel['id'] + '_' + productViewModel['catId']);
            }
            if (qtyCaseElement.length) {
                qtyCaseElement[0].setAttribute('data-bind', 'value: getProduct().qtyCase');
            }

            if(window.isSpotPage){
                caseDisplayElement = container.find('#case_display');
            } else {
                caseDisplayElement = container.find('#case_display_' + productViewModel['id'] + '_' + productViewModel['catId']);
            }
            if (caseDisplayElement.length) {
                productViewModel['caseDisplay'] = caseDisplayElement.val();
            }

            if(window.isSpotPage){
                unitQtyElement = container.find('#unit_qty');
            } else {
                unitQtyElement = container.find('#unit_qty_' + productViewModel['id'] + '_' + productViewModel['catId']);
            }
            if (unitQtyElement.length) {
                productViewModel['unitQty'] = unitQtyElement.val();
            }

            const giftWrappingSelectedElement  = container.find('#gift_wrapping_' + productViewModel['id'] + '_' + productViewModel['catId']);
            if (giftWrappingSelectedElement.length) {
                giftWrappingSelectedElement[0].setAttribute('data-bind', 'value: getProduct().giftWrappingSelected');
            }
            productViewModel.qtySelected.subscribe(function (v) {
                if(!window.isSpotPage) {
                    if (v > 0) {
                        setTimeout(function () {
                            if (productViewModel['generatedOptionAndReTrigger'] === false) {
                                productViewModel.qtySelected(v);
                                productViewModel['generatedOptionAndReTrigger'] = true;
                            }
                        });
                        if (!productViewModel.hasOwnProperty("generatedOptionAndReTrigger")) {
                            self.generateSelectOption(productViewModel);
                            productViewModel['generatedOptionAndReTrigger'] = false;
                        }
                    } else {
                        delete productViewModel["generatedOptionAndReTrigger"];
                    }
                }

                if (isNaN(v)) {
                    throw new Error("qty_selected_data_error");
                }
                let qty = parseInt(v);
                if (productViewModel.hasOwnProperty("caseDisplay") && productViewModel["caseDisplay"] === 'cs') {
                    var campaign_id = $('input[name="campaign_id"]').val();
                    if (campaign_id !== undefined && campaign_id.length > 0) {
                        productViewModel.qtyCase(qty);
                    } else {
                        qty = qty * productViewModel.unitQty;
                        productViewModel.qtyCase(qty);
                    }
                }
                productViewModel.qty(qty);
            });

        },

        generateSelectOption: function (productViewModel, elementNeedToGenerate) {
            // IE11 compatible
            if(elementNeedToGenerate === undefined) {
                elementNeedToGenerate = false;
            }
            const elemId = "qty_select_" + productViewModel['id'] + '_' + productViewModel['catId'];
            const qtySelectElem = $("#" + elemId);
            if (!elementNeedToGenerate) {
                elementNeedToGenerate = qtySelectElem;
            }
            if (!elementNeedToGenerate.data('render')) {
                let str = "";
                const maximum = !!qtySelectElem.data('quantity') ? qtySelectElem.data('quantity') : productViewModel['maxQty'];
                const existingValue = [];
                $("#" + elementNeedToGenerate.attr('id') + ' option').each(function () {
                    existingValue.push(parseInt($(this).val()));
                });
                for (let i = 0; i <= maximum; i++) {
                    if (existingValue.indexOf(i) < 0) {
                        str += "<option value='" + i + "'>" + i + "</option>";
                    }
                }
                elementNeedToGenerate.append(str);
                elementNeedToGenerate.data('render', '1');
            }
        },

        mbChangeQty: function (change) {
            var campaign_id = $('input[name="campaign_id"]').val();
            var productSelected = false;
            uiRegistry.set('mbAddToCartTmp', true);
            this.getProduct().qtySelected(parseInt(this.getProduct().qtySelected()) + change);
            if (campaign_id !== undefined && campaign_id.length > 0) {
                $('.action.to-subscription').removeClass('disabled');
                $('.action.to-subscription').prop('disabled', false);
                const formElement = $('#form-validate');
                formElement.find('.multiple-campaign-row-item').each(function () {
                    const divContainer = $(this);
                    if (divContainer.find('input.riki_qty_selected ').val() > 0){
                        productSelected = true;
                    }
                });

                if (!productSelected) {
                    $('.action.to-subscription').addClass('disabled');
                }
            }
            return this;
        },

        mbAddToCart: function () {
            var campaign_id = $('input[name="campaign_id"]').val();
            uiRegistry.set('mbAddToCartTmp', true);
            if (campaign_id !== undefined && campaign_id.length > 0) {
                $('.action.to-subscription').removeClass('disabled');
                $('.action.to-subscription').prop('disabled', false);
            }
            this.getProduct().qtySelected(1);
            return this;
        },

        getCurrentQty: function () {
            return this.getProduct().qty();
        },

        getProduct: function () {
            const self = this;
            return _.find(cartDataModel.getCartProducts(), function (p) {
                return p['catId'] == self.categoryId && p['id'] == self.productId;
            });
        },

    });
});