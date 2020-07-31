/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
define(
    [
        "jquery",
        'ko',
        'Riki_Subscription/js/model/course'
    ],
    function (
        $,
        ko,
        course
    ) {
        'use strict';
        var frequencyData = window.subscriptionConfig.frequency_options;
        var allowChangeFrequency = window.subscriptionConfig.course_setting;
        var flatFrequencyArray = ko.observableArray([]) ;
        return {
            getFrequencies: function() {
                $.map(frequencyData , function (value , key) {
                    flatFrequencyArray.push(
                        {
                            id: key,
                            text: value
                        }
                    )
                });
                return flatFrequencyArray;
            },
            getIsAllowedChangeFrequency: function(){
                return course.getAllowChangeFrequency();
            }
        };
    }
);
