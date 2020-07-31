define([
    'jquery',
    'ko',
    'uiComponent'
], function ($, ko, Component) {
    return Component.extend({
        listPaymentDate: '',

        initialize: function () {
            this._super();
            var shipmentNumber = $('#rma_shipment_number').val();
            if (shipmentNumber) {
                this.getPaymentDate(shipmentNumber);
            }

            return this;
        },

        checkKeyFocusOut: function(data, event) {
            if (event !== undefined) {
                event.preventDefault();
                var shipmentNumber = event.target.value;
                if (shipmentNumber) {
                    this.getPaymentDate(shipmentNumber);
                }
            }
            return true;
        },

        getPaymentDate: function (shipmentNumber) {
            $('body').trigger('processStart');
            if (this.listPaymentDate &&
                this.listPaymentDate[shipmentNumber]
            ) {
                $('#payment_date').html(this.listPaymentDate[shipmentNumber]);
            } else {
                $('#payment_date').html('');
            }
            $('body').trigger('processStop');
        },
    });
});