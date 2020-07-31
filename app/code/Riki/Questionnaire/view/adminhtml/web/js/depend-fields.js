require(["jquery"], function ($) {
    $(document).ready(function () {
        toggleRelatedFields();
    });
});

function toggleRelatedFields() {
    if (jQuery('[name="questionnaire[enquete_type]"]').val() == 1) {
        jQuery('.field-start_date').hide();
        jQuery('#questionnaire_start_date').removeClass('required-entry _required');
        jQuery('#questionnaire_start_date').val('');

        jQuery('.field-end_date').hide();
        jQuery('#questionnaire_end_date').removeClass('required-entry _required');
        jQuery('#questionnaire_end_date').val('');

        jQuery('.field-priority').hide();
        jQuery('#questionnaire_priority').val('');

        jQuery('.field-linked_product_sku').hide();

        jQuery('#questionnaire_linked_product_sku').removeClass('required-entry _required')

        jQuery('.field-visible_on_checkout').hide();
        jQuery('.field-visible_on_order_success_page').hide();

        jQuery('.field-is_available_backend_only').hide();
    } else {
        jQuery('.field-start_date').show();
        jQuery('#questionnaire_start_date').addClass('required-entry _required');
        jQuery('.field-end_date').show();
        jQuery('#questionnaire_end_date').addClass('required-entry _required');
        jQuery('.field-priority').show();
        jQuery('.field-linked_product_sku').show();
        jQuery('#questionnaire_linked_product_sku').addClass('required-entry _required')
        jQuery('.field-visible_on_checkout').show();
        jQuery('.field-visible_on_order_success_page').show();
        jQuery('.field-is_available_backend_only').show();
    }
}
