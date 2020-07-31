require([
    "jquery",
    'mage/translate',
    "mage/validation"
], function($, $t){
    $.validator.addMethod(
        'validate-frequency', function (value) {
            if (value == 0) {
                return false;
            }
            return true;
        },
        $.mage.__('This is a required field.')
    );
    submitForm = function (actionButton , formElement) {
        if ($('#'+formElement).validation() && $('#'+formElement).validation('isValid')) {
            $(actionButton).disable = true;
            $('body').trigger('processStart');
            return $('#'+formElement).submit();
        }
    };
});