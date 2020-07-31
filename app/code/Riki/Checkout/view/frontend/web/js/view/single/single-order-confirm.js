define(
    [
        'ko',
        'jquery',
        'underscore',
        'uiComponent',
        'Magento_Checkout/js/action/place-order',
        'Magento_Checkout/js/action/select-payment-method',
        'Magento_Checkout/js/model/quote',
        'Magento_Customer/js/model/customer',
        'Magento_Checkout/js/model/payment-service',
        'Magento_Checkout/js/checkout-data',
        'Magento_Checkout/js/model/checkout-data-resolver',
        'uiRegistry',
        'Magento_Checkout/js/model/payment/additional-validators',
        'Magento_Ui/js/model/messages',
        'uiLayout',
        'Magento_Catalog/js/price-utils',
        'Magento_Checkout/js/model/step-navigator',
        'mage/url',
        'Magento_Checkout/js/action/questionnaire',
        'mage/translate',
        'Magento_Ui/js/model/messageList',
        'Magento_Checkout/js/model/totals',
        'Magento_Ui/js/modal/confirm'
    ],
    function (
        ko,
        $,
        _,
        Component,
        placeOrderAction,
        selectPaymentMethodAction,
        quote,
        customer,
        paymentService,
        checkoutData,
        checkoutDataResolver,
        registry,
        additionalValidators,
        Messages,
        layout,
        priceUtils,
        stepNavigator,
        urlBuilder,
        questionnaireAction,
        $t,
        messageList,
        totals,
        modal
    ) {
        'use strict';
        var popUp = null;
        return Component.extend({
            defaults: {
                template: 'Riki_Checkout/single/confirm'
            },
            redirectAfterPlaceOrder: true,
            redirectCartPage: '',
            /**
             * After place order callback
             */
            afterPlaceOrder: function () {
                if(this.answersData().length > 0)
                    questionnaireAction(window.customerData.id, window.checkoutConfig.quoteData.entity_id, this.answersData(), this.messageContainer);
            },
            isPlaceOrderActionAllowed: ko.observable(quote.billingAddress() != null),

            pointUsed : ko.observable(0),

            formattedPointUsed : function () {
                var applyPoint = 0, format = JSON.parse(JSON.stringify(quote.getPriceFormat()));
                format.pattern = '%s ' + $.mage.__("Point");
                if (totals.getSegment('apply_point')) {
                    applyPoint += totals.getSegment('apply_point').value;
                }
                return priceUtils.formatPrice(applyPoint, format);
            },

            formattedPointBalance: function () {
                var pointBalance = window.customerData.loyalty_reward_point, format = JSON.parse(JSON.stringify(quote.getPriceFormat()));
                format.pattern = '%s ' + $.mage.__("Point");
                return priceUtils.formatPrice(pointBalance, format);
            },

            couponCode : ko.observable('') ,

            formattedSubTotal : ko.observable(""),
            formattedSubTotalForHanpukai : ko.observable(""),
            formattedTax : ko.observable(""),
            formattedShippingFee : ko.observable(""),
            formattedShippingTax : ko.observable(""),
            formattedGrandTotal : ko.observable("").extend({deferred:true}),
            paymentMethodName : ko.observable(""),
            shippingAddressConfirm : ko.observable(quote.shippingAddress()),
            shippingMethodName: ko.observable(""),
            shippingMethodPrice: ko.observable(0),
            deliveryTimes: ko.observableArray(),
            formattedSurchargeFee : ko.observable(0).extend({deferred:true}),
            deliveryInfoLayout: ko.observableArray(),
            formattedGrandTotalNotApplyPoint : ko.observable("").extend({deferred:true}),
            formattedGrandTotalTaxAmount: ko.observable(0),
            formattedGiftWrappingFee: ko.observable(0),
            formattedGiftWrappingTaxAmount: ko.observable(0),
            discountValue: ko.observable(0).extend({deferred:true}),
            isDisplayDiscount: ko.observable(true),
            rikiName: ko.observable(""),
            apartment: ko.observable(""),
            isSubscription: window.checkoutConfig.quoteData.riki_course_id,
            questionnairesArray: ko.observableArray([]),
            answersData: ko.observable(),
            gift_wrapping_available: window.checkoutConfig.gift_wrapping_available,
            titleConfirmButton: ko.observable($t('Complete the Order')),
            allowSubmitButton: ko.observable(false),
            cartSimulation: ko.observableArray(),
            grandTotalValue: ko.observable().extend({deferred:true}),
            grandTotalSimulation: ko.observable(),
            simulationTimes: ko.observable(0),
            simulationLoading: ko.observable(false),

            // always true although virtual product
            isVisible: ko.observable(false),

            initialize: function () {
                this._super().initChildren();

                var self = this;
                // register your step
                /*stepNavigator.registerStep(
                    'single_order_confirm',
                    null,
                    $t('Order Confirm '),
                    this.isVisible,
                    _.bind(this.navigate, this),
                    25
                );*/
                self.allowSubmitButton(!self.questionnairesArray().length);
                self.questionnairesArray.subscribe(function () {
                    self.allowSubmitButton(!self.questionnairesArray().length);
                });
                self.redirectCartPage = urlBuilder.build('checkout/cart');
                quote.totals.subscribe(function(newTotalObject){
                    registry.get('checkout.sidebar.summary.totals.before_grandtotal.gift-wrapping-item-level', function (giftWrapping) {
                        self.formattedGiftWrappingFee(giftWrapping.getIncludingTaxValue());
                        self.formattedGiftWrappingTaxAmount(giftWrapping.getTaxValue());
                    });
                    registry.get('checkout.sidebar.summary.totals.discount', function (discount) {
                        self.discountValue(discount.getValue());
                        self.isDisplayDiscount(discount.isDisplayed());
                    });
                    self.formattedSubTotal(
                        priceUtils.formatPrice(newTotalObject.subtotal_incl_tax,window.checkoutConfig.priceFormat)
                    );
                    self.formattedSubTotalForHanpukai (
                        // priceUtils.formatPrice((newTotalObject.subtotal_incl_tax + newTotalObject.discount_amount),window.checkoutConfig.priceFormat)
                        // NED-4140 subtotal for hanpukai calculated as normal
                        priceUtils.formatPrice(newTotalObject.subtotal_incl_tax,window.checkoutConfig.priceFormat)
                    );
                    self.formattedTax(
                        priceUtils.formatPrice(
                            newTotalObject.tax_amount,window.checkoutConfig.priceFormat
                        )
                    );
                    self.formattedShippingFee(
                        priceUtils.formatPrice(
                            newTotalObject.shipping_incl_tax,window.checkoutConfig.priceFormat
                        )
                    );
                    self.formattedShippingTax(
                        priceUtils.formatPrice(
                            newTotalObject.shipping_tax_amount,window.checkoutConfig.priceFormat
                        )
                    );
                    registry.get('checkout.sidebar.summary.totals.grand-total', function (grandtotal) {
                        self.formattedGrandTotal(grandtotal.getValue());
                        self.grandTotalValue(grandtotal.getBareValue());
                    });
                    registry.get('checkout.sidebar.summary.totals.total-not-apply-point', function (total_not_apply_point) {
                        self.formattedGrandTotalNotApplyPoint(total_not_apply_point.getValue());
                    });
                    self.formattedSurchargeFee(
                        priceUtils.formatPrice(
                            totals.getSegment('fee').value, window.checkoutConfig.priceFormat
                        )
                    );
                });
                quote.shippingAddress.subscribe(function(shippingAddressObject) {
                    self.shippingAddressConfirm(shippingAddressObject);

                    if(typeof shippingAddressObject.customerId == 'undefined') {
                        if(!(typeof shippingAddressObject.customAttributes == 'undefined'))
                            self.rikiName(shippingAddressObject.customAttributes.riki_nickname);
                    }else
                        self.rikiName(shippingAddressObject.customAttributes.riki_nickname.value);
                });
                this.shippingAddressConfirm.subscribe(function(address) {
                    if(typeof address.customerId == 'undefined') {
                        if(!(typeof address.customAttributes == 'undefined'))
                            self.apartment(' ' + address.customAttributes.apartment);
                    }else {
                        if(!(typeof address.customAttributes.apartment == 'undefined'))
                            self.apartment(' ' + address.customAttributes.apartment.value);
                    }
                });
                quote.shippingMethod.subscribe(function(shippingMethodObject){
                    self.shippingMethodName(shippingMethodObject.carrier_title);
                    self.shippingMethodPrice(
                        priceUtils.formatPrice(
                            shippingMethodObject.price_incl_tax
                            ,window.checkoutConfig.priceFormat
                        )
                    );
                });
                quote.paymentMethod.subscribe(function () {
                    if(typeof quote.paymentMethod() != 'undefined' && quote.paymentMethod() != null && quote.paymentMethod().method == 'paygent' && checkoutData.getPaygentOption() == '1') {
                        $('.warnning-cc').remove();
                        var warnningHtml = '<div class="warnning-cc"><span>'+ $t('Go to the screen of the settlement agent company.') + '</span><span>' + $t('After that, payment method can not be changed.') +'</span></div>';
                        $('#single-order-confirm-buttons-container').before(warnningHtml);
                    }else {
                        $('.warnning-cc').remove();
                    }
                });
                this.cartSimulation.subscribe(function(cartSimulationObj) {
                    var grandTotalSimulationTmp = 0;
                    cartSimulationObj.forEach(function(e) {
                        grandTotalSimulationTmp += e.grand_total;
                    });
                    self.grandTotalSimulation(priceUtils.formatPrice(
                        grandTotalSimulationTmp +  self.grandTotalValue()
                    ));
                    if(cartSimulationObj.length) {
                        self.simulationTimes(_.last(cartSimulationObj).order_times);
                    }
                });
                /* default value */
                this.formattedSubTotal(
                    priceUtils.formatPrice(
                        window.checkoutConfig.totalsData.subtotal_incl_tax,window.checkoutConfig.priceFormat
                    )
                );
                this.formattedTax(
                    priceUtils.formatPrice(
                        window.checkoutConfig.totalsData.tax_amount,window.checkoutConfig.priceFormat
                    )
                );
                this.formattedShippingFee(
                    priceUtils.formatPrice(
                        window.checkoutConfig.totalsData.shipping_incl_tax,window.checkoutConfig.priceFormat
                    )
                );
                this.formattedShippingTax(
                    priceUtils.formatPrice(
                        window.checkoutConfig.totalsData.shipping_tax_amount,window.checkoutConfig.priceFormat
                    )
                );

                this.formattedGrandTotal(
                    priceUtils.formatPrice(
                        window.checkoutConfig.totalsData.base_grand_total,window.checkoutConfig.priceFormat
                    )
                );
                this.formattedGrandTotalTaxAmount(
                    priceUtils.formatPrice(
                        window.checkoutConfig.tax_riki
                        ,window.checkoutConfig.priceFormat
                    )
                );

                this.pointUsed(window.checkoutConfig.quoteData.amrewards_point);

                if(typeof quote != 'undefined' && quote.shippingAddress() != null) {
                    if(typeof quote.shippingAddress().customerId == 'undefined') {
                        if(!(typeof quote.shippingAddress().customAttributes == 'undefined'))
                            self.rikiName(quote.shippingAddress().customAttributes.riki_nickname);
                    }else
                        self.rikiName(quote.shippingAddress().customAttributes.riki_nickname.value);

                    if(typeof quote.shippingAddress().customerId == 'undefined') {
                        if(!(typeof quote.shippingAddress().customAttributes == 'undefined'))
                            self.apartment(quote.shippingAddress().customAttributes.apartment);
                    }else {
                        if(!(typeof quote.shippingAddress().customAttributes.apartment == 'undefined'))
                            self.apartment(quote.shippingAddress().customAttributes.apartment.value);
                    }
                }

                for(var i = 0 ; i < window.checkoutConfig.questionnaire.length; i++) {
                    if(window.checkoutConfig.questionnaire[i]['is_available_backend_only'] == '0')
                        self.questionnairesArray.push(new self.setQuestionnairesList(window.checkoutConfig.questionnaire[i]));
                }

                var ruleName = '';
                if(typeof window.checkoutConfig.totalsData.extension_attributes.promotion_rules != 'undefined') {
                    var ruleArr = window.checkoutConfig.totalsData.extension_attributes.promotion_rules;
                    ruleArr.forEach(function(item) {
                        if(item.visible == 1)
                            ruleName+= '<span class="promotion">'+ item.title + '</span>';
                    })
                }
                this.couponCode(ruleName);

                return this;
            },

            /**
             * Initialize child elements
             *
             * @returns {Component} Chainable.
             */
            initChildren: function () {
                this.messageContainer = new Messages();
                this.createMessagesComponent();

                return this;
            },

            /**
             * Create child message renderer component
             *
             * @returns {Component} Chainable.
             */
            createMessagesComponent: function () {

                var messagesComponent = {
                    parent: this.name,
                    name: this.name + '.messages',
                    displayArea: 'messages',
                    component: 'Magento_Ui/js/view/messages',
                    config: {
                        messageContainer: this.messageContainer
                    }
                };

                layout([messagesComponent]);

                return this;
            },

            navigate: function () {
                var self = this;
                self.isVisible(true);
                stepNavigator.navigateTo('shipping', 'shipping');
            },

            placeOrderAfterConfirm: function (data, event) {
                var self = this;
                var questionValidators = false;
                $('.question-error').remove();
                $('.level1 input[type="text"]').removeClass('mage-error');
                $('.question-container select').removeClass('mage-error');
                $('.question-container input[type="radio"]').next().removeClass('mage-error');
                $('.question-item.required').each(function() {
                    var _this = $(this),
                        inputType = _this.find('.data-type').attr('rel');
                    if(inputType == '0') {
                        var checkboxName = _this.find('.level1 input[type="radio"]:first').attr('name'),
                            checkboxElement = $('input[name="'+ checkboxName +'"]'),
                            checkboxElementChecked = $('input[name="'+ checkboxName +'"]:checked'),
                            checkboxSubElement = $('select[name="'+ checkboxName +'[\'sub\']['+ checkboxElementChecked.val() +']"]');

                        if(!checkboxElementChecked.val()) {
                            checkboxElement.next().addClass('mage-error');
                            questionValidators = true;
                        }
                        if(checkboxElementChecked.hasClass('has_children') && checkboxSubElement.val() == '') {
                            checkboxSubElement.addClass('mage-error');
                            questionValidators = true;
                        }
                    }else if(inputType == '1') {
                        var selectBox = _this.find('.level1 select'),
                            selectBoxName = selectBox.attr('name'),
                            subSelectBox = $('select[name="'+ selectBoxName +'[\'sub\']"]');
                        if(selectBox.val() == '') {
                            selectBox.addClass('mage-error');
                            questionValidators = true;
                        }
                        if(subSelectBox.val() == '') {
                            subSelectBox.addClass('mage-error');
                            questionValidators = true;
                        }
                    }else if(inputType == '2') {
                        var inputText = _this.find('.level1 input[type="text"]');
                        if(inputText.val() == '') {
                            inputText.addClass('mage-error');
                            questionValidators = true;
                        }
                    }
                });

                if(questionValidators){
                    var errorHtml = '<div generated="true" class="mage-error question-error">'+ $t('Please answer the questionnaire.') +'</div>';
                    $('#single-order-confirm-buttons-container').before(errorHtml);
                    return false;
                }

                if (event) {
                    event.preventDefault();
                }

                self.placeOrderAction();

                return false;
            },

            placeOrderAction: function() {
                var self = this,
                    placeOrder;

                var answersListData = [];
                $.each(self.questionnairesArray() , function(index , item){
                    var object = {
                        enquete_id: item.enquete_id,
                        questions: []
                    };
                    $.each(item.optionQuestions , function(optionIndex , optionItem){
                        var optionQuestion = {
                            question_id: optionItem.question_id ,
                            answers: []
                        };
                        var choice_1 = self.escapeHTML($('input[name="questionnaire[' + item.enquete_id + '][\'questions\'][' +optionItem.question_id+ '][\'choice_id\']"]:checked').val()),
                            choice_select_1 = self.escapeHTML($('select[name="questionnaire[' + item.enquete_id + '][\'questions\'][' +optionItem.question_id+ '][\'choice_id\']"]').val()),
                            choice_select_2 = self.escapeHTML($('select[name="questionnaire[' + item.enquete_id + '][\'questions\'][' +optionItem.question_id+ '][\'choice_id\'][\'sub\']"]').val()),
                            content = self.escapeHTML($('input[name="questionnaire[' + item.enquete_id + '][\'questions\'][' +optionItem.question_id+ '][\'content\']"]').val());

                        var choice_2 = null;
                        if(!isNaN(choice_1)) {
                            choice_2 = self.escapeHTML($('select[name="questionnaire[' + item.enquete_id + '][\'questions\'][' +optionItem.question_id+ '][\'choice_id\'][\'sub\']['+ choice_1 +']"]').val());
                        }

                        if (typeof choice_1 === 'undefined') {
                            choice_1 = null;
                        }

                        if (typeof choice_2 === 'undefined') {
                            choice_2 = null;
                        }

                        if (typeof choice_select_1 === 'undefined') {
                            choice_select_1 = null;
                        }

                        if (typeof choice_select_2 === 'undefined') {
                            choice_select_2 = null;
                        }

                        if (typeof content === 'undefined') {
                            content = null;
                        }

                        var answerItem = {
                            choices: [
                                choice_1, // radio level 1
                                choice_2, // drop down level 2
                                choice_select_1, // drop down level 1
                                choice_select_2 // drop down level 2
                            ],
                            content: content
                        };
                        optionQuestion.answers.push(answerItem);
                        object.questions.push(optionQuestion);
                    });
                    answersListData.push(object);
                });
                this.answersData(answersListData);
                quote.question(answersListData);

                if (this.validate() && additionalValidators.validate()) {
                    this.isPlaceOrderActionAllowed(false);
                    placeOrder = placeOrderAction(this.getData(this.answersData()), this.redirectAfterPlaceOrder, messageList);

                    $.when(placeOrder).fail(function (response) {
                        self.isPlaceOrderActionAllowed(true);
                        window.history.go(-1);
                    })
                    return true;
                }
            },

            getWarningPopUp: function() {
                var self = this;
                var contentMessage = '<div>'+ $t('Changing payment method can not be done after this. Do you want to proceed with entering credit card information?') +'</div>';
                var optionsPopup = {
                    modalClass: 'paygent-warning',
                    content: contentMessage,
                    focus: '.action-accept',
                    actions: {

                        /**
                         * Callback always - called on all actions.
                         */
                        always: function () {},

                        /**
                         * Callback confirm.
                         */
                        confirm: function () {
                            self.placeOrderAction();
                        },

                        /**
                         * Callback cancel.
                         */
                        cancel: function () {}
                    },
                    buttons: [{
                        text: $.mage.__('Yes'),
                        class: 'action-primary action-accept',
                        click: function (event) {
                            this.closeModal(event, true);
                        }
                    },
                    {
                        text: $.mage.__('No Direct'),
                        class: 'action-secondary action-dismiss',
                        click: function (event) {
                            this.closeModal(event);
                        }
                    }]
                };
                popUp = modal(optionsPopup);
                return popUp;
            },

            /**
             * Get payment method data
             */
            getData: function(question) {
                return {
                    "method": quote.paymentMethod().method,
                    "po_number": null,
                    "additional_data": null,
                    "extension_attributes":{
                        questionare: JSON.stringify(question)
                    }
                };
            },

            validate: function() {
                return true;
            },

            /**
             * Get Gift Wrap name
             */
            showGiftWrappingName: function (id) {
                var label = '';
                var giftWrappingItem = window.checkoutConfig.giftWrapping.designsInfo[id];
                if (giftWrappingItem !== undefined) {
                    label = giftWrappingItem.label;
                }
                return label;
            },

            /**
             * Get Gift Wrap price
             */
            showGiftWrappingPrice: function (id) {
                var price = '';
                var giftWrappingItem = window.checkoutConfig.giftWrapping.designsInfo[id];
                if (giftWrappingItem !== undefined) {
                    price = this.getPriceFormat(giftWrappingItem.price);
                }
                return price;
            },

            /**
             * Format Price
             */
            getPriceFormat: function(amount){
                return priceUtils.formatPrice(
                    amount,window.checkoutConfig.priceFormat
                );
            },

            /**
             * Get value tax
             * @returns {*|String}
             */
            getValueTax: function() {
                var price = 0;
                if (totals.getSegment('tax_riki')) {
                    price = totals.getSegment('tax_riki').value;
                }
                return this.getPriceFormat(price);
            },

            /**
             * Get earn point
             * @returns {*|Int}
             */
            getValueEarnPoint: function() {
                var point = 0;
                if (totals.getSegment('earn_point')) {
                    point = totals.getSegment('earn_point').value;
                }
                return point;
            },

            goTo: function (str) {
                stepNavigator.navigateToCustom(str);
            },

            getDay: function(date_string) {
                var d = new Date(date_string);
                var weekday = new Array(7);
                weekday[0] = $t('Sunday');
                weekday[1] = $t('Monday');
                weekday[2] = $t('Tuesday');
                weekday[3] = $t('Wednesday');
                weekday[4] = $t('Thursday');
                weekday[5] = $t('Friday');
                weekday[6] = $t('Saturday');

                return weekday[d.getDay()];
            },

            getDeliveryInfo: function (code) {
                var deliveryTimesArr = this.deliveryTimes(),
                    deliveryInfo = '';
                if (deliveryTimesArr.length) {
                    for (var i = 0; i < deliveryTimesArr.length; i++) {
                        if (deliveryTimesArr[i].deliveryName == code) {
                            if (deliveryTimesArr[i].deliveryDate != '') {
                                deliveryInfo += deliveryTimesArr[i].deliveryDate + '(' + this.getDay(deliveryTimesArr[i].deliveryDate) + ')';
                            }
                            if (deliveryTimesArr[i].deliveryTime != '-1') {
                                if (deliveryTimesArr[i].deliveryDate != '') {
                                    deliveryInfo += '<br/>' + deliveryTimesArr[i].deliveryTimeLabel;
                                } else {
                                    deliveryInfo += deliveryTimesArr[i].deliveryTimeLabel;
                                }
                            } else {
                                if (deliveryTimesArr[i].deliveryDate != '' || deliveryTimesArr[i].deliveryTime != '-1') {
                                    deliveryInfo += '<br/>' + $t('unspecified');
                                } else {
                                    deliveryInfo += $t('unspecified');
                                }
                            }
                            break;
                        }
                    }
                } else {
                    deliveryInfo += $t('unspecified');
                }
                return deliveryInfo;
            },

            setQuestionnairesList: function (data) {
                var self = this;
                self.code = data['code'];
                self.name = data['name'];
                self.enquete_id = data['enquete_id'];
                self.optionQuestions = [];
                for(var i = 0; i < data['optionQuestions'].length; i++){
                    self.optionQuestions[i] = [];
                    self.optionQuestions[i]['index'] = $t('Question:');
                    self.optionQuestions[i]['id'] = data['optionQuestions'][i]['id'];
                    self.optionQuestions[i]['is_required'] = (data['optionQuestions'][i]['is_required'] == '1') ? 'required' : '';
                    self.optionQuestions[i]['question_id'] = data['optionQuestions'][i]['question_id'];
                    self.optionQuestions[i]['title'] = data['optionQuestions'][i]['title'];
                    self.optionQuestions[i]['type'] = data['optionQuestions'][i]['type'];
                    self.optionQuestions[i]['hasSecond'] = data['optionQuestions'][i]['hasSecond'];
                    if(data['optionQuestions'][i]['type'] != '2' && data['optionQuestions'][i]['optionChoices'].length) {
                        self.optionQuestions[i]['selectedOption'] = ko.observable();
                        self.optionQuestions[i]['optionChoices'] = ko.observableArray();
                        for(var j = 0; j < data['optionQuestions'][i]['optionChoices'].length; j++) {
                            if(data['optionQuestions'][i]['optionChoices'][j]['parent_choice_id'] == '0') {
                                self.optionQuestions[i]['optionChoices'].push(data['optionQuestions'][i]['optionChoices'][j]);
                            }
                        }
                        for(var j = 0; j < self.optionQuestions[i]['optionChoices']().length; j++) {
                            self.optionQuestions[i]['optionChoices']()[j].optionChoicesSecond = ko.observableArray([]);
                            for(var k = 0; k < data['optionQuestions'][i]['optionChoices'].length; k++) {
                                if(data['optionQuestions'][i]['optionChoices'][k]['parent_choice_id'] == self.optionQuestions[i]['optionChoices']()[j].choice_id) {
                                    self.optionQuestions[i]['optionChoices']()[j].optionChoicesSecond.push(data['optionQuestions'][i]['optionChoices'][k]);
                                }
                            }
                        }
                    }
                }
            },
            
            goBack: function () {
                window.history.go(-1);
                return false;
            },
            
            escapeHTML: function (str) {
                if(typeof str !== 'undefined') {
                    var div = document.createElement('div');
                    div.appendChild(document.createTextNode(str));
                    return div.innerHTML;
                }
                return str;
            },

            setAllowSubmitButton: function(el, component) {
                component.allowSubmitButton(true);
                //questionnaireAction(window.customerData.id, window.checkoutConfig.quoteData.entity_id, window.checkoutConfig.questionnaire[0]['enquete_id'], component.messageContainer);
            },

            hasPointForTrial: function () {
                var hasPointForTrial = new Boolean(window.checkoutConfig.quoteData.point_for_trial);
                return hasPointForTrial.valueOf();
            },

            getGrandTotal: function() {
                var price = 0;
                if (totals.getSegment('apply_point')) {
                    price += totals.getSegment('apply_point').value;
                }
                if (totals.getSegment('grand_total')) {
                    price += totals.getSegment('grand_total').value;
                }
                if (totals.getSegment('fee')) {
                    price -= totals.getSegment('fee').value;
                }
                return price;
            },
            pointForTrial: function() {
                var pointForTrial = (window.checkoutConfig.quoteData.point_for_trial).valueOf();
                var grandTotal = this.getGrandTotal();
                var format = JSON.parse(JSON.stringify(quote.getPriceFormat()));
                format.pattern = '%s ' + $.mage.__("Point");
                if ( pointForTrial >= grandTotal) {
                    return priceUtils.formatPrice(grandTotal,format);
                }
                return priceUtils.formatPrice(pointForTrial,format);
            },

            formatPrice: function(amount){
                return priceUtils.formatPrice(
                    amount, window.checkoutConfig.priceFormat
                );
            },

            isDayOfWeekAndIntervalUnitMonth: function(){
                var deliveryTypes = registry.get('deliveryTypes')()[0];

                if (typeof deliveryTypes != 'undefined') {
                    var arrFrequency = deliveryTypes.arrFrequency;
                    var nextDeliveryDateCalculationOption = deliveryTypes.nextDeliveryDateCalculationOption;

                    if (arrFrequency[1] === 'month' && nextDeliveryDateCalculationOption === 'day_of_week') {
                        return true;
                    }
                }

                return false;
            },

            calculateNthWeekdayOfMonth: function(date_string){
                var d = new Date(date_string),
                    nthweekdayOfMonth = new Array(5);
                nthweekdayOfMonth[1] = $t('1st');
                nthweekdayOfMonth[2] = $t('2nd');
                nthweekdayOfMonth[3] = $t('3rd');
                nthweekdayOfMonth[4] = $t('4th');
                nthweekdayOfMonth[5] = $t('Last');

                return nthweekdayOfMonth[Math.ceil(d.getDate() / 7)];
            },

            getDeliveryMessage: function(code){
                var deliveryTimesArr = this.deliveryTimes(),
                    deliveryMessage = '',
                    lang = $('html').attr('lang');
                if (deliveryTimesArr.length) {
                    for (var i = 0; i < deliveryTimesArr.length; i++) {
                        if (deliveryTimesArr[i].deliveryName == code) {
                            if (deliveryTimesArr[i].deliveryDate != '') {
                                if (lang == 'ja-JP') {
                                    deliveryMessage += this.calculateNthWeekdayOfMonth(deliveryTimesArr[i].deliveryDate);
                                    deliveryMessage += this.getDay(deliveryTimesArr[i].deliveryDate);
                                    deliveryMessage += $t('every');
                                } else {
                                    deliveryMessage += $t('every');
                                    deliveryMessage += ' ' + this.calculateNthWeekdayOfMonth(deliveryTimesArr[i].deliveryDate);
                                    deliveryMessage += ' ' + this.getDay(deliveryTimesArr[i].deliveryDate);
                                }
                            }
                            break;
                        }
                    }
                }
                return deliveryMessage;
            },
        });
    }
);