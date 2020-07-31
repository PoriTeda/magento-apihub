define([
    'jquery',
    'ko',
    'uiComponent',
    'mage/storage',
    'mage/loader',
    'mage/url',
    'Magento_Ui/js/model/messageList',
    'mage/translate'
], function ($, ko, Component, storage, loader, urlBuilder, messageList,$t) {
    return Component.extend({
        pressPlay: function(elementId, event){
            var elementObj = $('#' + elementId);
            event.preventDefault();
            elementObj.hide();
            elementObj.next().show();
            elementObj.next().next().val(0);

            /** Set new value from - to */
            var from_v = elementObj.parent().parent().find('.from input').val();
            var from_t = elementObj.parent().parent().find('.to input').val();

            elementObj.parent().parent().find('.from-text .content').html(from_v);
            elementObj.parent().parent().find('.to-text .content').html(from_t);

            /** Show / hide calendar or text */
            elementObj.parent().parent().find('.from').show();
            elementObj.parent().parent().find('.to').show();
            elementObj.parent().parent().find('.time').show();
            elementObj.parent().parent().find('.from-text').hide();
            elementObj.parent().parent().find('.to-text').hide();
        },
        generateOption: function (item, event) {
            var self = $(event.target);

            if (self.data('render') == '0') {
                var str = "";
                var len = self.data('quantity');
                var selected = self.data('selected');
                var i = 11;
                for (i; i <= len; i++) {
                    if(i != selected){
                        str += "<option value='" + i + "'>" + i + "</option>";
                    }
                }
                self.append(str);
                self.data('render','1');
                self.unbind( "click");
                self.unbind("touchstart");
            }
            return false;
        },
        renderSeasonalFromCalendar: function(elementId, allowSkipFrom, allowSkipTo){
            var $calendar_from = $('#' + elementId);
            var options_from = {
                dayNames: ["Sunday","Monday","Tuesday","Wednesday","Thursday","Friday","Saturday"],
                dayNamesMin: ["Sun","Mon","Tue","Wed","Thu","Fri","Sat"],
                monthNames: ["January","February","March","April","May","June","July","August","September","October","November","December"],
                monthNamesShort: ["Jan","Feb","Mar","Apr","May","Jun","Jul","Aug","Sep","Oct","Nov","Dec"],
                firstDay: 1,
                closeText: "Close",
                currentText: "Go Today",
                prevText: "Previous",
                nextText: "Next",
                weekHeader: "WK",
                timeText: "Time",
                hourText: "Hour",
                minuteText: "Minute",
                dateFormat: "yy-mm-dd",
                showOn: "button",
                buttonText: "",
                showAnim: "",
                changeMonth: false,
                changeYear: false,
                buttonImageOnly: null,
                buttonImage: null,
                showButtonPanel: false,
                showOtherMonths: true,
                showWeek: false,
                timeFormat: '',
                showTime: false,
                showHour: false,
                showMinute: false,
                minDate: allowSkipFrom,
                maxDate: allowSkipTo,
                onSelect: function () {
                    var $this = $(this);
                    var minDateCalendarTo = $this.val();
                    var objMinNextDate = new Date(minDateCalendarTo);
                    var arrSplitId = $this.attr('id').split('-');
                    if (arrSplitId.length > 1) {
                        $calendar_to = $('#calendar-to-' + arrSplitId[2]);
                        var CurrentCalendarToVal = $calendar_to.val();
                        /** Set new max, min date for next delivery date */
                        $calendar_to.val('');
                        $calendar_to.datepicker("destroy");
                        var options_to_new = {
                            dayNames: ["Sunday", "Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday"],
                            dayNamesMin: ["Sun", "Mon", "Tue", "Wed", "Thu", "Fri", "Sat"],
                            monthNames: ["January", "February", "March", "April", "May", "June", "July", "August", "September", "October", "November", "December"],
                            monthNamesShort: ["Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"],
                            firstDay: 1,
                            closeText: "Close",
                            currentText: "Go Today",
                            prevText: "Previous",
                            nextText: "Next",
                            weekHeader: "WK",
                            timeText: "Time",
                            hourText: "Hour",
                            minuteText: "Minute",
                            dateFormat: "yy-mm-dd",
                            showOn: "button",
                            buttonText: "",
                            showAnim: "",
                            changeMonth: false,
                            changeYear: false,
                            buttonImageOnly: null,
                            buttonImage: null,
                            showButtonPanel: false,
                            showOtherMonths: true,
                            showWeek: false,
                            timeFormat: '',
                            showTime: false,
                            showHour: false,
                            minDate: objMinNextDate,
                            maxDate: allowSkipTo,
                        };
                        if($('html').attr('lang') == 'ja-JP') {
                            options_to_new.closeText = "閉じる";
                            options_to_new.prevText = $t("&#x3C;Prev");
                            options_to_new.nextText = $t("Next&#x3E;");
                            options_to_new.currentText = $t("Today");
                            options_to_new.monthNames = [ $t("January"),$t("February"),$t("March"),$t("April"),$t("May"),$t("June"),$t("July"),$t("August"),$t("September"),$t("October"),$t("November"),$t("December") ];
                            options_to_new.monthNamesShort = [ $t("January"),$t("February"),$t("March"),$t("April"),$t("May"),$t("June"),$t("July"),$t("August"),$t("September"),$t("October"),$t("November"),$t("December") ];
                            options_to_new.dayNames = [ $t("Sunday"),$t("Monday"),$t("Tuesday"),$t("Wednesday"),$t("Thursday"),$t("Friday"),$t("Saturday") ];
                            options_to_new.dayNamesShort = [ $t("Sunday"),$t("Monday"),$t("Tuesday"),$t("Wednesday"),$t("Thursday"),$t("Friday"),$t("Saturday") ];
                            options_to_new.dayNamesMin = [ $t("Sunday"),$t("Monday"),$t("Tuesday"),$t("Wednesday"),$t("Thursday"),$t("Friday"),$t("Saturday") ];
                            options_to_new.weekHeader = $t("Week");
                        }
                        $calendar_to.datepicker(options_to_new);
                        $('.ui-datepicker').addClass('notranslate');
                        if(Date.parse(CurrentCalendarToVal) > Date.parse(minDateCalendarTo)) {
                            $calendar_to.datepicker('setDate',  CurrentCalendarToVal );
                        }
                    }
                }
            };
            if($('html').attr('lang') == 'ja-JP') {
                options_from.closeText = $t("Close");
                options_from.prevText = $t("&#x3C;Prev");
                options_from.nextText = $t("Next&#x3E;");
                options_from.currentText = $t("Today");
                options_from.monthNames = [ $t("January"),$t("February"),$t("March"),$t("April"),$t("May"),$t("June"),$t("July"),$t("August"),$t("September"),$t("October"),$t("November"),$t("December") ];
                options_from.monthNamesShort = [ $t("January"),$t("February"),$t("March"),$t("April"),$t("May"),$t("June"),$t("July"),$t("August"),$t("September"),$t("October"),$t("November"),$t("December") ];
                options_from.dayNames = [ $t("Sunday"),$t("Monday"),$t("Tuesday"),$t("Wednesday"),$t("Thursday"),$t("Friday"),$t("Saturday") ];
                options_from.dayNamesShort = [ $t("Sunday"),$t("Monday"),$t("Tuesday"),$t("Wednesday"),$t("Thursday"),$t("Friday"),$t("Saturday") ];
                options_from.dayNamesMin = [ $t("Sunday"),$t("Monday"),$t("Tuesday"),$t("Wednesday"),$t("Thursday"),$t("Friday"),$t("Saturday") ];
                options_from.weekHeader = $t("Week");

            }
            $calendar_from.datepicker(options_from);
            $('.ui-datepicker').addClass('notranslate');

        },
        renderSeasonalToCalendar: function(elementId, allowSkipFrom, allowSkipTo) {
            var $calendar_to = $('#' + elementId);
            var options_to = {
                dayNames: ["Sunday","Monday","Tuesday","Wednesday","Thursday","Friday","Saturday"],
                dayNamesMin: ["Sun","Mon","Tue","Wed","Thu","Fri","Sat"],
                monthNames: ["January","February","March","April","May","June","July","August","September","October","November","December"],
                monthNamesShort: ["Jan","Feb","Mar","Apr","May","Jun","Jul","Aug","Sep","Oct","Nov","Dec"],
                firstDay: 1,
                closeText: "Close",
                currentText: "Go Today",
                prevText: "Previous",
                nextText: "Next",
                weekHeader: "WK",
                timeText: "Time",
                hourText: "Hour",
                minuteText: "Minute",
                dateFormat: "yy-mm-dd",
                showOn: "button",
                buttonText: "",
                showAnim: "",
                changeMonth: false,
                changeYear: false,
                buttonImageOnly: null,
                buttonImage: null,
                showButtonPanel: false,
                showOtherMonths: true,
                showWeek: false,
                timeFormat: '',
                showTime: false,
                showHour: false,
                showMinute: false,
                minDate: allowSkipFrom,
                maxDate: allowSkipTo,
            };
            if($('html').attr('lang') == 'ja-JP') {
                options_to.closeText = $t("Close");
                options_to.prevText = $t("&#x3C;Prev");
                options_to.nextText = $t("Next&#x3E;");
                options_to.currentText = $t("Today");
                options_to.monthNames = [ $t("January"),$t("February"),$t("March"),$t("April"),$t("May"),$t("June"),$t("July"),$t("August"),$t("September"),$t("October"),$t("November"),$t("December") ];
                options_to.monthNamesShort = [ $t("January"),$t("February"),$t("March"),$t("April"),$t("May"),$t("June"),$t("July"),$t("August"),$t("September"),$t("October"),$t("November"),$t("December") ];
                options_to.dayNames = [ $t("Sunday"),$t("Monday"),$t("Tuesday"),$t("Wednesday"),$t("Thursday"),$t("Friday"),$t("Saturday") ];
                options_to.dayNamesShort = [ $t("Sunday"),$t("Monday"),$t("Tuesday"),$t("Wednesday"),$t("Thursday"),$t("Friday"),$t("Saturday") ];
                options_to.dayNamesMin = [ $t("Sunday"),$t("Monday"),$t("Tuesday"),$t("Wednesday"),$t("Thursday"),$t("Friday"),$t("Saturday") ];
                options_to.weekHeader = $t("Week");
            }
            $calendar_to.datepicker(options_to);
            $('.ui-datepicker').addClass('notranslate');
        },
        pressPause :function(elementId, event) {
            var elementObj = $('#' + elementId);
            event.preventDefault();
            elementObj.hide();
            elementObj.prev().show();
            elementObj.next().val(1);

            /** Set new value from - to */
            var from_v = elementObj.parent().parent().find('.from input').val();
            var from_t = elementObj.parent().parent().find('.to input').val();

            elementObj.parent().parent().find('.from-text .content').html(from_v);
            elementObj.parent().parent().find('.to-text .content').html(from_t);

            /** Show / hide calendar or text */
            elementObj.parent().parent().find('.from').hide();
            elementObj.parent().parent().find('.to').hide();
            elementObj.parent().parent().find('.time').hide();
            elementObj.parent().parent().find('.from-text').show();
            elementObj.parent().parent().find('.to-text').show();
        },
        refreshPrice: function (productCatId, productId, unitQty, elementId, courseId, frequencyUnit, frequencyInterval) {
            var self = this;
            var elementObject = $('#' + elementId);
            var iProfileId = $('#profile_id').val();
            var selectedQty = $("#"+ elementId + " option:selected").text() * unitQty;
            $('#maincontent').trigger('processStart');
            var params = JSON.stringify({
                courseId: courseId ? courseId : 0,
                iProfileId: iProfileId?iProfileId:0,
                frequencyUnit: frequencyUnit ? frequencyUnit : 0,
                frequencyInterval: frequencyInterval ? frequencyInterval : 0,
                productId: productId,
                qtyChange: selectedQty
            });
            if (elementObject.hasClass('qty_case') == true) {
                var productcartid = elementObject.attr('productcartid');
                var unitqty = $('#product_cart_id_case_' + productcartid).attr('unitqty');
                $('#product_cart_id_'+ productcartid).val($('#product_cart_id_case_'+ productcartid).val() * unitqty);
            }
            storage
                .post(urlBuilder.build('/rest/V1/editprofile/changeProductQty'), params)
                .done(function (response) {
                    response = JSON.parse(response);
                    if (Array.isArray(response.html_price) && response.html_price.length > 1) {
                        $('#subtotal_item_' + productCatId).html(response.html_price[1]);
                        $('#price_item_' + productCatId).html(response.html_price[2]);
                    }
                    $('#maincontent').trigger('processStop');
                });
        }
    });
});