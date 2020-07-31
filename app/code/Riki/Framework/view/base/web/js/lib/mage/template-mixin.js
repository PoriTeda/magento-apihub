define([
    'jquery',
    'mage/utils/wrapper',
    '../click-jacking-cleaner'
], function ($, wrapper, clickJackingCleaner) {
    'use strict';
    return function (template) {
        template = wrapper.wrap(template, function (orig, tmpl, data) {
            tmpl = clickJackingCleaner(tmpl);
            return orig(tmpl, data);
        });
        return template;
    };
});