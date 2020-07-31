define(
    [
        'jquery',
        'Magento_Ui/js/form/element/textarea'
    ], function(
        $, Textarea
    ) {
        return Textarea.extend(
            {
                initialize: function () {
                    this._super();

                    this.commentHtml = $('#tmprma_comments').html();
                    return this;
                }
            }
        );
    }
);