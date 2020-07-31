define([
    'ko',
    'Riki_Rma/js/view/rma/lib/expr',
    'uiRegistry',
    './goods-amount-expr'
], function (ko, Component, Registry) {

    return Component.extend({
        initialize: function (params) {
            this.totalBeforePointAdjustmentGoodsAmountExpr = Registry.get('totalBeforePointAdjustmentGoodsAmountExpr');
            this.totalBeforePointAdjustmentGoodsAmountExpr.trigger(this);
            this.shippingFeeExpr = Registry.get('shippingFeeExpr');
            this.shippingFeeExpr.trigger(this);
            this.paymentFeeExpr = Registry.get('paymentFeeExpr');
            this.paymentFeeExpr.trigger(this);
            this._codShipmentReject = params._codShipmentReject || 0;
            this._shipmentShoppingPoint = params._shipmentShoppingPoint || 0;
            this._returnablePointAmount = params._returnablePointAmount || 0;
            this._notRetractablePoint = params._notRetractablePoint || 0;
            this._isNormalRma = params._isNormalRma || false;
            this._super(params);
        },

        calc: function () {
            var total = this.totalBeforePointAdjustmentGoodsAmountExpr.result() + this.shippingFeeExpr.result() + this.paymentFeeExpr.result();

            if(!this._isNormalRma){
                return 0;
            } else if (this._codShipmentReject) {
                return Math.min(this._shipmentShoppingPoint, total, this._returnablePointAmount);
            } else {
                return Math.min(this._returnablePointAmount, total);
            }
        }
    });
});
