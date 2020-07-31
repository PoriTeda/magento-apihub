define(['Magento_Ui/js/form/element/select'], function(Select) {
    'use strict';

    return Select.extend({
        initialize: function() {
            this._super();
            this.caption("");
            this.setInitialValue();

            return this;
        }
    });
});