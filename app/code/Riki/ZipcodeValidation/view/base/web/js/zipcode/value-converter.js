define(
    [
    'jquery',
    'mageUtils'
    ], function ($, utils) {
        return function (element, valueFn) {
            var value = valueFn.call(element),
               len = !utils.isEmpty(value) ? value.length : 0;

            if ($.inArray('-', value) == -1 && (len == 4 || len == 7)) {
                valueFn.call(element, value.slice(0, 3) + '-' + value.slice(3));
            }
        }
    }
);