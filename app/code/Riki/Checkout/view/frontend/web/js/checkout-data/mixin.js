define([
    'underscore',
    'mage/utils/wrapper'
], function (_, wrapper) {
    'use strict';

    /**
     * Fix bug that new shipping/billing form is validated immediately when opening by replacing null/undefined value
     * with empty string to avoid data inconsistent between checkout data and checkout provider.
     *
     * @param data
     */
    var correctData = function (data) {
        return _.mapObject(data, function (value) {
            if (_.isObject(value)) {
                return correctData(value);
            } else if (_.isNull(value) || _.isUndefined(value)) {
                return '';
            } else {
                return value;
            }
        });
    };

    return function (checkoutData) {
        _.extend(checkoutData, {
            getShippingAddressFromData: wrapper.wrap(checkoutData.getShippingAddressFromData, function (originGetShippingAddressFormData) {
                var data = originGetShippingAddressFormData();
                return _.isObject(data) ? correctData(data) : data;
            })
        });

        return checkoutData;
    };
});