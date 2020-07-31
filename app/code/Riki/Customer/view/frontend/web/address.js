/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
/*jshint browser:true, jquery:true*/
/*global confirm:true*/
define([
    "jquery",
    'Magento_Ui/js/modal/confirm',
    'Magento_Customer/js/section-config',
    'Magento_Customer/js/customer-data',
    "jquery/ui",
    "mage/translate"
], function($, confirm, sectionConfig, customerData){
    "use strict";

    $.widget('mage.address', {
        /**
         * Options common to all instances of this widget.
         * @type {Object}
         */
        options: {
            deleteConfirmMessage: $.mage.__('Are you sure you want to delete this address?')
        },

        /**
         * Bind event handlers for adding and deleting addresses.
         * @private
         */
        _create: function() {
            var options         = this.options,
                addAddress      = options.addAddress,
                deleteAddress   = options.deleteAddress;

            if( addAddress ){
                $(document).on('click', addAddress, this._addAddress.bind(this));
            }

            if( deleteAddress ){
                $(document).on('click', deleteAddress, this._deleteAddress.bind(this));
            }
        },

        /**
         * Add a new address.
         * @private
         */
        _addAddress: function() {
            window.location = this.options.addAddressLocation;
        },

        /**
         * Delete the address whose id is specified in a data attribute after confirmation from the user.
         * @private
         * @param {Event}
         * @return {Boolean}
         */
        _deleteAddress: function(e) {
            var self = this;

            confirm({
                content: this.options.deleteConfirmMessage,
                actions: {
                    confirm: function() {
                        var sections;

                        sections = sectionConfig.getAffectedSections(self.options.deleteUrlPrefix);
                        if (sections) {
                            customerData.invalidate(sections);
                        }

                        if (typeof $(e.target).parent().data('address') !== 'undefined') {
                            window.location = self.options.deleteUrlPrefix + $(e.target).parent().data('address');
                        }
                        else {
                            window.location = self.options.deleteUrlPrefix + $(e.target).data('address');
                        }
                    }
                }
            });

            return false;
        }
    });
    
    return $.mage.address;
});