require([
    'jquery',
    'mage/translate',
    'Magento_Ui/js/modal/confirm',
    'mage/calendar'
], function ($, $t, confirm) {
    $(function () {
        $('#reason_id').on('change', function () {
            var value = $(this).val();
            var shipmentNumberEle = $('#rma_shipment_number');
            var shipmentNumberLabelEle = $('#shipment_number-label-wrap');
            var returnWithoutGoodsReasonId = $(this).attr('data-return-without-goods-reason_id');
            if (value == returnWithoutGoodsReasonId) {
                if (shipmentNumberEle.hasClass('required-entry')) {
                    shipmentNumberEle.removeClass('required-entry');
                }
                if (shipmentNumberLabelEle.hasClass('_required')) {
                    shipmentNumberLabelEle.removeClass('_required');
                }
            } else {
                if (!shipmentNumberEle.hasClass('required-entry')) {
                    shipmentNumberEle.addClass('required-entry');
                }
                if (!shipmentNumberLabelEle.hasClass('_required')) {
                    shipmentNumberLabelEle.addClass('_required');
                }
            }
        });
        // reason_id
        if ($('#entity_id').val()) {
            $('#reason_id').on('change', function () {
                var value = $(this).val();
                var label = $(this).find('option[value="'+value+'"]').text();
                confirm({
                    content: $t("Are you sure want to change reason to '%1'? All of total return amount will be reset, you need to review return amount again.").replace("%1", "<strong>" + label + "</strong>"),
                    actions: {
                        confirm: function () {
                            $('#save_and_edit_button').click()
                        }
                    }
                });
            });

            $('#rma_shipment_number').on('blur', function () {
                var value = $(this).val();
                if (value && value != $(this).data('value')) {
                    confirm({
                        content: $t('Are you sure want to change Shipment Number to "%1"? Refund allowed will be reset, you need to review return amount again.').replace('%1', '<strong>' + value + '</strong>'),
                        actions: {
                            confirm: function () {
                                $('#save_and_edit_button').click()
                            }
                        }
                    });
                }
            });

            $('#substitution_order').on('blur', function () {
                var value = $(this).val();
                if (value != $(this).data('value')) {
                    confirm({
                        content: $t('Are you sure want to change Substitution order to "%1"? Refund allowed will be reset, you need to review return amount again.').replace('%1', '<strong>' + value + '</strong>'),
                        actions: {
                            confirm: function () {
                                $('#save_and_edit_button').click()
                            }
                        }
                    });
                }
            })
        }

        // returned_date
        $('#returned_date_calendar').calendar({
            showTime: false,
            dateFormat: $('#returned_date_calendar').data('date-format'),
            maxDate: new Date(),
            onSelect: function (d, i) {
                var year = i.selectedYear,
                    month = i.selectedMonth + 1,
                    day = i.selectedDay;
                month = month < 10 ? '0' + month : month;
                day = day < 10 ? '0' + day : day;

                $('#returned_date').val(year + '-' + month + '-' + day);
            }
        });
    });
});