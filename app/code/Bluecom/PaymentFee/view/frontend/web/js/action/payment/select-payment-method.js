/*global define*/
define(
    [
        'jquery',
        'ko',
        'Magento_Checkout/js/model/quote',
        'uiRegistry',
        'Magento_Catalog/js/price-utils',
        'jquery/jquery-storageapi'
    ],
    function($, ko, quote, uiRegistry, priceUtils ) {
        'use strict';

        return function (paymentMethod) {
            if(paymentMethod != null) {

                uiRegistry.get('checkout.steps.billing-step.payment' , function (paymentComponent){
                    paymentComponent.selectedPaymentLabel(paymentMethod.title);
                    if(paymentMethod.method == 'paygent'){
                        if (paymentMethod.paygent_option == 0) {
                            paymentComponent.selectedPaymentLabel('クレジットカード支払い（前回使用）');
                            $(".btn_place-order span").text("注文確定");
                        } else if (paymentMethod.paygent_option == 1) {
                            paymentComponent.selectedPaymentLabel('クレジットカード支払い');
                            $(".btn_place-order span").text("カード情報の入力へ進む");
                        } else {
                            if (window.checkoutConfig.cc_used_date) {
                                paymentComponent.selectedPaymentLabel('クレジットカード支払い（前回使用）');
                                $(".btn_place-order span").text("注文確定");
                            } else {
                                paymentComponent.selectedPaymentLabel('クレジットカード支払い');
                                $(".btn_place-order span").text("カード情報の入力へ進む");
                            }
                        }
                    }
                    $(".payment-methods-select").change(function () {
                        if ($(this).children("option:selected").val() == 'paygent_redirect')
                        {
                            $(".btn_place-order span").text("カード情報の入力へ進む");
                        } else
                        {
                            $(".btn_place-order span").text("注文確定");
                        }
                    });
                });

                uiRegistry.get('checkout.placeOrder', function(placeOrderBlock) {
                    placeOrderBlock.isPlaceOrderActionAllowed(true);
                });

                uiRegistry.get('checkout.steps.confirm-info-step' , function (singleConfirm){
                    singleConfirm.paymentMethodName(paymentMethod.title);
                    var formattedSurchargeFee =  priceUtils.formatPrice(
                        0,window.checkoutConfig.priceFormat
                    );

                    if(typeof window.paymentFee[paymentMethod.method] != "undefined") {
                        formattedSurchargeFee =  priceUtils.formatPrice(
                            window.paymentFee[paymentMethod.method],window.checkoutConfig.priceFormat
                        );
                    }
                    singleConfirm.formattedSurchargeFee(formattedSurchargeFee);
                });
                uiRegistry.get('checkout.steps.multiple-checkout-order-confirmation' , function (multipleConfirm){
                    multipleConfirm.paymentMethodName(paymentMethod.title);
                    var formattedSurchargeFee =  priceUtils.formatPrice(
                        0,window.checkoutConfig.priceFormat
                    );

                    if(typeof window.paymentFee[paymentMethod.method] != "undefined") {
                        formattedSurchargeFee =  priceUtils.formatPrice(
                            window.paymentFee[paymentMethod.method],window.checkoutConfig.priceFormat
                        );
                    }
                    multipleConfirm.formattedSurchargeFee(formattedSurchargeFee);
                });
            }
            quote.paymentMethod(paymentMethod);
        }
    }
);