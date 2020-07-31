(function (factory) {
    if (typeof define === 'function' && define.amd) {
        define([
            "jquery"
        ], factory);
    } else {
        factory(jQuery);
    }
}(function ($) {
    return function (mageTranslate) {
        if (typeof window.translateData != 'undefined') {
            $.mage.translate.add(window.translateData);
        }
        return mageTranslate;
    }
}));