define([
    "jquery",
    "jquery/ui",
    'Magento_Catalog/js/catalog-add-to-cart'
], function($) {
    'use strict';

    $.widget('mage.rikiPreorder', {
       options: {
           aviabilityElement: null,
           addToCartButton: null,
           preOrderNote: '',
           addToCartLabel: ''
       },

        _original: {
            aviabilityText: '',
            addToCartLabel: ''
        },

        _enabled: false,

        _create: function() {
            this._saveOriginal();
        },
        _saveOriginal: function(){
            this._original.aviabilityText = this.options.aviabilityElement.html();
            this._original.addToCartLabel = this.options.addToCartButton.html();
        },

        _changeLabels: function() {
            $.mage.catalogAddToCart.prototype.options.addToCartButtonTextDefault = this.options.addToCartLabel;
            //this.options.aviabilityElement.html(this.options.preOrderNote);
            //this.options.addToCartButton.html(this.options.addToCartLabel);
        },

        enable: function() {
            if(this._enabled) {
                return;
            }
            this._enabled = true;
            this._changeLabels();
        },

        disable: function() {
            if(!this._enabled) {
                return;
            }
            this._enabled = false;
            this.options.aviabilityElement.html(this._original.aviabilityText);
            this.options.addToCartButton.html(this._original.addToCartLabel);
            $.mage.catalogAddToCart.prototype.options.addToCartButtonTextDefault = this._original.addToCartLabel;
        }
    });

    return $.mage.rikiPreorder;
});
