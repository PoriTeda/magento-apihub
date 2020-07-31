PointOfSale = {
    importCsvModal: function () {
        jQuery('#pos-import-csv').modal({
            'type': 'slide',
            'title': 'Import a csv File',
            'modalClass': 'mage-new-category-dialog form-inline',
            buttons: [{
                    text: 'Import File',
                    'class': 'action-primary',
                    click: function () {
                        PointOfSale.importCsvFile();
                    }
                }]
        });
        jQuery('#pos-import-csv').modal('openModal');
    },
    importCsvFile: function () {
        jQuery("#import-csv-file").find("#csv-file-error").remove();
        var input = jQuery("#import-csv-file").find("input#csv-file");
        var csv_file = input.val();

        // file empty ?
        if (csv_file == "") {
            jQuery("<label>", {
                "class": "mage-error",
                "id": "csv-file-error",
                "text": "This is a required field"
            }).appendTo(input.parent());
            return;
        }

        // valid file ?
        if (csv_file.indexOf(".csv") < 0) {
            jQuery("<label>", {
                "class": "mage-error",
                "id": "csv-file-error",
                "text": "Invalid file type"
            }).appendTo(input.parent());
            return;
        }

        // file not empty + valid file
        jQuery("#import-csv-file").submit();

    }

};

require([
    "jquery",
    "mage/mage",
    "jquery/ui",
    "Magento_Ui/js/modal/modal"
], function ($) {
    $(function () {});
});