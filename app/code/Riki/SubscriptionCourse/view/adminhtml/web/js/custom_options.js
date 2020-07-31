/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
/*jshint browser:true*/
/*global alert:true*/
define([
    'jquery',
    'mage/template',
    'Magento_Ui/js/modal/alert',
    'jquery/ui',
    'useDefault',
    'collapsable',
    'mage/translate',
    'mage/backend/validation',
    'Magento_Ui/js/modal/modal'
], function ($, mageTemplate, alert) {
    'use strict';

    $.widget('mage.SubscriptionCourseOptions', {
        options: {
            selectionItemCount: {}
        },

        _create: function () {
            var self = this;
            self.baseTmplMinimumAmount = mageTemplate('#custom-option-base-template-minimum-amount');
            self.baseTmplMaximumQty = mageTemplate('#custom-option-base-template-maximum-qty');

            $.each(self.options.fieldId , function(fieldName, itemCount) {
                self._initOptionBoxes(fieldName);
                self._initSortableSelections(fieldName);
            });

            self._addValidation();
        },

        _addValidation: function () {
        },

        _initOptionBoxes: function (fieldName) {
            if (!this.options.isReadonly) {
                this.element.sortable({
                    axis: 'y',
                    handle: '[data-role=draggable-handle]',
                    items: '#' + fieldName + '_options_container_top > div',
                    update: this._updateOptionBoxPositions,
                    tolerance: 'pointer'
                });
            }
            var syncOptionTitle = function (event) {
                var elementCommonId = event.target.id;
                var newBoxTitle = $.mage.__('From order time: - To order time: ');
                if (elementCommonId.indexOf('order_from')!== -1) {
                    var elementOrderFrom = elementCommonId;
                    var elementOrderTo = elementCommonId.replace('order_from','order_to');
                    newBoxTitle = $.mage.__('From order time: ') + $('#'+ elementOrderFrom).val()
                        + ' - ' +  $.mage.__('To order time: ') + $('#'+ elementOrderTo).val();
                }
                if (elementCommonId.indexOf('order_to')!== -1) {
                    var elementOrderTo = elementCommonId;
                    var elementOrderFrom = elementCommonId.replace('order_to','order_from');
                    newBoxTitle = $.mage.__('From order time: ') + $('#'+ elementOrderFrom).val()
                        + ' - ' +  $.mage.__('To order time: ') + $('#'+ elementOrderTo).val();
                }
                var currentValue = $(event.target).val(),
                    optionBoxTitle = $('.admin__collapsible-title > span', $(event.target).closest('.fieldset-wrapper')),
                    newOptionTitle = $.mage.__('From order time: - To order time: ') + currentValue ;
                optionBoxTitle.text(newBoxTitle);
            };
            this._on({
                /**
                 * Add new custom option minimum_amount
                 */
                'click #minimum_amount_add_new_defined_option': function (event) {
                    this.addOption(event, 'minimum_amount');
                },

                /**
                 * Remove custom option minimum_amount
                 */
                'click button[id^=minimum_amount_][id$=_delete]': function (event) {
                    var targetElement = event.target.id.replace('minimum_amount','option');
                    targetElement = targetElement.replace('_delete','');
                    $('#minimum_amount_'+ targetElement).remove();
                },

                /**
                 * Add new custom option maximum qty
                 */
                'click #maximum_qty_add_new_defined_option': function (event) {
                    this.addOption(event, 'maximum_qty');
                },

                /**
                 * Remove custom option maximum qty
                 */
                'click button[id^=maximum_qty_][id$=_delete]': function (event) {
                    var targetElement = event.target.id.replace('maximum_qty','option');
                    targetElement = targetElement.replace('_delete','');
                    $('#maximum_qty_'+ targetElement).remove();
                },

                //Sync title
                'change .control > input[id$="_order_from"]': syncOptionTitle,
                'keyup .control > input[id$="_order_from"]': syncOptionTitle,
                'paste .control > input[id$="_order_from"]': syncOptionTitle,
                'change .control > input[id$="_order_to"]': syncOptionTitle,
                'keyup .control > input[id$="_order_to"]': syncOptionTitle,
                'paste .control > input[id$="_order_to"]': syncOptionTitle

            });
        },

        _initSortableSelections: function (fieldName) {
            if (!this.options.isReadonly) {
                this.element.find('[id^=' + fieldName + '_][id$=_type_select] tbody').sortable({
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
         * Update Custom option position
         */
        _updateOptionBoxPositions: function () {
            $(this).find('div[id^=option_]:not(.ignore-validate) .fieldset-alt > [name$="[sort_order]"]').each(function (index) {
                $(this).val(index);
            });
        },

        /**
         * Update selections positions for 'select' type of custom option
         */
        _updateSelectionsPositions: function () {
            $(this).find('tr:not(.ignore-validate) [name$="[sort_order]"]').each(function (index) {
                $(this).val(index);
            });
        },

        /**
         * Add custom option
         */
        addOption: function (event, fieldName) {
            var data = {},
                element = event.target || event.srcElement || event.currentTarget,
                baseTmpl;

            if (typeof element !== 'undefined') {
                data.id = this.options.fieldId[fieldName];
                data.type = '';
                data.option_id = 0;
            } else {
                data = event;
                this.options.fieldId[fieldName] = data.itemCount;
            }
            if (!data.id) {
                data.id = $('.' + fieldName + '_options').length;
            }
            baseTmpl = this['baseTmpl' + this.changeFieldName(fieldName)]({
                data: data
            });
            $(baseTmpl)
                .appendTo(this.element.find('#' + fieldName + '_options_container_top'))
                .find('.collapse').collapsable();
            if (data.from_order_time) {
                $('#' + fieldName + '_' + data.id + '_order_from').val(data.from_order_time).trigger('change', data);
            }
            if (data.to_order_time) {
                $('#' + fieldName + '_' + data.id + '_order_to').val(data.to_order_time).trigger('change');
            }
            if (data.amount) {
                $('#' + fieldName + '_' + data.id + '_' + fieldName).val(data.amount);
            }
            this.refreshSortableElements(fieldName);
            this.options.fieldId[fieldName]++;
            $('#' + fieldName + '_' + data.id + '_order_from').trigger('change');
            $('#' + fieldName + '_' + data.id + '_order_to').trigger('change');
        },

        refreshSortableElements: function (fieldName) {
            if (!this.options.isReadonly) {
                this.element.sortable('refresh');
                this._updateOptionBoxPositions.apply(this.element);
                this._updateSelectionsPositions.apply(this.element);
                this._initSortableSelections(fieldName);
            }

            return this;
        },

        changeFieldName: function (fieldName) {
            var splitStr = fieldName.toLowerCase().split('_');
            for (var i = 0; i < splitStr.length; i++) {
                splitStr[i] = splitStr[i].charAt(0).toUpperCase() + splitStr[i].substring(1);
            }

            return splitStr.join('');
        }
    });
});
