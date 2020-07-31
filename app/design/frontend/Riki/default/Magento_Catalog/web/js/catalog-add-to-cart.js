/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
define([
    'jquery',
    'mage/translate',
    'jquery/ui',
    'Riki_Theme/js/cart-data-model',
    'mage/url'
], function($, $t,jUi,cartDataModel, urlBuilder) {
    "use strict";

    $.widget('mage.catalogAddToCart', {

        options: {
            processStart: null,
            processStop: null,
            bindSubmit: true,
            minicartSelector: '[data-block="minicart"]',
            messagesSelector: '[data-placeholder="messages"]',
            productStatusSelector: '.stock.available',
            addToCartButtonSelector: '.action.tocart',
            addToCartButtonDisabledClass: 'disabled',
            addToCartButtonTextWhileAdding: $t('Adding...'),
            addToCartButtonTextAdded: $t('Added'),
            addToCartButtonTextDefault: $t('Add to Cart')

        },

        _create: function() {
            if (this.options.bindSubmit) {
                this._bindSubmit();
            }
            var self = this;
            $(self.element).find(this.options.addToCartButtonSelector + '.btn-mb').click(function () {
                $(self.element).submit();
            })
        },

        _bindSubmit: function() {
            var self = this;
            this.element.on('submit', function(e) {
                e.preventDefault();
                self.submitForm($(this));
            });
        },

        isLoaderEnabled: function() {
            return this.options.processStart && this.options.processStop;
        },

        submitForm: function(form) {
            var self = this;
            if (form.has('input[type="file"]').length && form.find('input[type="file"]').val() !== '') {
                self.element.off('submit');
                form.submit();
            } else {
                self.ajaxSubmit(form);
            }
        },

        ajaxSubmit: function(form) {
            var self = this;
            $(self.options.minicartSelector).trigger('contentLoading');
            self.disableAddToCartButton(form);
            if(window.isSpotPage){
                window.location.assign(urlBuilder.build('checkout/#single_order_confirm'));
                self.enableAddToCartButton(form);
            }
            $.ajax({
                url: form.attr('action'),
                data: form.serialize(),
                type: 'post',
                dataType: 'json',
                beforeSend: function() {
                    if (self.isLoaderEnabled()) {
                        $('body').trigger(self.options.processStart);
                    }
                },
                success: function(res) {
                    cartDataModel.resetCart();
                    if (self.isLoaderEnabled()) {
                        $('body').trigger(self.options.processStop);
                    }

                    if (res.backUrl) {
                        window.location = res.backUrl;
                        return;
                    }
                    if (res.messages) {
                        $(self.options.messagesSelector).html(res.messages);
                    }
                    if (res.minicart) {
                        $(self.options.minicartSelector).replaceWith(res.minicart);
                        $(self.options.minicartSelector).trigger('contentUpdated');
                    }
                    if (res.product && res.product.statusText) {
                        $(self.options.productStatusSelector)
                            .removeClass('available')
                            .addClass('unavailable')
                            .find('span')
                            .html(res.product.statusText);
                    }
                    self.enableAddToCartButton(form);
                }
            });
        },

        disableAddToCartButton: function(form) {
            var addToCartButton = $(form).find(this.options.addToCartButtonSelector);
            addToCartButton.addClass(this.options.addToCartButtonDisabledClass);
            addToCartButton.attr('title', $t('Adding...'));
            addToCartButton.find('span').text($t('Adding...'));
        },

        enableAddToCartButton: function(form) {
            var self = this,
                addToCartButton = $(form).find(this.options.addToCartButtonSelector),
                addToCartButtonSP = $(form).find(this.options.addToCartButtonSelector + '.btn-mb');

            addToCartButton.find('span').text($t('Added'));
            addToCartButtonSP.find('span').text($t('Added'));
            addToCartButton.attr('title', $t('Added'));

            setTimeout(function() {
                addToCartButton.removeClass(self.options.addToCartButtonDisabledClass);
                var default_title = addToCartButton.attr('default-title');
                var default_mobile_title = addToCartButtonSP.attr('default-title');
                addToCartButton.find('span').text(default_title);
                addToCartButtonSP.find('span').text(default_mobile_title);
                addToCartButton.attr('title', default_title);
            }, 1000);
        }
    });

    return $.mage.catalogAddToCart;
});