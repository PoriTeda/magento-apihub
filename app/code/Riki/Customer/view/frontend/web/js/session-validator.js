define([
    'jquery',
    'underscore',
    'Magento_Customer/js/customer-data'
], function ($, _, customerData) {
    var options;

    var sessionValidator = {
        init: function () {
            var customer = customerData.get('customer');

            $.ajax({
                type: 'GET',
                global: false,
                url: options.sessionValidationUrl,
                dataType: 'json',
                data: {'current_url': encodeURI(options.currentUrl), 'has_data': (_.isString(customer().firstname)  ? 1 : 0)}
            }).success(function (response) {
                if (!_.isUndefined(response.status) && !response.status) {
                    if (response.cleanStorage) {
                        customer({});
                    }

                    if (response.redirectUrl) {
                        window.location.href = response.redirectUrl;
                    }
                }
            });
        },
        'Riki_Customer/js/session-validator': function (settings) {
            options = settings;
            sessionValidator.init();
        }
    };

    return sessionValidator;
});