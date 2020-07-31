/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

define([
    'underscore',
    'Riki_Customer/js/lib/dateFormat',
    'uiRegistry',
    'Magento_Ui/js/form/element/date',
    'jquery'
], function (_, dateFormat, registry, Date) {
    'use strict';

    return Date.extend({
        initConfig: function () {
            this._super();
            if(window.current_locale == 'ja_JP'){
                this.dateFormat = 'YYYY/MM/DD';
            }
            return this;
        },
        onUpdate:function () {
            var value = this.value();
            if(window.current_locale == 'ja_JP'){
                var jpDate = dateFormat(value,'yyyy/mm/dd');
                this.value(jpDate);
            }
        }
    });
});
