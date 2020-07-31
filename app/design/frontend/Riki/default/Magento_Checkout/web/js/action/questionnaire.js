/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
define(
    [
        'Magento_Checkout/js/model/quote',
        'Magento_Checkout/js/model/url-builder',
        'mage/storage',
        'mage/url',
        'Magento_Checkout/js/model/error-processor',
        'Magento_Checkout/js/model/full-screen-loader'
    ],
    function (quote, urlBuilder, storage, url, errorProcessor, customer, fullScreenLoader) {
        'use strict';

        return function (customerId, quoteEntityId, answersQuestionnaire, messageContainer) {
            var serviceUrl,
                payload;

            serviceUrl = urlBuilder.createUrl('/questionnaire/saveAnswers/:customerId/:quoteId', {
                customerId: customerId,
                quoteId: quoteEntityId
            });
            payload = {
                submittedData: answersQuestionnaire
            };

            return storage.post(
                serviceUrl, JSON.stringify(payload), false
            ).done(
                function () {
                }
            ).fail(
                function (response) {
                    errorProcessor.process(response, messageContainer);
                }
            );
        };
    }
);
