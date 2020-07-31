define([
    'jquery',
    'knockout',
    'Magento_Ui/js/form/element/abstract',
    'uiRegistry',
    'mage/url',
], function ($, ko, Element,registry,urlBuilder) {
    'use strict';

   return Element.extend({
       defaults: {
           offlineCustomer: null,
           generateLinkVisible: ko.observable(true),
           listens: {
               '${ $.parentName + ".offline_customer" }:value': 'offlineCustomerHasChanged'
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
