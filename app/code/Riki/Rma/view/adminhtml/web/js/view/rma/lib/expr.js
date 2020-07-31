define([
    'ko',
    'uiComponent',
    'uiRegistry',
    'jquery'
], function (ko, Component, Registry, $) {

    return Component.extend({
        triggers: [],

        initialize: function (params) {
            this._super();

            var self = this;
            this.result = ko.observable(params._result || self.calc());
            this.result.subscribe(function (newValue) {
                for (var i=0; i<self.triggers.length; i++) {
                    var trigger = self.triggers[i];
                    if (typeof trigger == 'object'
                        && (typeof trigger.result == 'function')
                        && (typeof trigger.calc == 'function')
                    ) {
                        trigger.result(trigger.calc());
                    }
                }
            });
        },

        trigger: function (component) {
            if (typeof component != 'object') {
                component = Registry.get(component);
            }
            if (typeof component.calc == 'function') {
                this.triggers.push(component);
            }
        },

        calc: function () {
            return 0;
        }
    });
});