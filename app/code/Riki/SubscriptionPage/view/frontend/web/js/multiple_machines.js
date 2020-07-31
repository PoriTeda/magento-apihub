define([
    'uiComponent',
    'ko',
    'jquery',
    'mage/url',
    'Magento_Ui/js/modal/modal',
    'Magento_Customer/js/customer-data',
    'mage/translate',
    'domReady!'
], function(Component, ko, $, urlBuilder, modal, customerData, $t) {
    "use strict";
    var popUp = null;
    return Component.extend({
        popUpFormElement:'#wrapper-multiple-machines',
        categories: ko.observableArray(),
        options: {
            courseId: null
        },
        machines: ko.observableArray(),
        productIndex: {
            main: {},
            addition: {},
            machine: {}
        },
        products: [],
        pendingRequest: ko.observable(false),
        isVisible: ko.observable(false),
        skipChooseMachine: ko.observable(false),
        activeAddToCard: ko.observable(false),
        limitMachinesLoad: ko.observable(0),
        machineNotRequired: ko.observable(false),

        scrolled: function(data, event) {
            var elem = event.target;
            if (elem.scrollTop > (elem.scrollHeight - elem.offsetHeight - 100)) {
                this.getItem(data);
            }
        },
        initialize: function () {
            this._super();
            var self = this;

            var options2 = {
                type: 'popup',
                responsive: true,
                innerScroll: true,
                modalClass: 'machine-detail-modal custom_modal',
                buttons: [{
                    text: $.mage.__('Continue'),
                    class: 'btn_class',
                    click: function () {
                        $(".machine_content-item").removeClass('active');
                        $('.machine_content-checkout .btn_checkout').attr('disabled',true);
                        this.closeModal();
                    }
                }]
            };

            modal(options2, $('#popup-machine'));
        },
        getItem: function (data) {
            var typeId = data.type_id;
            var self = this;
            if (!self.pendingRequest() && data.lazyLoad() == true) {
                self.pendingRequest(true);
                if (!data['current_page']) {
                    data['current_page'] = 1;
                }else {
                    data['current_page'] = data['current_page'] + 1;
                }

                var datastring = JSON.stringify(data);
                var params = {
                    request: datastring
                };
                $.ajax({
                    url: urlBuilder.build('/rest/V1/subscription-page/load-more-machine'),
                    method: 'POST',
                    dataType: 'json',
                    contentType: "application/json; charset=utf-8",
                    data: JSON.stringify(params),
                    showLoader : true,
                    global: false,
                    beforeSend: function () {}
                }).done(function (response) {
                    response = JSON.parse(response);
                    if (response.is_valid == false) {
                        var refreshCategories = self.categories();
                        $.each(refreshCategories, function (key, category) {
                            if (category.type_id == typeId) {
                                var listProducts = category.products();
                                listProducts = listProducts.concat(response.data);
                                category.products(listProducts);

                                if (response.data.length == 0 || response.data.length < self.limitMachinesLoad()) {
                                    category.lazyLoad(false);
                                }
                            }
                        });

                        self.categories(refreshCategories);
                        self.pendingRequest(false);
                    }
                });
            }

        },

        getCategories : function() {
            var self = this;
            return self.categories;
        },

        showPopup: function () {
            $("body").trigger("processStop");
            var self = this,
                productSelected = {};
            self.pendingRequest(false);

            $.each(self.productIndex.main, function (k, id) {
                if (!self.products[id]) {
                    return;
                }
                if (!self.products[id].hasOwnProperty('qtySelected')) {
                    return;
                }

                if (self.products[id].qtySelected() > 0) {
                    productSelected[self.products[id].id] = self.products[id].qty();
                }
            });

            $.each(self.productIndex.addition, function (k, id) {
                if (!self.products[id]) {
                    return;
                }
                if (!self.products[id].hasOwnProperty('qtySelected')) {
                    return;
                }

                if (self.products[id].qtySelected() > 0) {
                    productSelected[self.products[id].id] = self.products[id].qty();
                }
            });

            var params = {
                courseId : self.options.courseId,
                selectedMain : productSelected,
                storeId: null
            };

            $("body").trigger("processStart");
            $.ajax({
                url: urlBuilder.build('/rest/V1/subscription-page/automaticallyMachine'),
                method: 'POST',
                dataType: 'json',
                contentType: "application/json; charset=utf-8",
                data: JSON.stringify(params)
            }).done(function (response) {
                response = JSON.parse(response);
                $("body").trigger("processStop");
                if (response.is_valid == false) {
                    var responseData = response.data;
                    self.machineNotRequired(false);
                    self.activeAddToCard(false);

                    ko.utils.arrayForEach(responseData, function (data) {
                        self.limitMachinesLoad(data.limit_machines_load);
                        if (data.products.length == 0 || data.products.length < data.limit_machines_load) {
                            data['lazyLoad'] = ko.observable(false);
                        } else {
                            data['lazyLoad'] = ko.observable(true);
                        }
                        if (data.available == true) {
                            self.activeAddToCard(true);
                        }
                        data['products'] = ko.observableArray(data.products);
                        if(data['type_code'] === 'No machine required'){
                            self.machineNotRequired(true);
                        }
                    });

                    self.categories(responseData);
                    $(".machine_content-item").removeClass('active');
                    if(self.machineNotRequired()){
                        $('.machine_content-checkout .btn_checkout').attr('disabled', false);
                    } else {
                        $('.machine_content-checkout .btn_checkout').attr('disabled', true);
                    }
                    $("#popup-machine").modal("openModal").on('modalclosed',function(){
                        $('input.machine-selected').each(function(){
                            $(this).val(undefined);
                        });
                    });
                    self.skipChooseMachine(response.skip_choose_machine);
                    /* remove message when show popup */
                    customerData.set('messages', {});
                    return;
                }
                if (response.is_valid == true && response.message != null) {
                    customerData.set('messages', {
                        messages: [{
                            type: 'error',
                            text: response.message
                        }]
                    });
                    $("html, body").animate({ scrollTop: 0 }, "slow");
                }
                return;
                // process error message
            });

            return;
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
        initBillProduct: function () {
            var self = this,
                formElement = $('#form-validate');

            var courseIdElement = formElement.find('#riki_course_id');
            if (courseIdElement.length) {
                self.options.courseId = courseIdElement.val();
            }

            var machine = formElement.find('#machine');
            if (machine.length) {
                machine.find('option').each(function () {
                    var viewModel = {};
                    viewModel['catId'] = 0;
                    viewModel['id'] = $(this).val();
                    viewModel['disabled'] = 0;
                    viewModel['name'] = $(this).html();
                    viewModel['finalPriceNumber'] = ko.observable($(this).data('final-price'));

                    viewModel['qty'] = ko.observable(1);
                    viewModel['qtySelected'] = ko.observable(1);
                    viewModel['qtyCase'] = 1;
                    viewModel['unitQty'] = 1;
                    viewModel['caseDisplay'] = 'ea';
                    viewModel['type'] = 'machine';
                    self.products.push(viewModel);
                });
            }
            formElement.find('tr.item').each(function () {
                var trContainer = $(this),
                    viewModel = {};

                viewModel['catId'] = trContainer.data('category-id');
                viewModel['id'] = trContainer.data('product-id');
                viewModel['disabled'] = 0;
                viewModel['caseDisplay'] = 'ea';
                viewModel['unitQty'] = 1;

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
                    if (qtySelectElement.is('select') ) {
                        viewModel['qtySelected'] = ko.observable(qtySelectElement.val());
                    } else {
                        viewModel['qtySelected'] = ko.observable(qtySelectElement.html());
                    }
                } else {
                    viewModel['qtySelected'] = ko.observable(0);
                }

                var qtyElement = trContainer.find('#qty_' + viewModel['id'] + '_' + viewModel['catId']);
                if (qtyElement.length) {
                    viewModel['qty'] = ko.observable(qtyElement.val());
                } else {
                    viewModel['qty'] = ko.observable(0);
                }

                var qtyCaseElement = trContainer.find('#qty_case_' + viewModel['id'] + '_' + viewModel['catId']);
                if (qtyCaseElement.length) {
                    viewModel['qtyCase'] = ko.observable(qtyCaseElement.val());
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

                self.products.push(viewModel);
            });
        },
        handleChange: function (categoryID, productID, available, inStock) {
            if(!available || !inStock || this.machineNotRequired()){
                return;
            }
            $('.machine_content-item[data-category="category_' + categoryID + '"]').addClass('active');
            $('.machine_content-item[data-category="category_' + categoryID + '"]').not('#selected_category_' + categoryID + '_' + productID).removeClass('active');
            $('input[data-category="category_' + categoryID + '"]').attr('value', productID);
            $('.machine_content-checkout .btn_checkout').attr('disabled', false);
        },
        submitMachine: function () {
            var self = this;
            var checkedMachine = this.checkMachineChecked();

            if (!checkedMachine && !self.machineNotRequired()) {
                this.isVisible(true);
                $('.multiple-machinery').animate({ scrollTop: 0 }, "slow");
                return true;
            } else {
                this.isVisible(false);
            }
            $('input.machine-selected').each(function(){
                if(!$(this).val()){
                    $(this).attr('disabled', true);
                }
            });

            $('#form-validate').submit();
        },

        setProducts: function(v) {
            var self = this,
                formElement = $('#form-validate');

            var courseIdElement = formElement.find('#riki_course_id');
            if (courseIdElement.length) {
                self.options.courseId = courseIdElement.val();
            }
            self.products = v;
        },

        switchOption: function(machine_not_required){
            var self = this;
            this.machineNotRequired(machine_not_required);
            if(machine_not_required) {
                $('.machine_content-checkout .btn_checkout').attr('disabled', false);
                $('input.machine-selected').each(function(){
                    $(this).val(undefined);
                });
                $('.machine_content-item').each(function(){
                    $(this).removeClass('active');
                });
            } else{
                if(self.checkMachineChecked()){
                    $('.machine_content-checkout .btn_checkout').attr('disabled', false);
                } else{
                    $('.machine_content-checkout .btn_checkout').attr('disabled', true);
                }
            }
        },

        checkMachineChecked: function(){
            var checkedMachine = false;
            $('.machine_content input[type="hidden"]').each(function () {
                if ($(this).val()) {
                    checkedMachine = true;
                    return true;
                }
            });

            /** skip choose machine again */
            if (this.skipChooseMachine()) {
                checkedMachine = this.skipChooseMachine();
            }

            return checkedMachine;
        }
    });
});