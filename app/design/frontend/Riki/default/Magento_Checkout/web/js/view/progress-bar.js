/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
/*jshint browser:true jquery:true*/
/*global alert*/
define(
    [
        'jquery',
        "underscore",
        'ko',
        'uiComponent',
        'Magento_Checkout/js/model/step-navigator',
        'mage/url',
        'mage/translate'
    ],
    function (
        $,
        _,
        ko,
        Component,
        stepNavigator,
        urlBuilder,
        $t
    ) {
        var steps = stepNavigator.steps;

        return Component.extend({
            defaults: {
                template: 'Magento_Checkout/progress-bar',
                visible: true
            },
            steps: steps,
            redirectCartPage : '',

            initialize: function() {
                this._super();
                window.addEventListener('hashchange', _.bind(stepNavigator.handleHash, stepNavigator));
                stepNavigator.handleHash();
                this.redirectCartPage = "location.href='" + urlBuilder.build('checkout/cart') + "'";
            },

            sortItems: function(itemOne, itemTwo) {
                return stepNavigator.sortItems(itemOne, itemTwo);
            },

            navigateTo: function (step) {
                stepNavigator.navigateTo(step.code);
                var stepIndex = stepNavigator.getActiveItemIndex();
                if (stepIndex == 0) {
                    $('#checkout .page-title-wrapper .page-title > span').text($t('Order Confirmation'));
                }
            },

            isProcessed: function(item) {
                return stepNavigator.isProcessed(item.code);
            }
        });
    }
);
