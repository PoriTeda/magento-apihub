define([
    'Magento_Ui/js/form/element/select',
    'uiRegistry',
    'underscore',
    'jquery',
    'knockout'
], function (SelectElement, registry, _, $, ko) {

    ko.extenders.confirmable = function (target, component) {
        var result = ko.computed({
            read: target,  //always return the original observables value
            write: function (newValue) {
                var current = target();

                if((current != 0) && newValue == 0 ){
                    component.error($.mage.__('Can not change to "Not Ambassador" from Ambassador'));
                    component.isInvalid = true;
                    target.notifySubscribers(current);
                }else{
                    component.error(false);
                    component.isInvalid = false;
                    target(newValue);
                }
            }
        }).extend({notify: 'always'});

        //return the new computed observable
        return result;
    };

    return SelectElement.extend({
        isInvalid: false,

        initialize: function () {

            this._super();
            if(this.value() ==0){
                return;
            }
            this.value = this.value.extend({confirmable: this});
        },
        validate: function () {
            var result = this._super();
            if (this.isInvalid) {
                result.valid = false;
            }
            return result;
        }
    });
});