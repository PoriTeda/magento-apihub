define([
    'ko',
    './expr'
], function (ko, Component) {

    return Component.extend({
        initialize: function (params) {

            var self = this;

            this.x = ko.pureComputed({
                read: function () {
                    return params._x || 0;
                },
                write: function (value) {
                    self._x = value;
                    self.result(self.calc());
                }
            });
            this.y = ko.pureComputed({
                read: function () {
                    return params._y || 0;
                },
                write: function (value) {
                    self._y = value;
                    self.result(self.calc());
                }
            });
            this._x = this.x();
            this._y = this.y();

            this._super(params);
        },

        calc: function () {
            var a = this._x, b = this._y;
            if (typeof this.x == 'object'
                && typeof this.x.result == 'function'
            ) {
                a = this.x.result();
            }
            if (typeof this.y == 'object'
                && typeof this.y.result == 'function'
            ) {
                b = this.y.result();
            }
            return Number(a) + Number(b);
        }
    });
});