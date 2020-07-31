/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
/*jshint browser:true jquery:true*/
/*global alert*/
define([
        "jquery",
        'ko',
        'uiComponent',
        'Magento_Ui/js/modal/alert',
        'mage/url',
        'Riki_Subscription/js/model/profile',
        'Riki_Subscription/js/model/utils',
        'Riki_Subscription/js/model/course',
        'Riki_Subscription/js/model/emulator-order',
        'Riki_Subscription/js/model/item-list',
        'Riki_Subscription/js/action/select-timeslot',
        'Riki_Subscription/js/action/select-delivery-date',
        'Riki_Subscription/js/action/select-gift-option',
        'Riki_Subscription/js/action/change-product-qty',
        'Riki_Subscription/js/action/select-skip-from-date',
        'Riki_Subscription/js/action/select-skip-to-date',
        "Magento_Ui/js/modal/confirm",
        'uiRegistry',
        "jquery/ui",
        "mage/translate",
        "mage/calendar",
        "mage/mage",
        "mage/validation"
    ], function (
        $,
        ko,
        Component,
        alert,
        urlBuilder,
        profile ,
        utils ,
        course,
        order,
        itemList,
        selectTimeSlotAction ,
        selectDeliveryDateAction ,
        selectGiftOption ,
        changeProductQtyAction ,
        selectSkipFromDate,
        selectSkipToDate,
        confirm ,
        uiRegistry ,
        mage ,
        $t,
        calendar
    ) {
        "use strict";
        var is_hanpukai = ko.observable(window.subscriptionConfig.is_hanpukai);

        var defaultCalendarOptions = {
            dayNames: ["Sunday","Monday","Tuesday","Wednesday","Thursday","Friday","Saturday"],
            dayNamesMin: ["Sun","Mon","Tue","Wed","Thu","Fri","Sat"],
            monthNames: ["January","February","March","April","May","June","July","August","September","October","November","December"],
            monthNamesShort: ["Jan","Feb","Mar","Apr","May","Jun","Jul","Aug","Sep","Oct","Nov","Dec"],
            firstDay: 1,
            closeText: "Close",
            currentText: "Go Today",
            prevText: "Previous",
            nextText: "Next",
            weekHeader: "WK",
            timeText: "Time",
            hourText: "Hour",
            minuteText: "Minute",
            dateFormat: "yy-mm-dd",
            showOn: "button",
            buttonText: "",
            showAnim: "",
            changeMonth: true,
            changeYear: true,
            buttonImageOnly: null,
            buttonImage: null,
            showButtonPanel: false,
            showOtherMonths: true,
            showWeek: false,
            timeFormat: '',
            showTime: false,
            showHour: false,
            showMinute: false
        };

        var isSubscriptionHanpukai = window.subscriptionConfig.is_hanpukai;
        var timeslotData = window.subscriptionConfig.timeslot_data;
        var customerAddressData = window.subscriptionConfig.addresses_data;
        var delete_product_cart_url = window.subscriptionConfig.delete_product_cart_url;
        var gift_avaiable = window.subscriptionConfig.gift_available;
        var message_avaiable = window.subscriptionConfig.message_available;
        var media_url = MEDIA_URL;
        var product_out_off_stock = window.subscriptionConfig.product_out_off_stock;
        var product_stock_level = window.subscriptionConfig.product_stock_level;

        return Component.extend({
            defaults: {
                template: 'Riki_Subscription/items-information'
            },
            /** Initialize observable properties */
            initObservable: function () {
                this._super();
                var self = this;
                this.itemData = itemList.getItemsData();
                this.isSubscriptionHanpukai = isSubscriptionHanpukai;
                this.hanpukaiDeliveryDateAllowed = course.getHanpukaiDeliveryDateAllowed();
                this.timeslotData = timeslotData;
                this.customerAddressData = customerAddressData;
                this.giftAvaiable = gift_avaiable;
                this.messageAvaiable = message_avaiable;
                this.isAllowChangeAddress = course.getAllowChangeAddress();
                this.isAllowChangeNextDeliveryDate = course.getAllowChangeNextDeliveryDate();
                this.isAllowChangeProduct = course.getAllowChangeProduct();
                this.wasDisengaged = profile.wasDisengaged();
                this.profileStatus = profile.getStatus() == 1;
                this.isCompleted = profile.getStatus() == 2;
                this.isAllowChangeQty = course.getAllowChangeQty();
                this.minimumOrderQty = parseInt(course.getMiniumOrderQty());
                this.frequency_interval  = profile.frequency_interval;
                this.frequency_unit  = profile.frequency_unit;
                this.next_delivery_date  = profile.next_delivery_date;
                this.product_out_off_stock = product_out_off_stock;
                this.product_stock_level = product_stock_level;
                this.isStockPointProfile = window.subscriptionConfig.is_stock_point_profile;
                /* control setting */
                uiRegistry.get(this.parentName , function (component) {
                    self.isDisabledAll = component.isDisabledAll;
                });

                window.customDataCalendar = {"currentMin":0,"maxDate":0, "minDate":0,"restrictDate":[]};

                ko.bindingHandlers.dateTimePickerFromTo = {
                    after: ['attr'],
                    init: function (element, valueAccessor, allBindingsAccessor) {
                        var $el = $(element);
                        var skip_from = allBindingsAccessor.get('skip_from');
                        var skip_to = allBindingsAccessor.get('skip_to');

                        var from_text = '#text_block_from_item_' + $el.attr('item_id');
                        var to_text = '#text_block_to_item_' + $el.attr('item_id');

                        var options = {
                            dayNames: ["Sunday","Monday","Tuesday","Wednesday","Thursday","Friday","Saturday"],
                            dayNamesMin: ["Sun","Mon","Tue","Wed","Thu","Fri","Sat"],
                            monthNames: ["January","February","March","April","May","June","July","August","September","October","November","December"],
                            monthNamesShort: ["Jan","Feb","Mar","Apr","May","Jun","Jul","Aug","Sep","Oct","Nov","Dec"],
                            firstDay: 1,
                            closeText: "Close",
                            currentText: "Go Today",
                            prevText: "Previous",
                            nextText: "Next",
                            weekHeader: "WK",
                            timeText: "Time",
                            hourText: "Hour",
                            minuteText: "Minute",
                            dateFormat: "yy-mm-dd",
                            showOn: "button",
                            buttonText: "",
                            showAnim: "",
                            changeMonth: false,
                            changeYear: false,
                            buttonImageOnly: null,
                            buttonImage: null,
                            showButtonPanel: false,
                            showOtherMonths: true,
                            showWeek: false,
                            timeFormat: '',
                            showTime: false,
                            showHour: false,
                            showMinute: false,
                            minDate: skip_from,
                            maxDate: skip_to,
                            onSelect: function (date) {
                                var $this = $(this);

                                var objMinNextDate = '';
                                /** Set value to span text */
                                if ($(this).hasClass('from_calendar')) {
                                    $(from_text).find('.text').html(date);
                                    $(to_text).find('.text').html($t('N/A'));
                                    objMinNextDate = new Date($this.val());
                                }
                                if ($(this).hasClass('to_calendar')) {
                                    $(to_text).find('.text').html(date);
                                    objMinNextDate = skip_from;
                                }

                                var productCartItemId = $(this).attr('item_id');
                                if ($(this).attr('name').indexOf('skip_from') != -1) {
                                    selectSkipFromDate(productCartItemId, date);
                                }

                                if ($(this).attr('name').indexOf('skip_to') != -1) {
                                    selectSkipToDate(productCartItemId, date);
                                }

                                /** If this input is not to calendar */
                                if (!$this.hasClass('to_calendar')) {
                                    var to_calendar = '#' + $this.attr('data-to-calendar');
                                    $(to_calendar).val('');

                                    /** Set new max, min date for next delivery date */
                                    $(to_calendar).datepicker("destroy");
                                    $(to_calendar).datepicker({
                                        dayNames: ["Sunday", "Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday"],
                                        dayNamesMin: ["Sun", "Mon", "Tue", "Wed", "Thu", "Fri", "Sat"],
                                        monthNames: ["January", "February", "March", "April", "May", "June", "July", "August", "September", "October", "November", "December"],
                                        monthNamesShort: ["Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"],
                                        firstDay: 1,
                                        closeText: "Close",
                                        currentText: "Go Today",
                                        prevText: "Previous",
                                        nextText: "Next",
                                        weekHeader: "WK",
                                        timeText: "Time",
                                        hourText: "Hour",
                                        minuteText: "Minute",
                                        dateFormat: "yy-mm-dd",
                                        showOn: "button",
                                        buttonText: "",
                                        showAnim: "",
                                        changeMonth: false,
                                        changeYear: false,
                                        buttonImageOnly: null,
                                        buttonImage: null,
                                        showButtonPanel: false,
                                        showOtherMonths: true,
                                        showWeek: false,
                                        timeFormat: '',
                                        showTime: false,
                                        showHour: false,
                                        minDate: objMinNextDate,
                                        maxDate: skip_to,
                                        onSelect: function (date) {
                                            if ($(this).attr('name').indexOf('skip_to') != -1) {
                                                selectSkipToDate(productCartItemId, date);
                                            }

                                            var to_text = '#text_block_to_item_' + $(this).attr('item_id');
                                            /** Set value to span text */
                                            $(to_text).find('.text').html(date);
                                        }
                                    });
                                    $('.ui-datepicker').addClass('notranslate');
                                }

                            }
                        };
                        $el.datepicker(options);
                        $('.ui-datepicker').addClass('notranslate');
                    },
                    update: function (element, valueAccessor, allBindings) {}
                };

                return this;
            },
            getHanpukaiDeliveryDateAllow: function () {
                if (!this.isSubscriptionHanpukai) {
                    return true;
                } else {
                    return this.hanpukaiDeliveryDateAllowed;
                }
            },
            getPriceFormatted: function (price) {
                return utils.getFormattedPrice(price);
            },
            afterCalendarChange: function (element,event) {
                console.log(element);
            },
            afterSelectCalendar: function (dateValue) {
                var parentBlock     = $(this).closest('.block-delivery-item');
                var deliveryDateAfterChange = dateValue;
                var profileCartItemIds = parentBlock.find('td.qty').map(function(){
                    return $(this).attr('data-productcartid');
                }).toArray();

                $('body').trigger('processStart');

                $.ajax({
                    url: window.subscriptionConfig.url_change_address_delivery,
                    method: 'POST',
                    dataType : 'json',
                    data: {
                        'profile_id':window.subscriptionConfig.profileData.profile_id,
                        'delivery_date' : deliveryDateAfterChange,
                        'profile_product_cart_id' : profileCartItemIds
                    },
                    async:true,
                    success:function (result) {
                        $('body').trigger('processStop');
                        // Remove isList parameters
                        var oldWindowURL = window.location.href;
                        if(oldWindowURL.includes("/list/1")) {
                            var newWindowURL = oldWindowURL.replace('/list/1','');
                            window.history.pushState(null, null, newWindowURL);
                        }
                    },
                    error:function () {
                        $('body').trigger('processStop');
                    }
                });
                profile.profileHasChanged(true);

                var addressId = $(this).attr('address-id');
                var deliveryType = $(this).attr('delivery-type');
                if (!_.isUndefined($('#head-address-' + addressId + '-' + deliveryType))) {
                    selectDeliveryDateAction($('#head-address-' + addressId + '-' + deliveryType).val() , dateValue);
                }
            },
            removeGiftOption: function (component , event) {
                var itemId = $(event.target).attr('product-cart-item-id');
                var isDisable = $(event.target).parent().attr('data-disable');
                if (isDisable) {
                    return false;
                }
                selectGiftOption(itemId , "0" , false);
            },
            selectGiftOption: function (component , event) {
                var itemId = $(event.target).parent().attr('product-cart-item-id');
                var isDisable = $(event.target).parent().attr('data-disable');
                if (isDisable) {
                    return false;
                }
                selectGiftOption(itemId , component.wrapping_id , component);
            },
            isExistBackOrderChooseDD: function (item) {
                return item.info.is_exist_back_order_not_allow_choose_dd;
            },

            calculateMaxDatePicker : function (item,frequency_unit,frequency_interval,next_delivery_date) {

                var nextDeliveryDate = item.info.next_delivery_date();
                if (nextDeliveryDate == null) {
                    nextDeliveryDate = next_delivery_date();
                }
                var d = new Date(nextDeliveryDate);
                if (frequency_unit() === 'month') {
                    d.setMonth(d.getMonth() + parseInt(frequency_interval()));
                }
                if (frequency_unit() === 'week') {
                    d.setDate(d.getDate() + (7 * parseInt(frequency_interval())));
                }
                d.setDate(d.getDate() - 1);

                return d;
            },
            calculateMinDatePicker : function (restrictDate) {

                /**
                 * Mapping data
                 */
                var dates = restrictDate.map(function (item) {
                    window.customDataCalendar.currentMin = item;
                    var newDate = new Date(item);
                    return newDate;
                });

                var latest = new Date(Math.max.apply(null,dates));
                var minDateRank = 0;
                var minDate = new Date();
                var oneDay = 1000 * 60 * 60 * 24;
                var currentDayTmp = new Date(minDate.getTime());
                var firstDDTmp = new Date(latest.getTime());
                var differenceMs = Math.abs(currentDayTmp - firstDDTmp);
                minDateRank = Math.round(differenceMs/oneDay) + 1;
                minDate.setDate(minDate.getDate() + minDateRank);

                return minDate;
            },
            compareDeliveryDateWithMinDateAfterChange :function () {


            },
            showCalendar: function (item,frequency_unit,frequency_interval,next_delivery_date) {
                var restrictDate = item.info.restrict_date;
                var maxDate = this.calculateMaxDatePicker(item,frequency_unit,frequency_interval,next_delivery_date);
                var minDate = this.calculateMinDatePicker(restrictDate);
                var hanpukaiAllowChangeDeliveryDate = course.getHanpukaiDeliveryDateAllowed();

                if (!hanpukaiAllowChangeDeliveryDate && this.isSubscriptionHanpukai ) {
                    var minimumDateFromList = null;
                    if (typeof restrictArray != 'undefined' && restrictArray.length > 0) {
                        minimumDateFromList = restrictArray[restrictArray.length - 1];
                        minimumDateFromList = new Date(minimumDateFromList);
                    }

                    var minimumDateFromConfig = course.getHanpukaiDeliveryDateFrom();
                    minimumDateFromConfig = new Date(minimumDateFromConfig);
                    if (minimumDateFromConfig > minimumDateFromList) {
                        minDate = minimumDateFromConfig;
                    } else {
                        minDate = minimumDateFromList;
                    }
                    maxDate = new Date(course.getHanpukaiDeliveryDateTo());
                }

                //set global value for max date
                window.customDataCalendar.maxDate = maxDate;
                window.customDataCalendar.minDate = minDate;

                var options = defaultCalendarOptions;
                options.onSelect = this.afterSelectCalendar;
                this.afterSelectCalendar.bind(this);
                options.minDate = minDate;
                options.maxDate = maxDate;
                options.beforeShowDay = function (date) {
                    var string = jQuery.datepicker.formatDate('yy-mm-dd', date);
                    return [ restrictDate.indexOf(string) == -1 ]
                };

                var _this = this;
                options.beforeShow = function () {
                    var indexBlockShippingAddress = $(this).closest('.block-delivery-item').attr('index-item');
                    if (window.customDataCalendar.restrictDate.hasOwnProperty(indexBlockShippingAddress)) {
                    var maxDate = window.customDataCalendar.maxDate ;
                        var minDate = _this.calculateMinDatePicker(window.customDataCalendar.restrictDate[indexBlockShippingAddress]);
                        restrictDate = window.customDataCalendar.restrictDate[indexBlockShippingAddress];
                        $(this).datepicker('option', { minDate: minDate, maxDate: maxDate });
                    }
                };

                var element = $('#' + 'calendar_inputField_' + item.address_id + '_' + item.info.code);
                element.datepicker(defaultCalendarOptions);
                $('.ui-datepicker').addClass('notranslate');
            },

            checkSeasonalSkip: function (item_id) {
                var checked = $('#checkbox-seasonal-skip-item-' + item_id).prop('checked');
                if (checked) {
                    $('#calendar_block_from_item_' + item_id).hide();
                    $('#calendar_block_to_item_' + item_id).hide();

                    $('#text_block_from_item_' + item_id).show();
                    $('#text_block_to_item_' + item_id).show();

                    $('#is_skip_product_'+ item_id).val(1);
                } else {
                    $('#calendar_block_from_item_' + item_id).show();
                    $('#calendar_block_to_item_' + item_id).show();

                    $('#text_block_from_item_' + item_id).hide();
                    $('#text_block_to_item_' + item_id).hide();
                    $('#is_skip_product_'+ item_id).val(0);
                }
            },

            getFormatedAddress: function (addressInfomation) {
                var formattedAddressString = '';
                $.each(addressInfomation() , function ( index , value ) {
                    formattedAddressString += value + '<br/>';
                });
                return formattedAddressString;
            },
            translate: function (neededString) {
                return $t(neededString);
            },
            changeProductQty: function (component , event) {
                var newValue = event.target.value;
                changeProductQtyAction(component,newValue);
            },
            changeProductQtyCase: function (component , event) {
                var newValue = event.target.value;
                if (!_.isUndefined($("#product_cart_id_" + component.item_id))) {
                    $("#product_cart_id_" + component.item_id).val(parseInt(newValue)*component.unit_qty);
                }
                if (!_.isUndefined($("#confirmation-product-cart-qty-" + component.item_id))) {
                    $("#confirmation-product-cart-qty-" + component.item_id).text(
                        component.getFinalQty(parseInt(newValue)*component.unit_qty , component.unit_qty , component.unit_case)
                    );
                }
                profile.profileHasChanged(true);
            },
            giftToggle: function (component , event) {
                if (component.gift_toggle()) {
                    component.gift_toggle(false);
                } else {
                    component.gift_toggle(true);
                }
            },
            getFinalQty: function (qty,unitQty,unitCase) {
                if ('CS' == unitCase) {
                    return qty/unitQty;
                } else {
                    return qty;
                }
            },
            deleteProductCart: function (component , event) {
                var url = delete_product_cart_url;
                var profile_id = profile.getProfileId();
                var element = this;
                confirm({
                    content: $t("Are you sure you want to delete this product?"),
                    actions: {
                        confirm: function () {
                            return $.ajax({
                                url: url,
                                dataType: 'json',
                                data: {
                                    pcart_id: element.item_id,
                                    id: profile_id,
                                    form_key: FORM_KEY
                                },
                                context: self.element,
                                showLoader : true
                            }).done($.proxy(function (data) {
                                if (data.status == true) {
                                    $('#product_cart_id_'+element.item_id).remove();
                                    $('<input />').attr({
                                        type: 'hidden',
                                        id: 'is_deleted',
                                        name: 'is_deleted',
                                        value: 1
                                    }).appendTo($('#form-submit-profile'));
                                    $('#form-submit-profile').find('input[name=save_profile]').val('delete_product');
                                    $('#form-submit-profile').submit();
                                    $('#is_deleted').remove();
                                } else {
                                    alert({content:data.message});
                                }
                            }, this));
                        },
                        cancel: function () {
                            return false;
                        }
                    }
                });
                return true;
            },
            getAllItemCheckDelete :function (component , event) {

                var itemCheck = [];
                $('.delete-product-cart-checkbox').each(function () {
                    if ($(this).is(':checked') && $(this).is(":visible") ) {
                        itemCheck.push($(this).val());
                    }
                })
                return itemCheck;
            },
            deleteAllProductCart: function (component , event) {
                var url = delete_product_cart_url;
                var profile_id = profile.getProfileId();
                var element = this;
                var itemsProductDelete = this.getAllItemCheckDelete();

                if (itemsProductDelete.length<=0) {
                alert({content: $t("Please select at least one item?")});
                } else {
                    confirm({
                        content: $t("Are you sure you want to delete this product?"),
                        actions: {
                            confirm: function () {
                                return $.ajax({
                                    url: url,
                                    dataType: 'json',
                                    data: {
                                        all_item_delete:  JSON.stringify(itemsProductDelete),
                                        id: profile_id,
                                        form_key: FORM_KEY
                                    },
                                    context: self.element,
                                    showLoader : true
                                }).done($.proxy(function (data) {
                                    if (data.status == true) {
                                        itemsProductDelete.forEach(function (item_id) {
                                            $('#product_cart_id_'+item_id).remove();
                                        });

                                        $('<input />').attr({

                                            type: 'hidden',
                                            id: 'is_deleted',
                                            name: 'is_deleted',
                                            value: 1
                                        }).appendTo($('#form-submit-profile'));
                                        $('#form-submit-profile').find('input[name=save_profile]').val('delete_product');
                                        $('#form-submit-profile').submit();
                                        $('#is_deleted').remove();
                                    } else {
                                        alert({content:data.message});
                                    }
                                }, this));
                            },
                            cancel: function () {
                                return false;
                            }
                        }
                    });
                    return true;
                }
            },
            checkAllItems : function (element,event) {
            
                if ($(event.target).is(':checked')) {
                    $(event.target).closest('table.list-product').find('input.delete-product-cart-checkbox').prop("checked",true);
                } else {
                    $(event.target).closest('table.list-product').find('input.delete-product-cart-checkbox').prop("checked",false);
                }
                return true;
            },
            selectTimeSlot: function (
                element ,
                // delivery info element
                event
            ) {
                var parentBlock     = $(event.target).closest('.block-delivery-item');
                var timeSlotId = $(event.target).val();
                var profileCartItemIds = parentBlock.find('td.qty').map(function(){
                    return $(this).attr('data-productcartid');
                }).toArray();

                $('body').trigger('processStart');

                $.ajax({
                    url: window.subscriptionConfig.url_change_address_delivery,
                    method: 'POST',
                    dataType : 'json',
                    data: {
                        'profile_id':window.subscriptionConfig.profileData.profile_id,
                        'time_slot_id' : timeSlotId,
                        'profile_product_cart_id' : profileCartItemIds
                    },
                    async:true,
                    success:function (result) {
                        $('body').trigger('processStop');
                        // Remove isList parameters
                        var oldWindowURL = window.location.href;
                        if(oldWindowURL.includes("/list/1")) {
                            var newWindowURL = oldWindowURL.replace('/list/1','');
                            window.history.pushState(null, null, newWindowURL);
                        }
                    },
                    error:function () {
                        $('body').trigger('processStop');
                    }
                });
                profile.profileHasChanged(true);

                selectTimeSlotAction(element);
                return true;
            },
            shouldShowMessage :function (parentBlock,flag) {
            
                if (flag) {
                var message = $t("Delivery date specified has been changed.");
                    parentBlock.find('.message-change-delivery').text(message);
                } else {
                    parentBlock.find('.message-change-delivery').text('');
                }
            },
            compareDateAfterChange:function (parentBlock,indexBlockShippingAddress) {
            
                this.calculateMinDatePicker(window.customDataCalendar.restrictDate[indexBlockShippingAddress]);

                var delivery         = parentBlock.find('.current-delivery-date');
                var currentDelivery  = new Date(delivery.val()).getTime();
                var minDate  = new Date(window.customDataCalendar.currentMin).getTime();
                if (minDate>currentDelivery) {
                var newDate = new Date(window.customDataCalendar.currentMin);
                    newDate.setDate(newDate.getDate() + 1);
                    return  newDate.getFullYear() + '-' + ('0' + (newDate.getMonth()+1)).slice(-2) + '-' + ('0' + newDate.getDate()).slice(-2);
                }
                return '';
            },
            selectAddress: function (element , event) {
                var parentComponent = uiRegistry.get('subscription-form-edit.items-information');
                var parentBlock     = $(event.target).closest('.block-delivery-item');
                var deliveryType    = parentBlock.attr('delivery-type');
                var indexBlockShippingAddress =parentBlock.attr('index-item');
                var deliveryDateAfterChange = parentBlock.find('.current-delivery-date').val();
                var profileCartItemIds = parentBlock.find('td.qty').map(function(){
                    return $(this).attr('data-productcartid');
                }).toArray();
                var selectAddressObj = _.find(customerAddressData , function (obj) {
                    return obj.address_id == element.address_id;
                });
                var self = this;

                element.info.address(selectAddressObj.info);
                element.info.address_html(selectAddressObj.address_html);

                $('body').trigger('processStart');

                $.ajax({
                    url: window.subscriptionConfig.url_change_address_delivery,
                    method: 'POST',
                    dataType : 'json',
                    data: {
                        'profile_id':window.subscriptionConfig.profileData.profile_id,
                        'shipping_address': selectAddressObj.address_id,
                        'delivery_type' : deliveryType,
                        'profile_product_cart_id' : profileCartItemIds
                    },
                    async:true,
                    success:function (result) {
                        window.customDataCalendar.restrictDate[indexBlockShippingAddress] = JSON.parse(result.data);
                        $('body').trigger('processStop');
                        var deliveryDateBeforeChange = parentBlock.find('.current-delivery-date').val();
                        var deliveryOld = new Date(deliveryDateAfterChange);
                        var deliveryNew = new Date(deliveryDateBeforeChange);

                        if (deliveryOld.getTime() != deliveryNew.getTime()) {
                        parentComponent.shouldShowMessage(parentBlock,true);
                            deliveryDateAfterChange = deliveryDateBeforeChange;
                        } else {
                            parentComponent.shouldShowMessage(parentBlock,false);
                        }

                        // Remove isList parameters
                        var oldWindowURL = window.location.href;
                        if(oldWindowURL.includes("/list/1")) {
                            var newWindowURL = oldWindowURL.replace('/list/1','');
                            window.history.pushState(null, null, newWindowURL);
                        }
                    },
                    error:function () {
                        $('body').trigger('processStop');
                    }
                });

                /* do mass change value */
                $.each(element.items , function (index , value) {
                    if (!_.isUndefined($("#address-productcat-" + value.item_id))) {
                        $("#address-productcat-" + value.item_id).val(element.address_id);
                    }
                });

                profile.profileHasChanged(true);
            },
            getNextDate: function (date) {
                var current_date = new Date(date);
                current_date.setDate(current_date.getDate() + 1);
                return jQuery.datepicker.formatDate('yy-mm-dd',current_date);

            },
            getStrToTime: function (date) {
                if (date == 'now') {
                    return new Date().getTime();
                }
                if (typeof date == 'undefined') {
                    return new Date(this.next_delivery_date()).getTime();
                }
                return new Date(date).getTime();
            },
            getNextDeliveryDate: function (item_delivery_date,next_delivery_date) {
                if (item_delivery_date() == null) {
                    return next_delivery_date;
                }
                return item_delivery_date;
            },
            getOrderStatus: function (product_id) {
                var productOutOffStock = this.product_out_off_stock;
                if ($.inArray(product_id,productOutOffStock) > -1) {
                return $t("Not delivered yet");
                }
                return null;

            },
            getStockLevel: function (product_id) {
                var productStockLevel = this.product_stock_level;
                if (productStockLevel[product_id]) {
                    return productStockLevel[product_id];
                }
                return null;

            },
            isDayOfWeekAndIntervalUnitMonth: function(){
                var frequencyUnit = profile.frequency_unit();
                var nextDeliveryDateCalculationOption = course.getNextDeliveryDateCalculationOption();

                if (nextDeliveryDateCalculationOption === 'day_of_week' && (frequencyUnit === 'month' || frequencyUnit === 'months')) {
                    return true;
                }

                return false;
            },
            getDayOfWeek: function(date) {
                var d = new Date(date);
                var dayOfWeek = new Array(7);
                dayOfWeek[0] = $t('Sunday');
                dayOfWeek[1] = $t('Monday');
                dayOfWeek[2] = $t('Tuesday');
                dayOfWeek[3] = $t('Wednesday');
                dayOfWeek[4] = $t('Thursday');
                dayOfWeek[5] = $t('Friday');
                dayOfWeek[6] = $t('Saturday');

                return dayOfWeek[d.getDay()];
            },
            calculateNthWeekdayOfMonth: function(date){
                var nthweekdayOfMonth = new Array(5);
                nthweekdayOfMonth[1] = $t('1st');
                nthweekdayOfMonth[2] = $t('2nd');
                nthweekdayOfMonth[3] = $t('3rd');
                nthweekdayOfMonth[4] = $t('4th');
                nthweekdayOfMonth[5] = $t('Last');

                if (!isNaN(date)) {
                    return nthweekdayOfMonth[date];
                } else {
                    var d = new Date(date);
                    return nthweekdayOfMonth[Math.ceil(d.getDate() / 7)];
                }
            },
            getDeliveryMessage: function(code){
                var itemData = this.itemData(),
                    deliveryMessage = '',
                    nthWeekdayOfMonth = '',
                    dayOfWeek = '',
                    lang = $('html').attr('lang');
                if (itemData.length) {
                    for (var i = 0; i < itemData.length; i++) {
                        if (itemData[i].info.code == code) {
                            if (itemData[i].info.next_delivery_date() != '') {
                                if (itemData[i].info.next_delivery_date() == profile.next_delivery_date()
                                    && profile.getNthWeekdayOfMonth() != null
                                    && profile.getDayOfWeek() != null
                                ) {
                                    nthWeekdayOfMonth = this.calculateNthWeekdayOfMonth(profile.getNthWeekdayOfMonth());
                                    dayOfWeek = $t(profile.getDayOfWeek());
                                } else {
                                    nthWeekdayOfMonth += this.calculateNthWeekdayOfMonth(itemData[i].info.next_delivery_date());
                                    dayOfWeek = this.getDayOfWeek(itemData[i].info.next_delivery_date());
                                }

                                if (lang == 'ja-JP') {
                                    deliveryMessage = nthWeekdayOfMonth + dayOfWeek + $t('every');
                                } else {
                                    deliveryMessage = $t('every') + ' ' + nthWeekdayOfMonth + ' ' + dayOfWeek;
                                }
                            }
                            break;
                        }
                    }
                }

                return deliveryMessage;
            },
        });
    });
