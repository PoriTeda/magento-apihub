define([
    'jquery',
    'ko',
    'mage/url',
    'Riki_SubscriptionPage/js/view/subscription-page-view',
    'Riki_Subscription/js/model/utils',
    'Riki_SubscriptionPage/js/view/qty',
    "Riki_SubscriptionPage/js/view/price",
    'Riki_Theme/js/cart-data-model',
    'Magento_Customer/js/customer-data',
    "Riki_Theme/js/sync-cart-data",
    'mage/translate',
], function (
    $,
    ko,
    urlBuilder,
    Component,
    priceUtils,
    qty,
    price,
    cartDataModel,
    customerData,
    syncCartData,
    $t
) {
    'use strict';

    return Component.extend({
        cartData: 0,
        cartSubtotal: {},
        subtotalNumberHan: ko.observable(0),
        isUpdateHanpukai:true,

        initialize: function (config) {
            this._super();
            var cartData = customerData.get("cart")();
            var quoteItems = cartData.hasOwnProperty("m-minicart-data") ? cartData["m-minicart-data"] : [];
            var rikiCourseIdCurrent = $('#riki_course_id').val();

            if (!_.isEmpty(quoteItems) && parseInt(rikiCourseIdCurrent) != parseInt(cartData.rikiCourseId)) {
                $('.action.tocart').attr('disabled', 'disabled');
            }
            customerData.get("cart").subscribe(
                function (cartData) {
                    var quoteItems = cartData.hasOwnProperty("m-minicart-data") ? cartData["m-minicart-data"] : [];
                    var rikiCourseIdCurrent = $('#riki_course_id').val();

                    if (!_.isEmpty(quoteItems) && parseInt(rikiCourseIdCurrent) != parseInt(cartData.rikiCourseId)) {
                        $('.action.tocart').attr('disabled', 'disabled');
                        const msg = $t("Only one subscription allowed in the shopping cart");
                        syncCartData.showErrorMessage(msg);
                        return false;
                    } else {
                        $('.action.tocart').prop('disabled',false);

                    }
                }
            );

            this.subtotalNumberHan(this.calcSubtotalNumber());
        },


        getCartTotalQty: function (items) {
            var total = 0;
            _.each(items, function (item) {
                total += parseInt(item.currentQty);
            });
            return total;
        },
        getCartSubtotal: function (items) {
            var totalPrice = 0;
            _.each(items, function (item) {
                totalPrice += parseInt(item.currentQty) * parseInt(item.price);
            });
            return totalPrice;
        },

        generateOption: function (item, event) {
            var self = $(event.target);
            if (self.data('render') == '0') {
                var str = "";
                var len = self.data('quantity');
                var minimum = self.data('minimum');
                var i = 10 + minimum;
                var id = self.attr('id');
                var list = [];
                $("#" + id + ' option').each(function () {
                    if($(this).val() >= i){
                        list.push(parseInt($(this).val()));
                    }
                });
                for (i; i <= len; i++) {
                    if(list.indexOf(i) == -1){
                        str += "<option value='" + i + "'>" + i + "</option>";
                    }
                }
                self.append(str);
                self.data('render', '1');
                self.unbind("click");
                self.unbind("touchstart");
            }
            return false;
        },

        initBinding: function () {
            var self = this,
                formElement = $('#form-validate');

            /** reset form onLoad */
            formElement[0].reset();

            var courseIdElement = formElement.find('#riki_course_id');
            if (courseIdElement.length) {
                self.courseId(courseIdElement.val());
            }

            var frequencyElement = formElement.find('#frequency');
            if (frequencyElement.length) {
                self.frequencyId = ko.observable(frequencyElement.val());
            }

            var massQtySelectedElement = formElement.find('#hanpukai_change_set_qty');
            if (massQtySelectedElement.length) {
                self.massQtySelected = ko.observable(massQtySelectedElement.val());
                massQtySelectedElement[0].setAttribute('data-bind', 'value: massQtySelected');
            }
            self.massQtySelected.subscribe(function (newValue) {
                self.preventDefault = true;
                $.each(self.products, function (k, product) {
                    if (product.hasOwnProperty('type') && product.type == 'main') {
                        if (!product.hasOwnProperty('disabled') || !product.disabled) {
                            var qty = product.qtyDefault * newValue;
                            var textQty = "#qty_select_" + product.id + "_" + product.catId;
                            $(textQty).html(qty);
                            product.qtySelected(qty);
                        }
                    }
                });
                self.subtotalNumberHan(self.calcSubtotalNumber());
                self.preventDefault = false;
            });

            var subtotalElement = formElement.find('.total-amount');
            subtotalElement.each(function () {
                $(this)[0].setAttribute('data-bind', 'html: subtotal');
            });
            self.subtotal = ko.pureComputed(function () {
                return priceUtils.getFormattedPrice(self.subtotalNumberHan());
            });

            var machine = formElement.find('#machine');
            if (machine.length) {
                machine.find('option').each(function () {
                    var viewModel = {};
                    viewModel['catId'] = 0;
                    viewModel['id'] = $(this).val();
                    viewModel['disabled'] = 0;
                    viewModel['name'] = $(this).html();
                    viewModel['finalPriceNumber'] = ko.observable($(this).data('final-price'));
                    viewModel['finalPrice'] = ko.observable(priceUtils.getFormattedPrice(viewModel['finalPriceNumber']()));
                    viewModel['finalPriceNumber'].subscribe(function (newValue) {
                        if (this.id == self.machineSelected()) {
                            self.subtotalNumberHan(self.calcSubtotalNumber());
                        }
                        this.finalPrice(priceUtils.getFormattedPrice(newValue));
                    }, viewModel);
                    viewModel['label'] = ko.pureComputed(function() {
                        return this.name + ', ' + this.finalPrice();
                    }, viewModel);
                    viewModel['subtotalNumberHan'] = ko.pureComputed(function() {
                        return this.finalPriceNumber() * this.qty();
                    }, viewModel);
                    viewModel['subtotal'] = ko.pureComputed(function() {
                        return priceUtils.getFormattedPrice(this.subtotalNumberHan());
                    }, viewModel);
                    viewModel['qty'] = ko.observable(1);
                    viewModel['qtySelected'] = ko.observable(1);
                    viewModel['qtyDefault'] = 1;
                    viewModel['qtyCase'] = 1;
                    viewModel['unitQty'] = 1;
                    viewModel['caseDisplay'] = 'ea';
                    viewModel['type'] = 'machine';
                    self.machineOptions().push(viewModel);
                    self.products.push(viewModel);
                });
                self.machineSelected(machine.data('default-value'));
                self.machineSelected.subscribe(function (newValue) {
                    self.subtotalNumberHan(self.calcSubtotalNumber());
                }, self.machineSelected);
            }

            formElement.find('.subscription-row-item').each(function () {
                var trContainer = $(this),
                    viewModel = {},
                    indexOfProduct = self.products.length;

                viewModel['imageUrl'] = trContainer.find('img.product-image-photo').attr('data-src');
                viewModel['name'] = trContainer.find('img.product-image-photo').attr('alt');
                viewModel['catId'] = trContainer.data('category-id');
                viewModel['id'] = trContainer.data('product-id');
                viewModel['disabled'] = 0;
                viewModel['caseDisplay'] = 'ea';
                viewModel['unitQty'] = 1;
                viewModel['maxQty'] = 99;
                viewModel['minQty'] = 1;

                var isAdditionElement = trContainer.find('#is_addition_' + viewModel['id'] + '_' + viewModel['catId']);
                if (isAdditionElement.length) {
                    if (isAdditionElement.val() == 0) {
                        viewModel['type'] = 'main';
                    } else {
                        viewModel['type'] = 'addition';
                    }
                }

                var qtySelectElement = trContainer.find('#qty_select_' + viewModel['id'] + '_' + viewModel['catId']);
                if (qtySelectElement.length) {
                    viewModel['disabled'] = qtySelectElement.prop('disabled');
                    viewModel['qtySelected'] = ko.observable(qtySelectElement.html());
                    qtySelectElement[0].setAttribute('data-bind', 'html: ' + 'products[' + indexOfProduct + '].qtySelected');
                } else {
                    viewModel['qtySelected'] = ko.observable(0);
                }
                viewModel['qtyDefault'] = viewModel['qtySelected']();

                var qtyElement = trContainer.find('#qty_' + viewModel['id'] + '_' + viewModel['catId']);
                if (qtyElement.length) {
                    viewModel['qty'] = ko.observable(qtyElement.val());
                    qtyElement[0].setAttribute('data-bind', 'value: ' + 'products[' + indexOfProduct  + '].qty');
                } else {
                    viewModel['qty'] = ko.observable(0);
                }

                var qtyCaseElement = trContainer.find('#qty_case_' + viewModel['id'] + '_' + viewModel['catId']);
                if (qtyCaseElement.length) {
                    viewModel['qtyCase'] = ko.observable(qtyCaseElement.val());
                    qtyCaseElement[0].setAttribute('data-bind', 'value: ' + 'products[' + indexOfProduct + '].qtyCase');
                } else {
                    viewModel['qtyCase'] = ko.observable(0);
                }

                var caseDisplayElement = trContainer.find('#case_display_' + viewModel['id'] + '_' + viewModel['catId']);
                if (caseDisplayElement.length) {
                    viewModel['caseDisplay'] = caseDisplayElement.val();
                }

                var unitQtyElement = trContainer.find('#unit_qty_' + viewModel['id'] + '_' + viewModel['catId']);
                if (unitQtyElement.length) {
                    viewModel['unitQty'] = unitQtyElement.val();
                }

                viewModel['finalPriceNumber'] = ko.observable(0);
                viewModel['finalPrice'] = ko.pureComputed(function() {
                    return priceUtils.getFormattedPrice(viewModel['finalPriceNumber']());
                }, viewModel);
                viewModel['subtotalNumberHan'] = ko.pureComputed(function() {
                    return viewModel['qtySelected']() * viewModel['finalPriceNumber']();
                }, viewModel);
                viewModel['subtotal'] = ko.pureComputed(function() {
                    return priceUtils.getFormattedPrice(viewModel['subtotalNumberHan']());
                }, viewModel);
                viewModel['qtySelected'].subscribe(function (newValue) {
                    var qty = newValue;
                    if (this.caseDisplay == 'cs') {
                        this.qtyCase(newValue);
                        qty = qty * this.unitQty;
                    }
                    this.qty(qty);
                }, viewModel);
                self.products.push(viewModel);
            });
            $('body.subscription-view-index').addClass('loaded');
        },

        reindex: function () {
            var self = this;
            self.productIndex = {
                main: {},
                addition: {},
                machine: {}
            };
            $.each(self.products, function (k, product) {
                if (!product.hasOwnProperty('type')) {
                    return;
                }
                var index = product.id + '_' + product.qty();
                if (self.productIndex[product.type].hasOwnProperty(index)) {
                    self.productIndex[product.type][index].push(k);
                } else {
                    self.productIndex[product.type][index] = [k];
                }
            });

            window.productIndex = self.productIndex;
        },

        generateCartParams: function () {
            var self = this,
                cartData = JSON.parse(JSON.stringify(self.cartData)),
                selectQty = Number(self.massQtySelected());
            $.each(cartData.product_info, function (k, productData) {
                productData.hanpukai_change_set_qty = selectQty;
                productData.qty = productData.qty * selectQty;
            });

            // add machine data
            if (self.machineSelected()) {
                cartData.product_info.push({
                    product_id: self.machineSelected(),
                    qty: 1,
                    product_type: 'simple',
                    bundle_option_qty: [],
                    hanpukai_change_set_qty: 1,
                    frequency: self.frequencyId(),
                    riki_course_id: self.courseId()
                });
            }

            return cartData;
        },

        getProductByIndex: function (index) {
            var self = this,
                types = ['main', 'addition', 'machine'],
                product = null;
            $.each(types, function (k, type) {
                if (!self.productIndex.hasOwnProperty(type)) {
                    return;
                }

                if (self.productIndex[type].hasOwnProperty(index)) {
                    $.each(self.productIndex[type][index], function (k, id) {
                        if (!self.products[id]) {
                            return;
                        }

                        product = self.products[id];
                    });
                }
            });

            return product;
        },

        calcSubtotalNumber: function () {
            var self = this,
                total = 0,
                key = self.massQtySelected() + '_' + self.machineSelected();

            if (!self.cartSubtotal.hasOwnProperty(key)) {

                if(self.isUpdateHanpukai === false) {
                    return;
                }
                var params = {
                    cartData: JSON.stringify(self.generateCartParams())
                };
                $.ajax({
                    url: urlBuilder.build('rest/V1/subscription-page/cart/emulate'),
                    method: 'POST',
                    dataType: 'json',
                    contentType: "application/json; charset=utf-8",
                    data: JSON.stringify(params),
                    showLoader : true,
                    beforeSend: function () {
                        self.isUpdateHanpukai = false;
                    }
                }).done(function (response) {
                    try {
                        response = JSON.parse(response);
                        if (response.hasOwnProperty('grand_total')) {
                            self.cartSubtotal[key] = response['grand_total'];
                            self.subtotalNumberHan(self.calcSubtotalNumber());
                        }
                        self.isUpdateHanpukai = true;
                    } catch (e) {

                    }
                }).fail(function () {
                }).always(function () {
                    $("#maincontent").trigger("processStop");
                });
            }

            total += self.cartSubtotal[key];

            return total;
        },

        getProductIds: function () {
            var ids = [];
            $.each(this.products, function (k, product) {
                ids.push(product.id + '|' + product.qty());
            });

            return ids;
        },

        refreshPrice: function (options) {
            this.reindex();
            var self = this;
            var courseId = options.courseId || this.courseId();
            var frequencyId = options.frequencyId || this.frequencyId();
            var ids = options.ids || this.getProductIds().join();
            var params = {
                searchCriteria: {
                    filterGroups: [
                        {
                            filters: [
                                {
                                    field: 'id',
                                    conditionType: 'in',
                                    value: ids
                                }
                            ]
                        },
                        {
                            filters: [
                                {
                                    field: 'course_id',
                                    conditionType: 'eq',
                                    value: courseId
                                }
                            ]
                        },
                        {
                            filters: [
                                {
                                    field: 'frequency_id',
                                    conditionType: 'eq',
                                    value: frequencyId
                                }
                            ]
                        }
                    ]
                }
            };
            $.ajax({
                url: urlBuilder.build('rest/V1/catalog/pricebox'),
                method: 'POST',
                dataType: 'json',
                contentType: "application/json; charset=utf-8",
                data: JSON.stringify(params),
                showLoader : true,
                beforeSend: function () {}
            }).done(function (response) {
                $.each(response, function (k, obj) {
                    if (!obj.hasOwnProperty('id') || !obj.hasOwnProperty('qty')) {
                        return;
                    }

                    var types = ['main', 'addition', 'machine'];
                    $.each(types, function (k, type) {
                        if (!self.productIndex.hasOwnProperty(type)) {
                            return;
                        }

                        var key = obj.id + '_' + obj.qty;
                        if (self.productIndex[type].hasOwnProperty(key)) {
                            $.each(self.productIndex[type][key], function (k, id) {
                                if (!self.products[id]) {
                                    return;
                                }
                                var newPrice = obj.final_price * self.products[id].unitQty;
                                if (self.products[id].finalPriceNumber() != newPrice) {
                                    self.products[id].finalPriceNumber(newPrice);
                                }
                            });
                        }
                    });
                });
            }).fail(function () {
            }).always(function () {
            });
        },
        updateQtyHanpukai: function(updateQtyHanpukai,adjustQty) {
            var self = this;
            var qty = parseInt(updateQtyHanpukai()) + parseInt(adjustQty);
            if (qty > 0 && qty <= 30) {
                self.massQtySelected(qty);
            }
            return false;
        }
    });
});