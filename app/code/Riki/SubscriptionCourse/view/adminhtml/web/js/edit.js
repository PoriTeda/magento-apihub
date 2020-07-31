
require([
    'jquery',
    'jquery/ui',
    'jquery/validate',
    'mage/translate'
], function($){

    var SUB_TYPE_HANPUKAI = 'Hanpukai';

    var showHanpukaiTab = function(value) {
        if(value !== SUB_TYPE_HANPUKAI) {
            $("#course_tabs_hanpukai_section").parent().hide();
        }
        else {
            $("#course_tabs_hanpukai_section").parent().show();
        }
    };

    var $subscriptionType = $("#cou_subscription_type");
    showHanpukaiTab($subscriptionType.val());

    $subscriptionType.on('change', function() {
        var $this = $(this);
        showHanpukaiTab($this.val());
    });

    var rulesAA = {
        "validate-hanpukai-if-any": [
            function () {
                var $sub_type = $("#cou_subscription_type"), $frequency =  $("#cou_frequency_ids");

                return $sub_type.val() != SUB_TYPE_HANPUKAI || ($sub_type.val() == SUB_TYPE_HANPUKAI) && $frequency.val() != null && $frequency.val().length == 1;
            },
            'Hanpukai subscription must choose one frequency.'
        ]
    };

    $.each(rulesAA, function (i, rule) {
        rule.unshift(i);
        $.validator.addMethod.apply($.validator, rule);
    });

});