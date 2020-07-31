/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
/*jshint browser:true */
/*global console:true*/
define([
    'jquery',
    'mage/template',
    'jquery/ui',
    'mage/translate'
], function ($, mageTemplate) {
    'use strict';

    $.widget('mage.loader', {
        loaderStarted: 0,
        loadingTime: 6000,
        loadingTextElement: '.loading-mask .loader > p',
        loadingText: '',
        options: {
            icon: '',
            texts: {
                loaderText: $.mage.__('Please wait...'),
                imgAlt: $.mage.__('Loading...')
            },
            template:
                '<div class="loading-mask" data-role="loader">' +
                    '<div class="loader">' +
                        '<i class="fa fa-spinner fa-spin"></i>' +
                    '</div>' +
                '</div>'

        },

        /**
         * Loader creation
         * @protected
         */
        _create: function () {
            this._bind();
        },

        /**
         * Bind on ajax events
         * @protected
         */
        _bind: function () {
            this._on({
                'processStop': 'hide',
                'processStart': 'show',
                'processStopCheckout': 'hideOnCheckout',
                'processStartCheckout': 'showOnCheckout',
                'show.loader': 'show',
                'hide.loader': 'hide',
                'contentUpdated.loader': '_contentUpdated'
            });
        },

        /**
         * Verify loader present after content updated
         *
         * This will be cleaned up by the task MAGETWO-11070
         *
         * @param {EventObject} e
         * @private
         */
        _contentUpdated: function (e) {
            this.show(e);
        },

        /**
         * Show loader
         */
        show: function (e, ctx) {
            this._render();
            this.loaderStarted++;
            this.spinner.show();

            if (ctx) {
                this.spinner
                    .css({
                        width: ctx.outerWidth(),
                        height: ctx.outerHeight(),
                        position: 'absolute'
                    })
                    .position({
                        my: 'top left',
                        at: 'top left',
                        of: ctx
                    });
            }

            return false;
        },

        /**
         * Hide loader
         */
        hide: function () {
            if (this.loaderStarted > 0) {
                this.loaderStarted--;

                if (this.loaderStarted === 0) {
                    this.spinner.hide();
                }
            }

            return false;
        },

        /**
         * Show loader on Checkout
         */
        showOnCheckout: function (e, ctx) {
            var self = this;
            this._render();
            this.loaderStarted++;
            this.spinner.show();

            if (ctx) {
                this.spinner
                    .css({
                        width: ctx.outerWidth(),
                        height: ctx.outerHeight(),
                        position: 'absolute'
                    })
                    .position({
                        my: 'top left',
                        at: 'top left',
                        of: ctx
                    });
            }

            self.Timer = setTimeout(function() {
                if($('#loadingText').length) {
                    self.loadingText = $('#loadingText').html();
                }
                $(self.loadingTextElement).html(self.loadingText);
                $(self.loadingTextElement).fadeIn();
            }, self.loadingTime);

            return false;
        },

        /**
         * Hide loader on Checkout
         */
        hideOnCheckout: function () {
            if (this.loaderStarted > 0) {
                this.loaderStarted--;

                if (this.loaderStarted === 0) {
                    if(typeof this.Timer != 'undefined') {
                        clearTimeout(this.Timer);
                    }

                    $(this.loadingTextElement).hide();
                    $(this.loadingTextElement).html(this.options.texts.loaderText);
                    this.spinner.hide();
                }
            }
            
            return false;
        },

        /**
         * Render loader
         * @protected
         */
        _render: function () {
            var html;

            if (!this.spinnerTemplate) {
                this.spinnerTemplate = mageTemplate(this.options.template);

                html = $(this.spinnerTemplate({
                    data: this.options
                }));

                html.prependTo(this.element);

                this.spinner = html;
            }
        },

        /**
         * Destroy loader
         */
        _destroy: function () {
            this.spinner.remove();
        }
    });

    /**
     * This widget takes care of registering the needed loader listeners on the body
     */
    $.widget('mage.loaderAjax', {
        options: {
            defaultContainer: '[data-container=body]',
            loadingClass: 'ajax-loading'
        },

        _create: function () {
            this._bind();
            // There should only be one instance of this widget, and it should be attached
            // to the body only. Having it on the page twice will trigger multiple processStarts.
            if (window.console && !this.element.is(this.options.defaultContainer) && $.mage.isDevMode(undefined)) {
                console.warn('This widget is intended to be attached to the body, not below.');
            }
        },

        _bind: function () {
            $(document).on({
                'ajaxSend': this._onAjaxSend.bind(this),
                'ajaxComplete': this._onAjaxComplete.bind(this)
            });
        },

        _getJqueryObj: function (loaderContext) {
            var ctx;
            // Check to see if context is jQuery object or not.
            if (loaderContext) {
                if (loaderContext.jquery) {
                    ctx = loaderContext;
                } else {
                    ctx = $(loaderContext);
                }
            } else {
                ctx = $('[data-container="body"]');
            }

            return ctx;
        },

        _onAjaxSend: function (e, jqxhr, settings) {
            $(this.options.defaultContainer)
                .addClass(this.options.loadingClass)
                .attr({
                    'aria-busy': true
                })

            if (settings && settings.showLoader) {
                var ctx = this._getJqueryObj(settings.loaderContext);
                ctx.trigger('processStart');

                // Check to make sure the loader is there on the page if not report it on the console.
                // NOTE that this check should be removed before going live. It is just an aid to help
                // in finding the uses of the loader that maybe broken.
                if (window.console && !ctx.parents('[data-role="loader"]').length) {
                    console.warn('Expected to start loader but did not find one in the dom');
                }
            }
        },

        _onAjaxComplete: function (e, jqxhr, settings) {
            $(this.options.defaultContainer)
                .removeClass(this.options.loadingClass)
                .attr('aria-busy', false);

            if (settings && settings.showLoader) {
                this._getJqueryObj(settings.loaderContext).trigger('processStop');
            }
        }

    });

    return {
        loader: $.mage.loader,
        loaderAjax: $.mage.loaderAjax
    };
});
