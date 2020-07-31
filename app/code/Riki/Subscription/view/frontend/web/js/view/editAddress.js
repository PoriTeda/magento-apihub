define([
    'jquery',
    'ko',
    'uiComponent',
    'mage/storage',
    'mage/loader',
    'Magento_Ui/js/model/messageList',
    'mage/translate',
    'Magento_Ui/js/modal/modal',
    'mage/url'
], function ($, ko, Component, storage, loader, messageList,$t, modal, urlBuilder) {
    return Component.extend({
        urlEditHomeNoCompany: '',
        urlEditHomeHaveCompany: '',
        urlEditCompany: '',
        initialize: function (config) {
            this.urlEditHomeNoCompany = config.urlEditHomeNoCompany;
            this.urlEditHomeHaveCompany = config.urlEditHomeHaveCompany;
            this.urlEditCompany = config.urlEditCompany;
        },
        changeAddress: function (addressId, deliveryType, allAddressDetailForNewDesign,profileId) {
            var self = this;
            var id = addressId + '-' + deliveryType;
            var embedChooseAddress = $('#embed-choose-address-' + id);
            var options = {
                type: 'popup',
                responsive: false,
                innerScroll: true,
                modalClass: 'tiny',
                title: $t('Choose Address'),
                buttons: [{
                    text: $t('Ok'),
                    click: function () {
                        var selected_add = $('select[name="address[' + addressId + '][' + deliveryType + ']"] option:selected').val();
                        $('input[name="address[' + addressId + '][' + deliveryType + ']"]').val(selected_add);

                        var arrAddress = allAddressDetailForNewDesign;
                        var selectAddress = '';
                        if (typeof(arrAddress[selected_add]) != 'undefined') {
                            selectAddress = arrAddress[selected_add];
                        }
                        if(!selectAddress.able_to_edit) {
                            $('#button_' + deliveryType)
                                .addClass('no-display');
                        }else {
                            $('#button_' + deliveryType)
                                .removeClass('no-display');
                        }
                        $('td#name_' + addressId + '_' + deliveryType)
                            .text(selectAddress.fullname);

                        $('h4#address_' + addressId + '_' + deliveryType)
                            .text(selectAddress.riki_nickname_label);

                        $('td#address_' + addressId + '_' + deliveryType)
                            .text(selectAddress.street);

                        $('td#telephone_' + addressId + '_' + deliveryType)
                            .text(selectAddress.telephone);
                        self.getDeliveryAddressChange(profileId,selected_add,deliveryType);
                        embedChooseAddress.modal('closeModal');
                    }
                }]
            };
            var popup = modal(options, embedChooseAddress);
            embedChooseAddress.modal('openModal');
        },
        getDeliveryAddressChange:function(profileId,addressId,deliveryType)
        {
            var self = this;
            var parentBlock = $('.block-delivery-item[delivery-type='+deliveryType+']');
            var deliveryDateAfterChange = parentBlock.find('.current-delivery-date').val();

            $('body').trigger('processStart');
            $.ajax({
                url: urlBuilder.build('subscriptions/profile/changeShippingAddress'),
                method: 'POST',
                dataType : 'json',
                data: {
                    'profile_id':profileId,
                    'shipping_address': addressId,
                    'delivery_type' : deliveryType
                },
                async:true,
                success:function(result){

                    //set data global
                    window.customDataCalendar.restrictDate[deliveryType] = JSON.parse(result.data);

                    //trigger event calendar

                    $('.ui-datepicker-trigger').trigger('click');
                    $('#ui-datepicker-div').hide();
                    $('.ui-datepicker-trigger').trigger('click');

                    var deliveryDateBeforeChange = parentBlock.find('.current-delivery-date').val();
                    var deliveryOld = new Date(deliveryDateAfterChange);
                    var deliveryNew = new Date(deliveryDateBeforeChange);
                    if(deliveryOld.getTime() != deliveryNew.getTime())
                    {
                        var message = $t("Delivery date specified has been changed.");
                        parentBlock.find('.message-change-delivery').text(message);
                        deliveryDateAfterChange = deliveryDateBeforeChange;
                    }else{
                        parentBlock.find('.message-change-delivery').text('');
                    }

                    //show text min max date
                    var dateMin = new Date(window.customDataCalendar.minDateTrigger);
                    var dateMax = new Date(window.customDataCalendar.maxDate);
                    var textMin = dateMin.getFullYear() + $t('Year')+ ('0' + (dateMin.getMonth()+1)).slice(-2) +$t('Month') + ('0' + dateMin.getDate()).slice(-2)+$t('Day');
                    var textMax = dateMax.getFullYear() + $t('Year')+ ('0' + (dateMax.getMonth()+1)).slice(-2) +$t('Month') + ('0' + dateMax.getDate()).slice(-2)+$t('Day');
                    var textDateMin = $t(textMin);
                    var textDateMax = $t(textMax);

                    textDateMin = textDateMin.replace('Year','-').replace('Month','-').replace('Day','');
                    textDateMax = textDateMax.replace('Year','-').replace('Month','-').replace('Day','');

                    $('.textDeliveryChange[delivery-type='+deliveryType+'] span:first').text(textDateMin);
                    $('.textDeliveryChange[delivery-type='+deliveryType+'] span:last').text(textDateMax);

                    $('body').trigger('processStop');
                },
                error:function () {
                    $('body').trigger('processStop');
                }
            });

        },
        editRegistrationAddress: function (addressId, deliveryType, currentEditProfileUrl, profileId) {
            var self = this;
            var currentAddressId =  $('input[name="address[' + addressId + '][' + deliveryType + ']"]').val();
            $('#maincontent').trigger('processStart');
            serviceUrl = urlBuilder.build('subscriptions/profile/getEditFormHtml');
            $.ajax({
                url: serviceUrl,
                async: true,
                type: "POST",
                dataType: 'json',
                data: {currentShippingAddressId: currentAddressId, currentEditProfileUrl : currentEditProfileUrl, profileId: profileId},
                success: function (response) {
                    if (response.link_edit != '') {
                        switch(response.link_edit) {
                            case 1:
                                window.location.href = self.urlEditHomeNoCompany;
                                break;
                            case 2:
                                window.location.href = self.urlEditHomeHaveCompany;
                                break;
                            case 3:
                                window.location.href = self.urlEditCompany;
                                break;
                        }
                    } else {
                        var embedFormEditAddress = $('#embed-form-edit-address');
                        embedFormEditAddress.html(response.html_edit_form);
                        embedFormEditAddress.trigger('contentUpdated');
                        var options = {
                            type: 'popup',
                            responsive: false,
                            innerScroll: true,
                            modalClass: 'small hide-footer',
                            title: $t('Edit address')
                        };
                        var popup = modal(options, embedFormEditAddress);
                        embedFormEditAddress.modal('openModal');
                    }
                    $('#maincontent').trigger('processStop');
                }
            });
        }
    });
});