define([
    'jquery'
], function ($) {
    'use strict';

    return function (widget) {

        $.widget('mage.categoryTree', widget, {
            _selectNode: function(event, data) {
                var node = data.rslt.obj.data();
                if (!node.disabled) {
                    var nodeId = parseInt(node.id);
                    window.location = window.location + '/' + nodeId;
                } else {
                    event.preventDefault();
                }
            }
        });

        return $.mage.categoryTree;
    }
});