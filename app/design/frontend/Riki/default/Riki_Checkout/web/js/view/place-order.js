define(
    [
        'ko',
        'jquery',
        'underscore',
        'uiComponent',
        'Magento_Checkout/js/action/place-order',
        'Riki_Checkout/js/action/multiple/place-order',
        'Magento_Checkout/js/action/select-payment-method',
        'Magento_Checkout/js/model/quote',
        'Magento_Customer/js/model/customer',
        'Magento_Checkout/js/model/payment-service',
        'Magento_Checkout/js/checkout-data',
        'Magento_Checkout/js/model/checkout-data-resolver',
        'uiRegistry',
        'Magento_Checkout/js/model/payment/additional-validators',
        'Magento_Catalog/js/price-utils',
        'Magento_Checkout/js/model/url-builder',
        'Magento_Checkout/js/action/questionnaire',
        'mage/translate',
        'Magento_Ui/js/model/messageList',
        'Magento_Checkout/js/model/full-screen-loader',
        'mage/storage',
        'Magento_Checkout/js/model/error-processor',
        'mage/url',
        'Magento_Checkout/js/action/set-shipping-information',
        'Riki_Theme/js/cart-data-model',
    ],
    function (
        ko,
        $,
        _,
        Component,
        placeOrderAction,
        placeOrderMultipleAction,
        selectPaymentMethodAction,
        quote,
        customer,
        paymentService,
        checkoutData,
        checkoutDataResolver,
        registry,
        additionalValidators,
        priceUtils,
        urlBuilder,
        questionnaireAction,
        $t,
        messageList,
        fullScreenLoader,
        storage,
        errorProcessor,
        url,
        setShippingInformationAction,
        cartDataModel
    ) {
        'use strict';
        var popUp = null;
        return Component.extend({
            defaults: {
                template: 'Riki_Checkout/place-order'
            },
            isPlaceOrderActionAllowed: ko.observable(quote.billingAddress() != null),
            questionnairesArray: ko.observableArray([]),
            answersData: ko.observable(),
            allowSubmitButton: ko.observable(false),
            deliveryTimesSave: ko.observableArray(),
            deliveryTimes: ko.observableArray(),

            // always true although virtual product
            isVisible: ko.observable(false),

            initialize: function () {
                this._super();

                var self = this;
                cartDataModel.resetCart();
                for(var i = 0 ; i < window.checkoutConfig.questionnaire.length; i++) {
                    if(window.checkoutConfig.questionnaire[i]['is_available_backend_only'] == '0')
                        self.questionnairesArray.push(new self.setQuestionnairesList(window.checkoutConfig.questionnaire[i]));
                }

                return this;
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
                    var errorHtml = '<div generated="true" class="mage-error question-error" style="text-align: left; top: 0px; margin-bottom: 5px;">'+ $t('Please answer the questionnaire.') +'</div>';
                    $('.checkout-inner-container .opc_sidebar_btn').prepend(errorHtml);
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
            validate: function() {
                return true;
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

            placeOrderMultipleAfterConfirm: function (data, event) {
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
                    var errorHtml = '<div generated="true" class="mage-error question-error" style="text-align: left; top: 0px; margin-bottom: 5px;">'+ $t('Please answer the questionnaire.') +'</div>';
                    $('.checkout-inner-container .opc_sidebar_btn').prepend(errorHtml);
                    return false;
                }

                if (event) {
                    event.preventDefault();
                }

                self.placeOrderMultipleAction();

                return false;
            },
            placeOrderMultipleAction: function() {
                var self = this,
                    placeOrder;

                /**
                 * Compile cart items at the confirm page.
                 */
                //var dataString  = shippingStep.formData();

                fullScreenLoader.startLoader();

                registry.get('checkout.steps.shipping-step.shippingAddress', function(shippingStep) {
                    if($('body').hasClass('multicheckout-index-index')) {
                        shippingStep.updateDeliveryAndTimeSlot();

                        /** Transfer new data render at checkout confirm multiple page */
                        if(shippingStep.validateShippingInformation()) {
                            storage.get(
                                url.build("rest/V1/multicheckout/manageCart/" + quote.getQuoteId())
                            ).done(
                                function (response) {
                                    var payload = JSON.parse(response);
                                    quote.quoteItemDdateInfo(payload);
                                    registry.get('checkout.steps.multiple-checkout-order-confirmation' , function (multipleConfirm){
                                        multipleConfirm.addressDdateInfoConfirmTmp(quote.quoteItemDdateInfo());
                                    });
                                    setShippingInformationAction().done(
                                        function() {
                                            //$('#opc-select-payment-method').modal('closeModal');
                                            /**
                                             * Save Delivery date, timeslot
                                             */
                                            var payloadDelivery = {
                                                cart_id : quote.getQuoteId()
                                            };
                                            var serviceUrl = urlBuilder.createUrl('/multicheckout/setDeliveryMethod', {});
                                            payloadDelivery.customer_address_info = self.deliveryTimesSave();
                                            storage.post(
                                                serviceUrl, JSON.stringify(payloadDelivery), false
                                            ).done(
                                                function () {
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
                                                    self.answersData(answersListData);
                                                    if (self.validate() && additionalValidators.validate()) {
                                                        self.isPlaceOrderActionAllowed(false);
                                                        placeOrder = placeOrderMultipleAction(self.getData(self.answersData()), self.redirectAfterPlaceOrder, messageList);

                                                        $.when(placeOrder).fail(function (response) {
                                                            self.isPlaceOrderActionAllowed(true);
                                                            fullScreenLoader.stopLoader();
                                                        })

                                                        return true;
                                                    }
                                                }
                                            ).fail(
                                                function (response) {
                                                    errorProcessor.process(response, messageContainer);
                                                    deferred.reject();
                                                    fullScreenLoader.stopLoader();
                                                }
                                            );
                                        }
                                    );
                                }
                            ).fail(
                                function () {
                                    fullScreenLoader.stopLoader();
                                }
                            );

                        }
                    }
                });
            },
            checkMultiple: function() {
                if( window.location.href.indexOf('multicheckout') >= 0){
                    return true;
                }
                return  false;
            },

            escapeHTML: function (str) {
                if(typeof str !== 'undefined') {
                    var div = document.createElement('div');
                    div.appendChild(document.createTextNode(str));
                    return div.innerHTML;
                }
                return str;
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
            }

        });
    }
);