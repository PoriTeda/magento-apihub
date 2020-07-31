define([
    "jquery",
    'Magento_Ui/js/modal/alert',
    'Magento_Sales/order/create/form'
], function(jQuery, alert){
    window.AdminOrderEarn = new Class.create();

    AdminOrderEarn.prototype = {
        initialize: function (loadBaseUrl) {
            this.loadBaseUrl = loadBaseUrl;
            this.earnElm = jQuery('#allowed_earned_point');
        },

        setLoadBaseUrl : function(url){
            this.loadBaseUrl = url;
        },

        changeType: function(){
            var selectedType = this.earnElm.is(':checked');
            var area = window.order.prepareArea(['sidebar', 'items', 'totals']);
            window.order.loadingAreas = area;
            var url = this.loadBaseUrl + 'block/' + area + '?isAjax=true';

            new Ajax.Request(url, {
                parameters:{'type':selectedType, 'json':true},
                loaderArea: 'html-body',
                onSuccess: function(transport) {
                    var response = transport.responseText.evalJSON();
                    window.order.loadAreaResponseHandler(response);
                }.bind(this)
            });
        }
    }
});


