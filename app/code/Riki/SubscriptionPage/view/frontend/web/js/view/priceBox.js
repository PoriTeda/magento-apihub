define([
    'jquery',
    'ko',
    'uiComponent',
    'mage/storage',
    'mage/loader',
    'mage/url',
    'Riki_Subscription/js/model/utils',
    'Magento_Ui/js/model/messageList',
    'mage/translate'
], function ($, ko, Component, storage, loader, urlBuilder, utils, messageList,$t) {

    return Component.extend({
        productIds: [],
        productCatIds: [],
        productQtyIds: [],
        machineIds: [],
        isSetAllQty: false,
        isFirstLoad: true,
        isSubscriptionHanpukai: 0,

        initialize: function (config) {
            var self = this;
            self.productIds = config.productIds.split(',');
            self.productCatIds = config.productCatIds.split(','),
            self.productMainCatIds = config.productMainCatIds.split(','),
            self.productAdditionCatIds = config.productAdditionCatIds.split(','),
            self.machineIds = config.machineConfig.machineIds.split(',');
            self.isSubscriptionHanpukai = config.isSubscriptionHanpukai;
            self.machineData = ko.observable(config.machineConfig.machineData);
            self.selectedMachine = config.machineConfig.machineSelected;
            self.selectedPrice = ko.computed(function () {
                var price = 0;
                ko.utils.arrayForEach(self.machineData(), function (item) {
                    if (item.id == self.selectedMachine) {
                        price = item.price;
                        return;
                    }
                });
                return price;
            });
            self.selectPrice = ko.observable(self.selectedPrice());

            $.each(self.productIds, function (id, val) {
                var prop = 'product_price_' + val;
                self[prop] = ko.observable($('#product_price_' + val).html());
            });

            $.each(self.productCatIds, function (id, val) {
                var qtySelected = 'qty_selected_' + val + '';
                self[qtySelected] = ko.observable(0);
                var qtyCaseSelected = 'qty_case_selected_' + val + '';
                self[qtyCaseSelected] = ko.observable(0);
                var caseDisplay = 'case_display_' + val + '';
                self[caseDisplay] = ko.observable('');
                var subtotalItem = 'subtotal_item_' + val + '';
                self[subtotalItem] = ko.observable('');
                var unitCase = 'unit_case_' + val + '';
                self[unitCase] = ko.observable(false);
                var unitQty = 'unit_qty_' + val + '';
                self[unitQty] = ko.observable(true);
                var unitQtyValue = 'unit_qty_value_' + val + '';
                self[unitQtyValue] = ko.observable($('#unit_qty_value_'+ val + '').val());
                var productHanpukaiQty = 'product_hanpukai_qty_' + val + '';
                self[productHanpukaiQty] = ko.observable('');
                var productHanpukaiQtyCase = 'product_hanpukai_qty_case_' + val + '';
                self[productHanpukaiQtyCase] = ko.observable('');
                var productHanpukaiQtySelected = 'product_hanpukai_qty_selected_' + val + '';
                self[productHanpukaiQtySelected] = ko.observable('');
            });
            self['total_amount'] = ko.observable(0);
            self['change_all_qty'] = ko.observable(0);
            self['change_all_qty_hanpukai'] = ko.observable(0);

        },

        refreshPieceCase : function (item, event) {
            //change when choose unit from dropdown => change qty of case
            var self = this;


            var caseDisplay = $(event.target).attr('id');
            var unitCase = self[caseDisplay]();

            var catProductId = caseDisplay.replace('case_display_','');

            if('ea' == unitCase){
                self['unit_qty_'+catProductId](true);
                self['unit_case_'+catProductId](false);
            }
            else
            if('cs' == unitCase){
                self['unit_case_'+catProductId](true);
                self['unit_qty_'+catProductId](false);

                var qtyCaseSelected = 'qty_case_selected_'+catProductId;
                var qtyCaseSelectedValue = self[qtyCaseSelected]();

                var unitQtyValueKey = 'unit_qty_value_'+catProductId;
                var unitQtyValue = self[unitQtyValueKey]();
                var totalQtyCaseSelected = qtyCaseSelectedValue* unitQtyValue;
                self['qty_case_selected_'+catProductId](totalQtyCaseSelected);
            }
        },

        refreshPriceCase:function(item, event){
            //change when change qty of case => change qty of piece
            var self = this;

            var catProductId = $(event.target).attr('id');

            var qtyCaseSelected = 'qty_case_selected_'+catProductId;
            var qtyCaseSelectedValue = self[qtyCaseSelected]();

            var caseDisplay = 'case_display_'+catProductId;
            var unitCase = self[caseDisplay]();

            var unitQtyValueKey = 'unit_qty_value_'+catProductId;
            var unitQtyValue = self[unitQtyValueKey]();

            if('cs' == unitCase){
                var totalQtySelected = (qtyCaseSelectedValue * unitQtyValue);
                self['qty_selected_'+catProductId](totalQtySelected);
            }
        },
        refreshPrice: function (item, event) {
            //change when change qty of piece
            var self = this,
                courseId = $('#riki_course_id').val(),
                frequencyId = $('#frequency').val(),
                iProfileId = $('#profile_id').val();

            if(this.isFirstLoad == true){
                if($(event.target).attr('id').indexOf(self.productCatIds[self.productCatIds.length-1]) != -1){
                    this.isFirstLoad = false;
                }
                return;
            }

            self.productQtyIds = [];
            var iListItemId= [];

            if(true == this.isSetAllQty){
                return;
            }

            if(typeof  event !== 'undefined'){

                var keyProductId = $(event.target).attr('id').replace('qty_','');

                var qtySelected = 'qty_selected_'+keyProductId;
                var qtySelectedValue = self[qtySelected]();

                self.productQtyIds.push(qtySelectedValue);
                iListItemId.push(keyProductId);

                var params = JSON.stringify({
                    courseId: courseId ? courseId : 0,
                    frequencyId: frequencyId ? frequencyId : 0,
                    iProfileId: iProfileId ? iProfileId : 0,
                    productCatIds: iListItemId ? iListItemId : [],
                    productQtyIds: self.productQtyIds ? self.productQtyIds : []
                });


                storage.post(urlBuilder.build('rest/V1/subscription-page/getPriceItem'), params)
                    .done(function (response) {
                        response = JSON.parse(response);
                        $.each(response, function (key, val) {

                            if(key.indexOf('subtotal_item_') != -1){

                                var keyProductId = key.replace('subtotal_item_','');
                                var oldSubtotalItem = Number(self['subtotal_item_'+keyProductId]().replace(/(<([^>]+)>)/ig,"").replace(/[^0-9\.]+/g,""));

                                var newSubtotalItem = Number(val.replace(/(<([^>]+)>)/ig,"").replace(/[^0-9\.]+/g,""));

                                if(self['total_amount']() != 0){

                                    var totalAmount = Number(self['total_amount']().replace(/(<([^>]+)>)/ig,"").replace(/[^0-9\.]+/g,""));
                                    self['total_amount'](utils.getFormattedPrice(totalAmount + newSubtotalItem - oldSubtotalItem));
                                }
                            }
                            self[key](val);
                        })
                    });
            }
            else{

                $.each(self, function (key, val) {
                    if(key.indexOf('qty_selected_') == 0){

                        var qtySelected = key.toString();
                        var qtySelectedValue = self[qtySelected]();

                        self.productQtyIds.push(qtySelectedValue);
                    }
                });

                var params = JSON.stringify({
                    courseId: courseId ? courseId : 0,
                    frequencyId: frequencyId ? frequencyId : 0,
                    iProfileId: iProfileId ? iProfileId : 0,
                    productCatIds: self.productCatIds ? self.productCatIds : [],
                    productQtyIds: self.productQtyIds ? self.productQtyIds : [],
                    selectedMachineId: self.selectedMachine ? self.selectedMachine : 0
                });


                storage
                    .post(urlBuilder.build('rest/V1/subscription-page/priceBox'), params)
                    .done(function (response) {
                        response = JSON.parse(response);
                        $.each(response, function (key, val) {
                            self[key](val);
                        })
                    });
            }

        },
        updateMachine: function(){
            //change when change qty of piece
            var self = this,
                courseId = $('#riki_course_id').val(),
                frequencyId = $('#frequency').val();

            var machineParams = JSON.stringify({
                courseId: courseId ? courseId : 0,
                frequencyId: frequencyId ? frequencyId : 0,
                machineIds: self.machineIds ? self.machineIds : []
            });

            storage
                .post(urlBuilder.build('rest/V1/subscription-page/getListMachines'), machineParams)
                .done(function (response) {
                    response = JSON.parse(response);
                    self.machineData(response);
                    $("#maincontent").trigger("processStop");
                });
        },
        changeFrequency: function(){
            $("#maincontent").trigger("processStart");
            this.updateMachine();
            this.refreshPrice();
        },
        setOptionPrice: function(option, item){
            // set price data to selectbox
            if (item) {
                $(option).attr('data-price', item.price);
            } else {
                $(option).attr('data-price', 0);
            }
        },
        changeMachine: function(data){
            var selectedOption = ko.computed(function () {
                var selected = null;
                ko.utils.arrayForEach(data.machineData(), function (item) {
                    if (item.id == data.selectedMachine) {
                        selected = item;
                        return;
                    }
                });
                return selected;
            });

            var oldPrice = Number(this.total_amount().replace(/(<([^>]+)>)/ig,"").replace(/[^0-9\.]+/g,""));

            if (selectedOption()) {
                var machinePrice = selectedOption().price;
                var newPrice = oldPrice - this.selectPrice() + machinePrice;
                this.selectPrice(machinePrice);
            } else {
                var newPrice = oldPrice - this.selectPrice();
                this.selectPrice(0);
            }

            this.total_amount(utils.getFormattedPrice(newPrice));
        },
        refreshAllQty : function(){
            var self = this,
                courseId = $('#riki_course_id').val(),
                frequencyId = $('#frequency').val(),
                iProfileId = $('#profile_id').val();

            self.productAllQtyIds = [];

            var changeAllQty = self['change_all_qty']();

            this.isSetAllQty = true;

            $.each(self, function (key, val) {
                if(key.indexOf('case_display_') != -1){

                    var caseDisplay = key.toString();
                    var unitCase = self[caseDisplay]();

                    var catProductId = caseDisplay.replace('case_display_','');

                    if(this.isFirstLoad  || (!this.isFirstLoad  && self.productMainCatIds.indexOf(catProductId) != -1)){

                        var qtySelected = 'qty_selected_'+catProductId;

                        if('cs' == unitCase){
                            self['qty_case_selected_'+catProductId](changeAllQty);

                            var unitQtyValueKey = 'unit_qty_value_'+catProductId;
                            var unitQtyValue = self[unitQtyValueKey]();

                            var qtyCaseSelected = 'qty_case_selected_'+catProductId;
                            var qtyCaseSelectedValue = self[qtyCaseSelected]();

                            if(changeAllQty == qtyCaseSelectedValue){

                                if($('#'+catProductId).attr('disabled') == 'disabled'){
                                    self['qty_case_selected_'+catProductId](0);
                                    self.productAllQtyIds.push(0);
                                } else {
                                    self['qty_case_selected_' + catProductId](changeAllQty);
                                    var changeAllQtyUnitQtyValue = changeAllQty * unitQtyValue;
                                    self.productAllQtyIds.push(changeAllQtyUnitQtyValue);
                                }
                            }
                            else{
                                self['qty_case_selected_'+catProductId](0);
                                self.productAllQtyIds.push(0);
                            }
                        }
                        else{
                            self['qty_selected_'+catProductId](changeAllQty);

                            var qtySelected = 'qty_selected_'+catProductId;
                            var qtySelectedValue = self[qtySelected]();

                            if(changeAllQty == qtySelectedValue){

                                if($('#'+catProductId).attr('disabled') == 'disabled'){
                                    self['qty_selected_'+catProductId](0);
                                    self.productAllQtyIds.push(0);
                                } else {
                                    self['qty_selected_' + catProductId](changeAllQty);
                                    self.productAllQtyIds.push(changeAllQty);
                                }
                            }
                            else{
                                self['qty_selected_'+catProductId](0);
                                self.productAllQtyIds.push(0);
                            }
                        }
                    }
                }
            });

            this.isSetAllQty = false;

            var params = JSON.stringify({
                courseId: courseId ? courseId : 0,
                frequencyId: frequencyId ? frequencyId : 0,
                iProfileId: iProfileId ? iProfileId : 0,
                productCatIds: this.isFirstLoad ? self.productCatIds : self.productMainCatIds,
                productQtyIds: self.productAllQtyIds ? self.productAllQtyIds : [],
                selectedMachineId: self.selectedMachine ? self.selectedMachine : 0
            });

            storage
                .post(urlBuilder.build('rest/V1/subscription-page/priceBox'), params)
                .done(function (response) {
                    response = JSON.parse(response);
                    $.each(response, function (key, val) {
                        self[key](val);
                    })
                });
        }
        ,refreshAllQtyForHapukai : function(){
            var self = this;
            courseId = $('#riki_course_id').val(),
                frequencyId = $('#frequency').val();
            var qtyChange = self['change_all_qty_hanpukai']();
            self.productAllQtyIds = [];
            this.isSetAllQty = true;
            var params = JSON.stringify({
                courseId: courseId ? courseId : 0,
                frequencyId: frequencyId ? frequencyId : 0,
                productCatIds: self.productCatIds ? self.productCatIds : [],
                qtyChangeAll: qtyChange
            });

            storage
                .post(urlBuilder.build('rest/V1/subscription-page/changeHanpukaiQty'), params)
                .done(function (response) {
                    response = JSON.parse(response);
                    $.each(response, function (key, val) {
                        self[key](val);
                    })
                });
        }
        ,addProductToProfile : function(item, event){

            var self = this;

            var productId   = $(event.target).attr('product_id');
            var profileId   = $('#riki_profile_id').val();
            var urlAddProductCart         = $('#riki_url_add_product_profile').val();

            var catProductId     = $(event.target).attr('id');
            var catProductId     = catProductId.replace('addproduct_','');
            var caseDisplayCatProductId = 'case_display_'+catProductId;
            var unitCase = self[caseDisplayCatProductId]();

            var qtySelectedValue = 0 ;
            var qtyCaseSelectedValue = 0 ;

            var unitQtyValueKey = 'unit_qty_value_'+catProductId;
            var unitQtyValue = self[unitQtyValueKey]();

            if ('cs' == unitCase) {
                var qtyCaseSelectedCatProductId = 'qty_case_selected_'+catProductId;
                qtyCaseSelectedValue = self[qtyCaseSelectedCatProductId]();
                qtySelectedValue = qtyCaseSelectedValue * unitQtyValue;
            }
            else {
                var qtySelectedCatProductId = 'qty_selected_'+catProductId;
                qtyCaseSelectedValue = self[qtySelectedCatProductId]();
                qtySelectedValue = qtyCaseSelectedValue;
            }


            var data = [];
            data.push({
                'product_id': productId,
                'profile_id': profileId,
                'product_qty':  qtySelectedValue,
                'product_case':  qtyCaseSelectedValue,
                'unit_case':  unitCase,
                'unit_qty':  unitQtyValue,
                'is_addition' : 1
            });
            var payload = JSON.stringify(data);
            return $.ajax({
                    url: urlAddProductCart,
                    data: payload,
                    type: 'POST',
                    dataType: 'json',
                    context: this,
                    showLoader : true
                })
                .done(function(response) {
                    location.reload();
                })
                .fail(function(error) {
                    location.reload();
                });

        }
    });
});