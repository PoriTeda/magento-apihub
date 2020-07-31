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
            return storage.put(
                url,
                {},
                false
            ).done(
                function (response) {
                    if (response) {
                        var deferred = $.Deferred();
                        isLoading(false);
                        isApplied(true);
                        getTotalsAction([], deferred);
                        $.when(deferred).done(function() {
                            paymentService.setPaymentMethods(
                                paymentService.getAvailablePaymentMethods()
                            );
                            var codeList =  response.split(',');
                            $('#discount-code').val(response).change();
                            var newCode = $('#discount-code-fake').val();
                            if (isDelete==true){
                                messageList.addSuccessMessage({'message': messageDelete});
                            }else if ( $.inArray( newCode , codeList  )==-1 ) {
                                messageList.addErrorMessage({'message': messageError});
                            }else{
                                messageList.addSuccessMessage({'message': message});
                            }
                            $('#discount-code-fake').val('');

                        });

                    }
                }
            ).fail(
                function (response) {
                    isLoading(false);
                    errorProcessor.process(response);
                }
            );
        };
    }
);
