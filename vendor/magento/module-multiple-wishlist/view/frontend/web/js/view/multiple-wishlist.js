/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

define([
    'uiComponent',
    'Magento_Customer/js/customer-data',
    'underscore'
], function (Component, customerData, _) {
    'use strict';

    /**
     * @param {Object} options
     */
    function initWidget(options) {
        if (options.canCreate || options.wishlists && options.wishlists.length > 0) {
            require([
                'jquery',
                'mage/mage',
                'mage/dropdowns'
            ], function ($) {
                $('body').mage('multipleWishlist', options);
                $('.products.list [data-toggle=dropdown]')
                    .add('.cart.items.data [data-toggle=dropdown]')
                    .add('.product-addto-links [data-toggle=dropdown]')
                    .add('.secondary-addto-links.actions-secondary [data-toggle=dropdown]')
                    .dropdown({
                        events: [{
                            'name': 'mouseleave',
                            'selector': '.item.product',

                            /**
                             * Action.
                             */
                            'action': function () {
                                var triggerElem = $('[data-toggle=dropdown]', this);

                                triggerElem.hasClass('active') && triggerElem.trigger('click.toggleDropdown');
                            }
                        }]
                    });
            });
        }
    }

    return Component.extend({
        /** @inheritdoc */
        initialize: function () {
            this._super();

            this.multiplewishlist = customerData.get('multiplewishlist');
            this.multiplewishlist.subscribe(function (options) {
                initWidget(_.extend(this.multipleWishlistOptions, {
                    'canCreate': options['can_create'],
                    'wishlists': options['short_list']
                }));
            }, this);

            initWidget(_.extend(this.multipleWishlistOptions, {
                'canCreate': this.multiplewishlist()['can_create'],
                'wishlists': this.multiplewishlist()['short_list']
            }));
        }
    });
});
