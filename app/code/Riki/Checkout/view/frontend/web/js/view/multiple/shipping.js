/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
/*global define*/
define(
    [
        'jquery',
        "underscore",
        'Magento_Ui/js/form/form',
        'ko',
        'Magento_Customer/js/model/customer',
        'Magento_Customer/js/model/customer/address',
        'Magento_Customer/js/model/address-list',
        'Magento_Checkout/js/model/address-converter',
        'Magento_Checkout/js/model/quote',
        'Magento_Ui/js/model/messageList',
        'Magento_Checkout/js/action/create-shipping-address',
        'Magento_Checkout/js/action/select-shipping-address',
        'Magento_Checkout/js/model/shipping-rates-validator',
        'Magento_Checkout/js/model/shipping-address/form-popup-state',
        'Magento_Checkout/js/model/shipping-service',
        'Magento_Checkout/js/action/select-shipping-method',
        'Magento_Checkout/js/model/shipping-rate-registry',
        'Magento_Checkout/js/action/set-shipping-information',
        'Magento_Checkout/js/model/step-navigator',
        'Magento_Ui/js/modal/modal',
        'Magento_Checkout/js/model/checkout-data-resolver',
        'Magento_Checkout/js/checkout-data',
        'uiRegistry',
        'mage/translate',
        'mage/storage',
        'Riki_Checkout/js/action/multiple/set-shipping-address',
        'mage/url',
        'Magento_Checkout/js/model/url-builder',
        'Magento_Catalog/js/price-utils',
        'Magento_Checkout/js/model/full-screen-loader',
        'Magento_Theme/js/datepicker-ja',
        'Magento_Checkout/js/model/shipping-rate-service'
    ],
    function(
        $,
        _,
        Component,
        ko,
        customer,
        address,
        addressList,
        addressConverter,
        quote,
        messageList,
        createShippingAddress,
        selectShippingAddress,
        shippingRatesValidator,
        formPopUpState,
        shippingService,
        selectShippingMethodAction,
        rateRegistry,
        setShippingInformationAction,
        stepNavigator,
        modal,
        checkoutDataResolver,
        checkoutData,
        registry,
        $t,
        storage,
        setShippingAddressAction,
        urlBuilder,
        urlModelBuilder,
        priceUtils,
        fullScreenLoader
    ) {
        'use strict';
        var popUp = null;
        var selectPopUp = null;
        var arrSelectPopup = [];
        return Component.extend({
            defaults: {
                template: 'Riki_Checkout/multiple/shipping'
            },
            options: {
                url: {
                    remove: urlBuilder.build('checkout/sidebar/removeItem'),
                    update_item_qty: urlBuilder.build('checkout/sidebar/updateItemQty'),
                    updateGiftWrapping: urlBuilder.build('multicheckout/update/wrapping')
                }
            },
            visible: ko.observable(!quote.isVirtual()),
            addressListRender: ko.observable(window.checkoutConfig.customerData.addresses),
            errorValidationMessage: ko.observable(false),
            isCustomerLoggedIn: customer.isLoggedIn,
            isFormPopUpVisible: formPopUpState.isVisible,
            isFormInline: addressList().length == 0,
            isNewAddressAdded: ko.observable(false),
            quoteIsVirtual: quote.isVirtual(),
            redirectCartPage : ko.observable(''),
            formData : ko.observable(''),
            addressDdateInfo: ko.observableArray([]),
            itemImages: ko.observableArray(),
            redirectSingleCheckout: ko.observable(''),
            subUnit: ko.observable(""),
            subInterval: ko.observable(""),
            isEditNextDDate: ko.observable(""),
            isSubscription: ko.observable(''),
            allowChooseDeliveryDate: ko.observable(true),
            isHanpukai: ko.observable(''),
            isAllowChangeHanpukaiDeliveryDate: ko.observable(""),
            isEnableDeliveryDate: ko.observable(0),
            arrHanpukaiDeliveryDateConfig: ko.observableArray([]),
            collectedTotals: ko.observable(false),
            isContainFreeGift: ko.observable(false),
            initialize: function () {
                var self = this;
                this._super();
                var quoteItemData = window.checkoutConfig.quoteItemData;
                this.storeCode = window.checkoutConfig.storeCode;
                this.isSubscription(window.checkoutConfig.quoteData.riki_course_id);
                this.allowChooseDeliveryDate(window.checkoutConfig.quoteData.allow_choose_delivery_date);
                this.isHanpukai(window.checkoutConfig.quoteData.is_hanpukai);
                this.subUnit(window.checkoutConfig.quoteData.sub_unit);
                this.subInterval(window.checkoutConfig.quoteData.sub_interval);
                this.isEditNextDDate(window.checkoutConfig.quoteData.is_edit_next_ddate);
                this.isAllowChangeHanpukaiDeliveryDate(window.checkoutConfig.quoteData.is_allow_change_hanpukai_delivery_date);
                this.arrHanpukaiDeliveryDateConfig(window.checkoutConfig.quoteData.hanpukai_delivery_date_config);
                if (typeof self.isAllowChangeHanpukaiDeliveryDate() !== 'undefined') {
                    if (self.isAllowChangeHanpukaiDeliveryDate() == 1) {
                        self.isEnableDeliveryDate(1);
                    } else if(self.isAllowChangeHanpukaiDeliveryDate() == 0) {
                        self.isEnableDeliveryDate(0);
                    }
                }

                if (!quote.isVirtual()) {
                    stepNavigator.registerStep(
                        'multiple_order_confirm',
                        '',
                        $t('Order Confirmation'),
                        this.visible,
                        _.bind(this.navigate, this),
                        10
                    );
                }

                /** handle browser back button or back link flow */
                window.onhashchange = function() {
                    if(window.location.hash == '#multiple_order_confirm') {
                        fullScreenLoader.startLoader();

                        var sortedItems = stepNavigator.steps().sort(stepNavigator.sortItems);
                        var bodyElem = $.browser.safari || $.browser.chrome ? $("body") : $("html");
                        var scrollToElementId = null;
                        sortedItems.forEach(function(element) {
                            if (element.code == 'shipping') {
                                element.isVisible(true);
                            } else {
                                element.isVisible(false);
                            }
                        });
                        $('#checkout .page-title-wrapper .page-title > span').text($.mage.__('Order Confirmation'));
                        bodyElem.animate({scrollTop: $('#shipping').offset().top}, 0);

                        window.location.reload(true);
                    }
                };

                /**
                 * Multiple address does not allow checkout with Subscription not Hanpukai
                 * Redirect to homepage if Subscription not Hanpukai
                 */
                if (this.isSubscription() != null && this.isHanpukai() == 0) {
                    location.href = urlBuilder.build('');
                    return false;
                }

                var hasNewAddress = addressList.some(function (address) {
                    return address.getType() == 'new-customer-address';
                });

                this.isNewAddressAdded(hasNewAddress);
                this.redirectSingleCheckout(urlBuilder.build('checkout/#shipping'));
                this.isFormPopUpVisible.subscribe(function (value) {
                    if (value) {
                        self.resetShippingAddressError();
                        self.getPopUp().openModal();
                    }
                });

                quote.shippingMethod.subscribe(function (shippingMethod) {
                    self.errorValidationMessage(false);
                    if(shippingMethod != null && !self.isFormInline && !self.collectedTotals()) {
                        self.collectedTotals(true);
                        registry.get('checkout.rewardPoints' , function (rewardPointsObj){
                            /*use setting using point from profile page*/
                            rewardPointsObj.pointControl(window.customerData.reward_user_setting);
                            rewardPointsObj.pointAmount(window.customerData.reward_user_redeem);
                            if(rewardPointsObj.pointControl() == 2 && rewardPointsObj.pointAmount() >= 0) {
                                rewardPointsObj.apply();
                            } else {
                                rewardPointsObj.applyLabel();
                            }
                        });
                    }
                });

                checkoutDataResolver.resolveShippingAddress();

                registry.async('checkoutProvider')(function (checkoutProvider) {
                    var shippingAddressData = checkoutData.getShippingAddressFromData();
                    if (shippingAddressData) {
                        checkoutProvider.set(
                            'shippingAddress',
                            $.extend({}, checkoutProvider.get('shippingAddress'), shippingAddressData)
                        );
                    }
                    checkoutProvider.on('shippingAddress', function (shippingAddressData) {
                        checkoutData.setShippingAddressFromData(shippingAddressData);
                    });
                });
                $('#checkout .page-title-wrapper .page-title > span').text($t('Order Confirmation'));

                messageList.noticeMessages.subscribe(function(noticeMessages) {
                    if(noticeMessages.length == 0) {
                        $('input.delivery_date, select.delivery_time').removeClass('warning-ddate');
                    }
                });

                /** Init submittedFormData default */
                var submittedFormData = '';
                var defaultShipping = null,
                    isShippingAddressInitialized = false;
                if(window.checkoutConfig.customerData.addresses.length > 0 && !_.isUndefined(window.checkoutConfig.customerData.custom_attributes.amb_type) && window.checkoutConfig.customerData.custom_attributes.amb_type.value == '1') {
                    var addressListTmp = window.checkoutConfig.customerData.addresses;
                    isShippingAddressInitialized = addressListTmp.some(function (address) {
                        if(!_.isUndefined(address.custom_attributes.riki_type_address) && address.custom_attributes.riki_type_address.value == 'company') {
                            defaultShipping = address.id;
                            return true;
                        }
                        return false;
                    });

                }
                if(!isShippingAddressInitialized) {
                    defaultShipping = window.checkoutConfig.customerData.default_shipping;
                    if (!defaultShipping) {
                        defaultShipping = (window.checkoutConfig.customerData.addresses[0]) ? window.checkoutConfig.customerData.addresses[0].id : null;
                    }
                }

                if (defaultShipping != null) {
                    for (var $i = 0; $i < quoteItemData.length; $i++) {
                        var customer_address_id = quoteItemData[$i].customer_address_id;
                        if (customer_address_id != null) {
                            submittedFormData += '&cart[' + quoteItemData[$i].item_id + '][address]=' + customer_address_id;
                        } else {
                            submittedFormData += '&cart[' + quoteItemData[$i].item_id + '][address]=' + defaultShipping;
                        }
                    }
                    self.formData(submittedFormData.substr(1, submittedFormData.length));
                }

                /** Render block items follow address & DeliveryType */
                if(!this.isFormInline) {
                    setShippingAddressAction(quote, self.formData()).done(
                        quote.quoteItemDdateInfo.subscribe(function (newPayload) {
                            self.addressDdateInfo(newPayload);
                            /** Check order contain Free Gift */
                            for(var i=0; i < newPayload.addressDdateInfo.length; i++) {
                                for(var j=0; j < newPayload.addressDdateInfo[i].ddate_info.length; j++) {
                                    for(var x=0; x < newPayload.addressDdateInfo[i].ddate_info[j].cartItems.length; x++) {
                                        if(newPayload.addressDdateInfo[i].ddate_info[j].cartItems[x].free_item) {
                                            self.isContainFreeGift(true);
                                        }else {
                                            if(typeof newPayload.addressDdateInfo[i].items_error_messages != 'undefined' && newPayload.addressDdateInfo[i].ddate_info[j].cartItems[x].id in newPayload.addressDdateInfo[i].items_error_messages){
                                                registry.get('checkout.steps.billing-step.payment.payments-list', function (element){
                                                    element.isPlaceOrderActionAllowedFromSelectAddress(false);
                                                });
                                            }
                                        }
                                    }
                                }
                            }
                        })
                    ).fail();
                }

                /** Render block items follow address & DeliveryType after select address */
                this.formData.subscribe(function (value) {
                    /** START google dataLayer tag */
                    window.dataLayer = window.dataLayer || [];
                    window.dataLayer.push({
                        'event': 'checkoutOption',
                        'ecommerce': {
                            'checkout_option': {
                                'actionField': {
                                    'step': 2,
                                    'option': ['Shipping Address - Change']
                                }
                            }
                        }
                    });
                    setShippingAddressAction(quote, value).done(
                        quote.quoteItemDdateInfo.subscribe(function (newPayload) {
                            self.addressDdateInfo(newPayload);

                            /**
                             * This will reload page after choose address success
                             */
                            fullScreenLoader.startLoader();
                            window.location.reload(true);
                        })
                    ).fail();
                });

                /** Render dateTimePicker */
                ko.bindingHandlers.multiDateTimePicker = {
                    init: function (element, valueAccessor, allBindingsAccessor) {
                        var $el = $(element);

                        //initialize datetimepicker
                        var restrictDateList = allBindingsAccessor.get('restrictDateList'),
                            periodRank = allBindingsAccessor.get('periodRank') || 30;
                        var errors = allBindingsAccessor.get('errors');
                        var itemIds = allBindingsAccessor.get('itemIds');
                        var currentDateServer = '0';
                        var maxDate = "+" + periodRank + "D";

                        //Calculate min date range
                        var dates = restrictDateList.map(function(item) {
                            return new Date(item);
                        });

                        var latest = new Date(Math.max.apply(null,dates));
                        var minDateRank = 0;
                        var oneDay = 1000 * 60 * 60 * 24;
                        var currentDayObj = new Date();
                        var currentDayTmp = new Date(currentDayObj.getTime());
                        var firstDDTmp = new Date(latest.getTime());
                        var differenceMs = Math.abs(currentDayTmp - firstDDTmp);
                        minDateRank = Math.round(differenceMs/oneDay) + 1;

                        var minDate = new Date(currentDayObj.getTime());
                        minDate.setDate(minDate.getDate() + minDateRank);


                        if (typeof self.isHanpukai() !== 'undefined' && self.isHanpukai() == 1 ) {
                            if (typeof self.isAllowChangeHanpukaiDeliveryDate() !== 'undefined') {
                                if (self.isAllowChangeHanpukaiDeliveryDate() == 0) {
                                    maxDate = null;
                                } else if (self.isAllowChangeHanpukaiDeliveryDate() == 1) {
                                    var maxDateInRestrictDateList = null;
                                    if (restrictDateList.length > 0) {
                                        maxDateInRestrictDateList = restrictDateList[restrictDateList.length - 1];
                                        maxDateInRestrictDateList = new Date(maxDateInRestrictDateList);
                                    }
                                    maxDate = new Date(self.arrHanpukaiDeliveryDateConfig['hanpukai_delivery_date_to']);
                                    var hanpukaiDeliveryDateFromSetting
                                        = new Date(self.arrHanpukaiDeliveryDateConfig['hanpukai_delivery_date_from']);
                                    if (hanpukaiDeliveryDateFromSetting >= maxDateInRestrictDateList) {
                                        minDate = hanpukaiDeliveryDateFromSetting;
                                    } else {
                                        minDate = maxDateInRestrictDateList;
                                    }
                                }
                            }
                        }

                        var disableCalendar = false;

                        if (typeof errors != 'undefined' && typeof  itemIds != 'undefined') {
                            for (var item_error in errors) {
                                if (errors.hasOwnProperty(item_error) && itemIds.indexOf(item_error) != -1) {
                                    disableCalendar = true;
                                    maxDate = null;
                                    break;
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
                            showMinute: false,
                            minDate: minDate,
                            maxDate: maxDate,
                            disabled: disableCalendar,
                            onSelect: function (date) {
                                var $this = $(this);

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

                                var frequency = $this.data('datepicker').inline = false;

                                /** If this input is not next delivery */
                                if (!$this.hasClass('next_delivery_date')) {
                                    if (typeof self.subUnit() !== 'undefined'
                                        && self.subUnit() !== '' && typeof self.subInterval() !== 'undefined'
                                        && self.subInterval() !== '') {
                                        var unit = self.subUnit(),
                                            interval = self.subInterval(),
                                            delivery_date_input_name = $this.attr('name'),
                                            delivery_date_input_value = $this.val(),
                                            next_delivery_date_input_name = 'input[name="next_' + delivery_date_input_name + '"]',
                                            objDate = new Date(delivery_date_input_value);

                                        /** Minimum next delivery date */
                                        var objMinNextDate = new Date(delivery_date_input_value);
                                        objMinNextDate.setDate(objMinNextDate.getDate() + 1);

                                        /** Maximum next delivery date */
                                        var objMaxNextDate = new Date();
                                        objMaxNextDate.setDate(objMaxNextDate.getDate() + periodRank);

                                        if (unit === 'month') {
                                            objDate.setMonth(objDate.getMonth() + parseInt(interval));

                                            /** Maximum next delivery date */
                                            objMaxNextDate.setMonth(objMaxNextDate.getMonth() + parseInt(interval));
                                        } else if (unit === 'week') {
                                            objDate.setDate(objDate.getDate() + parseInt(interval) * 7);

                                            /** Maximum next delivery date */
                                            objMaxNextDate.setDate(objMaxNextDate.getDate() + parseInt(interval) * 7);
                                        }
                                        $(next_delivery_date_input_name).val(objDate.getFullYear() + '/' + ('0' + (parseInt(objDate.getMonth()) + 1)).slice(-2) + '/' + ('0' + objDate.getDate()).slice(-2));

                                        /** Set new max, min date for next delivery date */
                                        $(next_delivery_date_input_name).datepicker("destroy");
                                        $(next_delivery_date_input_name).datepicker({
                                            firstDay: 1,
                                            dateFormat: "yy/mm/dd",
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
                                            maxDate: objMaxNextDate
                                        });
                                        $('.ui-datepicker').addClass('notranslate');
                                    }
                                }
                            },
                            beforeShowDay: function (date) {
                                var string = jQuery.datepicker.formatDate('yy-mm-dd', date);
                                return [restrictDateList.indexOf(string) == -1]
                            },
                            beforeShow: function(input) {
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
                        };
                        if($('html').attr('lang') == 'ja') {
                            $.datepicker.setDefaults($.datepicker.regional['ja']);
                        }
                        $el.datepicker(options);
                        $('.ui-datepicker').addClass('notranslate');

                        // Calculate plus date
                        var datePlusFrequency = function (paramObjFirstDD, arrFrequency) {
                            var objFirstDD = new Date(paramObjFirstDD.getTime());
                            var interval = parseInt(arrFrequency[0]), unit = arrFrequency[1];
                            if (unit === 'month') {
                                objFirstDD.setMonth(objFirstDD.getMonth() + interval);
                                return objFirstDD
                            }

                            /** week */
                            objFirstDD.setDate(objFirstDD.getDate() + interval * 7);
                            return objFirstDD;
                        };

                        if (typeof self.isHanpukai() !== 'undefined' && self.isHanpukai() == 1) {
                            if (typeof self.isAllowChangeHanpukaiDeliveryDate() !== 'undefined'
                                && self.isAllowChangeHanpukaiDeliveryDate() == 0) {
                                if (typeof self.arrHanpukaiDeliveryDateConfig['hanpukai_first_delivery_date'] !== 'undefined') {
                                    if ($el.hasClass('next_delivery_date') === false) {
                                        $el.datepicker('setDate', self.arrHanpukaiDeliveryDateConfig['hanpukai_first_delivery_date']);
                                    }
                                    if ($el.hasClass('next_delivery_date') === true) {
                                        var unit = self.subUnit(),
                                            interval = self.subInterval(),
                                            objDate = new Date(self.arrHanpukaiDeliveryDateConfig['hanpukai_first_delivery_date']);
                                        var arrFrequency = [];
                                        arrFrequency[0] = interval;
                                        arrFrequency[1] = unit;
                                        var nextDeliveryDate = datePlusFrequency(objDate, arrFrequency);
                                        $el.datepicker('setDate', nextDeliveryDate);
                                    }
                                }
                            }
                        }
                    },
                    update: function (element, valueAccessor, allBindings) {}
                };

                /** Push image product */
                for(var i=0; i < window.checkoutConfig.quoteItemData.length; i++) {
                    this.itemImages[window.checkoutConfig.quoteItemData[i].item_id] = window.checkoutConfig.quoteItemData[i].thumbnail;
                }

                return this;
            },

            /**
             * Render data-form in option select address for every items
             * This value will be push to this.formData
             * and trigger to ko.subscribe: quote.quoteItemDdateInfo
             * @return String
             */
            renderFormData: function (item_id, new_address_id) {
                var quoteItemData = quote.quoteItemDdateInfo().addressDdateInfo;
                var submittedFormData = '';

                for (var $i = 0; $i < quoteItemData.length; $i++) {
                    var ddate_info = quoteItemData[$i].ddate_info;
                    var address_id = quoteItemData[$i].address_id;
                    for (var $y = 0; $y < ddate_info.length; $y++) {
                        var cart_items = ddate_info[$y].cartItems;
                        for (var $z = 0; $z < cart_items.length; $z++) {
                            if (item_id == cart_items[$z].item_id) {
                                submittedFormData += '&cart[' + cart_items[$z].item_id + '][address]=' + new_address_id;
                            } else {
                                submittedFormData += '&cart[' + cart_items[$z].item_id + '][address]=' + address_id;
                            }
                        }
                    }
                }
                return submittedFormData.substr(1, submittedFormData.length);
            },

            /**
             * Check customer has not address will show popup add new address
             * @param element
             * @param viewModel
             */
            afterRenderAddresses: function (element, viewModel) {
                if (addressList().length == 0) {
                    checkoutData.setShippingAddressAction('add');
                    viewModel.showFormPopUp();
                }
            },

            navigate: function () {
            },

            initElement: function(element) {
                if (element.index === 'shipping-address-fieldset') {
                    shippingRatesValidator.bindChangeHandlers(element.elems(), false);
                }
            },

            addNewAddress: function (item_id) {
                checkoutData.setShippingAddressAction('add');

                /** Reset NEW shipping address added from single which we don't want to save after checkout */
                checkoutData.setNewCustomerShippingAddress(null);

                var shippingAddressData = checkoutData.getShippingAddressFromData();

                if(shippingAddressData != null) {
                    shippingAddressData.telephone = '';
                    shippingAddressData.custom_attributes.riki_type_address = '';
                    shippingAddressData.custom_attributes.riki_nickname = '';
                    shippingAddressData.custom_attributes.firstnamekana = '';
                    shippingAddressData.custom_attributes.lastnamekana = '';
                    shippingAddressData.custom_attributes.apartment = '';
                    shippingAddressData.city = '';
                    shippingAddressData.street[0] = '';
                    shippingAddressData.street[1] = '';
                    shippingAddressData.lastname = '';
                    shippingAddressData.firstname = '';
                    shippingAddressData.postcode = '';
                    shippingAddressData.region = '';
                    shippingAddressData.save_in_address_book = true;

                    if (typeof shippingAddressData.customer_address_id != 'undefined') {
                        shippingAddressData.customer_address_id = null;
                    }

                    if (typeof shippingAddressData.region_code != 'undefined') {
                        shippingAddressData.region_code = null;
                    }

                    if (typeof shippingAddressData.region_id != 'undefined') {
                        shippingAddressData.region_id = null;
                    }

                    if (typeof shippingAddressData.customer_id != 'undefined') {
                        shippingAddressData.customer_id = null;
                    }

                    /** Push item_id to save new address and set new id_address to item */
                    shippingAddressData.item_id = item_id;
                } else {
                    /** Push item_id to save new address and set new id_address to item */
                    shippingAddressData = {};
                    shippingAddressData.item_id = item_id;
                }

                window.checkoutConfig.isNewAddress = true;
                registry.get('checkoutProvider', function(checkoutProvider) {
                    checkoutProvider.set(
                        'shippingAddress',
                        $.extend({}, checkoutProvider.get('shippingAddress'), shippingAddressData)
                    );
                });
                this.showFormPopUp();
                window.checkoutConfig.isNewAddress = false;
            },

            editSelectedShippingAddress: function (address_id) {
                checkoutData.setShippingAddressAction('edit');
                var selectedAddressItem = _.filter(addressList(), function (item) {
                    return item.customerAddressId == address_id;
                });

                if (selectedAddressItem.length) {
                    var quoteAddress = selectedAddressItem[0],
                        shippingAddressData = null,
                        addressType = (!_.isUndefined(quoteAddress.customAttributes.riki_type_address.value)) ? quoteAddress.customAttributes.riki_type_address.value : quoteAddress.customAttributes.riki_type_address;

                    /** redirect to KSS page to edit address if addressType is not shipping */
                    var editedAddressType = false;

                    if(addressType == 'home') {
                        if(!_.isUndefined(quoteAddress.company) && quoteAddress.company != '') {
                            editedAddressType = 'homeCompany';
                        } else {
                            editedAddressType = 'home';
                        }
                    } else if(addressType == 'company') {
                        editedAddressType = 'company';
                    }

                    if(editedAddressType) {
                        window.location = urlBuilder.build('customer/account/setUpdateFlag/type/' + editedAddressType);
                        return false;
                    }

                    quoteAddress.customAttributes.lastnamekana = (typeof quoteAddress.customAttributes.lastnamekana.value != 'undefined') ? quoteAddress.customAttributes.lastnamekana.value : quoteAddress.customAttributes.lastnamekana;
                    quoteAddress.customAttributes.firstnamekana = (typeof quoteAddress.customAttributes.firstnamekana.value != 'undefined') ? quoteAddress.customAttributes.firstnamekana.value : quoteAddress.customAttributes.firstnamekana;
                    quoteAddress.customAttributes.riki_nickname = (typeof quoteAddress.customAttributes.riki_nickname.value != 'undefined') ? quoteAddress.customAttributes.riki_nickname.value : quoteAddress.customAttributes.riki_nickname;
                    if (typeof quoteAddress.customAttributes.apartment != 'undefined') {
                        quoteAddress.customAttributes.apartment = (typeof quoteAddress.customAttributes.apartment.value != 'undefined') ? quoteAddress.customAttributes.apartment.value : quoteAddress.customAttributes.apartment;
                    } else {
                        quoteAddress.customAttributes.apartment = '';
                    }
                    if (typeof quoteAddress.customAttributes.riki_type_address != 'undefined') {
                        quoteAddress.customAttributes.riki_type_address = (typeof quoteAddress.customAttributes.riki_type_address.value != 'undefined') ? quoteAddress.customAttributes.riki_type_address.value : quoteAddress.customAttributes.riki_type_address;
                    } else {
                        quoteAddress.customAttributes.riki_type_address = '';
                    }
                    shippingAddressData = addressConverter.quoteAddressToFormAddressData(quoteAddress);
                    registry.get('checkoutProvider', function (checkoutProvider) {
                        if (shippingAddressData) {
                            checkoutProvider.set(
                                'shippingAddress',
                                $.extend({}, checkoutProvider.get('shippingAddress'), shippingAddressData)
                            );
                        }
                    });
                    registry.get('checkout.steps.shipping-step.shippingAddress', function (el) {
                        el.showFormPopUp();
                    });
                }
            },

            /** Hide Edit Address Button when user is CNC or CIS member */
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

            getPopUp: function() {
                var self = this;
                if (!popUp) {
                    var buttons = this.popUpForm.options.buttons;
                    this.popUpForm.options.buttons = [
                        {
                            text: buttons.save.text ? buttons.save.text : $t('Save Address'),
                            class: buttons.save.class ? buttons.save.class : 'action primary action-save-address',
                            click: self.saveNewAddress.bind(self)
                        }
                    ];
                    this.popUpForm.options.modalClass = "add-address modal_checkout";
                    if (window.customerData.addresses.length < 1 && !quote.isVirtual()) {
                        this.popUpForm.options.modalClass = "no-close";
                    }

                    this.popUpForm.options.closed = function() {
                        self.isFormPopUpVisible(false);
                        self.resetShippingAddressError();
                    };
                    popUp = modal(this.popUpForm.options, $(this.popUpForm.element));
                }
                return popUp;
            },

            resetShippingAddressError: function () {
                var elementFormArray = ['riki_normal_name_group.0', 'riki_normal_name_group.1', 'riki_kana_name_group.0', 'riki_kana_name_group.1', 'riki_nickname', 'postcode', 'region_id', 'street.0', 'telephone'];
                elementFormArray.forEach(function(el) {
                    registry.get('checkout.steps.shipping-step.shippingAddress.shipping-address-fieldset.' + el).error(false);
                });
            },

            /** Show select address popup */
            getSelectAddressPopUp: function(address_id, item_id) {
                var self = this;
                var id = '#opc-select-shipping-address-' + address_id + '-item-' + item_id;
                if ($.inArray(id, arrSelectPopup) < 0) { // if Node id is not in array
                    arrSelectPopup.push(id); // push Node id
                    var optionsPopup = {
                        innerScroll: true,
                        responsive: false,
                        title: $t('Setting the address'),
                        modalClass: 'select-shipping-address-modal modal_checkout',
                        type: 'popup',
                        buttons: [{
                            text: $t('Shipping here'),
                            class: 'action primary action-save-address',
                            click: function(){
                                var selector = "select[data-select='" + id.substr(1, id.length) + "'] option:selected";
                                var data = $(selector).last().attr('data-form');

                                /** Set data to render again */
                                self.setDataFormRenderAgain(data);

                                this.closeModal();
                            }
                        }]
                    };
                    modal(optionsPopup, $(id));
                }
                return $(id);
            },

            setDataFormRenderAgain: function(data){
                this.formData(data);
            },

            /** Show address form popup */
            showFormPopUp: function() {
                this.isFormPopUpVisible(true);
            },

            /** Show select address popup */
            showSelectShippingAddressPopUp: function(address_id, item_id) {
                var select = '#select-address-' + address_id + '-item-' + item_id;
                $(select).val(address_id);
                this.getSelectAddressPopUp(address_id, item_id).modal('openModal');
            },


            /** Save new shipping address */
            saveNewAddress: function () {
                var self = this,
                    shippingAddressAction = checkoutData.getShippingAddressAction();
                if (shippingAddressAction == 'add' || (shippingAddressAction == 'edit' && quote.shippingAddress().getType() == 'new-customer-address')) {
                    this.source.set('params.invalid', false);
                    this.source.trigger('shippingAddress.data.validate');
                    this.source.trigger('shippingAddress.custom_attributes.data.validate');
                    this.source.trigger('shippingAddress.data.validate');

                    if (!this.source.get('params.invalid')) {
                        fullScreenLoader.startLoader();
                        var addressData = this.source.get('shippingAddress');

                        addressData.region = {
                            region_id: addressData.region_id
                        };
                        delete addressData.region_id;
                        delete addressData.customer_address_id;
                        delete addressData.region_code;
                        delete addressData.save_in_address_book;

                        /** Get item_id to push new address_id */
                        var item_id = addressData.item_id;
                        delete addressData.item_id;

                        storage.post(
                            urlModelBuilder.createUrl('/addresses/me', {}),
                            JSON.stringify({address: addressData}),
                            false
                        ).done($.proxy(function (response) {
                            checkoutData.setShippingAddressFromData(null);

                            var new_customer_address_id = response.id;
                            /** Auto set new address to item */
                            var submittedFormData = '';
                            var defaultShipping = window.checkoutConfig.customerData.default_shipping;
                            var quoteItemData = window.checkoutConfig.quoteItemData;
                            if (!defaultShipping) {
                                defaultShipping = (window.checkoutConfig.customerData.addresses[0]) ? window.checkoutConfig.customerData.addresses[0].id : null;
                            }
                            if (defaultShipping != null) {
                                for (var $i = 0; $i < quoteItemData.length; $i++) {
                                    var customer_address_id = quoteItemData[$i].customer_address_id;
                                    if (customer_address_id != null) {
                                        if (quoteItemData[$i].item_id == item_id) {
                                            submittedFormData += '&cart[' + quoteItemData[$i].item_id + '][address]=' + new_customer_address_id;
                                        } else {
                                            submittedFormData += '&cart[' + quoteItemData[$i].item_id + '][address]=' + customer_address_id;
                                        }
                                    } else {
                                        if (quoteItemData[$i].item_id == item_id) {
                                            submittedFormData += '&cart[' + quoteItemData[$i].item_id + '][address]=' + new_customer_address_id;
                                        } else {
                                            submittedFormData += '&cart[' + quoteItemData[$i].item_id + '][address]=' + defaultShipping;
                                        }
                                    }
                                }
                                /** START google dataLayer tag */
                                window.dataLayer = window.dataLayer || [];
                                window.dataLayer.push({
                                    'event': 'checkoutOption',
                                    'ecommerce': {
                                        'checkout_option': {
                                            'actionField': {
                                                'step': 2,
                                                'option': ['Shipping Address - Change']
                                            }
                                        }
                                    }
                                });
                                self.formData(submittedFormData.substr(1, submittedFormData.length));
                            }
                            this.getPopUp().closeModal();
                            fullScreenLoader.stopLoader();
                        }, this)).fail(
                            function (response) {
                                response = JSON.parse(response);
                                messageList.addErrorMessage({'message': response.message});
                                self.getPopUp().closeModal();
                                fullScreenLoader.stopLoader();
                            }
                        );
                    }
                } else {
                    fullScreenLoader.startLoader();
                    var payload = checkoutData.getShippingAddressFromData();
                    return storage.post(
                        urlBuilder.build('riki-checkout/address/save'),
                        payload,
                        false,
                        'application/x-www-form-urlencoded'
                    ).done(
                        function (response) {
                            var items = [];
                            if (!response.error) {
                                messageList.addSuccessMessage({'message': response.message});
                                var customerData = response.customerData;
                                if (Object.keys(customerData).length) {
                                    $.each(customerData.addresses, function (key, item) {
                                        items.push(new address(item));
                                    });
                                }
                                var newAddressItem = _.filter(addressList(), function (item) {
                                    return item.getType() == 'new-customer-address';
                                });
                                addressList(items);
                                if (newAddressItem.length) {
                                    addressList.push(newAddressItem[0]);
                                }
                                var selectedAddressId = checkoutData.getShippingAddressFromData().customer_address_id,
                                    selectedAddressItem = _.filter(addressList(), function (item) {
                                        return item.customerAddressId == selectedAddressId;
                                    });
                                if (selectedAddressItem.length) {
                                    // Address after edited must be selected as a shipping address
                                    selectShippingAddress(selectedAddressItem[0]);
                                    checkoutData.setSelectedShippingAddress(selectedAddressItem[0].getKey());
                                }

                                /** START google dataLayer tag */
                                window.dataLayer = window.dataLayer || [];
                                window.dataLayer.push({
                                    'event': 'checkoutOption',
                                    'ecommerce': {
                                        'checkout_option': {
                                            'actionField': {
                                                'step': 2,
                                                'option': ['Shipping Address - Change']
                                            }
                                        }
                                    }
                                });

                            } else {
                                messageList.addErrorMessage({'message': response.message});
                            }
                            self.getPopUp().closeModal();

                            /**
                             * This will reload page when edit address success
                             * to update address information
                             */
                            window.location.reload(true);
                        }
                    ).fail(
                        function (response) {
                            messageList.addErrorMessage({'message': response.message});
                            self.getPopUp().closeModal();
                            fullScreenLoader.stopLoader();
                        }
                    );
                }
            },

            /** Shipping Method View **/
            rates: shippingService.getShippingRates(),
            isLoading: shippingService.isLoading,
            isSelected: ko.computed(function () {
                    return quote.shippingMethod()
                        ? quote.shippingMethod().carrier_code + '_' + quote.shippingMethod().method_code
                        : null;
                }
            ),

            /** Format Price **/
            formatPrice: function(amount){
                return priceUtils.formatPrice(
                    amount, window.checkoutConfig.priceFormat
                );
            },


            /**
             * Ajax func to change Qty and remove items
             *
             * */
            _ajax: function (url, data) {
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
                    type: 'post',
                    global: false,
                    dataType: 'json',
                    context: this,
                    beforeSend: function () {
                        fullScreenLoader.startLoader();
                    }
                }).done(function (response) {
                    if (response.success) {
                        if(!_.isUndefined(data.show_warning)) {
                            checkoutData.setWarningMessage(true);
                        }
                        window.location.reload(true);
                    } else {
                        var msg = response.error_message;
                        if (msg) {

                            if(response.type == 'updateQty'){
                                checkoutData.setErrorMessage(msg);
                                window.location.reload(true);
                            }else{
                                messageList.addErrorMessage({'message': $t(msg)});
                            }
                            fullScreenLoader.stopLoader();
                        }
                    }
                }).fail(function (error) {
                    console.log(JSON.stringify(error));
                });
            },

            /** UpdateItemQty block render **/
            updateItemQty: function (item_id, address_id, unit_case, unit_qty, adjustQty) {
                var item_qty = 1;
                var elementCurrent;

                if (adjustQty != false) {
                    if ('CS' == unit_case) {
                        elementCurrent = $('input[name="qty_case_' + item_id + '"]');
                        item_qty = (parseInt(elementCurrent.val()) + parseInt(adjustQty)) * unit_qty;
                    }
                    else {
                        elementCurrent = $('input[name="qty_' + item_id + '"]');
                        item_qty = parseInt(elementCurrent.val()) + parseInt(adjustQty);
                    }
                }
                else {
                    if ('CS' == unit_case) {
                        elementCurrent = $('select[name="qty_case_' + item_id + '"]');
                        item_qty = elementCurrent.val();
                        item_qty = item_qty * unit_qty;
                    }
                    else {
                        elementCurrent = $('select[name="qty_' + item_id + '"]');
                        item_qty = elementCurrent.val();
                    }
                }
                if(item_qty == 0) {
                    var productId = elementCurrent.attr('data-product-id');
                    this.removeItem(item_id, productId);
                } else {

                    this.pushDataLayerProductCart(elementCurrent);

                    this._ajax(this.options.url.update_item_qty, {
                        show_warning: true,
                        item_id: item_id,
                        item_qty: item_qty,
                        address_id: address_id
                    });
                }
            },

            /** Remove Items in block render **/
            removeItem: function (item_id) {

                this.pushDataLayerRemoveItemMultiple(item_id);

                this._ajax(this.options.url.remove, {
                    show_warning: true,
                    item_id: item_id
                });
            },

            /** Update GiftWrapping when select **/
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

            /**
             * @return Array
             * Convert list gift wrapping from string to
             * array object with details
             */
            getOptionGiftWrapping: function (gift_wrapping) {
                var list = [];
                var allWrappingItems = window.checkoutConfig.giftWrapping.designsInfo;
                var availableDesignIds = gift_wrapping.split(',');
                _.filter(allWrappingItems, function (item) {
                    if (_.indexOf(availableDesignIds, item.id) != -1) {
                        var obj = {};
                        obj.id = item.id;
                        obj.label = item.label;
                        obj.price = item.price;
                        obj.priceAfterFormat = priceUtils.formatPrice( item.price, window.checkoutConfig.priceFormat);
                        list.push(obj);
                    }
                });
                return list;
            },

            /**
             * Re-push data DateTime and Time slots when
             * choose DateTime or time slots.
             */
            updateDeliveryAndTimeSlot: function (add_id) {
                if (quote.quoteItemDdateInfo != null) {
                    var customer_address_info = [],
                        delivery_date_tmp = [];
                    $.each(quote.quoteItemDdateInfo().addressDdateInfo, function (index, item) {
                        var Deliveries = [];
                        var addressId = item.address_id;
                        $.each(item.ddate_info, function (dDateInfoKey, dDateInfo) {
                            var deliveryTimeLabel = null;
                            var deliveryDate = $('input[name="delivery_date[' + addressId + '][' + dDateInfo.code + ']"]').val() || null;
                            var nextDeliveryDate = $('input[name="next_delivery_date[' + addressId + '][' + dDateInfo.code + ']"]').val() || null;
                            var deliveryTime = $('#delivery_time-' + addressId + '-' + dDateInfo.code).val() || null;

                            /** START google dataLayer tag */
                            var timeSlotLabel = $t('Unspecified');

                            if (parseInt(deliveryTime) > 0) { // if has select time slots
                                deliveryTimeLabel = $('#delivery_time-' + addressId + '-' + dDateInfo.code + ' option[value="' + deliveryTime + '"]').text();
                                timeSlotLabel = deliveryTimeLabel.replace(/:/g , "\\:");
                            }

                            if(addressId == add_id) {
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

                            var DeliveryInformation = {
                                deliveryName: dDateInfo.code,
                                deliveryDate: deliveryDate,
                                nextDeliveryDate: nextDeliveryDate,
                                deliveryTime: deliveryTime,
                                deliveryTimeLabel: deliveryTimeLabel
                            };
                            Deliveries.push(DeliveryInformation);
                        });
                        customer_address_info.push($.param({
                            address_id: item.address_id,
                            cart_items: item.cartItemIds,
                            delivery_date: Deliveries
                        }));
                        delivery_date_tmp.push({
                            address_id: item.address_id,
                            cart_items: item.cartItemIds,
                            delivery_date: Deliveries
                        });
                    });
                    registry.get('checkout.placeOrder', function (multiConfirm) {
                        multiConfirm.deliveryTimes.removeAll();
                        multiConfirm.deliveryTimes(delivery_date_tmp);
                        multiConfirm.deliveryTimesSave.removeAll();
                        multiConfirm.deliveryTimesSave(customer_address_info);
                    });
                }
            },

            selectShippingMethod: function(shippingMethod) {
                selectShippingMethodAction(shippingMethod);
                checkoutData.setSelectedShippingRate(shippingMethod.carrier_code + '_' + shippingMethod.method_code);
                return true;
            },

            setShippingInformation: function () {
                if (this.validateShippingInformation()) {
                    setShippingInformationAction().done(
                        function () {
                            stepNavigator.next();
                            registry.get('checkout.rewardPoints', function (rewardPointsObj) {
                                /*use setting using point from profile page*/
                                rewardPointsObj.pointControl(window.customerData.reward_user_setting);
                                rewardPointsObj.pointAmount(window.customerData.reward_user_redeem);
                                if(rewardPointsObj.pointControl() == 2 && rewardPointsObj.pointAmount() >= 0) {
                                    rewardPointsObj.apply();
                                } else {
                                    rewardPointsObj.applyLabel();
                                }
                            });
                            $('#checkout .page-title-wrapper .page-title > span').text($t('Order Confirm'));
                        }
                    );
                }
            },

            validateShippingInformation: function () {
                var shippingAddress,
                    addressData,
                    loginFormSelector = 'form[data-role=email-with-possible-login]',
                    emailValidationResult = customer.isLoggedIn();

                if (!quote.shippingMethod()) {
                    this.errorValidationMessage('Please specify a shipping method');
                    return false;
                }

                if (!customer.isLoggedIn()) {
                    $(loginFormSelector).validation();
                    emailValidationResult = Boolean($(loginFormSelector + ' input[name=username]').valid());
                }

                if (!emailValidationResult) {
                    $(loginFormSelector + ' input[name=username]').focus();
                }

                var quoteData = window.checkoutConfig.quoteData;
                if( ! $.isEmptyObject(quoteData['riki_course_id'])) {
                    var isDateValid = true;
                    $(("[name='delivery_date']")).each(function() {
                        var $this = $(this);
                        if($this.val() == "") {
                            isDateValid = false;
                            return false; /** Stop foreach */
                        }
                    });
                    if(!isDateValid) {
                        this.errorValidationMessage($t("Please specify delivery date and time"));
                        return false;
                    }
                }

                if (this.isFormInline) {
                    this.source.set('params.invalid', false);
                    this.source.trigger('shippingAddress.data.validate');
                    if (this.source.get('shippingAddress.custom_attributes')) {
                        this.source.trigger('shippingAddress.custom_attributes.data.validate');
                    }
                    if (this.source.get('params.invalid')
                        || !quote.shippingMethod().method_code
                        || !quote.shippingMethod().carrier_code
                        || !emailValidationResult
                    ) {
                        return false;
                    }
                    shippingAddress = quote.shippingAddress();
                    addressData = addressConverter.formAddressDataToQuoteAddress(
                        this.source.get('shippingAddress')
                    );

                    //Copy form data to quote shipping address object
                    for (var field in addressData) {
                        if (addressData.hasOwnProperty(field)
                            && shippingAddress.hasOwnProperty(field)
                            && typeof addressData[field] != 'function'
                        ) {
                            shippingAddress[field] = addressData[field];
                        }
                    }

                    if (customer.isLoggedIn()) {
                        shippingAddress.save_in_address_book = true;
                    }
                    selectShippingAddress(shippingAddress);
                }
                return true;
            },

            /**
             * Get min sale qty
             * For multiple: after split items to 1 qty so min should start from 1
             * @param min_sale_qty
             * @returns {Number}
             */
            getMinSaleQty: function(min_sale_qty) {
                return 1;
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

            pushDataLayerRemoveItemMultiple: function(item_id) {
                var cartStorage = localStorage.getItem('googleTagAddToCartStorage');
                var parentItem  = $('.parent-item-cart[data-cart-item="'+item_id+'"]');

                var productId  = 0;
                var quantity   = 0;
                if (typeof(parentItem.attr('data-cart-product-id')) != "undefined") {
                    productId = parseInt(parentItem.attr('data-cart-product-id'));
                }
                if (typeof(parentItem.find('.data-remove-qty')) != "undefined") {
                    quantity = parseInt(parentItem.find('.data-remove-qty').val());
                }

                if ( cartStorage !=null && productId !=null  ) {
                    var dataProductRemove  = JSON.parse(cartStorage);
                    if (dataProductRemove.hasOwnProperty(productId)) {
                        var dataProductItemsRemove   = this.removeItemFromCart(dataProductRemove[productId]);
                        dataProductItemsRemove['quantity'] = quantity;
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
            },
            reindex: function() {
                $( ".index-data" ).each(function( index ) {
                    $( this ).text(index + 1);
                });
            }

        });
    }
);
