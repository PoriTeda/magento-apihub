define([
    'ko',
    'Riki_Rma/js/view/rma/lib/expr',
    'uiRegistry',
    'Riki_Rma/js/view/rma/total-before-point-adjustment/return-point-expr',
    'Riki_Rma/js/view/rma/total-before-point-adjustment/total-expr'
], function (ko, Component, Registry) {

    return Component.extend({
        initialize: function (params) {
            this.totalBeforePointAdjustmentReturnPointExpr = Registry.get('totalBeforePointAdjustmentReturnPointExpr');
            this.totalBeforePointAdjustmentReturnPointExpr.trigger(this);
            this.totalBeforePointAdjustmentTotalExpr = Registry.get('totalBeforePointAdjustmentTotalExpr');
            this.totalBeforePointAdjustmentTotalExpr.trigger(this);
            this._super(params);
        },

        calc: function () {
            if (this.totalBeforePointAdjustmentReturnPointExpr._codShipmentReject) {
                return 0;
            } else {
                return Math.max(
                    0,
                    this.totalBeforePointAdjustmentReturnPointExpr._notRetractablePoint
                    - this.totalBeforePointAdjustmentTotalExpr.result()
                );
            }
        }
    });
});