define(
    [
        'jquery',
        'Magento_Ui/js/modal/modal',
        "mage/translate",
        'mage/backend/validation'
    ],
    function ($, modal) {
        var reasonCancelForm = $('#frm-confirm-reason-cancel');
        var optionsCancel = {
            type: 'popup',
            responsive: false,
            innerScroll: true,
            title: $.mage.__('Reason Cancel Order'),
            buttons: [
                {
                    text: $.mage.__('No cancel'),
                    class: 'button back-action',
                    click: function () {
                        this.closeModal();
                    }
                },
                {
                    text: $.mage.__('Yes'),
                    class: 'button confirm-reason-cancel',
                    click: function () {
                        if (!reasonCancelForm.valid()) {
                            return;
                        }
                        reasonCancelForm.submit();
                    }
                }
            ]
        };

        var popup = modal(optionsCancel, $('#reason-cancel-order'));

        $('.action.cancel').on('click', function (e) {
            e.preventDefault();
            reasonCancelForm.mage('validation');
            $('#reason-cancel-order').modal('openModal');
        });

    }
);