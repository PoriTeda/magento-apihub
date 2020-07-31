require([
    'mage/adminhtml/grid',
    'Riki_Rule/js/validation/rules'
], function () {
    serializerController.prototype.rowInit = function(grid, row) {
        if(this.multidimensionalMode){
            var isFree = $(row).select('.is-free-select')[0];
            var wbs = $(row).select('.input-wbs')[0];
            var discountAmount = $(row).select('input[name="discount_amount"]')[0];
            if (typeof discountAmount == 'undefined') {
                discountAmount = $(row).select('input[name="discount_percent"]')[0];
            }
            var checkbox = $(row).select('.checkbox')[0];
            var selectors = this.inputsToManage.map(function (name) {
                if (name != 'wbs') {
                    return ['input[name="' + name + '"]', 'select[name="' + name + '"]'];
                } else {
                    return ['input[rel="' + name + '"]', 'select[rel="' + name + '"]'];
                }
            });
            var inputs = $(row).select.apply($(row), selectors.flatten());
            if(checkbox && inputs.length > 0) {
                checkbox.inputElements = inputs;
                for(var i = 0; i < inputs.length; i++) {
                    inputs[i].checkboxElement = checkbox;
                    if(this.gridData.get(checkbox.value) && this.gridData.get(checkbox.value)[inputs[i].name]) {
                        inputs[i].value = this.gridData.get(checkbox.value)[inputs[i].name];
                    }
                    inputs[i].disabled = !checkbox.checked;
                    inputs[i].tabIndex = this.tabIndex++;
                    Event.observe(inputs[i],'keyup', this.inputChange.bind(this));
                    Event.observe(inputs[i],'change', this.inputChange.bind(this));
                }
                // disable discount amount when init
                if (isFree && isFree.getValue() == 1) {
                    discountAmount.disabled = true;
                }
                if (checkbox.checked && wbs) {
                    wbs.addClassName('required-entry');
                }
            }
        }
        this.getOldCallback('init_row')(grid, row);
    };
    serializerController.prototype.rowClick = function (grid, event) {
        var trElement = Event.findElement(event, 'tr');
        var isInput = Event.element(event).tagName == 'INPUT' || Event.element(event).tagName == 'SELECT' || Event.element(event).tagName == 'OPTION';
        if (trElement) {
            var checkbox = Element.select(trElement, 'input');
            var isFreeSelect = trElement.down('.is-free-select');
            var discountAmount = trElement.down('input[name="discount_amount"]');
            if (typeof discountAmount == 'undefined') {
                discountAmount = trElement.down('input[name="discount_percent"]');
            }
            var wbs = trElement.down('.input-wbs');
            if (checkbox[0] && !checkbox[0].disabled) {
                var checked = isInput ? checkbox[0].checked : !checkbox[0].checked;
                this.grid.setCheckboxChecked(checkbox[0], checked);
                // toggle is_free select when check row
                if (!checked) {
                    isFreeSelect.disable();
                    if(wbs)
                        wbs.removeClassName('required-entry');
                } else {
                    isFreeSelect.enable();
                    if(wbs)
                        wbs.addClassName('required-entry');
                }
                // toggle discount_amount when select is_free
                if (isFreeSelect && isFreeSelect.getValue() == 1) {
                    discountAmount.disable();
                }
            }
        }
        this.getOldCallback('row_click')(grid, event);
    };
});

