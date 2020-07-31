define([
    'jquery',
    'ko',
    'underscore',
    'Magento_Ui/js/form/element/multiselect',
    'uiRegistry'
], function ($, ko, _, Multiselect, uiRegistry) {
    'use strict';

    return Multiselect.extend({

        initialize: function() {
            this._super();

            uiRegistry.async(this.parentName + '.apply_subscription')(function (courseSelection) {
                this.handleCourseChange(courseSelection.value());
            }.bind(this));

        },

        initObservable: function () {
            this._super();
            var modifiedOptions = this.options();
            _.map(modifiedOptions, function (node) {
                if (_.isUndefined(node.enabled)) {
                    node.disabled = ko.observable(true);
                }
            });

            this.setOptions(modifiedOptions);

            return this;
        },

        setOptionDisable: function (option, item) {
            ko.applyBindingsToNode(option, {disable: item.disabled}, item);
        },

        /**
         * Disable required validation, when 'use config option' checked
         */
        handleCourseChange: function (courseIds) {
            var courseNumber = courseIds.length,
                listFrequenciesInCourse = [],
                jsonData;

            if (courseNumber > 0) {

                jsonData = window.courseFrequencyList;

                for (var i = 0; i < courseNumber; i++) {
                    let id = courseIds[i];
                    if (!jsonData[id]) {
                        return false;
                    }

                    listFrequenciesInCourse = $.unique(listFrequenciesInCourse.concat(jsonData[id]));
                }

                if (listFrequenciesInCourse.length > 0) {
                    var options = this.options();
                    _.each(options, function (option) {
                        option.disabled(($.inArray(option.value, listFrequenciesInCourse) == -1));
                    });

                    this.setOptions(options);
                }
            }
        }
    });
});
