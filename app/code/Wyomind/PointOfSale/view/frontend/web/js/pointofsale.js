
var PointOfSale = {
    places: new Array(),
    map: null,
    markers: null,
    dirRenderer: null,
    myLatLng: null,
    myStore: null,
    infowindow: null,
    __initialize: function () {
        var latlng = new google.maps.LatLng(0, 0);
        if (PointOfSale.places[0] != undefined) {
            latlng = new google.maps.LatLng(PointOfSale.places[0].lat, PointOfSale.places[0].lng);
        }
        var myOptions = {
            zoom: 10,
            center: latlng,
            mapTypeId: google.maps.MapTypeId.ROADMAP
        };
        PointOfSale.map = new google.maps.Map(document.getElementById("map_canvas_pointofsale"), myOptions);
        PointOfSale.markers = new Array;
        PointOfSale.setPlaces();
        PointOfSale.geoLocation();
        setTimeout(
                function () {
                    if (W_GP.myAddress == null) {
                        PointOfSale.displaySearch(true);
                    }
                }, 10000);
    },
    geoLocation: function () {
        // Try W3C Geolocation (Preferred)
        if (navigator.geolocation) {
            navigator.geolocation.getCurrentPosition(function (position) {
                initialLocation = new google.maps.LatLng(position.coords.latitude, position.coords.longitude);
                PointOfSale.map.setCenter(initialLocation);
                PointOfSale.findPlace(null, initialLocation);
            }, function () {
                handleNoGeolocation(true);
            });
            // Try Google Gears Geolocation
        } else if (google.gears) {
            var geo = google.gears.factory.create('beta.geolocation');
            geo.getCurrentPosition(function (position) {
                initialLocation = new google.maps.LatLng(position.latitude, position.longitude);
                PointOfSale.map.setCenter(initialLocation);
                PointOfSale.findPlace(null, initialLocation);
            }, function () {
                PointOfSale.handleNoGeoLocation(true);
            });
            // Browser doesn't support Geolocation
        } else {
            PointOfSale.handleNoGeolocation(false);
        }
        return false;
    },
    handleNoGeolocation: function (errorFlag) {

        PointOfSale.displaySearch(true);
        PointOfSale.displayStore(0);
    },
    displaySearch: function (error) {
        var msg = "";
        var value = "";
        if (error) {
            msg = "<span>" + W_GP.strings.unableToFindYourLocation + "<span><br/>";
            value = W_GP.strings.enterYourLocation;
        } else {
            msg = "<span class='tools-new-location'>" + W_GP.strings.setANewLocation + "<span>";
            value = W_GP.myAddress;
        }

        var myPlace = "<div>";
        myPlace += msg;
        myPlace += "<input id='geocoder' value='" + value + "' onfocus='this.value=\"\";'/>";
        myPlace += "<button onclick='PointOfSale.findPlace(jQuery(\"#geocoder\").attr(\"value\"));' value='" + W_GP.strings.findMe + "'>" + W_GP.strings.findMe + "</button>";
        jQuery('#tools').html(myPlace);
    },
    displayMyAddress: function (myAddress) {

        var myPlace = "<div>";
        myPlace += "<span class='tools-location'>" + W_GP.strings.yourLocation + " : </span>";
        myPlace += "<span class='tools-address'><b>" + myAddress + "</b></span>";
        myPlace += "<span class='tools-buttons'>";
        myPlace += "<a href='javascript:PointOfSale.displaySearch(false);'>" + W_GP.strings.changeMyLocation + "</a>";
        myPlace += "<a href='javascript:javascript:PointOfSale.displayLocation(W_GP.myAddress,true);'>" + W_GP.strings.showMyLocation + "</a>";
        myPlace += "</span>";
        myPlace += "<div>";
        jQuery('#tools').html(myPlace);
    },
    convertRad: function (input) {
        return (Math.PI * input) / 180;
    },
    distance: function (lat_a_degre, lon_a_degre, lat_b_degre, lon_b_degre) {

        var R = 6378000; //Rayon de la terre en mètre

        var lat_a = PointOfSale.convertRad(lat_a_degre);
        var lon_a = PointOfSale.convertRad(lon_a_degre);
        var lat_b = PointOfSale.convertRad(lat_b_degre);
        var lon_b = PointOfSale.convertRad(lon_b_degre);
        var d = R * (Math.PI / 2 - Math.asin(Math.sin(lat_b) * Math.sin(lat_a) + Math.cos(lon_b - lon_a) * Math.cos(lat_b) * Math.cos(lat_a)))
        return d;
    },
    sortBy: function (a, b) {
        return Math.round(a.distance) > Math.round(b.distance);
    },
    findPlace: function (myAddress) {
        PointOfSale.closeDirection();
        var geocoder = new google.maps.Geocoder();
        var data = {};
        if (typeof arguments[1] != "undefined") {
            data = {location: arguments[1]};
        } else {
            data = {'address': myAddress};
        }
        geocoder.geocode(data, function (results, status) {
            if (PointOfSale.dirRenderer != null) {
                PointOfSale.dirRenderer.setMap(null);
            }
            if (status == google.maps.GeocoderStatus.OK) {
                if (results[0]) {


                    PointOfSale.updateList('*');
                    var coord = new Array(results[0].geometry.location.lat(), results[0].geometry.location.lng());
                    PointOfSale.myLatLng = new google.maps.LatLng(coord[0], coord[1]);
                    jQuery.each(PointOfSale.stores, function (i) {
                        PointOfSale.stores[i].distance = PointOfSale.distance(coord[0], coord[1], PointOfSale.stores[i].position.lat(), PointOfSale.stores[i].position.lng());
                    });
                    var storeTemp = new Array();
                    var storeTemp = PointOfSale.stores.sort(PointOfSale.sortBy);
                    var storeList = new Array();
                    PointOfSale.storeListTemp = new Array();
                    var i = 0;
                    jQuery.each(storeTemp, function (s) {

                        if (i < 25) {
                            PointOfSale.storeListTemp.push(storeTemp[s]);
                            storeList.push(storeTemp[s].position);
                        }
                        i++;
                    });
                    if (storeList.length > 0) {
                        var service = new google.maps.DistanceMatrixService();
                        service.getDistanceMatrix(
                                {
                                    origins: [PointOfSale.myLatLng],
                                    destinations: storeList,
                                    travelMode: google.maps.TravelMode.DRIVING,
                                    unitSystem: google.maps.UnitSystem.METRIC,
                                    avoidHighways: false,
                                    avoidTolls: false
                                }, function (response, statusDistance) {
                            if (statusDistance === "OK") {
                                PointOfSale.getDistances(response);
                            } else
                                alert(W_GP.strings.distanceCalculationFailed + statusDistance);
                            myAddress = results[0].formatted_address;
                            W_GP.myAddress = results[0].formatted_address;
                            PointOfSale.displayLocation(myAddress);
                        });
                    } else {
                        myAddress = results[0].formatted_address;
                        W_GP.myAddress = results[0].formatted_address;
                        PointOfSale.displayLocation(myAddress);
                    }
                } else {
                    alert(W_GP.strings.noResultFound);
                }
            } else {
                alert(W_GP.strings.unableToFindYourLocation);
            }
        });
    },
    getStoreIndexById: function (id) {
        var i = 0;
        var index = null;
        jQuery.each(PointOfSale.places, function (ind, p) {
            if (p.id == id) {
                index = i;
            }
            i++;
        });
        return index;
    },
    getStoreIdByIndex: function (index) {
        var i = 0;
        var id = null;
        jQuery.each(PointOfSale.places, function (ind, p) {
            if (i == index) {
                id = p.id;
            }
            i++;
        });
        return id;
    },
    getDistances: function (response) {

        PointOfSale.myStore = {
            status: false,
            duration: {
                text: null,
                value: null
            },
            distance: {
                text: null,
                value: null
            }
        };
        var s = 0;
        jQuery.each(PointOfSale.places, function (i, p) {
            PointOfSale.places[s].status = false;
            PointOfSale.places[s].duration.value = null;
            PointOfSale.places[s].duration.text = null;
            PointOfSale.places[s].distance.value = null;
            PointOfSale.places[s].distance.text = null;
            s++;
        });
        s = 0;
        jQuery.each(response.rows[0].elements, function (s, e) {
            if (e.status != "ZERO_RESULTS") {


                var index = PointOfSale.getStoreIndexById(PointOfSale.storeListTemp[s].id);
                PointOfSale.places[index].status = true;
                PointOfSale.places[index].duration.value = e.duration.value;
                PointOfSale.places[index].duration.text = e.duration.text;
                PointOfSale.places[index].distance.value = e.distance.value;
                PointOfSale.places[index].distance.text = e.distance.text;
                if (!PointOfSale.myStore.status || e.duration.value < PointOfSale.myStore.duration.value) {
                    PointOfSale.myStore.status = true;
                    PointOfSale.myStore.duration.value = e.duration.value;
                    PointOfSale.myStore.duration.text = e.duration.text;
                    PointOfSale.myStore.distance.value = e.distance.value;
                    PointOfSale.myStore.distance.text = e.distance.text;
                    PointOfSale.myStore.index = index;
                }
            } else
                PointOfSale.places[s].status = false;
        });
    },
    displayLocation: function (myAddress) {

        var blueIcon = new google.maps.MarkerImage("http://www.google.com/intl/en_us/mapfiles/ms/micons/blue-dot.png");
        if (typeof myLocation === "undefined") {
            var myLocation = new google.maps.Marker({
                position: PointOfSale.myLatLng,
                map: PointOfSale.map,
                icon: blueIcon

            });
            google.maps.event.addListener(myLocation, 'click', function () {
                PointOfSale.displayLocation(myAddress, true);
            });
        } else {
            myLocation.setPosition(PointOfSale.myLatLng);
        }
        if (PointOfSale.myStore != undefined) {
            if (PointOfSale.myStore.status) {
                PointOfSale.infowindow.setContent(
                        "<h4><b>"
                        + W_GP.strings.youAreHere
                        + "</b></h4><br>"
                        + W_GP.strings.theClosestStoreIs
                        + " <b><a href='javascript:displayStore("
                        + PointOfSale.myStore.index
                        + ")'/>"
                        + PointOfSale.places[PointOfSale.myStore.index].name
                        + "</a></b><br> "
                        + PointOfSale.myStore.distance.text
                        + " - "
                        + PointOfSale.myStore.duration.text
                        + "<br><a href='javascript:PointOfSale.getDirections()'>"
                        + W_GP.strings.getDirections
                        + "</a>"
                        );
                PointOfSale.blindStore(PointOfSale.myStore.index);
            } else {
                PointOfSale.infowindow.setContent("<h4><b>" + W_GP.strings.youAreHere + "</b></h4><br/><b>" + W_GP.strings.noStoreLocated + "</b>");
            }
            if (!arguments[1]) {
                var zoom = 12 - Math.round((PointOfSale.myStore.distance.value * 100 / 500000) * (12 / 100));
                if (zoom < 4)
                    zoom = 4;
                PointOfSale.map.setZoom(zoom);
            }
        }
        PointOfSale.infowindow.open(PointOfSale.map, myLocation);
        PointOfSale.map.panTo(PointOfSale.myLatLng);
        PointOfSale.displayMyAddress(myAddress);
    },
    displayStore: function (index) {
        if (typeof PointOfSale.places[index] != "undefined") {
            var latlng = new google.maps.LatLng(PointOfSale.places[index].lat, PointOfSale.places[index].lng);
            PointOfSale.map.panTo(latlng);
            var content = PointOfSale.places[index].title;
            if (PointOfSale.places[index].status) {
                content += "<br>"
                        + PointOfSale.places[index].distance.text
                        + " - "
                        + PointOfSale.places[index].duration.text
                        + " "
                        + W_GP.strings.from
                        + " "
                        + "<a href='javascript:displayLocation(W_GP.myAddress,true)'>"
                        + W_GP.myAddress
                        + "</a><br>";
                content += PointOfSale.places[index].links.directions + " | ";
            } else {
                content += '<br>';
            }
            content += PointOfSale.places[index].links.showOnMap;
            PointOfSale.infowindow.setContent(content);
            PointOfSale.infowindow.open(PointOfSale.map, PointOfSale.markers[index]);
            PointOfSale.blindStore(index);
        }
    },
    blindStore: function (index) {
        var id = PointOfSale.getStoreIdByIndex(index);
        jQuery('#pointofsale_scroll .details[id!=place_' + id + ']').each(function (d) {
            jQuery(this).hide(200);
        });
        jQuery("#place_" + id).show(200);
        jQuery(document).trigger("store_selected_pos", id);
    },
    setPlaces: function () {
        PointOfSale.stores = new Array();
        jQuery.each(PointOfSale.places, function (i, p) {

            PointOfSale.infowindow = new google.maps.InfoWindow();
            var latlng = new google.maps.LatLng(p.lat, p.lng);
            PointOfSale.markers[i] = new google.maps.Marker({
                position: latlng,
                map: PointOfSale.map,
                id: p.id
            });
            google.maps.event.addListener(PointOfSale.markers[i], 'click', function () {
                PointOfSale.displayStore(PointOfSale.getStoreIndexById(this.id));
            });
            PointOfSale.stores.push({id: p.id, position: PointOfSale.markers[i].position})
            i++;
        });
    },
    updateList: function () {
        if (jQuery('#country_place').length) {
            if (arguments[0])
                jQuery('#country_place').val(arguments[0]);
            jQuery(".place").each(function (c) {
                if (jQuery('#country_place').attr('value') != "*") {
                    jQuery(this).hide();
                } else {
                    jQuery(this).show();
                }
            });
            if (jQuery('#country_place').attr('value') != "*") {
                jQuery("." + jQuery('#country_place').attr('value')).each(function (c) {
                    jQuery(this).show();
                });
            }
        }
    },
    getDirections: function () {
        PointOfSale.updateList('*');
        if (PointOfSale.dirRenderer != null) {
            PointOfSale.dirRenderer.setMap(null);
        }
        var dirService = new google.maps.DirectionsService();
        PointOfSale.dirRenderer = new google.maps.DirectionsRenderer({suppressMarkers: true, suppressInfoWindows: true});
        jQuery('#directions').html('');
        var fromStr = W_GP.myAddress;
        var toStr = "";
        if (typeof arguments[0] == "undefined") {
            toStr = PointOfSale.places[PointOfSale.myStore.index].lat + ',' + PointOfSale.places[PointOfSale.myStore.index].lng;
        } else {
            toStr = PointOfSale.places[arguments[0]].lat + ',' + PointOfSale.places[arguments[0]].lng;
        }
        var dirRequest = {
            origin: fromStr,
            destination: toStr,
            travelMode: google.maps.DirectionsTravelMode.DRIVING,
            unitSystem: google.maps.DirectionsUnitSystem.METRIC,
            provideRouteAlternatives: true
        };
        dirService.route(dirRequest, function (dirResult, dirStatus) {
            PointOfSale.dirRenderer.setMap(PointOfSale.map);
            jQuery('#dirRendererBlock').show();
            PointOfSale.dirRenderer.setPanel(document.getElementById('directions'));
            PointOfSale.dirRenderer.setDirections(dirResult);
            PointOfSale.infowindow.close();
        });
    },
    closeDirection: function () {
        if (PointOfSale.dirRenderer != null) {
            PointOfSale.dirRenderer.setMap(null);
        }
        jQuery('#directions').text('');
        jQuery('#dirRendererBlock').hide();
    }


};

require(["jquery", "mage/mage"], function ($) {
    $(function () {
        $(document).on("store_selected_pas", function (evt, id) {
            PointOfSale.displayStore(PointOfSale.getStoreIndexById(id));
        });
        $(document).on("click", '.go-to-place', function () {
            PointOfSale.displayStore(PointOfSale.getStoreIndexById(jQuery(this).attr('id')));
        });
        $(document).on('change', '#country_place', function () {
            PointOfSale.updateList();
        });
    });
});
