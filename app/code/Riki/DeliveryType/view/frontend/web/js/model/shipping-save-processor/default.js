/*global define,alert*/
define(
    [
        'jquery',
        'ko',
        'Magento_Checkout/js/model/quote',
        'Magento_Checkout/js/model/url-builder',
        'mage/storage',
        'Magento_Checkout/js/model/payment-service',
        'Magento_Checkout/js/model/payment/method-converter',
        'Magento_Checkout/js/model/error-processor',
        'Magento_Checkout/js/model/full-screen-loader',
        'Magento_Checkout/js/action/select-billing-address',
        'uiRegistry'
    ],
    function (
        $,
        ko,
        quote,
        urlBuilder,
        storage,
        paymentService,
        methodConverter,
        errorProcessor,
        fullScreenLoader,
        selectBillingAddressAction,
        uiRegistry
    ) {
        'use strict';

        return {
            saveShippingInformation: function () {
                var payload,
                    deliveryOption = [],
                    serviceUrl,
                    paygentComponent = uiRegistry.get('checkout.steps.billing-step.payment.payments-list.paygent'),
                    paygentOption;

                serviceUrl = urlBuilder.createUrl('/rikicarts/mine/payment-information', {});
                paygentOption = (typeof paygentComponent != 'undefined') ? paygentComponent.paygentOption() : null;

                $('[name="delivery_date"]').each(function (i) {
                    var $delivery_name = $('[name="delivery_name"]:eq('+ i +')'),
                        $deliveryDate = '',
                        $next_delivery_date = $delivery_name.parents('.shipping-block').find('.block-next-delivery-date').find('input');
                    $deliveryDate = ($('[name="delivery_date"]:eq('+ i +')').val() != undefined) ? $('[name="delivery_date"]:eq('+ i +')').val() : '';

                    deliveryOption.push({
                        'deliveryName': ($('[name="delivery_name"]:eq('+ i +')').val() != undefined) ? $('[name="delivery_name"]:eq('+ i +')').val() : '',
                        'deliveryDate': $deliveryDate,
                        'deliveryTime': ($('[name="delivery_time"]:eq('+ i +')').val() != undefined) ? $('[name="delivery_time"]:eq('+ i +')').val() : '',
                        'deliveryTimeLabel': ($('[name="delivery_time"]:eq('+ i +') option:selected').text() != undefined) ? $('[name="delivery_time"]:eq('+ i +') option:selected').text() : '',
                        'nextDeliveryDate': ($next_delivery_date.val() != undefined) ? $next_delivery_date.val() : ''
                    });

                });

                uiRegistry.get('checkout.steps.confirm-info-step' , function (singleConfirm){
                    singleConfirm.deliveryTimes.removeAll();
                    singleConfirm.deliveryTimes(deliveryOption);
                });

                var multiDelivery = JSON.stringify(deliveryOption);

                if (!quote.billingAddress()) {
                    selectBillingAddressAction(quote.shippingAddress());
                }

                payload = {
                    addressInformation: {
                        shipping_address: quote.shippingAddress(),
                        billing_address: quote.billingAddress(),
                        shipping_method_code: quote.shippingMethod().method_code,
                        shipping_carrier_code: quote.shippingMethod().carrier_code,
                        extension_attributes:{
                            delivery_date: multiDelivery
                        }
                    },
                    paymentMethod: {
                        method: quote.paymentMethod().method,
                        extension_attributes: {
                            paygent_option: paygentOption
                        }
                    }
                };

                if(paygentOption == null) {
                    payload.paymentMethod = {
                        method: quote.paymentMethod().method
                    }
                }

                fullScreenLoader.startLoader();

                return storage.post(
                    serviceUrl,
                    JSON.stringify(payload),
                    false
                ).done(
                    function (response) {
                        quote.setTotals(response);
                        fullScreenLoader.stopLoader();
                        $('#opc-select-payment-method').modal('closeModal');
                    }
                ).fail(
                    function (response) {
                        errorProcessor.process(response);
                        fullScreenLoader.stopLoader();
                    }
                );
            }
        };
    }
);
