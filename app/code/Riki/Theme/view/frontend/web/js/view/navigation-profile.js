define([
    'jquery',
    'ko',
    'mage/url',
    'uiComponent',
    'mage/translate',
    'Magento_Customer/js/customer-data',
    'Magento_Catalog/js/owl.carousel',
    'mage/calendar',
    'jquery/ui',
    'uiRegistry',
    "domReady!"
], function (
    $,
    ko,
    urlBuilder,
    Component,
    $t,
    customerData
) {
    'use strict';

    return Component.extend({

        profiles: ko.observableArray(),
        timeslotErrMsg: ko.observable(""),
        deliveryDateErrMsg: ko.observable(""),
        toggleFromQuicklink: ko.observable(false),
        currentCustomer: ko.observable(),

        /** @inheritdoc */
        initialize: function () {
            var self = this;
            customerData.reload('customer-profiles');
            this._super();
            this.profiles = customerData.get('customer-profiles');
            if(customerData.get('customer')().fullname){
                $("#owlslider-logout").hide();
            } else{
                $("#owlslider-login").hide();
            }
            customerData.get('customer').subscribe(function(v){
               if(v.fullname){
                   self.currentCustomer(v.fullname);
               }
            });
            this.initBinding();
            setTimeout(function(){
                $("#owlslider-logout").owlCarousel({
                    autoplay: true,
                    autoplayTimeout: 3000,
                    loop: true,
                    items : 1,
                    margin: 5
                });
            }, 200);

            this.currentCustomer.subscribe(function(v){
                if(v){
                    $("#owlslider-logout").hide();
                    $("#owlslider-login").show();
                    setTimeout(function(){
                        $("#owlslider-login").owlCarousel({
                            autoplay: true,
                            autoplayTimeout: 3000,
                            loop: true,
                            items : 1,
                            margin: 5
                        });
                    }, 200);
                }
            });
        },

        initBinding: function(){
            var self = this;
        },

        updateTimeslot: function(data, event){
            if(!event.originalEvent){
                return;
            }
            var self = this;
            this.timeslotErrMsg("");
            var timeslot = event.currentTarget.value;
            var profileId = event.currentTarget.dataset.profileId;
            var params = {
                "profileId": profileId,
                "timeslot": timeslot
            };
            $("body").trigger("processStart");
            $.ajax({
                url: urlBuilder.build('subscriptions/profile/ajaxUpdateProfileTimeslot'),
                method: 'POST',
                data: params
            }).done(function (response) {
                if(response){
                    if(response.code === 1){
                        var deliveryDate = $(".delivery-date-" + profileId).val();
                        var reloadParams = {
                            "profile_id": profileId,
                            "next_delivery_date": deliveryDate,
                        };
                        $.ajax({
                            url: urlBuilder.build('subscriptions/profile/ajaxUpdateNavigationProfile'),
                            method: 'POST',
                            data: reloadParams
                        }).done(function (response) {
                            if(response){
                                self.updateRenderCalendar(response);
                            } else {
                                $("body").trigger("processStop");
                            }
                        });
                    } else{
                        self.timeslotErrMsg(response.message);
                        $("body").trigger("processStop");
                    }
                }
            });
        },

        toggleMenu: function(data, event){
            var self = this;
            var profileId = event.currentTarget.dataset.profileId;
            var index = event.currentTarget.dataset.index;
            const calendar = $('.calendar-wrapper[data-profile-id=' + profileId + ']');
            const price = $('.base-subtotal-incl-tax[data-profile-id=' + profileId + ']');
            const items = $('.list_product[data-profile-id=' + profileId + ']');
            if(calendar.html()){
                $(event.currentTarget).siblings().slideToggle("fast");
                return;
            }
            var params = {
                profile_id: profileId,
                index: index
            };
            $("body").trigger("processStart");
            $.ajax({
                url: urlBuilder.build('subscriptions/profile/ajaxFirstRenderCalendar'),
                method: 'POST',
                data: params
            }).done(function (response) {
                if(response.result) {
                    price.html(response.price);
                    if(response.html.length > 0) {
                        calendar.html(response.html);
                        var options = self.renderCalendar(response.calendarConfig);
                        var itemsHtml = '';
                        response.items.forEach(function (item) {
                            for (var j = 0; j < item['qty']; j++) {
                                itemsHtml += '<div class="list_product_photo"><img class="img-responsive" src="' + item['thumbnail'] + '"alt="' + item['name'] + '" title="' + item['name'] + '"></div>';
                            }
                        });
                        items.html(itemsHtml);
                        $('.submenu_course-change[data-profile-id=' + profileId + ']').show();
                        $('.submenu_course-items[data-profile-id=' + profileId + ']').show();
                        $(document).ready(function () {
                            const calendarNode = $('#calendar_inputField_' + profileId);
                            calendarNode.datepicker(options);
                            $("body").trigger("processStop");
                            $(event.currentTarget).siblings().slideToggle("fast");
                        });
                    } else {
                        $('.submenu_course-change[data-profile-id=' + profileId + ']').hide();
                        $('.submenu_course-items[data-profile-id=' + profileId + ']').hide();
                        $(event.currentTarget).siblings().slideToggle("fast");
                        $("body").trigger("processStop");
                    }
                } else{
                    $("body").trigger("processStop");
                }
            });
        },

        closeMenu: function(){
            $('.mob-screen').removeClass("active");
            $('.mob-content').removeClass("active");
            $('#main-course-container .actions-toolbar').removeClass("z_index");
            this.toggleFromQuicklink(false);
        },

        openMenu: function(data, event){
            var quicklink = event.currentTarget.dataset.quicklink == 1 ? true : false;
            if(event.originalEvent !== undefined || quicklink == true) {
                $('.mob-content').addClass("active");
                $('.mob-screen').addClass("active");
                $('#main-course-container .actions-toolbar').addClass("z_index");
                this.toggleFromQuicklink(quicklink);
            }
        },

        renderCalendar: function(calendarConfig, index){
            if (index === undefined) {
                index = 0;
            }
            var self = this;
            var restrictArray = [], hanpukaiAllowChangeDeliveryDate = [], minDate = [],
                maxDate = [], customDataCalendar = [], options = [];
            restrictArray[index] = calendarConfig['_checkCalendar'];
            hanpukaiAllowChangeDeliveryDate[index] = calendarConfig['hanpukaiDeliveryDateAllowed'];
            minDate[index] = calendarConfig['objMinDate'];
            maxDate[index] = calendarConfig['maxDate'];

            //set config retrict calendar
            customDataCalendar[index] = {
                "minDateTrigger": 0,
                "currentMin": 0,
                "maxDate": 0,
                "minDate": 0,
                "restrictDate": []
            };

            //set global value for max date
            customDataCalendar[index].maxDate = maxDate[index];
            customDataCalendar[index].minDate = minDate[index];

            if (hanpukaiAllowChangeDeliveryDate[index] == 1) {
                maxDate[index] = new Date(calendarConfig['hanpukaiDeliveryDateTo']);
            }

            options[index] = {
                firstDay: 1,
                dateFormat: "yy/mm/dd",
                showOn: "button",
                disabled: calendarConfig['isDisableDatePicker'] == true ? true : false,
                buttonText: "",
                showAnim: "",
                changeMonth: true,
                changeYear: true,
                buttonImageOnly: null,
                buttonImage: null,
                showButtonPanel: false,
                showOtherMonths: true,
                showWeek: false,
                timeFormat: '',
                showTime: false,
                showHour: false,
                showMinute: false,
                minDate: minDate[index],
                maxDate: maxDate[index],
                beforeShowDay: function (date, hanpukaiAllowChangeDeliveryDate) {
                    var string = $.datepicker.formatDate('yy/mm/dd', date);
                    return [restrictArray[index].indexOf(string) == -1]
                },
                onSelect: function (delivery_date) {
                    var profileId = calendarConfig['profileId'];
                    var url = urlBuilder.build('subscriptions/profile/ajaxUpdateNavigationProfile');
                    var params = {
                        "profile_id": profileId,
                        "next_delivery_date": delivery_date,
                    };
                    $("body").trigger("processStart");
                    $.ajax({
                        url: url,
                        method: 'POST',
                        data: params
                    }).done(function (response) {
                        if(response){
                            self.updateRenderCalendar(response);
                        }
                        else {
                            $("body").trigger("processStop");
                        }
                    });
                },
                beforeShow: function () {
                    //set value current min date from calendar after trigger event click
                    customDataCalendar[index].minDateTrigger = $(this).datepicker("option", "minDate");
                }
            };

            if ($('html').attr('lang') == 'ja') {
                options[index].closeText = "閉じる";
                options[index].prevText = "&#x3C;前";
                options[index].nextText = "次&#x3E;";
                options[index].currentText = "今日";
                options[index].monthNames = ["1月", "2月", "3月", "4月", "5月", "6月",
                    "7月", "8月", "9月", "10月", "11月", "12月"];
                options[index].monthNamesShort = ["1月", "2月", "3月", "4月", "5月", "6月",
                    "7月", "8月", "9月", "10月", "11月", "12月"];
                options[index].dayNames = ["日曜日", "月曜日", "火曜日", "水曜日", "木曜日", "金曜日", "土曜日"];
                options[index].dayNamesShort = ["日", "月", "火", "水", "木", "金", "土"];
                options[index].dayNamesMin = ["日", "月", "火", "水", "木", "金", "土"];
                options[index].weekHeader = "週";
            }
            return options[index];
        },

        updateRenderCalendar: function(response){
            var self = this;
            var newOptions0, newOptions1, calendarNode0, calendarNode1;
            var responses = [];
            for(var id in response){
                responses.push(response[id]);
            }
            if (responses.length === 2){
                if(parseInt(responses[0].index) === 1) {
                    responses.unshift(responses.pop());
                }
            }
            responses.forEach(function(currentResponse, index){
                var priceNode = $('#price-' + currentResponse.index);
                var calendar = $('#calendar-' + currentResponse.index);
                var itemsNode = $('#list-product-' + currentResponse.index);
                // set price
                priceNode.html(currentResponse.price);

                if(currentResponse.html.length > 0) {
                    calendar.html(currentResponse.html);
                    // set items
                    var itemsHtml = '';
                    currentResponse.items.forEach(function(item){
                        for(var j = 0; j < item['qty']; j++){
                            itemsHtml += '<div class="list_product_photo"><img class="img-responsive" src="' + item['thumbnail'] + '"alt="' + item['name'] + '" title="' + item['name'] + '"></div>';
                        }
                    });
                    itemsNode.html(itemsHtml);
                    // render calendar
                    if (index === 0) {
                        newOptions0 = self.renderCalendar(currentResponse.calendarConfig, index);
                        calendarNode0 = $('.calendar-node-0');
                        calendarNode0.datepicker(newOptions0);
                    } else if (index === 1) {
                        newOptions1 = self.renderCalendar(currentResponse.calendarConfig, index);
                        calendarNode1 = $('.calendar-node-1');
                        calendarNode1.datepicker(newOptions1);
                    }
                    $('#submenu_course-change-' + currentResponse.index).show();
                    $('#submenu_course-items-' + currentResponse.index).show();
                } else {
                    $('#submenu_course-change-' + currentResponse.index).hide();
                    $('#submenu_course-items-' + currentResponse.index).hide();
                }
            });
            $("body").trigger("processStop");
            setTimeout(function(){
                alert($t('Estimated delivery date changed'));
            }, 200);

        }
    });
});