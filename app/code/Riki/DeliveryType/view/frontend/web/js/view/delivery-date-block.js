define([
    'underscore',
    'jquery',
    'ko',
    'uiComponent',
    'Magento_Checkout/js/model/quote',
    'Magento_Checkout/js/checkout-data',
    'Magento_Customer/js/customer-data',
    'mage/url',
    'uiRegistry',
    'Magento_Catalog/js/price-utils',
    'Magento_Ui/js/model/messageList',
    'Magento_Checkout/js/model/full-screen-loader',
    'mage/translate',
    'Magento_Checkout/js/model/payment/method-converter',
    'Magento_Checkout/js/model/payment-service',
    'Magento_Theme/js/datepicker-ja'
], function (
    _,
    $,
    ko,
    Component,
    quote,
    checkoutData,
    customerData,
    urlBuilder,
    uiRegistry,
    priceUtils,
    messageList,
    fullScreenLoader,
    $t,
    methodConverter,
    paymentService
) {
    'use strict';
    return Component.extend({
        defaults: {
            template: 'Riki_DeliveryType/delivery-date-block'
        },
        visible: ko.observable(!quote.isVirtual()),
        options: {url: {
            remove: urlBuilder.build('checkout/sidebar/removeItem'),
            update: urlBuilder.build('riki-checkout/sidebar/updateItemQtyCustom'),
            updateGiftWrapping: urlBuilder.build('multicheckout/update/wrapping')
        }},
        initialize: function () {
            this._super();
            var self = this;
            self.isSubscription = true;
            self.deliveryTypes = ko.observableArray([]);
            self.hanpukaiChangeQtySelect = ko.observableArray([1,2,3,4,5]);

            /* if quote if virtual do no thing */
            if(quote.isVirtual()) {
                return this;
            }

            quote.shippingAddress.subscribe(function(address) {
                var type = address.getType(),
                    addressId = '',
                    postcode = '',
                    serviceUrl = urlBuilder.build('deliverytype/delivery/shippingaddress');

                /** Hide Edit Address Button when user is CNC or CIS member */
                if(self.isCISCNCMember()) {
                    var visibleEditAddressButton = true;
                    if(type != 'new-customer-address' && address.customAttributes.riki_type_address.value != 'shipping') {
                        visibleEditAddressButton = false;
                    }
                    uiRegistry.get('checkout.steps.shipping-step.shippingAddress.address-list', function(addressListBlock) {
                        addressListBlock.visibleEditAddressButton(visibleEditAddressButton);
                    })
                }
                if(type == 'new-customer-address') {
                    addressId = address.regionId;
                    postcode = address.postcode;
                }else {
                    addressId = address.customerAddressId;
                }
                uiRegistry.get('checkout.steps.shipping-step.shippingAddress.address-list' , function (addressListBlock){
                    var addressData = $.extend(true, {}, address);
                    var apartment = '';
                    if (typeof addressData.customAttributes.apartment != "undefined") {
                        apartment = addressData.customAttributes.apartment.value;
                    }
                    addressData.apartment = apartment;
                    addressListBlock.addressSelected(addressData);
                });
                uiRegistry.get('checkout.steps.confirm-info-step' , function (singleConfirm){
                    singleConfirm.shippingAddressConfirm(address);
                });

                uiRegistry.get('checkout.steps.billing-step.payment.payments-list' , function (element){
                    element.isPlaceOrderActionAllowedFromSelectAddress(true);
                });

                //ajax get delivery day
                $.ajax({
                    url: serviceUrl,
                    type: "POST",
                    global: false,
                    dataType: 'json',
                    data: {customerAddressType: type, customerAddress : addressId, customerAddressPostcode : postcode},
                    beforeSend: function() {
                        fullScreenLoader.startLoader();
                    },
                    success: function (data) {
                        if(!data.error) {

                            uiRegistry.get('checkout.steps.billing-step.payment.payments-list' , function (element){
                                element.isPlaceOrderActionAllowedFromSelectAddress(true);
                            });

                            var cartItemData = data['cart_item_data'];
                            self.deliveryTypes.removeAll();
                            var deliveryInfoLayoutTmp = [],
                                paymentMethods = [],
                                allGiftWrappingItems = window.checkoutConfig.giftWrapping.designsInfo;

                            data['payment_methods'].forEach(function (paymentMethod, index) {
                                paymentMethods[index] = {method:paymentMethod['code'], title: paymentMethod['title']}
                            });

                            paymentService.setPaymentMethods(methodConverter(paymentMethods));

                            for (var i=0 ; i < cartItemData.length; i++) {
                                var cartItems = [],
                                    cartItemsTmp = [],
                                    cartCount = 0;
                                cartItemsTmp['deliveryType'] = cartItemData[i].name;
                                cartItemsTmp['only_dm'] = cartItemData[i].name == "direct_mail" ? 1 : 0;
                                cartItemsTmp['cartItems'] = [];
                                for(var j=0; j <cartItemData[i].cartItems.length; j++) {
                                    for(var k=0; k < window.checkoutConfig.quoteItemData.length; k++) {
                                        if(window.checkoutConfig.quoteItemData[k].item_id == cartItemData[i].cartItems[j]) {
                                            var item = [];
                                            var item_id = window.checkoutConfig.quoteItemData[k].item_id;
                                            item['free_product'] = window.checkoutConfig.quoteItemData[k].free_item;
                                            item['item_id'] = item_id;
                                            item['product_id'] = window.checkoutConfig.quoteItemData[k].product_id;
                                            item['name'] = window.checkoutConfig.quoteItemData[k].name;
                                            item['thumbnail'] = window.checkoutConfig.quoteItemData[k].thumbnail;
                                            item['product_stock_class'] = window.checkoutConfig.quoteItemData[k].product_stock_class;
                                            item['product_stock_message'] = window.checkoutConfig.quoteItemData[k].product_stock_message;
                                            item['price'] = priceUtils.formatPrice(window.checkoutConfig.quoteItemData[k].price_incl_tax, window.checkoutConfig.priceFormat);

                                            item['visibleInCart'] = window.checkoutConfig.quoteItemData[k].visibleInCart;

                                            if(typeof cartItemData[i].items_error_messages != 'undefined' && item_id in cartItemData[i].items_error_messages){
                                                item['error_message'] = cartItemData[i].items_error_messages[item_id];

                                                if (!item['free_product']) {
                                                    uiRegistry.get('checkout.steps.billing-step.payment.payments-list' , function (element){
                                                        element.isPlaceOrderActionAllowedFromSelectAddress(false);
                                                    });
                                                }

                                            }else{
                                                item['error_message'] = null;
                                            }

                                            if(typeof cartItemData[i].machine_oos_messages != 'undefined' && item_id in cartItemData[i].machine_oos_messages){
                                                item['machine_oos_messages'] = cartItemData[i].machine_oos_messages[item_id];
                                            }else{
                                                item['machine_oos_messages'] = null;
                                            }

                                            item['qty'] = window.checkoutConfig.quoteItemData[k].qty;
                                            item['unit_case'] = window.checkoutConfig.quoteItemData[k].unit_case;
                                            item['unit_qty'] = window.checkoutConfig.quoteItemData[k].unit_qty;
                                            item['unit_case_ea'] = $t('CS');
                                            item['qty_case'] = window.checkoutConfig.quoteItemData[k].qty / window.checkoutConfig.quoteItemData[k].unit_qty;
                                            item['is_riki_machine'] = window.checkoutConfig.quoteItemData[k].is_riki_machine;
                                            item['min_sale_qty'] = window.checkoutConfig.quoteItemData[k].min_sale_qty;
                                            item['max_sale_qty'] = window.checkoutConfig.quoteItemData[k].max_sale_qty;
                                            item['price_html'] = '';
                                            item['price_final'] = '';
                                            var isSpecialPrice = window.checkoutConfig.quoteItemData[k].product_final_price < window.checkoutConfig.quoteItemData[k].product_regular_price ? 'special-price' : '';

                                            if('CS' == item['unit_case']){
                                                var unitQty = (null === item['unit_qty'])?1:item['unit_qty'];
                                                item['price_html'] = '<span class="price '+ isSpecialPrice +'">'+ priceUtils.formatPrice(window.checkoutConfig.quoteItemData[k].price_incl_tax*unitQty, window.checkoutConfig.priceFormat) +'</span>';
                                                item['price_final'] = '<span class="price">'+ priceUtils.formatPrice(window.checkoutConfig.quoteItemData[k].price_incl_tax*unitQty, window.checkoutConfig.priceFormat) +'</span>';
                                            }
                                            else{
                                                item['price_html'] = '<span class="price '+ isSpecialPrice +'">'+ priceUtils.formatPrice(window.checkoutConfig.quoteItemData[k].price_incl_tax, window.checkoutConfig.priceFormat) +'</span>';
                                                item['price_final'] = '<span class="price">'+ priceUtils.formatPrice(window.checkoutConfig.quoteItemData[k].price_incl_tax, window.checkoutConfig.priceFormat) +'</span>';
                                            }

                                            if(item['free_product'] && window.checkoutConfig.quoteItemData[k].is_riki_machine == 0) {
                                                item['price_html'] = '<span class="price">'+ priceUtils.formatPrice(window.checkoutConfig.quoteItemData[k].price_incl_tax, window.checkoutConfig.priceFormat) +'</span>';
                                            }

                                            item['tier_price'] = '';
                                            if(typeof window.checkoutConfig.quoteItemData[k].tier_price != 'undefined' && !item['free_product']) {
                                                var tierPriceObj = window.checkoutConfig.quoteItemData[k].tier_price;
                                                var minTierPrice = _.min(tierPriceObj, function (o) {
                                                    return o.price;
                                                });

                                                if(minTierPrice.price < window.checkoutConfig.quoteItemData[k].product_final_price) {
                                                    item['tier_price']+= '<ul class="prices-tier-cart items">';
                                                    $.each(tierPriceObj, function(index, tierItem) {
                                                        if('CS' == item['unit_case']){
                                                            var unitQty = (null === item['unit_qty'])?1:item['unit_qty'];
                                                            item['tier_price']+= '<li class="item">'+ $t('%1 case:').replace('%1', Math.ceil(tierItem.price_qty/unitQty)) + $t('%2 / case').replace('%2', priceUtils.formatPrice(tierItem.price*unitQty, window.checkoutConfig.priceFormat)) +'</li>';
                                                        }
                                                        else
                                                        if('EA' == item['unit_case']){
                                                            item['tier_price']+= '<li class="item">'+ $t('%1 cases:').replace('%1', parseInt(tierItem.price_qty)) + $t('%2 / piece').replace('%2', priceUtils.formatPrice(tierItem.price, window.checkoutConfig.priceFormat)) +'</li>';
                                                        }
                                                        else{
                                                            item['tier_price']+= '<li class="item">'+ $t('Buy %1 or more:').replace('%1', parseInt(tierItem.price_qty)) + $t('%2 / set').replace('%2', priceUtils.formatPrice(tierItem.price, window.checkoutConfig.priceFormat)) +'</li>';
                                                        }

                                                    });
                                                    item['tier_price']+= '</ul>';
                                                }
                                            }

                                            item['subtotal'] = priceUtils.formatPrice(window.checkoutConfig.quoteItemData[k].row_total_incl_tax, window.checkoutConfig.priceFormat);

                                            item['delivery_type'] = window.checkoutConfig.quoteItemData[k].delivery_type;
                                            item['gift_wrapping_available'] = window.checkoutConfig.quoteItemData[k].product.gift_wrapping_available;
                                            item['gift_wrapping'] = window.checkoutConfig.quoteItemData[k].product.gift_wrapping;
                                            item['gw_id'] = window.checkoutConfig.quoteItemData[k].gw_id;
                                            item['gift_wrapping_list'] = '';
                                            if(typeof item['gift_wrapping'] == 'string' && item['gift_wrapping'] != '') {
                                                var availableDesignIds = item['gift_wrapping'].split(',');
                                                item['gift_wrapping_list'] = _.filter(allGiftWrappingItems, function (item) {
                                                    item.priceAfterFormat = priceUtils.formatPrice(item.price, window.checkoutConfig.priceFormat);
                                                    return _.indexOf(availableDesignIds, item.id) != -1;
                                                });
                                                item['gift_wrapping_list'] = _.sortBy(item['gift_wrapping_list'], function(item) {
                                                    return item.price;
                                                });
                                            }
                                            /** Check seasonal product **/
                                            item['is_seasonal_skip'] = false;
                                            if(window.checkoutConfig.quoteItemData[k].product.allow_seasonal_skip == '1' && window.checkoutConfig.quoteItemData[k].product.seasonal_skip_optional != '1') {
                                                item['is_seasonal_skip'] = true;
                                                item['allow_skip_from'] = window.checkoutConfig.quoteItemData[k].product.allow_skip_from;
                                                item['allow_skip_to'] = window.checkoutConfig.quoteItemData[k].product.allow_skip_to;
                                            }
                                            if(typeof cartItemData[i].items_error_messages == 'undefined' || !(item_id in cartItemData[i].items_error_messages) || !item['free_product']){
                                                cartItemsTmp['cartItems'].push(item);
                                                cartItems.push(item);
                                            }
                                        }
                                    }
                                }
                                cartCount = cartItems.length;
                                deliveryInfoLayoutTmp.push(cartItemsTmp);
                                self.deliveryTypes.push(new self.setDeliveryType(i+1, cartItemData[i].name, cartItemData[i].deliverydate, cartItemData[i].period,  cartItemData[i].timeslot, cartItems, cartCount, cartItemData[i]));
                            }
                            uiRegistry.set('deliveryTypes', self.deliveryTypes);

                            fullScreenLoader.stopLoader();
                        }else {
                            window.location.href = urlBuilder.build('checkout/cart');
                        }
                    }
                });

            });
            messageList.noticeMessages.subscribe(function(noticeMessages) {
                if(noticeMessages.length == 0) {
                    $('input.delivery_date, select.delivery_time').removeClass('warning-ddate');
                }
            });
            return this;
        },
        setDeliveryType: function (i, name, restrictDate, period, timeSlot, cartItems, cartCount, data) {
            var self = this;
            
            self.dataBound = cartItems;
            self.index = $.mage.__('Shipping %1:').replace('%1', i);
            self.name = name;
            self.restrictDate = ko.observableArray(restrictDate);
            self.period = ko.observable(period);
            self.onlyDm = typeof data['onlyDm'] !== 'undefined' ? data['onlyDm'] : false;
            self.timeSlot = ko.observableArray(timeSlot);
            self.cartItems = cartItems;
            self.cartCount = cartCount;
            self.isSubscription = typeof  data['isSub'] !== 'undefined' && data['isSub'] === true;
            self.strCurrentDateServer =  data['serverInfo']['currentDate'];
            self.isActive = ko.observable('0');
            self.preOrder = false;
            if(typeof data['pre_order'] != 'undefined') {
                self.preOrder = data['pre_order'];
            }
            self.backOrder = data['back_order'];
            self.allowChooseDeliveryDate = typeof data['allow_choose_delivery_date'] != 'undefined' && data['allow_choose_delivery_date'] === true;
            self.timeSlotValue = ko.observable();

            self.items_error_messages = ko.observableArray();

            if (typeof data.items_error_messages !== "undefined" ) {
                self.items_error_messages(data.items_error_messages);
            }

            self.timeSlotValue.subscribe(function (val) {
                if(val != '') {
                    var timeSlotSelected =  _.findWhere(self.timeSlot(), {'value': val});
                    var timeSlotLabel = $t('Unspecified');
                    if(typeof timeSlotSelected != 'undefined') {
                        timeSlotLabel = timeSlotSelected.label;
                        timeSlotLabel = timeSlotLabel.replace(/:/g , "\\:");
                    }
                    window.dataLayer = window.dataLayer || [];
                    window.dataLayer.push({
                        'event': 'checkoutOption',
                        'ecommerce': {
                            'checkout_option': {
                                'actionField': {
                                    'step': 2,
                                    'option': ['Time Slot - ' + timeSlotLabel]
                                }
                            }
                        }
                    });
                }
            });


            if(self.isSubscription)
                self.isActive('1');

            if(self.isSubscription) {
                self.chooseNextDD = data['str_choose_next_date'];
                self.isAllowChangeNextDD = data['is_allow_change_next_dd'];
                self.arrFrequency = data['arr_frequency'];
                self.nextDeliveryDateCalculationOption = data['next_delivery_date_calculation_option'];
                self.isAlllowChangeHanpukaiDeliveryDate = data['is_allow_change_hanpukai_delivery_date'];
                if(self.isAlllowChangeHanpukaiDeliveryDate == 1) {
                    self.hanpukaiDeliveryDateFrom = data['hanpukai_delivery_date_from'];
                    self.hanpukaiDeliveryDateTo = data['hanpukai_delivery_date_to'];
                } else if(self.isAlllowChangeHanpukaiDeliveryDate == 0) {
                    self.hanpukaiFirstDeliveryDate = data['hanpukai_first_delivery_date'];
                }
            }

            if (window.checkoutConfig.isSubHanpukai) {
                self.hanpukaiMaximumOrderTimes = data['hanpukai_maximum_order_times'];
            }

            (function(self_chooseNextDD) {
                ko.bindingHandlers.datetimepicker = {
                    init: function (element, valueAccessor, allBindingsAccessor) {
                        var datePlusFrequency = function(paramObjFirstDD, arrFrequency) {
                            var objFirstDD = new Date(paramObjFirstDD.getTime());
                            var interval = parseInt(arrFrequency[0]), unit = arrFrequency[1];

                            if(unit === 'month') {
                                objFirstDD.setMonth(objFirstDD.getMonth() + interval);

                                return objFirstDD
                            }
                            /** week */
                            objFirstDD.setDate(objFirstDD.getDate() + interval * 7);

                            return objFirstDD;
                        };

                        var $el = $(element);

                        // Hanpukai rule here
                        var isDeliverDateInput = $el.attr('name') === "delivery_date";

                        var isNextDeliverDateInput = $el.attr('name') === "next_delivery_date";

                        //initialize datetimepicker


                        var arrCurrentDateServer = self.strCurrentDateServer.split("-"),
                            restrictDateList = allBindingsAccessor.get('restrictDateList')(),
                            periodRank = allBindingsAccessor.get('periodRank')() || 30,
                            errors = allBindingsAccessor.get('errors'),
                            maxDate = '',
                            minDate = '',
                            currentDateServer = '';

                        currentDateServer = new Date(arrCurrentDateServer[0], parseInt(arrCurrentDateServer[1]) - 1, arrCurrentDateServer[2]);

                        //Calculate min date range
                        var dates = restrictDateList.map(function(item) {
                            return new Date(item);
                        });

                        var latest = new Date(Math.max.apply(null,dates));

                        var minDateRank = 0;

                        var oneDay = 1000 * 60 * 60 * 24;
                        var currentDayTmp = new Date(currentDateServer.getTime());
                        var firstDDTmp = new Date(latest.getTime());

                        /* The delivery date is automatically set as the earliest day (for subscription) */
                        if(self.isSubscription) {
                            var earliestDate = $.datepicker.formatDate('yy/mm/dd', new Date(firstDDTmp.getTime() + oneDay));
                            if(isDeliverDateInput) {
                                $el.attr('data-earliest', earliestDate);
                            }
                            if(isNextDeliverDateInput) {
                                var earliestNextDateTmp = datePlusFrequency(new Date(earliestDate), self.arrFrequency),
                                    earliestNextDate = $.datepicker.formatDate('yy/mm/dd', earliestNextDateTmp);
                                $el.attr('data-earliest', earliestNextDate);
                            }
                        }

                        if(isNextDeliverDateInput
                            && self.isAllowChangeNextDD == false
                            && self.isAlllowChangeHanpukaiDeliveryDate == -1) {
                            return; /* Do not set calendar for this */
                        }

                        var differenceMs = Math.abs(currentDayTmp - firstDDTmp);
                        minDateRank = Math.round(differenceMs/oneDay) + 1;


                        maxDate = new Date(currentDateServer.getTime());
                        minDate = new Date(currentDateServer.getTime());

                        maxDate.setDate(maxDate.getDate() + periodRank);
                        minDate.setDate(minDate.getDate() + minDateRank);

                        // if hanpukai set min date and max date from database

                        self.isDisablePicker = false;

                        //Disable DatePicker for normal subscription and normal checkout

                        if(self.backOrder && data['first_date'] != null) {
                            self.isDisablePicker = true;
                            maxDate = null;
                        }

                        /*disable datepicker if leadtime is inactive for product delivery type*/
                        for (var key in errors()) {
                            if (errors().hasOwnProperty(key)) {
                                self.isDisablePicker = true;
                                maxDate = null;
                                break;
                            }
                        }

                        if (typeof self.isAlllowChangeHanpukaiDeliveryDate !== 'undefined') {
                            if (self.isAlllowChangeHanpukaiDeliveryDate == 0) {
                                self.isDisablePicker = true;
                                maxDate = null;
                            } else if (self.isAlllowChangeHanpukaiDeliveryDate == 1) {
                                if (isNextDeliverDateInput) {
                                    self.isDisablePicker = true;
                                }
                                var maxDateInRestrictDateList = null;
                                if (restrictDateList.length > 0) {
                                    maxDateInRestrictDateList = restrictDateList[restrictDateList.length - 1];
                                    maxDateInRestrictDateList = new Date(maxDateInRestrictDateList);
                                }
                                maxDate = new Date(self.hanpukaiDeliveryDateTo);
                                var hanpukaiDeliveryDateFromSetting = new Date(self.hanpukaiDeliveryDateFrom);
                                if (hanpukaiDeliveryDateFromSetting >= maxDateInRestrictDateList) {
                                    minDate = hanpukaiDeliveryDateFromSetting;
                                } else {
                                    minDate = maxDateInRestrictDateList;
                                }
                            }
                        }

                        var options = {
                            firstDay: 1,
                            dateFormat: "yy/mm/dd",
                            showOn: "button",
                            buttonText: "",
                            showAnim: "",
                            changeMonth: false,
                            changeYear: false,
                            buttonImageOnly: null,
                            buttonImage: null,
                            showButtonPanel: true,
                            showOtherMonths: true,
                            showWeek: false,
                            timeFormat: '',
                            showTime: false,
                            showHour: false,
                            disabled: self.isDisablePicker,
                            showMinute: false,
                            minDate: minDate,
                            maxDate: maxDate,
                            onSelect: function(date) {
                                var $this = $(this);
                                $this.data('datepicker').inline = false;

                                /** START google dataLayer tag */
                                window.dataLayer = window.dataLayer || [];
                                window.dataLayer.push({
                                    'event': 'checkoutOption',
                                    'ecommerce': {
                                        'checkout_option': {
                                            'actionField': {
                                                'step': 2,
                                                'option': ['Delivery Date - Change']
                                            }
                                        }
                                    }
                                });

                                /**  not subscription */
                                if( ! self.isSubscription) {
                                    return true;
                                }

                                var $nextDeliveryDateInput = $this.parents('.shipping-block').find('.block-next-delivery-date').find('input');
                                if(date != '') {
                                    /** If this input is not next delivery */
                                    if($this.attr('name') !== "next_delivery_date") {
                                        /** 1 */
                                        var arrSelectDate = date.split('/');
                                        var objFirstDD = new Date(arrSelectDate[0], parseInt(arrSelectDate[1]) - 1, arrSelectDate[2]);

                                        /*  Set min and max date again
                                         minDate
                                         maxDate
                                         */

                                        if(typeof $nextDeliveryDateInput === "undefined") {
                                            return;
                                        }

                                        var chooseNextDD = datePlusFrequency(objFirstDD, self.arrFrequency);
                                        var minCalendar = new Date(objFirstDD.getTime()); minCalendar.setDate(minCalendar.getDate() + 1);


                                        var maxDateByFrequency = new Date(chooseNextDD.getTime()) ;
                                        var maxDateByPeriod = new Date(currentDateServer.getTime());
                                        maxDateByPeriod.setDate(maxDateByPeriod.getDate() + periodRank);
                                        var maxCalendar = maxDateByPeriod.getTime() > maxDateByFrequency.getTime() ? maxDateByPeriod : maxDateByFrequency;

                                        if(self.isAllowChangeNextDD == false) {
                                            $nextDeliveryDateInput.val(chooseNextDD.getFullYear() +'/'+ ('0' + (chooseNextDD.getMonth()+1)).slice(-2) +'/' + chooseNextDD.getDate());
                                            return;
                                        }

                                        /** 2 */

                                        // Check is Hanpukai and set mindate and max date
                                        if (typeof self.isAlllowChangeHanpukaiDeliveryDate !== 'undefined') {
                                            if (self.isAlllowChangeHanpukaiDeliveryDate == 1) {
                                                maxDate = new Date(self.hanpukaiDeliveryDateTo);
                                                minCalendar = new Date(self.hanpukaiDeliveryDateFrom);
                                            }
                                        }


                                        if(typeof $nextDeliveryDateInput.data('datepicker') !== 'undefined' ) {

                                            /** set min date */
                                            $nextDeliveryDateInput.data('datepicker').settings.minDate = minCalendar;
                                            $nextDeliveryDateInput.data('datepicker').settings.maxDate = maxCalendar;
                                            var currentDateNextDD = $nextDeliveryDateInput.datepicker("getDate");


                                            /*
                                             if(currentDateNextDD != null) {
                                             if(currentDateNextDD.getTime() < minCalendar.getTime()) {
                                             $nextDeliveryDateInput.datepicker('setDate',  minCalendar );
                                             }
                                             else if(currentDateNextDD.getTime() > maxCalendar.getTime()) {
                                             $nextDeliveryDateInput.datepicker('setDate',  maxCalendar );
                                             }
                                             }
                                             */

                                            $nextDeliveryDateInput.datepicker('setDate',  chooseNextDD );

                                        }
                                    }
                                }else {
                                    if(typeof $nextDeliveryDateInput === "undefined") {
                                        return;
                                    }
                                    $nextDeliveryDateInput.val('');
                                }
                            },
                            beforeShowDay: function(date){
                                var string = jQuery.datepicker.formatDate('yy-mm-dd', date);
                                var ruleDefault = [restrictDateList.indexOf(string) == -1];
                                return ruleDefault;

                            },
                            beforeShow: function(input, inst) {
                                setTimeout(function() {
                                    var buttonPane = $(input)
                                        .datepicker( "widget" )
                                        .find( ".ui-datepicker-buttonpane" );

                                    $( "<button>", {
                                        text: $t("Unspecified"),
                                        click: function() {
                                            $.datepicker._clearDate(input);
                                        }
                                    }).appendTo( buttonPane ).addClass("ui-datepicker-clear ui-state-default ui-priority-primary ui-corner-all");
                                }, 10);
                            },
                            onChangeMonthYear: function(year, month, instance) {
                                if(!self.isSubscription) {
                                    setTimeout(function() {
                                        var buttonPane = $(instance)
                                            .datepicker( "widget" )
                                            .find( ".ui-datepicker-buttonpane" );

                                        $( "<button>", {
                                            text: $t("Unspecified"),
                                            click: function() {
                                                $.datepicker._clearDate(instance.input);
                                            }
                                        }).appendTo(buttonPane).addClass("ui-datepicker-clear ui-state-default ui-priority-primary ui-corner-all");
                                    }, 10);
                                }
                            }
                        };
                        if($('html').attr('lang') == 'ja') {
                            $.datepicker.setDefaults($.datepicker.regional['ja']);
                        }
                        $el.datepicker(options);
                        $('.ui-datepicker').addClass('notranslate');

                        //set First delivery date back order case normal order
                        if (!self.isSubscription) {
                            if (self.backOrder && data['first_date'] != null) {
                                $el.datepicker('setDate', data['first_date']);
                            }
                        }


                        // Hanpukai rule not change first and next delivery date
                        if (isDeliverDateInput && self.isDisablePicker) {
                            if (typeof self.hanpukaiFirstDeliveryDate !== 'undefined') {
                                $el.datepicker('setDate', self.hanpukaiFirstDeliveryDate);
                            }
                        }

                        /* For next delivery date - recalculate */
                        if(isNextDeliverDateInput && self.isSubscription && !self.isDisablePicker) {

                            var arrStrChooseNextDD = self_chooseNextDD.split("/");

                            /** 3 - setvalue default */
                            if(self.isAllowChangeNextDD == false) {
                                $el.datepicker('setDate', new Date(arrStrChooseNextDD[0], parseInt(arrStrChooseNextDD[1])-1, arrStrChooseNextDD[2]));
                                return;
                            }

                            /** min is current */

                            /** mx is largest between period or frequency */
                            var maxDate = '', maxDateByFrequency = datePlusFrequency(new Date(currentDateServer.getTime()), self.arrFrequency);
                            var maxDateByPeriod = new Date(currentDateServer.getTime());
                            maxDateByPeriod.setDate(maxDateByPeriod.getDate() + periodRank);

                            var maxDate = maxDateByPeriod.getTime() > maxDateByFrequency.getTime() ?  maxDateByPeriod : maxDateByFrequency;

                            $el.data('datepicker').settings.maxDate = maxDate;

                        }

                        if (isNextDeliverDateInput && self.isDisablePicker) {
                            if (typeof self.hanpukaiFirstDeliveryDate !== 'undefined') {
                                var objHanpukaiFirstDeliveryDate = new Date(self.hanpukaiFirstDeliveryDate);
                                var nextHanpukaiDeliveryDate = datePlusFrequency(objHanpukaiFirstDeliveryDate, self.arrFrequency);
                                $el.datepicker('setDate', nextHanpukaiDeliveryDate);
                            }
                        }

                        //set delivery date for back order
                        if(self.backOrder && data['first_date'] != null && !isNextDeliverDateInput) {
                            if (typeof self.isAlllowChangeHanpukaiDeliveryDate !== 'undefined') {
                                if (self.isAlllowChangeHanpukaiDeliveryDate == 0) {
                                    if (typeof self.hanpukaiFirstDeliveryDate !== 'undefined'
                                        && data['first_date'] > self.hanpukaiFirstDeliveryDate
                                    ){
                                        $el.datepicker('setDate', data['first_date']);
                                    }
                                } else {
                                    $el.datepicker('setDate', data['first_date']);
                                }
                            }

                        }

                        if (isNextDeliverDateInput && self.backOrder) {
                            if (typeof data['first_date'] !== 'undefined') {
                                if (typeof self.isAlllowChangeHanpukaiDeliveryDate !== 'undefined'
                                    && self.isAlllowChangeHanpukaiDeliveryDate != -1) {
                                    if (self.isAlllowChangeHanpukaiDeliveryDate == 0) {
                                        if (typeof self.hanpukaiFirstDeliveryDate !== 'undefined'
                                            && data['first_date'] > self.hanpukaiFirstDeliveryDate
                                        ){
                                            var objBackOrderFirstDeliveryDate = new Date(data['first_date']);
                                            var nextBackOrderDeliveryDate = datePlusFrequency(objBackOrderFirstDeliveryDate, self.arrFrequency);
                                            $el.datepicker('setDate', nextBackOrderDeliveryDate);
                                        }

                                    }
                                    if (self.isAlllowChangeHanpukaiDeliveryDate == 1) {
                                        if (data['first_date'] != null) {
                                            var objBackOrderFirstDeliveryDate = new Date(data['first_date']);
                                            var nextBackOrderDeliveryDate = datePlusFrequency(objBackOrderFirstDeliveryDate, self.arrFrequency);
                                            $el.datepicker('setDate', nextBackOrderDeliveryDate);
                                        }
                                    }
                                } else {
                                    if (typeof self.hanpukaiFirstDeliveryDate !== 'undefined'
                                        && data['first_date'] > self.hanpukaiFirstDeliveryDate
                                    ){
                                        var objBackOrderFirstDeliveryDate = new Date(data['first_date']);
                                        var nextBackOrderDeliveryDate = datePlusFrequency(objBackOrderFirstDeliveryDate, self.arrFrequency);
                                        $el.datepicker('setDate', nextBackOrderDeliveryDate);
                                    }

                                }
                            }
                        }
                    },
                    update: function (element, valueAccessor, allBindings) {

                    }
                };
            })(self.chooseNextDD);

        },
        _ajax: function(url, data) {
            var formKey = $.mage.cookies.get('form_key');
            if (!formKey) {
                formKey = this.generateRandomString('0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ', 16);
                $.mage.cookies.set('form_key', formKey);
            }
            $.extend(data, {
                'form_key': formKey
            });
            $.ajax({
                url: url,
                data: data,
                global: false,
                type: 'post',
                dataType: 'json',
                context: this,
                beforeSend: function() {
                    fullScreenLoader.startLoader();
                }
            })
                .done(function(response) {
                    if (response.success) {
                        if(!_.isUndefined(data.show_warning)) {
                            checkoutData.setWarningMessage(true);
                        }
                        window.location.reload(true);
                    } else {
                        var msg = response.error_message;
                        /*update item quantity failed, revert to old value */
                        if(response.type == 'updateQty'){
                            checkoutData.setErrorMessage(msg);
                            window.location.reload(true);
                        }
                        if(response.type == 'updateHanpukaiQty'){
                            jQuery('select[name=hanpukai_change_all_qty]').val(response.qtyValue);
                        }
                        if (msg) {
                            messageList.addErrorMessage({'message': msg});
                            fullScreenLoader.stopLoader();
                        }
                    }
                })
                .fail(function(error) {
                    console.log(JSON.stringify(error));
                });
        },
        updateItemQty: function (item_id,unit_case,unit_qty, adjustQty) {

            var elementCurrent, item_qty;

            if (adjustQty != false) {
                if ('CS' == unit_case) {
                    elementCurrent = $('input[name="qty_case_' + item_id + '"]');
                    item_qty = (parseInt(elementCurrent.val()) + parseInt(adjustQty)) * unit_qty;
                } else {
                    elementCurrent = $('input[name="qty_' + item_id + '"]');
                    item_qty = parseInt(elementCurrent.val()) + parseInt(adjustQty);
                }
            } else {
                if ('CS' == unit_case) {
                    elementCurrent = $('select[name="qty_case_' + item_id + '"]');
                    item_qty = elementCurrent.val();
                    item_qty = item_qty * unit_qty;
                } else {
                    elementCurrent = $('select[name="qty_' + item_id + '"]');
                    item_qty = elementCurrent.val();
                }
            }

            if(item_qty == 0) {
                var productId = elementCurrent.attr('data-product-id');
                this.removeItem(item_id, productId);
            } else {
                this.pushDataLayerProductCart(elementCurrent);
                this._ajax(this.options.url.update, {
                    show_warning: true,
                    item_id: item_id,
                    item_qty: item_qty
                });
            }
        },
        updateHanpukaiQty: function(value) {
            var elementCurrent = $('select[name="hanpukai_change_all_qty"]');
            if (value === undefined){
                value = 0;
            }
            var hanpukai_change_all_qty = parseInt(elementCurrent.val()) + value;
            var url = urlBuilder.build('riki-checkout/sidebar/hanpukaiUpdateQty');

            var parentItem  = elementCurrent.closest('.parent-item-cart');
            var cartStorage = localStorage.getItem('googleTagAddToCartStorage');
            var dataProductRemove  = JSON.parse(cartStorage);
            var currentQty = parseInt(elementCurrent.val());
            var qtyOld     = parseInt(parentItem.find('.data-remove-qty').val());
            var hanpukaiItemsRemove = [];

            //push data when change qty or remove
            if (currentQty <= qtyOld) {
                var qtyRemove = parseInt(qtyOld-currentQty);
                for(var index in dataProductRemove) {
                    dataProductRemove[index]['quantity'] = qtyRemove;
                    hanpukaiItemsRemove.push(dataProductRemove[index]);
                }

                dataLayer.push({
                    'event': 'removeFromCart',
                    'currencyCode' : dlCurrencyCode,
                    'ecommerce': {
                        'remove': {
                            'actionField': {},
                            'products':hanpukaiItemsRemove
                        }
                    }
                });
            }

            uiRegistry.get('checkout.steps.shipping-step.shippingAddress.delivery_date' , function (self){
                self._ajax(url, {
                    hanpukai_change_all_qty:hanpukai_change_all_qty
                });
            });
        },

        removeItem: function (item_id,product_id) {

            this.pushDataLayerProductCartActionRemove(product_id);

            this._ajax(this.options.url.remove, {
                show_warning: true,
                item_id: item_id
            });
        },
        updateGiftWrapping: function(item_id) {
            var gw_id = $('select[name="gift_wrapping_' + item_id +'"]').val();

            /** START google dataLayer tag */
            var optionMessage = '';
            if(gw_id == -1) {
                optionMessage = 'Gift Wrapping - Removed';
            }else {
                optionMessage = 'Gift Wrapping - Added';
            }
            window.dataLayer = window.dataLayer || [];
            window.dataLayer.push({
                'event': 'checkoutOption',
                'ecommerce': {
                    'checkout_option': {
                        'actionField': {
                            'step': 2,
                            'option': [optionMessage]
                        }
                    }
                }
            });

            this._ajax(this.options.url.updateGiftWrapping, {
                show_warning: true,
                cart_id: window.checkoutConfig.quoteData.entity_id,
                item_id: item_id,
                gw_id: gw_id
            });
        },

        /** Show warning message if customer edit or delete product in checkout page */
        showMessage: function() {
            if(checkoutData.getErrorMessage()) {
                messageList.addErrorMessage({'message': checkoutData.getErrorMessage()});
                checkoutData.setErrorMessage(false);
            }
            if(checkoutData.getWarningMessage()) {
                messageList.addNoticeMessage({'message': $t('Please set the delivery date again, when you are already set the date before.')});
                $('.ddate-block .left.ddate').each(function() {
                    var delivery_date = $(this).find('input.delivery_date');
                    var delivery_time = $(this).find('select.delivery_time');
                    if(delivery_date.val() == '') {
                        delivery_date.addClass('warning-ddate');
                    }
                    if(delivery_time.val() == '-1' || delivery_time.val() == '') {
                        delivery_time.addClass('warning-ddate');
                    }
                });
                checkoutData.setWarningMessage(false);
            }
        },

        /**
         * Get min sale qty
         * @param min_sale_qty
         * @returns {Number}
         */
        getMinSaleQty: function(min_sale_qty) {
            var qty = 0;
            if (typeof min_sale_qty == 'undefined' || min_sale_qty == '') {
                qty = 1;
            } else {
                qty = min_sale_qty;
            }
            return parseInt(qty);
        },

        /**
         * Get max sale qty
         * @param max_sale_qty
         * @returns {Number}
         */
        getMaxSaleQty: function(max_sale_qty) {
            var qty = 0;
            if (typeof max_sale_qty == 'undefined' || max_sale_qty == '') {
                qty = 99;
            } else {
                if (max_sale_qty > 99) {
                    qty = 99;
                } else {
                    qty = max_sale_qty;
                }
            }
            return parseInt(qty);
        },

        /**
         * Helper. Generate random string
         * TODO: Merge with mage/utils
         * @param {String} chars - list of symbols
         * @param {Number} length - length for need string
         * @returns {String}
         */
        generateRandomString: function (chars, length) {
            var result = '';
            length = length > 0 ? length : 1;

            while (length--) {
                result += chars[Math.round(Math.random() * (chars.length - 1))];
            }

            return result;
        },

        /** Clone button showSelectShippingAddressPopUp for Mobile*/
        showSelectShippingAddressPopUpDDate: function() {
            uiRegistry.get('checkout.steps.shipping-step.shippingAddress.address-list', function(component) {
                component.showSelectShippingAddressPopUp();
            });
        },

        /** Clone button addNewAddress for Mobile*/
        addNewAddressDDate: function() {
            uiRegistry.get('checkout.steps.shipping-step.shippingAddress.address-list', function(component) {
                component.addNewAddress();
            });
        },

        /** Check user is CNC or CIS member */
        isCISCNCMember: function() {
            if (!_.isUndefined(window.customerData.custom_attributes.membership.value)) {
                var isCISMember = _.filter(JSON.parse("[" + window.customerData.custom_attributes.membership.value + "]"), function(id) {
                    return (id == 5 || id == 6);
                })
                if(isCISMember.length > 0) {
                    return true;
                }
            }
            return false;
        },

        removeItemFromCart: function(objectData) {
            var dataRemove = {};
            for(var index in objectData) {
                if (objectData.hasOwnProperty(index)) {
                    if (index !='sku' && index !='qty') {
                        dataRemove[index] = objectData[index];
                    }
                }
            }
            return dataRemove;
        },

        pushDataLayerProductCart: function(elementCurrent) {
            var parentItem  = elementCurrent.closest('.parent-item-cart');
            var productId   = parseInt(parentItem.attr('data-cart-product-id'));
            var cartStorage = localStorage.getItem('googleTagAddToCartStorage');
            if ( cartStorage !=null && productId !=null  ) {
                var dataProductRemove  = JSON.parse(cartStorage);
                if (dataProductRemove.hasOwnProperty(productId)) {
                    var currentQty = parseInt(elementCurrent.val());
                    var qtyOld     = parseInt(parentItem.find('.data-remove-qty').val());
                    var dataProductItemsRemove   = this.removeItemFromCart(dataProductRemove[productId]);
                    //push data when change qty or remove
                    if (currentQty <= qtyOld) {
                        var newQty = parseInt(qtyOld-currentQty);
                        dataProductItemsRemove['quantity'] = newQty;
                        dataLayer.push({
                            'event': 'removeFromCart',
                            'currencyCode' : dlCurrencyCode,
                            'ecommerce': {
                                'remove': {
                                    'actionField': {},
                                    'products':[
                                        dataProductItemsRemove
                                    ]
                                }
                            }
                        });
                    }
                }
            }
        },

        pushDataLayerProductCartActionRemove: function(productId) {
            var cartStorage = localStorage.getItem('googleTagAddToCartStorage');
            if ( cartStorage !=null && productId !=null  ) {
                var dataProductRemove  = JSON.parse(cartStorage);
                if (dataProductRemove.hasOwnProperty(productId)) {
                    var dataProductItemsRemove   = this.removeItemFromCart(dataProductRemove[productId]);
                    dataLayer.push({
                        'event': 'removeFromCart',
                        'currencyCode' : dlCurrencyCode,
                        'ecommerce': {
                            'remove': {
                                'actionField': {},
                                'products':[
                                    dataProductItemsRemove
                                ]
                            }
                        }
                    });
                }
            }
        }
    });
});