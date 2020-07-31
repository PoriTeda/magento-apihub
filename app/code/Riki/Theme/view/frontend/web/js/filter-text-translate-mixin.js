(function (factory) {
    if (typeof define === 'function' && define.amd) {
        define([
            "jquery",
            'mage/utils/wrapper',
            "mage/mage"
        ], factory);
    } else {
        factory(jQuery);
    }
}(function ($, wrapper) {
    return function (mageTranslate) {
        var escaped = wrapper.wrap(mageTranslate, function (originalFunction, text) {
            var result = originalFunction(text);
            return $('<div/>').html(result).text();
        });
        $.mage.__ = escaped;
        return escaped;
    }
}));