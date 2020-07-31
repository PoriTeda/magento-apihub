define([
    'ko',
    './sum-expr'
], function (ko, Component) {

    return Component.extend({
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
            return Number(a) - Number(b);
        }
    });
});