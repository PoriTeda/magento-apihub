define(
    [
        'ko',
        'jquery',
        'underscore',
        'uiComponent',
        'Magento_Checkout/js/model/quote',
        'Magento_Catalog/js/price-utils',
        'mage/url',
        'mage/translate',
        'mage/storage',
        'uiRegistry',
    ],
    function (
        ko,
        $,
        _,
        Component,
        quote,
        priceUtils,
        urlBuilder,
        $t,
        storage,
        registry
    ) {
        'use strict';
        return Component.extend({
            defaults: {
                template: 'Riki_Checkout/single/cart-simulation'
            },
            isSubscription: window.checkoutConfig.quoteData.riki_course_id,
            cartSimulation: ko.observableArray(),
            grandTotalValue: ko.observable(0),
            grandTotalSimulation: ko.observable(""),
            simulationTimes: ko.observable(0),
            simulationLoading: ko.observable(false),
            courseName : ko.observable(),
            frequency : ko.observable(),
            courseMinimumOrderTimes: ko.observable(),
            orderTotalMinimumAmountString: '',
            orderTotalMinimumAmountArr : [],

            // always true although virtual product
            isVisible: ko.observable(false),

            initialize: function () {
                this._super();

                var self = this;

                if (self.isSubscription != 'undefined' && ! $.isEmptyObject(self.isSubscription)) {
                    self.courseName(window.checkoutConfig.courseName);
                    self.frequency(window.checkoutConfig.frequency);
                    self.courseMinimumOrderTimes(window.checkoutConfig.courseMinimumOrderTimes);
                    if (window.checkoutConfig.orderTotalMinimumAmount != '') {
                        self.orderTotalMinimumAmountArr = JSON.parse(window.checkoutConfig.orderTotalMinimumAmount);
                        self.orderTotalMinimumAmountString = priceUtils.formatPrice(window.checkoutConfig.orderTotalMinimumAmount,window.checkoutConfig.priceFormat);
                    }

                    var serviceUrl = urlBuilder.build('rest/ec/V1/rikicarts/mine/cart-total-simulation');
                    self.simulationLoading(true);
                    self.cartSimulation([]);
                    storage.get(
                        serviceUrl
                    ).done(
                        function (response) {
                            if(typeof response.message != 'undefined') {
                                response = [];
                            }
                            self.cartSimulation(response);
                            self.simulationLoading(false);
                        }
                    ).fail(
                        function () {
                            self.cartSimulation([]);
                            self.simulationLoading(false);
                        }
                    );
                }

                const calculateSimulateGrandTotal = function (cartSimulationObj) {
                    var grandTotalSimulationTmp = 0;
                    cartSimulationObj.forEach(function(e) {
                        grandTotalSimulationTmp += e.grand_total;
                    });

                    registry.get('checkout.sidebar', function (sidebar) {
                        self.grandTotalValue(sidebar.getPureValueGrandTotal());
                        self.grandTotalSimulation(priceUtils.formatPrice(
                            grandTotalSimulationTmp +  self.grandTotalValue()
                        ));
                    });

                    if(cartSimulationObj.length) {
                        self.simulationTimes(_.last(cartSimulationObj).order_times);
                    }
                };


                self.cartSimulation.subscribe(function(cartSimulationObj) {
                    calculateSimulateGrandTotal(cartSimulationObj);
                });

                quote.totals.subscribe(function () {
                    calculateSimulateGrandTotal(self.cartSimulation());
                });

                return this;
            },
            formatPrice: function(amount){
                return priceUtils.formatPrice(
                    amount, window.checkoutConfig.priceFormat
                );
            },
            checkOrderTotalMinimumAmountArr: function(){
                if (window.checkoutConfig.orderTotalMinimumAmount == '') {
                    return false;
                }
                return Array.isArray(JSON.parse(window.checkoutConfig.orderTotalMinimumAmount));
            },
        });
    }
);