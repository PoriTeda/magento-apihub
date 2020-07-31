define(
    [
        'jquery',
        'ko',
        'mage/storage',
        'uiComponent',
        'Magento_Checkout/js/model/full-screen-loader',
        'mage/translate',
        'Magento_Ui/js/model/messageList'
    ],
    function($, ko, storage, Component, fullScreenLoader, $t, messageContainer) {
        'use strict';
        return Component.extend({
            defaults: {
                template: 'Riki_Checkout/questionnaire-on-success'
            },
            questionnairesArray: ko.observableArray([]),
            answersData: ko.observable(),
            initialize: function() {
                this._super();
                var self = this;
                for(var i = 0 ; i < self.questionnaireData.questionnaire.length; i++) {
                    if(self.questionnaireData.questionnaire[i]['is_available_backend_only'] == '0')
                        self.questionnairesArray.push(new self.setQuestionnairesList(self.questionnaireData.questionnaire[i]));
                }

                return this;
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

            trans: function(str) {
                return $t(str);
            },

            submitQuestionnaire: function(data, event) {
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
                    $('#questionnaire-buttons-container').before(errorHtml);
                    return false;
                }
                fullScreenLoader.startLoader();
                var self = this;

                if (event) {
                    event.preventDefault();
                }

                var answersListData = [];
                $.each(self.questionnairesArray() , function(index , item){
                    var object = {
                        enquete_id: item.enquete_id,
                        order_id: self.order_id,
                        customer_id:self.customer_id,
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
                return storage.post(
                    'questionnaire/answers/save',
                    JSON.stringify(self.answersData()),
                    false
                ).done(function (response) {
                    fullScreenLoader.stopLoader();
                    if (response.errors) {
                        messageContainer.addErrorMessage(response);
                    } else {
                        messageContainer.addSuccessMessage(response);
                        $('#questionnaires-container').remove();
                        $("html, body").animate({ scrollTop: 0 }, "slow");
                    }

                }).fail(function () {
                    fullScreenLoader.stopLoader();
                    messageContainer.addErrorMessage({'message': 'Could not authenticate. Please try again later'});
                });
            },

            escapeHTML: function (str) {
                if(typeof str !== 'undefined') {
                    var div = document.createElement('div');
                    div.appendChild(document.createTextNode(str));
                    return div.innerHTML;
                }
                return str;
            }
            
        });
    }
);
