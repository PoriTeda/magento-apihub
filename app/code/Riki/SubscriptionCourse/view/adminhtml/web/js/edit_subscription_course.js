require([
    "jquery",
    "mage/validation"
], function($){
    submitForm = function (actionButton, formElement, action = false) {
        // Disable button save course to prevent double form submission
        $(actionButton).attr('disabled', true);

        // Check and reset data for minimum_amount
        var minimumAmountOption = $('#cou_order_total_amount_option').val();
        if (minimumAmountOption != 2) {
            // Reset data of custom order
            $('div[id^=minimum_amount_option_]').each(function (index, element){
                var id =  $(element).attr('id');
                $('#' + id).remove();
            });
        } else {
            // Reset data of second order and all order
            $("#cou_oar_minimum_amount_threshold").val('');
        }

        // Check and reset data for maximum_qty
        var maximumQtyOption = $('#cou_maximum_qty_restriction_option').val();
        if (maximumQtyOption != 3) {
            // Reset data of custom order
            $('div[id^=maximum_qty_option_]').each(function (index, element){
                var id =  $(element).attr('id');
                $('#' + id).remove();
            });
        } else {
            // Reset data of first order and second order
            $("#cou_oqr_maximum_qty_restriction").val('');
        }

        // Validate before submit form
        var isValid = $('#' + formElement).mage('validation').valid();
        if (isValid) {
            $('body').trigger('processStart');
            if (action && action == 'saveandcontinue') {
                $('#' + formElement).append("<input type='hidden' name='back' value='1' />");
            }
            return $('#' + formElement).submit();
        }

        // Enable again button save course if validation is false
        $(actionButton).attr('disabled', false);
        return false;
    };
});