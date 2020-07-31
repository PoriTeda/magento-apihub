define([
    'Magento_Ui/js/form/element/select',
    'uiRegistry',
    'underscore',
    'jquery',
    'knockout',
    'Magento_Ui/js/modal/alert',
    'mage/translate'
], function (Select, registry, _, $, ko, alert, $t) {

    ko.extenders.confirmable = function (target, component) {
        var result = ko.computed({
            read: target,
            write: function (newValue) {
                if (component.isReset()) {
                    target(newValue);
                    component.isInvalid = true;
                    target.notifySubscribers(newValue);
                    return;
                }

                var isSetHome = false;
                var isSetCompany = false;
                var current = target();
                // Can create only shipping
                // if(newValue != "shipping"){
                //     alert({
                //         content: $t($.mage.__('You can only create shipping address'))
                //     });
                //     component.isInvalid = true;
                //     target.notifySubscribers(current);
                //     target(current);
                // }else {
                //     component.isInvalid = false;
                //     target(newValue);
                // }
                // END can create only shiping
// Comment for cannot create Address Home Or Company in BO
                var isAmb = registry.get('customer_form.areas.customer.customer.amb_type').value();
                _.each(registry.get('customer_form.areas.address.address.address_collection').elems(), function (v) {
                    var addressType = registry.get(v.name + '.riki_type_address');
                    if (addressType.name != component.name)
                    {
                        if(newValue == "home" && newValue == addressType.value() ){
                            isSetHome = true;

                        }
                        if(newValue == "company" && newValue == addressType.value()){
                            isSetCompany = true;
                        }
                    }
                });
                if(isSetCompany){
                    alert({
                        content: $t($.mage.__('You can not add more than 1 "Company" address'))
                    });
                    component.isInvalid = true;
                    target.notifySubscribers(current);
                    target(current);
                }else if(isAmb != 1 && isAmb != 9 && newValue == 'company'){
                    alert({
                        content: $t($.mage.__("The address type 'Company' is only applied for Ambassador."))
                    });
                    component.isInvalid = true;
                    target.notifySubscribers(current);
                }else {
                    component.isInvalid = false;
                    target(newValue);
                }
                if(isSetHome){
                    alert({
                        content: $t($.mage.__('You can not add more than 1 "Home" address'))
                    });
                    component.isInvalid = true;
                    target.notifySubscribers(current);
                    target(current);
                }
 // END
            }
        }).extend({notify: 'always'});

        //return the new computed observable
        return result;
    };

    var isResetAction = false;
    return Select.extend({
        initialize: function () {

            this._super();
            this.value = this.value.extend({confirmable: this});
            if(window.is_edit_customer){
                if(this.value() == 'home' || this.value() == 'company'){
                    this.disabled(true);
                }else{
                    this.disabled(false);
                }
            }
        },
        validate: function () {
            var result = this._super();
            var selectValue = this.value();

            if (this.isInvalid) {
                result.valid = false;
                return result;
            }

            var isAmb = registry.get('customer_form.areas.customer.customer.amb_type').value();

            if(isAmb != 1 && isAmb != 9 && selectValue == 'company'){
                alert({
                    content: $t($.mage.__("There are invalid data from Customer Address Type field. 'Company' is only applied for Ambassador."))
                });
                result.valid = false;
            }

            return result;
        },
        reset: function () {
            isResetAction = true;
            this._super();
            isResetAction = false;
        },
        isReset: function () {
            return isResetAction;
        }
    });
});