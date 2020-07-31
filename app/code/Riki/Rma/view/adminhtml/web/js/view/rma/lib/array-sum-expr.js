define([
    'ko',
    './expr'
], function (ko, Component) {

    return Component.extend({
        list: [],

        push: function(value) {
            if (typeof value == 'object'
                && typeof value.result == 'function'
                && typeof value.trigger == 'function'
            ) {
                value.trigger(this);
            }
            this.list.push(value);
        },

        calc: function () {
            var sum = this.list.reduce(function (x, y) {
                var a = x, b = y;
                if (typeof x == 'object'
                    && typeof x.result == 'function'
                ) {
                    a = x.result();
                }
                if (typeof y == 'object'
                    && typeof y.result == 'function'
                ) {
                    b = y.result();
                }
                return a + b;
            }, 0);
            return Number(sum);
        }
    });
});