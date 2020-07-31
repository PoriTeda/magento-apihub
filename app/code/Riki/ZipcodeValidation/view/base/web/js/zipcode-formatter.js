define(
    [
    'jquery',
    './zipcode/value-converter',
    'jquery/ui'
    ], function ($, valueConverter) {
        'use strict';

        $.widget(
            'mage.zipcodeFormatter', {
                options: {},
                _create: function () {
                    this.element.on(
                        'keydown', $.proxy(
                            function () {
                                valueConverter(this.element, this.element.val);
                            }, this
                        )
                    );
                }
            }
        );

        return $.mage.zipcodeFormatter;
    }
);