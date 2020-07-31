define([
    'jquery',
    'underscore',
    'ko',
    'mage/url',
    'uiComponent',
    'Riki_Subscription/js/model/utils',
    'Magento_Ui/js/modal/modal',
    'mage/translate',
    'Riki_Theme/js/cart-data-model',
    'Magento_Customer/js/customer-data',
    'Riki_SubscriptionPage/js/view/qty',
    "Riki_SubscriptionPage/js/view/price",
    'Riki_SubscriptionPage/js/view/toolbar',
    'Riki_SubscriptionPage/js/view/product-detail',
    'Riki_SubscriptionPage/js/multiple_machines',
    "uiRegistry",
    "domReady!"
], function (
    $,
    _,
    ko,
    urlBuilder,
    Component,
    priceUtils,
    modal,
    $t,
    cartDataModel,
    customerData,
    qty,
    price,
    toolBar,
    productDetail,
    multiple,
    uiRegistry
) {
    'use strict';
    var popUp = null;
    return Component.extend(_.extend({}, {
        popUpFormElement: '#additional-course-container',
        isFormPopUpVisible: ko.observable(false),
        courseId: ko.observable(0),
        frequencyId: ko.observable(0),
        subtotalNumber: cartDataModel.getCartSubtotal(),
        subtotal: ko.observable(0),
        totalQty: cartDataModel.getCartTotalQty(),
        products: cartDataModel.getCartProducts(),
        productIndex: {
            main: {},
            addition: {},
            machine: {}
        },
        massQtySelected: ko.observable(0),
        preventDefault: false,
        machineOptions: ko.observableArray([]),
        machineSelected: ko.observable(),
        notSubmitForm: true,
        currentSection: ko.observable(''),
        currentSectionName: ko.observable($t('Search subscription products by category button')),
        scrolling: ko.observable(false),
        isGridMode: ko.observable(true),
        isHanpukai: false,

        initialize: function (config) {
            var self = this;
            this._super();
            this['refreshPrice'] = _.debounce(function (options) {
                self.reindex();
                var courseId = options.courseId || self.courseId();
                var frequencyId = options.frequencyId || self.frequencyId();
                var ids = options.ids || self.getProductSelectedIds().join();
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
                    global: false,
                    dataType: 'json',
                    contentType: "application/json; charset=utf-8",
                    headers: {
                        "formKey": $('#form_key').val()
                    },
                    data: JSON.stringify(params),
                    showLoader: true,
                    beforeSend: function () {
                    }
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
                                    if (!self.products[id] || (self.products[id]['isInRealCart'] === true && !cartDataModel.isSameSubPageWithRealCart())) {
                                        return;
                                    }

                                    var newPrice = obj.final_price * self.products[id].unitQty;

                                    if (typeof self.products[id].tierPriceNumber == 'function'
                                        && obj.qty <= 1
                                        && self.products[id].tierPriceNumber() != newPrice
                                    ) {
                                        self.products[id].tierPriceNumber(newPrice)
                                    }

                                    if (self.products[id].finalPriceNumber() != newPrice && !isNaN(newPrice)) {
                                        self.products[id].finalPriceNumber(newPrice);

                                        if (typeof window['tierPriceObj_' + obj.id] == 'object' && window['tierPriceObj_' + obj.id]['hasTierPrice']
                                            && newPrice <= Math.floor(window['tierPriceObj_' + obj.id]['minTierPrice']) * self.products[id].unitQty) {
                                            $('.product-tier-price-' + obj.id).hide();
                                        } else {
                                            $('.product-tier-price-' + obj.id).show();
                                        }
                                    }
                                });
                                cartDataModel.whenChangeQty(false);
                            }
                        });
                    });
                }).fail(function () {
                }).always(function () {
                });
            }, 250);
            this.initBinding();
            var isHanpukai = 1;
            if (typeof (config.cartData) !== 'undefined') {
                isHanpukai = 1;
            } else {
                cartDataModel.setCurrentPageType("subscription")
                    .setCurrentPageId(this.courseId())
                    .mergeQuote(true)
                    .isCartReady(true)
                    .whenChangeQty(false);
            }
            this.reindex();
            this.isFormPopUpVisible.subscribe(function (value) {
                if (value) {
                    self.getAdditionalPopUp().openModal();
                }
            });

            this.checkOffset();
            $(window).scroll(function () {
                self.checkOffset();
            });

            // select machine and frequency from quote
            setTimeout(function () {
                const realCartData = customerData.get("cart")();
                if (realCartData.hasOwnProperty('m-minicart-data') && _.isObject(realCartData['m-minicart-data'])) {
                    const machineProductSelected = _.find(realCartData['m-minicart-data'], function (item) {
                        return item['is_riki_machine'] == '1' && item['currentQty'] > 0;
                    });

                    if (machineProductSelected) {
                        const id = machineProductSelected.hasOwnProperty("entity_id") ? machineProductSelected['entity_id'] : machineProductSelected['id'];
                        if ($(".rk-single-machine").length && id) {
                            setTimeout(function () {
                                self.machineSelected(id);
                            }, 700);
                        }
                    }
                }
                if (realCartData.hasOwnProperty("frequencyId") && !!realCartData['frequencyId']) {
                    if ($("ul#ulFrequency li#" + realCartData['frequencyId']).length) {
                        setTimeout(function () {
                            self.frequencyId(realCartData['frequencyId']);
                        }, 700);
                    }
                } else {
                    self.refreshPrice({});
                }
            });
            //case delete all item minicart set machine and frequency = default
            customerData.get("cart").subscribe(
                function (cartData) {
                    $(window).on('remove.ItemMiniCart', $.proxy(function() {
                        var quoteItems = cartData.hasOwnProperty("m-minicart-data") ? cartData["m-minicart-data"] : [];
                        if (_.isEmpty(quoteItems)) {
                            self.machineSelected(null);
                        }
                        if (cartData.hasOwnProperty("frequencyId") && _.isEmpty(cartData['frequencyId'])) {
                            self.frequencyId(0);
                        }
                    }, this));
                }
            );

            toolBar.prototype.bindingToolbar(self.isGridMode);
            multiple.prototype.setProducts(self.products);
            multiple.prototype.reindex();
            return this;
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
                    if ($(this).val() >= i) {
                        list.push(parseInt($(this).val()));
                    }
                });
                for (i; i <= len; i++) {
                    if (list.indexOf(i) == -1) {
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
                formElement = $('#form-validate'),
                tierPriceIds = [];

            /** reset form onLoad */
            formElement[0].reset();

            var courseIdElement = formElement.find('#riki_course_id');
            if (courseIdElement.length) {
                self.courseId(courseIdElement.val());
            }

            var frequencyElement = formElement.find('#frequency');
            if (frequencyElement.length) {
                self.frequencyId = ko.observable(frequencyElement.val());
                self.frequencyId.subscribe(function (newValue) {
                    self.refreshPrice({
                        frequencyId: newValue,
                        ids : self.getProductIds().join()
                    });
                });
                frequencyElement[0].setAttribute('data-bind', 'value: frequencyId');
            }

            var massQtySelectedElement = formElement.find('#hanpukai_change_set_qty');
            if (massQtySelectedElement.length) {
                self.massQtySelected = ko.observable(massQtySelectedElement.val());
                massQtySelectedElement[0].setAttribute('data-bind', 'value: massQtySelected');
            } else {
                massQtySelectedElement = $('.action-change-qty');
                massQtySelectedElement.each(function () {
                    self.massQtySelected = ko.observable($(this).val());
                    $(this)[0].setAttribute('data-bind', 'value: massQtySelected');
                });
            }
            self.massQtySelected.subscribe(function (newValue) {
                self.preventDefault = true;
                var ids = [];
                $.each(self.products, function (k, product) {
                    if (product.hasOwnProperty('type') && product.type == 'main') {
                        if (!product.hasOwnProperty('disabled') || !product.disabled) {
                            product.qtySelected(parseInt(newValue));
                        }
                        ids.push(product.id + '|' + newValue);
                    }
                });
                if (ids) {
                    self.refreshPrice({
                        ids: ids.join()
                    });
                }
                self.preventDefault = false;
            });

            var subtotalElement = formElement.find('.total-amount');
            subtotalElement.each(function () {
                $(this)[0].setAttribute('data-bind', 'html: subtotal');
            });
            self.subtotal = ko.pureComputed(function () {
                return priceUtils.getFormattedPrice(self.subtotalNumber());
            });

            var machine = formElement.find('#machine');
            if (machine.length) {
                machine.find('option').each(function () {
                    var viewModel = {};
                    viewModel['imageUrl'] = $(this).data('image-url');
                    viewModel['catId'] = 0;
                    viewModel['id'] = $(this).val();
                    viewModel['disabled'] = 0;
                    viewModel['name'] = $(this).html();
                    viewModel['finalPriceNumber'] = ko.observable($(this).data('final-price'));
                    viewModel['finalPrice'] = ko.observable(priceUtils.getFormattedPrice(viewModel['finalPriceNumber']()));
                    viewModel['finalPriceNumber'].subscribe(function (newValue) {
                        this.finalPrice(priceUtils.getFormattedPrice(newValue));
                    }, viewModel);
                    viewModel['label'] = ko.pureComputed(function () {
                        return this.name + ', ' + this.finalPrice();
                    }, viewModel);
                    viewModel['subtotalNumber'] = ko.pureComputed(function () {
                        return this.finalPriceNumber() * this.qty();
                    }, viewModel);
                    viewModel['subtotal'] = ko.pureComputed(function () {
                        return priceUtils.getFormattedPrice(this.subtotalNumber());
                    }, viewModel);
                    viewModel['qty'] = ko.observable(0);
                    viewModel['qtySelected'] = cartDataModel.numberObservable(0, 1, 0, function () {
                        return viewModel['isInRealCart'] !== true && !viewModel.hasOwnProperty('is_multiple_campaign') ? cartDataModel.validateChangeQtySubpage() : true;
                    });
                    viewModel['qtyCase'] = 1;
                    viewModel['unitQty'] = 1;
                    viewModel['maxQty'] = 1;
                    viewModel['caseDisplay'] = 'ea';
                    viewModel['type'] = 'machine';
                    viewModel['gift_wrapping'] = '';
                    viewModel['giftWrappingSelected'] = -1;
                    viewModel['free_item'] = false;

                    viewModel['qtySelected'].subscribe(function (newValue) {
                        if (isNaN(newValue)) {
                            return;
                        }
                        if (newValue > 0) {
                            if (viewModel.id != self.machineSelected()) {
                                return self.machineSelected(viewModel.id);
                            }
                        }
                        viewModel.qty(newValue);
                    });
                    viewModel['qty'].subscribe(function () {
                        cartDataModel.whenChangeQty(false);
                    });
                    self.machineOptions().push(viewModel);
                    self.products.push(viewModel);
                });
                self.machineSelected(machine.data('default-value'));
                self.machineSelected.subscribe(function (newValue) {
                    const realCartData = customerData.get("cart")();
                    uiRegistry.set('mbAddToCartTmp', true);
                    var isRealCart = false;
                    if (realCartData.hasOwnProperty('m-minicart-data') && !_.isEmpty(realCartData['m-minicart-data'])) {
                        isRealCart = true;
                    }
                    if (isRealCart) {
                        if (realCartData.rikiCourseId == cartDataModel.currentPageId) {
                            _.each(cartDataModel.getCartProducts(), function (p) {
                                if (p['type'] == "machine") {
                                    if (p['id'] == newValue) {
                                        p.qtySelected(1);
                                    } else {
                                        p.qtySelected(0);
                                    }
                                }
                            });
                        } else {
                            self.machineSelected(null);
                            cartDataModel.validateChangeQtySubpage();
                        }

                    } else {
                        _.each(cartDataModel.getCartProducts(), function (p) {
                            if (p['type'] == "machine") {
                                if (p['id'] == newValue) {
                                    p.qtySelected(1);
                                } else {
                                    p.qtySelected(0);
                                }
                            }
                        });
                    }

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
                viewModel['giftWrappingSelected'] = -1;
                viewModel['free_item'] = false;

                var isAdditionElement = trContainer.find('#is_addition_' + viewModel['id'] + '_' + viewModel['catId']);
                if (isAdditionElement.length) {
                    if (isAdditionElement.val() == 0) {
                        viewModel['type'] = 'main';
                    } else {
                        viewModel['type'] = 'addition';
                    }
                }

                qty.prototype.assignProductQtyData(viewModel, trContainer);
                price.assignPriceData(viewModel, trContainer);

                viewModel['qtySelected'].subscribe(function (newValue) {
                    if (isNaN(newValue)) {
                        newValue = 0;
                    }
                    var qty = parseInt(newValue);
                    if (this.caseDisplay == 'cs') {
                        this.qtyCase(parseInt(newValue));
                        qty = qty * this.unitQty;
                    }
                    this.qty(qty);

                    if (!self.preventDefault) {
                        if (self.frequencyId() > 0) {
                            self.refreshPrice({
                                ids : self.getProductIds().join()
                            })
                        } else {
                            self.refreshPrice({})
                        }
                    }

                }, viewModel);

                viewModel['finalPrice'] = ko.pureComputed(function () {
                    if (typeof this.tierPriceNumber == 'function') {
                        return priceUtils.getFormattedPrice(this.tierPriceNumber());
                    } else {
                        return priceUtils.getFormattedPrice(this.finalPriceNumber());
                    }
                }, viewModel);

                trContainer.find('[data-product-id]').each(function () {
                    var productContainer = $(this);
                    productContainer.find('[data-price-type="finalPrice"]').each(function () {
                        var priceTypeContainer = $(this);

                        const _price = priceTypeContainer.data('price-amount') ? priceTypeContainer.data('price-amount') : 0;
                        viewModel['finalPriceNumber'](_price);
                        var priceContainer = priceTypeContainer.find('span.price');
                        if (priceContainer.length) {
                            priceContainer.each(function () {
                                $(this)[0].setAttribute('data-bind', 'html: ' + 'products[' + indexOfProduct + '].finalPrice');
                            });
                        } else {
                            priceTypeContainer[0].setAttribute('data-bind', 'html: ' + 'products[' + indexOfProduct + '].finalPrice');
                        }

                    });
                });

                if (typeof window['tierPriceObj_' + viewModel['id']] == 'object') {
                    if (window['tierPriceObj_' + viewModel['id']]['hasTierPrice']) {
                        viewModel['tierPriceNumber'] = ko.observable(viewModel['finalPriceNumber']());
                        tierPriceIds.push(viewModel['id'] + '|' + 1);
                    }
                }

                var subtotalElement = trContainer.find('#subtotal_item_' + viewModel['id'] + '_' + viewModel['catId']);
                if (subtotalElement.length) {
                    subtotalElement[0].setAttribute('data-bind', 'html: ' + 'products[' + indexOfProduct + '].subtotal');
                }

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
                var keys = [
                    product.id + '_' + product.qty()
                ];
                if (typeof product.tierPriceNumber == 'function') {
                    keys.push(product.id + '_' + 1);
                }
                $.each(keys, function (i, index) {
                    if (self.productIndex[product.type].hasOwnProperty(index)) {
                        self.productIndex[product.type][index].push(k);
                    } else {
                        self.productIndex[product.type][index] = [k];
                    }
                });
            });
        },

        calcSubtotalNumber: function () {
            var self = this,
                total = 0;
            $.each(self.products, function (k, product) {
                if (!product.hasOwnProperty('type')) {
                    return;
                }
                if (product.type == 'main' || product.type == 'addition') {
                    total += product.subtotalNumber();
                } else if (product.type == 'machine' && product.id == self.machineSelected()) {
                    total += product.subtotalNumber();
                }
            });
            return total;
        },

        getProductIds: function () {
            var ids = [];
            $.each(this.products, function (k, product) {
                if (typeof product.tierPriceNumber == 'function'
                    && product.qty() > 1
                ) {
                    ids.push(product.id + '|' + 1);
                }
                ids.push(product.id + '|' + product.qty());
            });

            return ids;
        },

        getProductSelectedIds: function () {
            var ids = [];
            $.each(this.products, function (k, product) {
                if(product.qty() > 0) {
                    if (typeof product.tierPriceNumber == 'function'
                        && product.qty() > 1
                    ) {
                        ids.push(product.id + '|' + 1);
                    }
                    ids.push(product.id + '|' + product.qty());
                }
            });

            return ids;
        },

        showFormPopUp: function () {
            this.isFormPopUpVisible(true);
        },

        getAdditionalPopUp: function () {
            var self = this;
            if (!popUp && $(self.popUpFormElement).length) {
                self.options = {
                    type: 'popup',
                    responsive: false,
                    innerScroll: false,
                    clickableOverlay: false,
                    modalClass: 'add-another-course subscription-additional-view-index',
                    title: $t('Add another course'),
                    buttons: [],
                    closed: function () {
                        $('body').css('position', 'relative');
                        self.isFormPopUpVisible(false);
                        if (self.notSubmitForm) {
                            self.onBackMainPage();
                        }
                    },
                    opened: function () {
                        $('body').css('position', 'fixed');
                        self.stickyActionsToolbar();
                    }
                };
                popUp = modal(self.options, $(self.popUpFormElement));
                $('.add-another-course.subscription-additional-view-index').scroll(function () {
                    self.stickyActionsToolbar();
                });
                $('#add-another-course-popup input.toggle-checkbox').on('change', function () {
                    setTimeout(function () {
                        self.stickyActionsToolbar();
                    }, 300);
                });
                $(window).resize(function () {
                    self.stickyActionsToolbar();
                })
            }
            return popUp;
        },

        onRedirectAddition: function () {
            var self = this,
                productSelectedMainQtyIds = [],
                ids = [];
            $.each(self.productIndex.main, function (k, id) {
                if (!self.products[id]) {
                    return;
                }
                if (!self.products[id].hasOwnProperty('qtySelected')) {
                    return;
                }

                if (self.products[id].qtySelected() > 0) {
                    productSelectedMainQtyIds.push(self.products[id].id);
                }
            });

            var params = {
                courseId: self.courseId(),
                frequencyId: self.frequencyId(),
                selectedMain: productSelectedMainQtyIds
            };

            $("body").trigger("processStart");

            $.ajax({
                url: urlBuilder.build('/rest/V1/subscription-page/validateAdditionalCat'),
                method: 'POST',
                dataType: 'json',
                contentType: "application/json; charset=utf-8",
                data: JSON.stringify(params),
                showLoader: true,
                beforeSend: function () {
                }
            }).done(function (response) {
                response = JSON.parse(response);
                $("body").trigger("processStop");
                if (response.is_valid == false) {
                    if (response.message != '') {
                        if (!$('#frequency').parent().find('#frequency-error').length) {
                            $('#frequency').after("<div for='frequency' generated='true' class='mage-error' id='frequency-error'>" + response.message + "</div>");
                            $('#frequency-error').delay(15000).hide('slow');
                        } else if ($('#frequency').parent().find('#frequency-error').length && !$('#frequency').parent().find('#frequency-error').is(':visible')) {
                            $('#frequency-error').show();
                            $('#frequency-error').delay(15000).hide('slow');
                        }
                    }
                    $("html, body").animate({scrollTop: 0}, "slow");
                } else {
                    if ($('#form-validate #additional-course-container').length && popUp) {
                        $('#form-validate #additional-course-container').remove();
                    }

                    $.each(self.productIndex.addition, function (k, id) {
                        if (!self.products[id]) {
                            return;
                        }
                        if (!self.products[id].hasOwnProperty('qtySelected')) {
                            return;
                        }
                        if (self.products[id].qtySelected() > 0) {
                            self.products[id].qtySelected(0);
                        }
                        ids.push(self.products[id]["id"] + '|' + 0);
                    });

                    if (ids) {
                        self.refreshPrice({
                            ids: ids.join()
                        })
                    }
                    self.showFormPopUp();
                }
            });
        },

        onBackMainPage: function () {
            var self = this,
                ids = [];
            $("body").trigger("processStart");
            $.each(self.productIndex.addition, function (k, id) {
                if (!self.products[id]) {
                    return;
                }
                if (!self.products[id].hasOwnProperty('qtySelected')) {
                    return;
                }
                self.preventDefault = true;
                if (self.products[id].qtySelected() > 0) {
                    self.products[id].qtySelected(0);
                }

                ids.push(self.products[id]["id"] + '|' + 0);

                self.preventDefault = false;
            });
            if (ids) {
                self.refreshPrice({
                    ids: ids.join()
                })
            }
            $("body").trigger("processStop");
        },

        submitAdditionalForm: function () {
            var additionalClone = $('.subscription-additional-view-index #additional-course-container').detach();
            // additionalClone.find('input').each(function () {
            //     var name = $(this).attr('name');
            //     $(this).val($('.subscription-additional-view-index #additional-course-container').find('input[name="' + name + '"]').val());
            // });
            $('#main-course-container').append(additionalClone);
            additionalClone.hide();
            this.notSubmitForm = false;
            this.getAdditionalPopUp().closeModal();
            popUp = null;
            $('#form-validate').submit();
        },

        stickyActionsToolbar: function () {
            var curPoint = $(window).scrollTop();
            var stopPoint = $('#add-another-course-popup .check-offset').offset().top;
            var windowHeight = $(window).height();
            var additionHeight = $('#add-another-course-popup .actions-toolbar').outerHeight();
            var cssBottom = stopPoint - curPoint - windowHeight + additionHeight;
            cssBottom = (cssBottom < 0) ? 0 : cssBottom;
            $('#add-another-course-popup').css('padding-bottom', additionHeight + 'px');
            $('#add-another-course-popup .actions-toolbar').css('bottom', cssBottom + 'px');
        },

        checkOffset: function () {
            if (!this.scrolling()) {
                var top = $(document).scrollTop() + $(window).height() / 4,
                    self = this,
                    optionValue = "";

                $('.category-container:visible').each(function () {
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
                    self.currentSectionName($t('Search subscription products by category button'));
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
                    viewModel.currentSectionName($t('Search subscription products by category button'));
                    viewModel.scrolling(false);
                });
            }
        },
        calcQty: function () {
            return;
            var total = 0;
            const self = this;
            $.each(this.products, function (k, product) {
                if (!product.hasOwnProperty('type')) {
                    return;
                }
                if (product.type === 'main' || product.type === 'addition') {
                    total += parseInt(product.qtySelected());
                } else if (product.type === 'machine' && product.id === self.machineSelected()) {
                    total += parseInt(product.qtySelected());
                }
            });
            return total;
        },
        moveToTop: function(data, e){
            e.preventDefault();
            $('html,body').animate({
                scrollTop: 0
            }, 700);
        }

    }, toolBar.prototype.extendObject(), productDetail.prototype.extendObject()));

});