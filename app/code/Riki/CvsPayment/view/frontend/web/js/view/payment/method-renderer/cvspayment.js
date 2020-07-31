define(
    [
        'jquery',
        'ko',
        'Magento_Checkout/js/view/payment/default',
        'Magento_Customer/js/model/customer',
        'uiRegistry'
    ],
    function (
        $,
        ko,
        Component,
        customer,
        uiRegistry
    ) {
        'use strict';

        return Component.extend(
            {
                showCvsMessage: ko.observable(false),
                defaults: {
                    template: 'Riki_CvsPayment/payment/cvspayment'
                },

                initialize: function () {
                    this._super();
                    var self = this;
                    if($('body').hasClass('multicheckout-index-index')) {
                        uiRegistry.get(
                            'checkout.steps.multiple-checkout-order-confirmation',
                            function (multiConfirm) {
                                self.showCvsMessage(false);
                                outerLoop: for(var i=0; i < multiConfirm.deliveryTimes().length; i++) {
                                    for(var j=0; j < multiConfirm.deliveryTimes()[i].delivery_date.length; j++) {
                                        if(multiConfirm.deliveryTimes()[i].delivery_date[j].deliveryDate != null) {
                                            self.showCvsMessage(true);
                                            break outerLoop;
                                        }
                                    }
                                }
                            }
                        );
                    } else {
                        uiRegistry.get(
                            'checkout.steps.confirm-info-step',
                            function (singleConfirm) {
                                self.showCvsMessage(false);
                                for(var i=0; i < singleConfirm.deliveryTimes().length; i++) {
                                    if(singleConfirm.deliveryTimes()[i].deliveryDate != '') {
                                        self.showCvsMessage(true);
                                        break;
                                    }
                                }
                            }
                        );
                    }
                    return this;
                },

                /**
                 * Returns send check to info
                 */
                getMailingAddress: function() {
                    return window.checkoutConfig.payment.cvspayment.mailingAddress;
                },

                /**
                 * Returns payable to info
                 */
                getPayableTo: function() {
                    return window.checkoutConfig.payment.cvspayment.payableTo;
                },
                isAvailable: function () {
                    return true;
                },
                getInstructions: function() {
                    return window.checkoutConfig.payment.instructions[this.item.method];
                }
            }
        );
    }
);