define(
    [
        'Magento_Ui/js/form/components/collection/item',
        'uiRegistry'
    ],
    function (
        Item,
        Registry
    ) {
        return Item.extend(
            {
                initialize: function () {
                    this._super();
                    if (!window.tmpRmaItemsCount) {
                        window.tmpRmaItemsCount = 1;
                    }
                    if (this.hasOwnProperty('index') && parseInt(this.index, 10)) {
                        this.label = this.label + ' ' + window.tmpRmaItemsCount++;
                    } else {
                        var parent = Registry.get(this.parent);
                        if (parent && parent._elems && parent._elems.length) {
                            this.label = this.label + ' ' + (parent._elems.length - 1); //In case parent always has a false child element, minus length 1
                        }
                    }
                }
            }
        );
    }
);