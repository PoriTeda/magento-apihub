define([
    'Magento_Ui/js/form/element/abstract',
    'jquery',
    'uiRegistry',
    'knockout',
    'Riki_Customer/js/lib/validation/validator'
], function (AbstractElement, $, registry,ko, validator) {
    return AbstractElement.extend({
        defaults: {
            imports: {
                update: '${ $.parentName }.email_1_type:value'
            },
            offlineCustomer: null,
            generateLinkVisible: ko.observable(true),
            listens: {
                '${ $.parentName + ".offline_customer" }:value': 'offlineCustomerHasChanged'
            }
        },
        update: function (email_1_type) {
           if(window.is_edit_customer){
               if(email_1_type == 9){
                   this.disabled(true);
               }else{
                   this.disabled(false);
               }
           }
        },
        validate: function () {
          if(this.value() ==''){
              this.error(false);
              return {
                      valid: true,
                      target: this
                };
          }  else{
              var value = this.value(),
                  msg = validator(this.validation, value),
                  isValid = !this.visible() || !msg;

              this.error(msg);

              if (!isValid) {
                  this.source.set('params.invalid', true);
              }

              return {
                  valid: isValid,
                  target: this
              };
          }
        },
        initialize: function () {
            this._super();
            this.offlineCustomer = registry.get(this.parentName + '.offline_customer');

            var self = this;
            registry.async(this.parentName + '.offline_customer')(function (offlineCustomer) {
                self.offlineCustomerHasChanged(offlineCustomer.value());
            });

        },
        generateFakeEmail: function () {
            var urlPostFakeEmail =  this.generate_email_url;
            $.ajax({
                type: 'POST',
                url: urlPostFakeEmail,
                data: {form_key: window.FORM_KEY},
                dataType: 'json',
                context: $('body')
            }).success($.proxy(function (data) {
                if(data.random_email != ''){
                    this.value(data.random_email);
                }
            }, this));

        },
        offlineCustomerHasChanged: function (value) {
            if (value == 1) {
                this.generateLinkVisible(true);
            } else {
                this.generateLinkVisible(false);
            }
        }
    });
});