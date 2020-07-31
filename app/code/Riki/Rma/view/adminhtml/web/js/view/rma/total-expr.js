define([
    'ko',
    'Riki_Rma/js/view/rma/lib/sum-expr',
    'uiRegistry',
    'Riki_Rma/js/view/rma/total-before-global-adj-expr',
    'Riki_Rma/js/view/rma/return-point-expr',
    'Riki_Rma/js/view/rma/total-before-point-adjustment/return-point-expr'
], function (ko, Component, Registry) {

    return Component.extend({
        initialize: function (params) {
            this.totalBeforeGlobalAdjExpr = Registry.get('totalBeforeGlobalAdjExpr');
            this.totalBeforeGlobalAdjExpr.trigger(this);
            this.returnPointExpr = Registry.get('returnPointExpr');
            this.returnPointExpr.trigger(this);

            this.totalBeforePointAdjustmentReturnPointExpr = Registry.get('totalBeforePointAdjustmentReturnPointExpr');

            this._super(params);

            this.result(this.calc());
        },
        calc: function () {
            return this._super()
                + this.totalBeforeGlobalAdjExpr.result()
                - (this.totalBeforePointAdjustmentReturnPointExpr._isNormalRma ? 0 : this.returnPointExpr._y);
        }
    });
});
