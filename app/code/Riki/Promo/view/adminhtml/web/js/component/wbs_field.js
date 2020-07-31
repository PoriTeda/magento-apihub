define([
    'mageUtils',
    'Magento_Ui/js/form/element/abstract'
], function (utils, Abstract) {
    'use strict';

    return Abstract.extend({

        /**
         *
         * @param {(String|Object)} rule
         * @param {(Object|Boolean)} [options]
         * @returns {Abstract} Chainable.
         */
        setValidation: function (rule, options) {
            var rules = utils.copy(this.validation),
                changed;

            if (_.isObject(rule)) {
                _.extend(this.validation, rule);
            } else {
                this.validation[rule] = options;
            }

            changed = utils.compare(rules, this.validation).equal;

            if (changed) {
                this.required(!!rules['required-entry']);
            }

            return this;
        },

        setRequired: function (value) {
            this.setValidation('required-entry', !!value);

            return this;
        }
    });
});
