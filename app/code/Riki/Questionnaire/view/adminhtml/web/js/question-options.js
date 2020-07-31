/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
/*jshint browser:true*/
/*global alert:true*/
define([
    'jquery',
    'mage/template',
    'jquery/ui',
    'useDefault',
    'collapsable',
    'mage/translate',
    'prototype',
    'mage/backend/validation',
    'Magento_Ui/js/modal/modal'
], function ($, mageTemplate) {
    'use strict';

    $.widget('mage.questionOptions', {
        options: {
            selectionItemCount: {}
        },

        _create: function () {
            this.baseTmpl = mageTemplate('#question-option-base-template');
            this.rowTmpl = mageTemplate('#question-option-select-type-row-template');

            this._initOptionBoxes();
            this._initSortableSelections();
            this._bindCheckboxHandlers();
            this._bindReadOnlyMode();
            this._addValidation();
        },

        _addValidation: function () {
            $.validator.addMethod(
                'required-option-select', function (value) {
                    return (value !== '');
                }, $.mage.__('Select type of option.'));

            $.validator.addMethod(
                'required-option-select-type-rows', function (value, element) {
                    var optionContainerElm = element.up('div[id*=_type_]'),
                        selectTypesFlag = false,
                        selectTypeElements = $('#' + optionContainerElm.id + ' .select-type-title');

                    selectTypeElements.each(function () {
                        if (!$(this).closest('tr').hasClass('ignore-validate')) {
                            selectTypesFlag = true;
                        }
                    });

                    return selectTypesFlag;
                }, $.mage.__('Please add rows to option.'));
                        
            $.validator.addMethod(
                'required-parent-choice-id-select', function (value, element) {
                    var optionContainerElm = element.up('tr[id*=_select_]'),
                        selectIsChildFlag = false,
                        selectIsChildElements = $('#' + optionContainerElm.id + ' .is-children');

                    selectIsChildElements.each(function () {
                         if ($(this).prop('checked')) {
                             var parentId = $(this).closest('.col-parent-choice-id').find('.select-parent-choice-id').val();
                             if ($.trim(parentId) === '') {
                                 selectIsChildFlag = false;
                             } else {
                                 selectIsChildFlag = true;
                             }
                         } else {
                             selectIsChildFlag = true;
                         }
                    });

                    return selectIsChildFlag;
                }, $.mage.__('Please enter in parent choice id field.'));

            $.validator.addMethod(
                'required-valid-parent-choice-id', function (value, element) {
                    var optionContainerElm = element.up('tr[id*=_select_]'),
                        selectIsChildFlag = false,
                        selectIsChildElements = $('#' + optionContainerElm.id + ' .is-children');

                    selectIsChildElements.each(function () {
                         if ($(this).prop('checked')) {
                             var parentId = $(this).closest('.col-parent-choice-id').find('.select-parent-choice-id').val();
                             var currentId = $('#' + optionContainerElm.id + ' .parent-id').val();
                             if ($.trim(parentId) === $.trim(currentId)) {
                                    selectIsChildFlag = false;
                             } else {
                                 selectIsChildFlag = true;
                             }
                         } else {
                             selectIsChildFlag = true;
                         }
                    });

                    return selectIsChildFlag;
                }, $.mage.__('Do not input parent choice id is itself.'));


        },

        _initOptionBoxes: function () {
            if (!this.options.isReadonly) {
                this.element.sortable({
                    axis: 'y',
                    handle: '[data-role=draggable-handle]',
                    items: '#question_options_container_top > div',
                    update: this._updateOptionBoxPositions,
                    tolerance: 'pointer'
                });
            }
            var syncOptionTitle = function (event) {
                var currentValue = $(event.target).val(),
                    optionBoxTitle = $('.admin__collapsible-title > span', $(event.target).closest('.fieldset-wrapper')),
                    newOptionTitle = $.mage.__('New Question');

                optionBoxTitle.text(currentValue === '' ? newOptionTitle : currentValue);
            };
            this._on({
                /**
                 * Reset field value to Default
                 */
                'click .use-default-label': function (event) {
                    $(event.target).closest('label').find('input').prop('checked', true).trigger('change');
                },

                /**
                 * Remove question option or option row for 'select' type of question option
                 */
                'click button[id^=question_option_][id$=_delete]': function (event) {
                    var element = $(event.target).closest('#question_options_container_top > div.fieldset-wrapper,tr');

                    if (element.length) {
                        $('#question_' + element.attr('id').replace('question_', '') + '_is_delete').val(1);
                        element.addClass('ignore-validate').hide();
                        this.refreshSortableElements();
                    }
                },
                /**
                 * Minimize question option block
                 */
                'click #question_options_container_top [data-target$=-content]': function () {
                    if (this.options.isReadonly) {
                        return false;
                    }
                },

                /**
                 * Add new question option
                 */
                'click #add_new_question_option': function (event) {
                    this.addOption(event);
                },

                /**
                 * Add new option row for 'select' type of question option
                 */
                'click button[id^=question_option_][id$=_add_select_row]': function (event) {
                    this.addSelection(event);
                },

                /**
                 * Change question option type
                 */
                'change select[id^=question_option_][id$=_type]': function (event, data) {
                    data = data || {};
                    var widget = this,
                        currentElement = $(event.target),
                        parentId = '#' + currentElement.closest('.fieldset-alt').attr('id'),
                        group = 'select',
                        previousBlock = $(parentId + '_type_select'),
                        tmpl,
                        disabledBlock = $(parentId).find(parentId + '_type_' + group);

                    if (currentElement.val() == 2 || currentElement.val() == null) {
                        if (previousBlock.length) {
                            previousBlock.addClass('ignore-validate').hide();
                        }
                    } else {
                        if (!previousBlock.length) {
                            if (disabledBlock.length) {
                                disabledBlock.removeClass('ignore-validate').show();
                            } else {
                                if ($.isEmptyObject(data)) {
                                    data.question_id = $(parentId + '_id').val();
                                }
                                data.group = group;

                                tmpl = widget.element.find('#question-option-' + group + '-type-template').html();
                                tmpl = mageTemplate(tmpl, {
                                    data: data
                                });

                                $(tmpl).insertAfter($(parentId));

                                //Add selections
                                if (data.optionChoices) {
                                    data.optionChoices.each(function (value) {
                                        widget.addSelection(value);
                                    });
                                }
                            }
                        } else {
                            previousBlock.removeClass('ignore-validate').show();
                        }
                    }

                },

                /**
                 * Change show option parent choice Id
                 */
                'click input[id^=question_option_][id$=_is_children]': function (event, data) {
                    data = data || {};
                    var currentElement = $(event.target),
                        parentId = '#' +currentElement.closest('.fieldset-alt-row').attr('id');
                    if ($.isEmptyObject(data)) {
                        if (currentElement.prop('checked')) {
                            currentElement.parent().find('.parent-choice-id').show();
                        } else {
                            $(parentId + '_parent_choice_id').val('');
                            currentElement.parent().find('.parent-choice-id').hide();
                        }
                    } else {
                        if (data.parent_choice_id > 0) {
                            currentElement.prop('checked', true);
                        } else {
                            $(parentId + '_parent_choice_id').val('');
                        }
                    }
                },
                //Sync title
                'change .field-option-title > .control > textarea[id$="_title"]': syncOptionTitle,
                'keyup .field-option-title > .control > textarea[id$="_title"]': syncOptionTitle,
                'paste .field-option-title > .control > textarea[id$="_title"]': syncOptionTitle
            });
        },

        _initSortableSelections: function () {
            if (!this.options.isReadonly) {
                this.element.find('[id^=question_option_][id$=_type_select] tbody').sortable({
                    axis: 'y',
                    handle: '[data-role=draggable-handle]',
                    helper: function (event, ui) {
                        ui.children().each(function () {
                            $(this).width($(this).width());
                        });

                        return ui;
                    },
                    update: this._updateSelectionsPositions,
                    tolerance: 'pointer'
                });
            }
        },

        /**
         * Sync sort order checkbox with hidden dropdown
         */
        _bindCheckboxHandlers: function () {
            var self = this;
            this._on({
                'change [id^=question_option_][id$=_required_question]': function (event) {
                    var $this = $(event.target);
                    $this.closest('#question_options_container_top > div').find('[name$="[is_required]"]').val($this.is(':checked') ? 1 : 0);
                }
            });
            this.element.find('[id^=question_option_][id$=_required_question]').each(function () {
                $(this).prop('checked', $(this).closest('#question_options_container_top > div').find('[name$="[is_required]"]').val() > 0);
            });
            this.element.find('[id^=question_option_][id$=_is_hide_delete]').each(function () {
                $(this).prop('checked', $(this).val() > 0);
            });
            this.element.find('.fieldset-alt-row').each(function () {
                if(!$(this).find('[id^=question_option_][id$=_exist]').prop('disabled')) {
                    if($(this).find('[id^=question_option_][id$=_exist]').val() == '1') {
                        $(this).find('input, textarea, button').prop('disabled', true);
                        var parentId = $(this).find('[id^=question_option_][id$=_parent_id]').val();
                        self.element.find('.fieldset-alt-row').each(function () {
                            if(!$(this).find('[id^=question_option_][id$=_exist]').prop('disabled')) {
                                if($(this).find('[id^=question_option_][id$=_parent_choice_id]').val() == parentId) {
                                    $(this).find('input, textarea, button').prop('disabled', true);
                                }
                            }
                        });
                    }
                }
            });
            this._on({
                'change [id^=question_option_][id$=_is_hide_delete]': function (event) {
                    event.stopImmediatePropagation();
                    var $this = $(event.target);
                    $this.val($this.prop('checked'));
                }
            });
        },

        /**
         * Update question option position
         */
        _updateOptionBoxPositions: function () {
            $(this).find('div[id^=option_]:not(.ignore-validate) .fieldset-alt > [name$="[sort_order]"]').each(function (index) {
                $(this).val(index);
            });
        },

        /**
         * Update selections positions for 'select' type of question option
         */
        _updateSelectionsPositions: function () {
            $(this).find('tr:not(.ignore-validate) [name$="[sort_order]"]').each(function (index) {
                $(this).val(index);
            });
        },

        /**
         * Disable input data if "Read Only"
         */
        _bindReadOnlyMode: function () {
            if (this.options.isReadonly) {
                $('div.questionaire-question-options').find('button,input,select,textarea').each(function () {
                    $(this).prop('disabled', true);

                    if ($(this).is('button')) {
                        $(this).addClass('disabled');
                    }
                });
            }
        },

        /**
         * Add selection value for 'select' type of question option
         */
        addSelection: function (event) {
            var data = {},
                element = event.target || event.srcElement || event.currentTarget,
                rowTmpl;

            if (typeof element !== 'undefined') {
                data.id = $(element).closest('#question_options_container_top > div')
                    .find('[name^="questionnaire[questions]"][name$="[id]"]').val();
                data.choice_id = -1;

                if (!this.options.selectionItemCount[data.id]) {
                    this.options.selectionItemCount[data.id] = 1;
                }

                data.select_id = this.options.selectionItemCount[data.id];
                data.parent_id = 'New' + this.options.selectionItemCount[data.id];

            } else {
                data = event;
                data.id = data.question_id;
                data.select_id = data.choice_id;
                data.parent_id = data.choice_id;
                data.parent_choice_id = data.parent_choice_id;
                this.options.selectionItemCount[data.id] = data.item_count;
             }
            rowTmpl = this.rowTmpl({
                data: data
            });
            $(rowTmpl).appendTo($('#select_option_type_row_' + data.id));
            if (data.parent_choice_id > 0) {
                $('#' + this.options.fieldId + '_' + data.id + '_select_' + data.select_id + '_is_children').trigger('click', data);
            }
            this.refreshSortableElements();
            this.options.selectionItemCount[data.id] = parseInt(this.options.selectionItemCount[data.id], 10) + 1;
            $('#' + this.options.fieldId + '_' + data.id + '_select_' + data.select_id + '_title').focus();
        },

        /**
         * Add question option
         */
        addOption: function (event) {
            var data = {},
                element = event.target || event.srcElement || event.currentTarget,
                baseTmpl;

            if (typeof element !== 'undefined') {
                data.id = this.options.itemCount;
                data.type = '';
                data.question_id = 0;
            } else {
                data = event;
                this.options.itemCount = data.item_count;
            }

            baseTmpl = this.baseTmpl({
                data: data
            });

            $(baseTmpl)
                .appendTo(this.element.find('#question_options_container_top'))
                .find('.collapse').collapsable();

            //set selected type value if set
            if (data.type) {
                $('#' + this.options.fieldId + '_' + data.id + '_type').val(data.type).trigger('change', data);
            }

            //set selected is_require value if set
            if (data.is_required) {
                $('#' + this.options.fieldId + '_' + data.id + '_is_required').val(data.is_required).trigger('change');
            }

            this.refreshSortableElements();
            this._bindCheckboxHandlers();
            this._bindReadOnlyMode();
            this.options.itemCount++;
            $('#' + this.options.fieldId + '_' + data.id + '_title').trigger('change');
        },

        refreshSortableElements: function () {
            if (!this.options.isReadonly) {
                this.element.sortable('refresh');
                this._updateOptionBoxPositions.apply(this.element);
                this._updateSelectionsPositions.apply(this.element);
                this._initSortableSelections();
            }

            return this;
        },

        getFreeOptionId: function (id) {
            return $('#' + this.options.fieldId + '_' + id).length ? this.getFreeOptionId(parseInt(id, 10) + 1) : id;
        }
    });

});
