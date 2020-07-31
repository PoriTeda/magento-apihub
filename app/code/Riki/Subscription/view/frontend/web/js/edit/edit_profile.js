require([
    "jquery",
    "mage/validation"
], function($){
    submitForm = function (actionButton , formElement) {
        var bCheckIE = false;
        if(actionButton != null) {
            if(($('html').attr('class') != undefined && ($('html').attr('class').indexOf('ies') != -1 || $('html').attr('class').indexOf('ie') != -1)) || /Edge/.test(navigator.userAgent)){
                bCheckIE = true;
            }
        }
        $(actionButton).attr('disabled', true);
        if (bCheckIE) {
            actionButton.preventDefault();
        }
        $('body').trigger('processStart');
        return $('#'+formElement).submit();
    };
});