define([
    "jquery",
    'Magento_Ui/js/modal/alert',
    'Magento_Sales/order/create/form'
], function(jQuery, alert){
    window.AdminOrderType = new Class.create();

    AdminOrderType.prototype = {
        initialize: function (loadBaseUrl, invalidMsg) {
            this.loadBaseUrl = loadBaseUrl;
            this.invalidMsg = invalidMsg;
            this.typeElm = jQuery('#order-type-id');
            this.groupsType = [];
            this.groupsType[0] = [];
            this.groupsType[1] = [];
            this.groupsType[2] = ['order-original-id_choice', 'order-reason_choice', 'siebel_enquiry_id_choice'];
            this.groupsType[3] = ['order-wbs_choice', 'order-free-samples-reason_choice', 'order-free-samples-cause_choice'];
        },

        setLoadBaseUrl : function(url){
            this.loadBaseUrl = url;
        },

        changeType: function(){
            this.hideAll();
            var selectedType = this.typeElm.val();

            if(this.groupsType[selectedType] !== undefined){

                if(selectedType){

                    var area = window.order.prepareArea(['sidebar', 'items', 'shipping_method', 'billing_method','totals', 'giftmessage','delivery_info','deliverydate']);
                    window.order.loadingAreas = area;
                    var url = this.loadBaseUrl + 'block/' + area + '?isAjax=true';

                    new Ajax.Request(url, {
                        parameters:{'type':selectedType, 'json':true},
                        loaderArea: 'html-body',
                        onSuccess: function(transport) {
                            var response = transport.responseText.evalJSON();
                            window.order.loadAreaResponseHandler(response);

                            var groupSelected = this.groupsType[selectedType];
                            var numItem = groupSelected.length;

                            for (var i = 0; i < numItem; i++) {
                                jQuery('#' + groupSelected[i]).show();
                                jQuery('#' + groupSelected[i] + ' input').prop( "disabled", false );
                                jQuery('#' + groupSelected[i] + ' select').prop( "disabled", false );
                            }
                        }.bind(this)
                    });
                }
            }else{
                alert({
                    content: this.invalidMsg
                });
            }
        },

        hideAll: function(){
            var numGroup = this.groupsType.length;

            for (var i = 0; i < numGroup; i++) {
                var group = this.groupsType[i];
                var numItem = group.length;

                for (var j = 0; j < numItem; j++) {
                    jQuery('#' + group[j]).hide();
                    jQuery('#' + group[j] + ' input').prop( "disabled", true );
                    jQuery('#' + group[j] + ' select').prop( "disabled", true );
                }
            }
        }
    }
});


