/*jshint browser:true jquery:true*/
/*global alert*/
define(
    [], function () {
        'use strict';

        return function (target) {
            target.postcodeValidation = function () {
                return true;
            };

            return target;
        };
    }
);
