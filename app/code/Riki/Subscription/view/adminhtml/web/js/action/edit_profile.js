require([
    "jquery",
    "mage/validation"
], function($){
    submitForm = function (actionButton , formElement) {
        var isValid = $('#'+formElement).mage('validation').valid();
        if(isValid) {
            $(actionButton).attr('disabled', true);
            $('body').trigger('processStart');
            return $('#'+formElement).submit();
        }
        return false;
    };
});