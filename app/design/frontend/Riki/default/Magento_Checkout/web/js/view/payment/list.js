/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
define([
    'jquery',
    'underscore',
    'ko',
    'mageUtils',
    'uiComponent',
    'Magento_Checkout/js/model/payment/method-list',
    'Magento_Checkout/js/model/payment/renderer-list',
    'uiLayout',
    'Magento_Checkout/js/model/checkout-data-resolver',
    'Magento_Checkout/js/model/payment-service',
    'Magento_Checkout/js/action/select-payment-method',
    'mage/translate',
    'Magento_Checkout/js/checkout-data',
    'Magento_Checkout/js/model/quote',
    'uiRegistry'
], function ($, _, ko, utils, Component, paymentMethods, rendererList, layout, checkoutDataResolver, paymentService, selectPaymentMethodAction, $t, checkoutData, quote, registry) {
    'use strict';

    return Component.extend({
        defaults: {
            template: 'Magento_Checkout/payment-methods/list',
            visible: paymentMethods().length > 0,
            configDefaultGroup: {
                name: 'methodGroup',
                component: 'Magento_Checkout/js/model/payment/method-group'
            },
            paymentGroupsList: [],
            defaultGroupTitle: $t('Select a new payment method')
        },

        paymentOptions: ko.observableArray(),
        selectedPayment: ko.observable(),

        /** @inheritdoc */
        initObservable: function () {
            this._super().
            observe(['paymentGroupsList']);

            return this;
        },

        /**
         * Initialize view.
         *
         * @returns {Component} Chainable.
         */
        initialize: function () {
            this._super().initDefaulGroup().initChildren();
            this.isPlaceOrderActionAllowedFromSelectAddress = ko.observable(true);
            this.initSelectedPayment();

            paymentMethods.subscribe(
                function (changes) {
                    checkoutDataResolver.resolvePaymentMethod();
                    //remove renderer for "deleted" payment methods
                    _.each(changes, function (change) {
                        if (change.status === 'deleted') {
                            this.removeRenderer(change.value.method);
                        }
                    }, this);
                    //add renderer for "added" payment methods
                    _.each(changes, function (change) {
                        if (change.status === 'added') {
                            this.createRenderer(change.value);
                        }
                    }, this);
                    this.initSelectedPayment();
                }, this, 'arrayChange');

            this.selectedPayment.subscribe(function (newPayment) {
                if(newPayment && !$('input[type=radio]#' + newPayment).prop('checked')) {
                    if(newPayment.method) {
                        $('input[type=radio]#' + newPayment.method).prop('checked', true).click();
                    }
                    else {
                        $('input[type=radio]#' + newPayment).prop('checked', true).click();
                    }
                }
                if(newPayment != null) {
                    $('.note .note-payment').addClass('hidden');
                }
            });

            var selectedDeliveryType = registry.get("checkout.steps.shipping-step.shippingAddress.address-list"),
                self = this;
            if (typeof selectedDeliveryType != 'undefined' && selectedDeliveryType !== null) {
                if(typeof selectedDeliveryType.selectedDeliveryAdress != 'undefined') {
                    selectedDeliveryType.selectedDeliveryAdress.subscribe(function () {
                        self.initSelectedPayment();
                    });
                }
            }

            return this;
        },

        /**
         * Creates default group
         *
         * @returns {Component} Chainable.
         */
        initDefaulGroup: function () {
            layout([
                this.configDefaultGroup
            ]);

            return this;
        },

        /**
         * Create renders for child payment methods.
         *
         * @returns {Component} Chainable.
         */
        initChildren: function () {
            var self = this;
            _.each(paymentMethods(), function (paymentMethodData) {
                self.createRenderer(paymentMethodData);
            });

            return this;
        },

        initSelectedPayment: function () {
            if(paymentMethods().length > 0) {
                var avaiablePayment = paymentService.getAvailablePaymentMethods();
                var selectedPaymentMethod = quote.paymentMethod() ? quote.paymentMethod().method : '',
                    paygentOption = checkoutData.getPaygentOption();
                var newAvaiablePayment = _.reject(avaiablePayment, function(payment){
                    return payment.method == 'paygent';
                });
                if (newAvaiablePayment.length < avaiablePayment.length) {
                    newAvaiablePayment.unshift({title: $t('Credit Card With Paygent'), method: 'paygent_redirect'});
                    if (window.checkoutConfig.cc_used_date) {
                        newAvaiablePayment.unshift({title: $t('Credit Card With Paygent') + $t('(used before)'), method: 'paygent_use_previous_card'});
                    }
                }
                var quoteDdateAddress = quote.quoteItemDdateInfo(),
                    rikiAddressType = quote.shippingAddress().customAttributes ? quote.shippingAddress().customAttributes.riki_type_address.value : '';
                if (typeof quoteDdateAddress.addressDdateInfo != 'undefined' || rikiAddressType == 'shipping' || $('body').hasClass('multicheckout-index-index')) {
                    newAvaiablePayment = _.reject(newAvaiablePayment, function(payment){
                        return payment.method == 'cashondelivery';
                    });
                }
                this.paymentOptions(newAvaiablePayment);

                this.selectedPayment('');
                if(selectedPaymentMethod != '') {
                    $('.note .note-payment').addClass('hidden');
                    if(selectedPaymentMethod == 'paygent'){
                        if(paygentOption == 0) {
                            this.selectedPayment('paygent_use_previous_card');
                        }else if(paygentOption == 1) {
                            this.selectedPayment('paygent_redirect');
                        }else {
                            if (window.checkoutConfig.cc_used_date) {
                                this.selectedPayment('paygent_use_previous_card');
                            }else{
                                this.selectedPayment('paygent_redirect');
                            }
                        }
                    }else {
                        this.selectedPayment(selectedPaymentMethod);
                    }
                }
                else {
                    $('.note .note-payment').removeClass('hidden');
                }
            }
        },

        onPaymentChange: function (payment) {
            selectPaymentMethodAction(payment);
        },

        /**
         * Collects unique groups of available payment methods
         *
         * @param {Object} group
         */
        collectPaymentGroups: function (group) {
            var groupsList = this.paymentGroupsList(),
                isGroupExists = _.some(groupsList, function (existsGroup) {
                    return existsGroup.alias === group.alias;
                });

            if (!isGroupExists) {
                groupsList.push(group);
                groupsList = _.sortBy(groupsList, function (existsGroup) {
                    return existsGroup.sortOrder;
                });
                this.paymentGroupsList(groupsList);
            }
        },

        /**
         * Returns payment group title
         *
         * @param {Object} group
         * @returns {String}
         */
        getGroupTitle: function (group) {
            var title = group().title;

            if (group().isDefault() && this.paymentGroupsList().length > 1) {
                title = this.defaultGroupTitle;
            }

            return title;
        },

        /**
         * Checks if at least one payment method available
         *
         * @returns {String}
         */
        isPaymentMethodsAvailable: function () {
            return _.some(this.paymentGroupsList(), function (group) {
                return this.getRegion(group.displayArea)().length;
            }, this);
        },

        /**
         * @returns
         */
        createComponent: function (payment) {
            var rendererTemplate,
                rendererComponent,
                templateData;

            templateData = {
                parentName: this.name,
                name: payment.name
            };
            rendererTemplate = {
                parent: '${ $.$data.parentName }',
                name: '${ $.$data.name }',
                displayArea: 'payment-method-items',
                component: payment.component
            };
            rendererComponent = utils.template(rendererTemplate, templateData);
            utils.extend(rendererComponent, {
                item: payment.item,
                config: payment.config
            });

            return rendererComponent;
        },

        /**
         * Create renderer.
         *
         * @param {Object} paymentMethodData
         */
        createRenderer: function (paymentMethodData) {
            var isRendererForMethod = false,
                currentGroup;

            registry.get(this.configDefaultGroup.name, function (defaultGroup) {
                _.each(rendererList(), function (renderer) {

                    if (renderer.hasOwnProperty('typeComparatorCallback') &&
                        typeof renderer.typeComparatorCallback == 'function'
                    ) {
                        isRendererForMethod = renderer.typeComparatorCallback(renderer.type, paymentMethodData.method);
                    } else {
                        isRendererForMethod = renderer.type === paymentMethodData.method;
                    }

                    if (isRendererForMethod) {
                        currentGroup = renderer.group ? renderer.group : defaultGroup;

                        this.collectPaymentGroups(currentGroup);

                        layout([
                            this.createComponent(
                                {
                                    config: renderer.config,
                                    component: renderer.component,
                                    name: renderer.type,
                                    method: paymentMethodData.method,
                                    item: paymentMethodData,
                                    displayArea: 'payment-method-items'
                                }
                            )]);
                    }
                }.bind(this));
            }.bind(this));
        },

        /**
         * Get renderer for payment method type.
         *
         * @param {String} paymentMethodCode
         * @returns {Object}
         */
        getRendererByType: function (paymentMethodCode) {
            var compatibleRenderer;
            _.find(rendererList(), function (renderer) {
                if (renderer.type === paymentMethodCode) {
                    compatibleRenderer = renderer;
                }
            });

            return compatibleRenderer;
        },

        /**
         * Remove view renderer.
         *
         * @param {String} paymentMethodCode
         */
        removeRenderer: function (paymentMethodCode) {
            var items;

            _.each(this.paymentGroupsList(), function (group) {
                items = this.getRegion('payment-method-items');

                _.find(items(), function (value) {
                    if (value.item.method.indexOf(paymentMethodCode) === 0) {
                        value.disposeSubscriptions();
                        value.destroy();
                    }
                });
            }, this);
        }
    });
});
