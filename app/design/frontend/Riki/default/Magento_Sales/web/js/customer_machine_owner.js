define(
    [
        'jquery',
        'ko',
        'uiComponent'
    ],
    function($,ko,Component) {
        'use strict';
        return Component.extend({
            initialize: function() {
                var self = this;
                this._super();
                return this;
            },
            nextProductMachineItem: function() {
                var url = this.url;
                var urlNoImage = this.urlNoImage;
                var page = parseInt($('#data-machine0wner').attr('data-page')) + 1;
                var htmlData = "";
                $.ajax({
                    url: url,
                    data:{'page':page},
                    async: false,
                    type: "GET",
                    dataType: 'json',
                    success: function (dataResult) {
                        if (dataResult != null) {
                            for (var i = 0; i < dataResult.length; i++) {
                                var img = (dataResult[i]['imageUrl'] != '') ? dataResult[i]['imageUrl'] : urlNoImage;
                                htmlData += '<a href="' + dataResult[i]['url'] + '">';
                                htmlData += '<img src="' + img + '" alt="' + dataResult[i]['name'] + '">';
                                if (dataResult[i]['stock_status'] != '') {
                                    htmlData += '<div class="stock unavailable"><span>' + dataResult[i]['stock_status'] + '</span></div>';
                                }
                                htmlData += '</a>';
                            }
                            $('#data-machine0wner').html(htmlData).attr('data-page', page);
                            if (dataResult.length < 7) {
                                $('.machine-owner .product-next').hide();
                            }
                            if (page > 1) {
                                $('.machine-owner .product-previous').show();
                            }
                        } else {
                            $('.machine-owner .product-next').hide();
                        }
                    }
                });
            },
            prevProductMachineItem: function() {
                var url = this.url;
                var urlNoImage = this.urlNoImage;
                var page = parseInt($('#data-machine0wner').attr('data-page')) - 1;
                var htmlData = "";
                $.ajax({
                    url: url,
                    data:{'page':page},
                    async: false,
                    type: "GET",
                    dataType: 'json',
                    success: function (dataResult) {
                        if (dataResult != null) {
                            for (var i = 0; i < dataResult.length; i++) {
                                var img = (dataResult[i]['imageUrl'] != '') ? dataResult[i]['imageUrl'] : urlNoImage;
                                htmlData += '<a href="' + dataResult[i]['url'] + '">';
                                htmlData += '<img src="' + img + '" alt="' + dataResult[i]['name'] + '">';
                                if (dataResult[i]['stock_status'] != '') {
                                    htmlData += '<div class="stock unavailable"><span>' + dataResult[i]['stock_status'] + '</span></div>';
                                }
                                htmlData += '</a>';
                            }
                            $('#data-machine0wner').html(htmlData).attr('data-page', page);
                            if (page <= 1) {
                                $('.machine-owner .product-previous').hide();
                            } else {
                                $('.machine-owner .product-next').show();
                            }
                        } else {
                            $('.machine-owner .product-previous').hide();
                        }
                    }
                });
            }
        });
    }
);