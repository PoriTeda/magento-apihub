require([
    'jquery',
    'mage/url'
], function ($, urlBuilder) {
    $('document').ready(function () {
        $('#frequency_id').on('change', function () {
            $('body').trigger('processStart');
            var params = {
                'searchCriteria[filterGroups][0][filters][0][field]': 'profile_id',
                'searchCriteria[filterGroups][0][filters][0][value]': $('#profile_id').val(),
                'searchCriteria[filterGroups][0][filters][0][conditionType]': 'eq',
                'searchCriteria[filterGroups][1][filters][1][field]': 'frequency_id',
                'searchCriteria[filterGroups][1][filters][1][value]': $(this).val(),
                'searchCriteria[filterGroups][1][filters][1][conditionType]': 'eq'
            };
            $.get(urlBuilder.build('rest/V1/catalogRule/product/search'), params)
                .done(function (response) {
                    $.each(response, function () {
                        if (!$(this).length) {
                            return true;
                        }
                        var item = $(this)[0];
                        $('.tr-product[data-id="'+ item.id + '"]').each(function () {
                           $(this).find('td.price').html(item.amount_formatted);
                        });
                    });
                    $('body').trigger('processStop');
                })
                .fail(function () {
                    $('body').trigger('processStop');
                });
        });
    });
});