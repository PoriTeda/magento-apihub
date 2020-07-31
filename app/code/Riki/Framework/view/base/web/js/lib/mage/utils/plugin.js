define([
    'mage/utils/wrapper',
    'underscore'
], function (wrapper, _) {
    return function (target) {
        var map = {
            'yyyy': 'YYYY',
            'yy': 'YY'
        };

        target.normalizeDate = wrapper.wrap(target.normalizeDate, function (originalNormalizeDate, mageFormat) {
            _.each(map, function (moment, mage) {
                mageFormat = mageFormat.replace(mage, moment);
            });
            return originalNormalizeDate(mageFormat);
        });

        return target;
    }
});
