define([
    'jquery',
    'mage/utils/wrapper',
    '../../click-jacking-cleaner'
], function ($, wrapper, clickJackingCleaner) {
    return function (loader) {
        loader.loadTemplate = wrapper.wrap(loader.loadTemplate, function (orig, source) {
            var isLoaded = $.Deferred();
            orig(source).done(function (tmpl) {
                tmpl = clickJackingCleaner(tmpl);
                isLoaded.resolve(tmpl);
            });
            return isLoaded.promise();
        });
        return loader;
    }
});