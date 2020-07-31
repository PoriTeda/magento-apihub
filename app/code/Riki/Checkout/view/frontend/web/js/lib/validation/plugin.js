define(['jquery'], function ($) {
    'use strict';

    return function (validationRules) {
        var defaultRules = validationRules;

        /* custom logic goes here for ignore undefined case */
        defaultRules.min_text_length = {
            handler : function (value, params) {
                return _.isUndefined(value) || value.length == 0 || value.length >= +params;
            },
            message : $.mage.__('Please enter more or equal than {0} symbols.')
        };
        defaultRules.max_text_length = {
            handler : function (value, params) {
                return !_.isUndefined(value) && value.length <= +params;
            },
            message : $.mage.__('Please enter less or equal than {0} symbols.')
        };
        return defaultRules;
    };
});