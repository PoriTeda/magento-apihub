PointOfSale = {
    getstate: function (selectElement) {
        jQuery('#state').html('Searching…');
        jQuery.ajax({
            url: reloadStateUrl + selectElement.value,
            type: 'GET',
            showLoader: true,
            data: {},
            success: function (data) {
                jQuery('#state').html(data);
            }
        });

    },
    initializeGMap: function () {

        var latitude = document.getElementById('latitude').value;
        var longitude = document.getElementById('longitude').value;
        if (latitude === "") {
            latitude = "48.856951";
        }
        if (longitude === "") {
            longitude = "2.346868";
        }
        var zoom = 10;
        var LatLng = new google.maps.LatLng(latitude, longitude);
        var mapOptions = {
            zoom: zoom,
            center: LatLng,
            panControl: false,
            scaleControl: true,
            mapTypeId: google.maps.MapTypeId.ROADMAP
        };

        var map = new google.maps.Map(document.getElementById('map'), mapOptions);
        var marker = new google.maps.Marker({
            position: LatLng,
            map: map,
            title: 'Drag Me!',
            draggable: true
        });
        google.maps.event.addListener(marker, 'dragend', function (marker) {
            var latLng = marker.latLng;
            document.getElementById('longitude').value = latLng.lng().toFixed(6);
            document.getElementById('latitude').value = latLng.lat().toFixed(6);
        });
    },
    initializeHours: function (id) {
        if (jQuery('#hours').val() === "") {
            jQuery('#hours').val("{}");
        }
        var data = jQuery('#hours').val().evalJSON();

        for (var day in data) {
            jQuery('#' + day).prop('checked', true);
            var time = data[day];
            jQuery('#' + day + '_open').val(time.from);
            jQuery('#' + day + '_close').val(time.to);
            if (typeof time.lunch_from != "undefined") {
                jQuery('#' + day + "_lunch").prop('checked', true);
                jQuery('#' + day + '_lunch_open').val(time.lunch_from);
                jQuery('#' + day + '_lunch_close').val(time.lunch_to);
            } else {
                jQuery('#' + day + "_lunch").prop('checked', false);
            }
        }
        jQuery('.' + id + '_day').each(function () {
            if (!jQuery(this).prop('checked')) {
                jQuery(this).parent().parent().find('SELECT')[0].disabled = true;
                jQuery(this).parent().parent().find('SELECT')[1].disabled = true;
            }
        });
        jQuery('.' + id + '_lunch').each(function () {
            var elementVal = jQuery(this).val().replace(/<\/?[^>]+(>|$)/g,"");
            jQuery(this).prop('disabled',!jQuery("#"+elementVal).prop('checked'));
            if (!jQuery(this).prop('checked')) {
                jQuery(this).parent().parent().find('SELECT')[0].disabled = true;
                jQuery(this).parent().parent().find('SELECT')[1].disabled = true;
            }
        });
    },
    activeField: function (e, id) {
        var enabled = jQuery(e).prop('checked');
        jQuery(e).parent().parent().find('SELECT')[0].disabled = !enabled;
        jQuery(e).parent().parent().find('SELECT')[1].disabled = !enabled;

        var elementVal = jQuery(e).val().replace(/<\/?[^>]+(>|$)/g,"");
        var lunch = jQuery("#"+elementVal+"_lunch");
        lunch.prop('checked',false);
        lunch.prop('disabled',!enabled);
        lunch.parent().parent().find('SELECT')[0].disabled = true;
        lunch.parent().parent().find('SELECT')[1].disabled = true;
        PointOfSale.summary(id);
    },
    activeFieldLunch: function (e, id) {
        jQuery(e).parent().parent().find('SELECT')[0].disabled = !jQuery(e).prop('checked');
        jQuery(e).parent().parent().find('SELECT')[1].disabled = !jQuery(e).prop('checked');
        PointOfSale.summary(id);
    },
    summary: function (id) {
        hours = {};
        jQuery('.' + id + '_day').each(function (e) {
            if (jQuery(this).prop('checked')) {
                hours[jQuery(this).val()] = {
                    from: jQuery(this).parent().parent().find('SELECT')[0].value,
                    to: jQuery(this).parent().parent().find('SELECT')[1].value
                };
            }
        });
        jQuery('.' + id + '_lunch').each(function (e) {
            if (jQuery(this).prop('checked')) {
                if (typeof hours[jQuery(this).val()] == "undefined") {
                    hours[jQuery(this).val()] =  {};
                }
                hours[jQuery(this).val()]['lunch_from'] = jQuery(this).parent().parent().find('SELECT')[0].value;
                hours[jQuery(this).val()]['lunch_to'] = jQuery(this).parent().parent().find('SELECT')[1].value;
            }
        });
        jQuery('#hours').val(Object.toJSON(hours));
    }

};

require([
    "jquery",
    "prototype",
    "mage/mage"
], function ($) {
    $(function () {
        jQuery(document).ready(function () {

            setTimeout(PointOfSale.initializeGMap, 5000);

            // initialize hours
            PointOfSale.initializeHours(elementId);

        });
    });
});