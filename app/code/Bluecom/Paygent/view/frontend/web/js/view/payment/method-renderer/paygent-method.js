/*browser:true*/
/*global define*/
define(
    [
        'ko',
        'jquery',
        'Magento_Checkout/js/view/payment/default',
        'Magento_Checkout/js/model/quote',
        'Magento_Customer/js/model/customer',
        'Magento_Checkout/js/model/url-builder',
        'mage/storage',
        'Magento_Checkout/js/model/error-processor',
        'Magento_Checkout/js/model/full-screen-loader',
        'mage/translate'
    ],
    function (ko, $, Component, quote, customer, urlBuilder, storage, errorProcessor, fullScreenLoader) {
        return Component.extend({
            defaults: {
                template: 'Bluecom_Paygent/payment/paygent-form',
                redirectAfterPlaceOrder: true
            },
            initialize: function () {
                this._super();
                var self = this;
                /**
                 * bindHtml binding
                 * @property {Function}  init
                 * @property {Function}  update
                 */
                ko.bindingHandlers.bindHtml = {

                    /**
                     * init bindHtml binding
                     * @param {Object} element
                     * @param {Function} valueAccessor
                     */
                    init: function (element, valueAccessor) {
                        var original = ko.unwrap(valueAccessor() || '');
                        $(element).html($.mage.__(original));
                    },
                    update: function (element, valueAccessor) {
                        
                    }
                };
            },
            afterPlaceOrder: function () {
                //var self = this;
                //redirect to paygent after place order Paygent Method
                //window.location.replace(self.getUrl());
            },
            getCode: function () {
                return 'paygent';
            },
            getUrl: function () {
                return window.checkoutConfig.payment[this.getCode()].transactionDataUrl;
            },
            isAvailable: function () {
                return true;
            }
        });
    }
);

