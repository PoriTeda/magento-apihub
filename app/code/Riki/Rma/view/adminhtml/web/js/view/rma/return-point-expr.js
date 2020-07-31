define([
    'ko',
    'Riki_Rma/js/view/rma/lib/sum-expr',
    'uiRegistry',
    'Riki_Rma/js/view/rma/remain-not-retractable-point-expr',
    'Riki_Rma/js/view/rma/total-before-point-adjustment/return-point-expr'
], function (ko, Component, Registry) {

    return Component.extend({
        initialize: function (params) {
            this.z = ko.observable(0);
            this.remainNotRetractablePointExpr = Registry.get('remainNotRetractablePointExpr');
            this.remainNotRetractablePointExpr.trigger(this);
            this.totalBeforePointAdjustmentReturnPointExpr = Registry.get('totalBeforePointAdjustmentReturnPointExpr');
            this.totalBeforePointAdjustmentReturnPointExpr.trigger(this);
            this._super(params);
        },

        calc: function () {
            this.z(this.totalBeforePointAdjustmentReturnPointExpr.result() - this.remainNotRetractablePointExpr.result());
            return Number(this.z()) + Number(this._y);
        }
    });
});