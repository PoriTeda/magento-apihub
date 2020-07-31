/**
 * Copyright © 2015 Amasty. All rights reserved.
 */

/**
 * Customer store credit(balance) application
 */
/*global define,alert*/
define(
    [
        'ko',
        'jquery',
        'Magento_Checkout/js/model/quote',
        'Magento_Checkout/js/model/resource-url-manager',
        'Magento_Checkout/js/model/payment-service',
        'Magento_Checkout/js/model/error-processor',
        'Magento_Ui/js/model/messageList',
        'mage/storage',
        'Magento_Checkout/js/action/get-totals',
        'mage/translate'
    ],
    function (ko, $, quote, urlManager, paymentService, errorProcessor, messageList, storage, getTotalsAction, $t) {
        'use strict';
        return function (coupon, isApplied, isLoading, isDelete ) {
            var quoteId = quote.getQuoteId();
            var url = urlManager.getApplyCouponUrl(coupon, quoteId);
            var message = $t('Your coupon was successfully applied');
            var messageError = $t('Coupon code is not valid');
            var messageDelete = $t('Coupon code was removed');
            $('#discount-msg-riki').text('');
            $('#discount-msg-riki-err').text('');
            return storage.put(url, {}, false)
                .done(
                    function (response) {
                        if (response) {
                            var deferred = $.Deferred(),
                                discountInput = $('#discount-code'),
                                discountHideInput = $('#discount-code-fake');

                            isLoading(false);
                            isApplied(true);
                            getTotalsAction([], deferred);
                            $.when(deferred).done(
                                function() {
                                    paymentService.setPaymentMethods(
                                        paymentService.getAvailablePaymentMethods()
                                    );
                                    var codeList = response.split(',');
                                    discountInput.val(response).change();
                                    var newCode = discountHideInput.val();
                                    if (isDelete) {
                                        $('#discount-msg-riki-err').text(messageDelete);
                                    } else if ($.inArray(newCode, codeList) == -1 && newCode != response) {
                                        $('#discount-msg-riki-err').text('クーポンは有効ではありません');
                                    } else {;
                                        $('#discount-msg-riki').append('<svg version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" width="20" height="20" viewBox="0 0 20 20">\n' +
                                            '<path fill="#0084E1" d="M9.5 20c-2.538 0-4.923-0.988-6.718-2.782s-2.782-4.18-2.782-6.717c0-2.538 0.988-4.923 2.782-6.718s4.18-2.783 6.718-2.783c2.538 0 4.923 0.988 6.718 2.783s2.782 4.18 2.782 6.718-0.988 4.923-2.782 6.717c-1.794 1.794-4.18 2.782-6.718 2.782zM9.5 2c-4.687 0-8.5 3.813-8.5 8.5s3.813 8.5 8.5 8.5 8.5-3.813 8.5-8.5-3.813-8.5-8.5-8.5z"></path>\n' +
                                            '<path fill="#0084E1" d="M7.5 14.5c-0.128 0-0.256-0.049-0.354-0.146l-3-3c-0.195-0.195-0.195-0.512 0-0.707s0.512-0.195 0.707 0l2.646 2.646 6.646-6.646c0.195-0.195 0.512-0.195 0.707 0s0.195 0.512 0 0.707l-7 7c-0.098 0.098-0.226 0.146-0.354 0.146z"></path>\n' +
                                            '</svg> クーポンが適用されました');
                                    }
                                    discountHideInput.val('');
                                }
                            );

                        }
                    }
                )
                .fail(
                    function (response) {
                        isLoading(false);
                        errorProcessor.process(response);
                    }
                ) .complete(function(response) {
                    $('.message-error').remove();
                    if (response.status != '200') {
                        $('#discount-msg-riki-err').text('クーポンは有効ではありません');
                    }
                });
        };
    }
);
