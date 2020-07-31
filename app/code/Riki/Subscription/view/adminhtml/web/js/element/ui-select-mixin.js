define(function () {
    'use strict';

    var mixin = {
        isHovered: function (index, elem) {
            return typeof this["hoverElIndex"] === "function" ? this.hoverElIndex() === index : false;
        }
    };

    return function (target) {
        return target.extend(mixin);
    };
});
