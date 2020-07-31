/*jshint browser:true jquery:true*/
/*global alert*/
define([
        "jquery",
        "ko",
        "uiComponent",
        "mage/mage",
        "mage/translate",
        "Riki_Subscription/js/model/profile-list",
        'mage/url',
        'mage/storage',
        'Magento_Customer/js/customer-data'
    ],
    function (
        $,
        ko,
        Component,
        mage,
        $t,
        profileList,
        urlBuilder,
        storage,
        customerData
    ) {
        "use strict";

        return Component.extend({
            defaults: {
                template: "Riki_Subscription/profile-information"
            },
            error_message: ko.observable(),
            showLoading: ko.observable(true),
            customer: customerData.get('customer'),
            loadedProfiles: false,
            initialize: function () {
                this._super();

                this.formPostUrl = window.multileCategoryCampaignConfig.confirm_url;
                urlBuilder.setBaseUrl(window.multileCategoryCampaignConfig.base_url);

                if (typeof this.customer().email != 'undefined' && this.customer().email) {
                    this.getProfiles();
                }
            },
            initObservable: function () {
                this._super();

                this.profiles = ko.observableArray([]);

                customerData.get('customer').subscribe((function (data) {
                    if (data.email) {
                        this.getProfiles();
                    }
                }).bind(this));

                return this;
            },
            getDeliveryTo: function (profileId) {
                if (this.deliveryTypes && this.deliveryTypes[profileId]) {
                    return $t(this.deliveryTypes[profileId]);
                }
            },
            getProfiles: function () {
                var self = this;
                var serviceUrl = urlBuilder.build('rest/V1/subscriptions/profiles/multiple-category-campaigns/me');
                var campaignId = window.multileCategoryCampaignConfig.campaign_id;

                if (this.loadedProfiles) {
                    return;
                }

                return storage.post(
                    serviceUrl,
                    JSON.stringify({campaign_id: campaignId}),
                    false
                ).done(function (response) {
                    self.showLoading(false);

                    if (response.error_message) {
                        self.error_message(response.error_message);
                    }

                    if (!response.error_message) {
                        self.profiles(response);
                        $('.choose-subscription button').removeAttr('disabled');
                        window.multileCategoryCampaignConfig.enableButonSubmit = true;
                        self.loadedProfiles = true;
                    }
                }).fail(function (response) {
                    self.showLoading(false);
                    self.error_message(response.responseJSON.message);
                });
            }
        });
    }
);
