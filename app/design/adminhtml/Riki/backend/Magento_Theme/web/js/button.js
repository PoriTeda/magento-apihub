define([
    'jquery',
    'jquery/ui',
    'mage/backend/validation'
], function($){
    'use strict';


    $.widget('mage.button', $.ui.button, {
        options: {
            eventData: {},
            waitTillResolved: true
        },

        /**
         * Button creation.
         * @protected
         */
        _create: function () {
            if (this.options.event) {
                this.options.target = this.options.target || this.element;
                this._bind();
            }

            this._super();
        },

        /**
         * Bind handler on button click.
         * @protected
         */
        _bind: function () {
            this.element
                .off('click.button')
                .on('click.button', $.proxy(this._click, this));
        },

        /**
         * Button click handler.
         * @protected
         */
        _click: function () {
            var options = this.options;

            var target = $(options.target);
            if(target.is('form')) {
                this.disable();
                if(target.mage('validation').valid()) {
                    $('body').trigger('processStart');
                    $(options.target).trigger(options.event, [options.eventData]);
                }
                this.enable();
            } else {
                $(options.target).trigger(options.event, [options.eventData]);
            }
        }
    });


    return $.mage.button;
});