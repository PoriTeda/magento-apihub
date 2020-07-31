/* Japanese initialisation for the jQuery UI date picker plugin. */
( function( factory ) {
    if (typeof define === "function" && define.amd) {
        // Register as an anonymous AMD module:
        define(["jquery", 'mage/translate'], factory);
    } else {
        // Browser globals:
        factory(jQuery);
    }
}( function($, $t) {

    $.datepicker.regional.ja = {
        closeText: $t("Close"),
        prevText: $t("&#x3C;Prev"),
        nextText: $t("Next&#x3E;"),
        currentText: $t("Today"),
        monthNames: [ $t("January"),$t("February"),$t("March"),$t("April"),$t("May"),$t("June"),$t("July"),$t("August"),$t("September"),$t("October"),$t("November"),$t("December") ],
        monthNamesShort: [ $t("January"),$t("February"),$t("March"),$t("April"),$t("May"),$t("June"),$t("July"),$t("August"),$t("September"),$t("October"),$t("November"),$t("December") ],
        dayNames: [ $t("Sunday"),$t("Monday"),$t("Tuesday"),$t("Wednesday"),$t("Thursday"),$t("Friday"),$t("Saturday") ],
        dayNamesShort: [ $t("Sunday"),$t("Monday"),$t("Tuesday"),$t("Wednesday"),$t("Thursday"),$t("Friday"),$t("Saturday") ],
        dayNamesMin: [ $t("Sunday"),$t("Monday"),$t("Tuesday"),$t("Wednesday"),$t("Thursday"),$t("Friday"),$t("Saturday") ],
        weekHeader: $t("Week"),
        dateFormat: "yy/mm/dd",
        firstDay: 1,
        isRTL: false,
        showMonthAfterYear: true,
        yearSuffix: $t("Year")
    };
    
    return $.datepicker.regional.ja;
} ) );