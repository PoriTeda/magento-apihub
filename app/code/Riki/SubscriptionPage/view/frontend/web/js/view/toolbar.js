define([
    'jquery',
    'ko',
    'mage/url',
    'uiClass',
    'Magento_Ui/js/modal/modal',
    'mage/translate',
    "uiRegistry",
    "domReady!"
], function (
    $,
    ko,
    urlBuilder,
    Component,
    modal,
    $t
) {
    var activeGridMode;
    return Component.extend({

        initialize: function () {
            this._super();
        },

        bindingToolbar: function (mode,form) {
            activeGridMode = mode;
            const formElement = form ? form : $('#form-validate');

            const modeListBt = formElement.find('.modes-list');
            const modeGridBt = formElement.find('.modes-grid');
            const categoryContainer = formElement.find('.category-change-mode');

            if (modeGridBt.length) {
                modeGridBt[0].setAttribute('data-bind', 'click: function(){changeViewMode(true)}, touchstart:function(){changeViewMode(true)},css: { active: isGridMode() == true}');
            }
            if (modeListBt.length) {
                modeListBt[0].setAttribute('data-bind', 'click: function(){changeViewMode(false)}, touchstart:function(){changeViewMode(false)},css: { active: isGridMode() != true}');
            }

            if (categoryContainer.length) {
                categoryContainer.each(function () {
                    this.setAttribute('data-bind', "css: { 'rk-product-grid': isGridMode() == true,'rk-product-list': isGridMode() != true}");
                });
            }
        },
        changeViewMode: function (isGrid) {
            // IE11 compatible
            if(isGrid === undefined) {
                isGrid = true;
            }
            if (typeof activeGridMode == "function") {
                activeGridMode(isGrid);
            }

            return this;
        },

        extendObject: function () {
            var self = this;
            return {
                changeViewMode: self.changeViewMode
            }
        }
    });
});