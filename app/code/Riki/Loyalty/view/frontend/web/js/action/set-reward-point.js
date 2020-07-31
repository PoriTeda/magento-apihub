/**
 * Apply reward point
 */
/*global define,alert*/
define(
    [
        'ko',
        'jquery',
        'Magento_Checkout/js/model/quote',
        'Magento_Checkout/js/model/url-builder',
        'Magento_Checkout/js/model/error-processor',
        'Magento_Ui/js/model/messageList',
        'mage/storage',
        'Magento_Checkout/js/model/totals',
        'mage/translate',
        'Magento_Checkout/js/model/full-screen-loader',
        'Magento_Checkout/js/model/payment/method-converter',
        'Magento_Checkout/js/model/payment-service'
    ],
    function (
        ko,
        $,
        quote,
        urlBuilder,
        errorProcessor,
        messageContainer,
        storage,
        totals,
        $t,
        fullScreenLoader,
        methodConverter,
        paymentService
    ) {
        'use strict';
        window.rewardActionSent = false;
        return function (option, usedPoints, pointAmount) {
            if(window.rewardActionSent) {
                return false;
            }

            window.rewardActionSent = true;
            option = $.mage.parseNumber(option);

            fullScreenLoader.startLoader();

            var deliveryOption = [];

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

            var multiDelivery = JSON.stringify(deliveryOption);

            var payload = {
                addressInformation: {
                    shipping_address: quote.shippingAddress(),
                    billing_address: quote.billingAddress(),
                    shipping_method_code: quote.shippingMethod().method_code,
                    shipping_carrier_code: quote.shippingMethod().carrier_code,
                    extension_attributes:{
                        delivery_date: multiDelivery
                    }
                },
                used_points: usedPoints,
                option: option
            };
            var params = {
                cart_id: quote.getQuoteId()
            };
            var serviceUrl = urlBuilder.createUrl('/riki-loyalty/:cartId/apply-reward-point', params);
            return storage.post(
                serviceUrl, JSON.stringify(payload), false
            ).done(
                function (response) {
                    quote.setTotals(response.totals);
                    paymentService.setPaymentMethods(methodConverter(response.payment_methods));
                    fullScreenLoader.stopLoader();
                    if (option == 2) {
                        pointAmount(totals.getSegment('apply_point').value);
                    }
                    if (response.totals.grand_total == 0 && $(".btn_place-order span").text() == 'カード情報の入力へ進むカード情報の入力へ進む')
                    {
                        $(".btn_place-order span").text("注文確定");
                    }
                    window.rewardActionSent = false;
                }
            ).fail(
                function (response) {
                    fullScreenLoader.stopLoader();
                    errorProcessor.process(response, messageContainer);
                }
            );
        };
    }
);
