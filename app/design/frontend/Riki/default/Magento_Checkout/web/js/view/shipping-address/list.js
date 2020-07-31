/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
/*global define*/
define([
    'jquery',
    'underscore',
    'ko',
    'mageUtils',
    'uiComponent',
    'uiLayout',
    'Magento_Checkout/js/model/quote',
    'Magento_Checkout/js/checkout-data',
    'Magento_Customer/js/model/address-list',
    'Magento_Checkout/js/model/address-converter',
    'Magento_Checkout/js/model/shipping-address/form-popup-state',
    'Magento_Ui/js/modal/modal',
    'uiRegistry',
    'mage/translate',
    'mage/url'
], function ($j, _, ko, utils, Component, layout, quote, checkoutData, addressList, addressConverter, formPopUpState, modal, registry, $t, urlBuilder) {
    'use strict';
    var popUp = null;
    var defaultRendererTemplate = {
        parent: '${ $.$data.parentName }',
        name: '${ $.$data.name }',
        component: 'Magento_Checkout/js/view/shipping-address/address-renderer/default'
    };

    return Component.extend({
        defaults: {
            template: 'Magento_Checkout/shipping-address/list',
            rendererTemplates: []
        },

        isFormPopUpVisible: formPopUpState.isVisible,
        isSelectShippingAddressVisible: ko.observable(false),
        visible: ko.observable(addressList().length > 0),
        addressSelected: ko.observable(false),
        visibleEditAddressButton: ko.observable(true),
        selectedDeliveryAdress: ko.observable(),

        /** Show address form popup */
        showFormPopUp: function() {
            this.isFormPopUpVisible(true);
        },

        initialize: function () {
            var self = this;
            this._super()
                .initChildren();

            addressList.subscribe(
                function(changes) {
                    var self = this;
                    changes.forEach(function(change) {
                        if (change.status === 'added') {
                            self.createRendererComponent(change.value, change.index);
                        }
                    });
                },
                this,
                'arrayChange'
            );

            this.isSelectShippingAddressVisible.subscribe(function (value) {
                if (value) {
                    var value_select = $j('select[name="select-address-from-popup"] option[selected]').val();
                    $j('select[name="select-address-from-popup"]').val(value_select);
                    self.getPopUp().openModal();
                }
            });

            return this;
        },

        initConfig: function () {
            this._super();
            // the list of child components that are responsible for address rendering
            this.rendererComponents = [];
            return this;
        },

        initChildren: function () {
            _.each(addressList(), this.createRendererComponent, this);
            return this;
        },

        /**
         * Create new component that will render given address in the address list
         *
         * @param address
         * @param index
         */
        createRendererComponent: function (address, index) {
            if (index in this.rendererComponents) {
                this.rendererComponents[index].address(address);
            } else {
                // rendererTemplates are provided via layout
                var rendererTemplate = (address.getType() != undefined && this.rendererTemplates[address.getType()] != undefined)
                    ? utils.extend({}, defaultRendererTemplate, this.rendererTemplates[address.getType()])
                    : defaultRendererTemplate;
                var templateData = {
                    parentName: this.name,
                    name: index
                };
                var rendererComponent = utils.template(rendererTemplate, templateData);
                utils.extend(rendererComponent, {address: ko.observable(address)});
                layout([rendererComponent]);
                this.rendererComponents[index] = rendererComponent;
            }
        },

        /** Show select address popup */
        showSelectShippingAddressPopUp: function() {
            this.isSelectShippingAddressVisible(true);
        },

        addNewAddress: function() {
            var self = this;
            registry.get('checkout.steps.shipping-step.shippingAddress', function(el) {
                if(!el.isNewAddressAdded()) {
                    checkoutData.setShippingAddressAction('add');
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

                        if(typeof shippingAddressData.customer_address_id != 'undefined') {
                            shippingAddressData.customer_address_id = null;
                        }

                        if(typeof shippingAddressData.region_code != 'undefined') {
                            shippingAddressData.region_code = null;
                        }

                        if(typeof shippingAddressData.region_id != 'undefined') {
                            shippingAddressData.region_id = null;
                        }

                        if(typeof shippingAddressData.customer_id != 'undefined') {
                            shippingAddressData.customer_id = null;
                        }
                    }
                    window.checkoutConfig.isNewAddress = true;
                    registry.get('checkoutProvider', function(checkoutProvider) {
                        checkoutProvider.set(
                            'shippingAddress',
                            $j.extend({}, checkoutProvider.get('shippingAddress'), shippingAddressData)
                        );
                    });
                    el.isEditAddress(false);
                    self.showFormPopUp();
                    window.checkoutConfig.isNewAddress = false;
                } else {
                    self.editSelectedShippingAddress('add');
                }
            })
        },

        editSelectedShippingAddress: function(type) {
            var shippingAddressData = null;
            if(type == 'add') {
                checkoutData.setShippingAddressAction('add');
                shippingAddressData = checkoutData.getNewCustomerShippingAddress();
            }else {
                var quoteAddress = quote.shippingAddress(),
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

                checkoutData.setShippingAddressAction('edit');
                if(quote.shippingAddress().getType() == 'new-customer-address') {
                    shippingAddressData = checkoutData.getNewCustomerShippingAddress();
                }else {
                    quoteAddress.customAttributes.lastnamekana = (typeof quoteAddress.customAttributes.lastnamekana.value != 'undefined') ? quoteAddress.customAttributes.lastnamekana.value : quoteAddress.customAttributes.lastnamekana;
                    quoteAddress.customAttributes.firstnamekana = (typeof quoteAddress.customAttributes.firstnamekana.value != 'undefined') ? quoteAddress.customAttributes.firstnamekana.value : quoteAddress.customAttributes.firstnamekana;
                    quoteAddress.customAttributes.riki_nickname = (typeof quoteAddress.customAttributes.riki_nickname.value != 'undefined') ? quoteAddress.customAttributes.riki_nickname.value : quoteAddress.customAttributes.riki_nickname;

                    if(typeof quoteAddress.customAttributes.apartment != 'undefined') {
                        quoteAddress.customAttributes.apartment = (typeof quoteAddress.customAttributes.apartment.value != 'undefined') ? quoteAddress.customAttributes.apartment.value : quoteAddress.customAttributes.apartment;
                    }else {
                        quoteAddress.customAttributes.apartment = '';
                    }

                    if(typeof quoteAddress.customAttributes.riki_type_address != 'undefined') {
                        quoteAddress.customAttributes.riki_type_address = (typeof quoteAddress.customAttributes.riki_type_address.value != 'undefined') ? quoteAddress.customAttributes.riki_type_address.value : quoteAddress.customAttributes.riki_type_address;
                    }else {
                        quoteAddress.customAttributes.riki_type_address = '';
                    }
                    shippingAddressData = addressConverter.quoteAddressToFormAddressData(quoteAddress);
                }
            }

            registry.get('checkoutProvider', function(checkoutProvider) {
                if (shippingAddressData) {
                    checkoutProvider.set(
                        'shippingAddress',
                        $j.extend({}, checkoutProvider.get('shippingAddress'), shippingAddressData)
                    );
                }
            });
            registry.get('checkout.steps.shipping-step.shippingAddress', function(el) {
                el.isEditAddress((type=='edit') ? true : false);
                el.showFormPopUp();
            });
        },

        getPopUp: function() {
            var self = this;
            if (!popUp) {
                var optionsPopup = {
                    innerScroll: true,
                    responsive: false,
                    title: $t('Setting the address'),
                    trigger: 'opc-select-shipping-address',
                    modalClass: 'select-shipping-address-modal modal_checkout',
                    type: 'popup',
                    buttons: [{
                        text: $t('Shipping here'),
                        class: 'action primary action-save-address',
                        click: self.selectAddressFromPopup.bind(self)
                    }]
                };
                optionsPopup.closed = function() {
                    self.isSelectShippingAddressVisible(false);
                };
                popUp = modal(optionsPopup, $j('#opc-select-shipping-address'));
            }
            return popUp;
        },

        selectAddressFromPopup: function() {
            this.getPopUp().closeModal();

            var addressIndex = $j('select[name="select-address-from-popup"]').val();
            registry.get('checkout.steps.shipping-step.shippingAddress.address-list.'+addressIndex, function(el) {
                if(!el.isSelected()) {
                    el.selectAddress();
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
                }
            });

            var rikiAddressType;
            if(typeof quote.shippingAddress().customerId == 'undefined') {
                if( !(typeof quote.shippingAddress().customAttributes == 'undefined')
                    && !(typeof quote.shippingAddress().customAttributes.riki_type_address == 'undefined') ){
                    if( quote.shippingAddress().customAttributes.riki_type_address.value != "" ){
                        rikiAddressType = quote.shippingAddress().customAttributes.riki_type_address.value;
                    }
                }

            }else {
                if( !(typeof quote.shippingAddress().customAttributes.riki_type_address == 'undefined') ){
                    if( quote.shippingAddress().customAttributes.riki_type_address.value != "" ){
                        rikiAddressType = quote.shippingAddress().customAttributes.riki_type_address.value;
                    }
                }
            }
            this.selectedDeliveryAdress(rikiAddressType);
        },

        afterRenderAddresses: function() {
            registry.get('checkout.steps.shipping-step.shippingAddress', function(el) {
                el.isFormInline(addressList().length == 0);
                if(el.isFormInline()) {
                    checkoutData.setShippingAddressAction('add');
                    return el.showFormPopUp();
                }
            });
        }

    });
});
