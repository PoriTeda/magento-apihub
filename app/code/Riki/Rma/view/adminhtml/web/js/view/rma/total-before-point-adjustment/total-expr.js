define([
    'ko',
    'Riki_Rma/js/view/rma/lib/sub-expr',
    'uiRegistry',
    './goods-amount-expr',
    './return-point-expr'
], function (ko, Component, Registry) {

    return Component.extend({
        initialize: function (params) {
            this.totalBeforePointAdjustmentGoodsAmountExpr = Registry.get('totalBeforePointAdjustmentGoodsAmountExpr');
            this.totalBeforePointAdjustmentGoodsAmountExpr.trigger(this);
            this.shippingFeeExpr = Registry.get('shippingFeeExpr');
            this.shippingFeeExpr.trigger(this);
            this.paymentFeeExpr = Registry.get('paymentFeeExpr');
            this.paymentFeeExpr.trigger(this);
            this.totalBeforePointAdjustmentReturnPointExpr = Registry.get('totalBeforePointAdjustmentReturnPointExpr');
            this.totalBeforePointAdjustmentReturnPointExpr.trigger(this);
            this._super(params);
            this.x = this.totalBeforePointAdjustmentGoodsAmountExpr;
            this.y = this.totalBeforePointAdjustmentReturnPointExpr;

            this.result(this.calc());
        },

        calc: function () {
            return this._super() + Number(this.shippingFeeExpr.result()) + Number(this.paymentFeeExpr.result());
        }
    });
});