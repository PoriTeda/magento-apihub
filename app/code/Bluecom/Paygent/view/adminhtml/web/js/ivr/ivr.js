var IvrManager = {
    debug: true,
    canSave: false,
    data: {},
    log: function (method, arguments) {
        if (this.debug)
            console.log("Connect to Ivr " + method + "()", arguments);
    },
    connectToIvr: function (url, id) {
        this.log('connectToIvr', arguments);
        jQuery.ajax({
            url: url,
            method: 'get',
            data: {data: IvrManager.data},
            showLoader: true,
            error: function () {
                location.reload();
            },
            success: function (data) {
                if(data.resultCode == 0) {
                    jQuery('#response_detail').html(data.identifier);
                    jQuery('#get_update').show();
                } else {
                    jQuery('#transaction_ivr').html(data.message);
                }
                jQuery('.connectIVR').hide();
                jQuery('#transaction_ivr').show();

            }
        });
    },
    getUpdateIvr: function (url, id) {
        this.log('getUpdateIvr', arguments);
        jQuery.ajax({
            url: url,
            method: 'get',
            data: {data: IvrManager.data},
            showLoader: true,
            error: function () {
                location.reload();
            },
            success: function (data) {
                if(data.resultCode == 0) {
                    if(data.statusCode != 3) {
                        jQuery('.result-data').append('<p>' + data.message + '</p>');
                    } else {
                        jQuery('#get_update').hide();
                        jQuery('.result-data').append('<p>' + data.message + '</p>');
                        location.reload();
                    }
                } else {
                    jQuery('.result-data').append('<p>' + data.message + '</p>');
                    location.reload();
                }
            }
        });
    },
}
