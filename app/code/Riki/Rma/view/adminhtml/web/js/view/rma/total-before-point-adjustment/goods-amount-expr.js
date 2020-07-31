define([
    'ko',
    'Riki_Rma/js/view/rma/lib/array-sum-expr',
    'jquery',
    'uiRegistry'
], function (ko, Component, $, Registry) {

    return Component.extend({
        initialize: function (params) {
            var self = this;
            $('[data-array-sum-expr="'+ params.name +'"]').each(function() {
                self.push(Registry.get($(this).prop('id')));
            });
            this._super(params);
        }
    });
});