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
            nextProductItem: function() {
                var url = this.url;
                var urlNoImage = this.urlNoImage;
                var page = parseInt($('#data-purchaseHistory').attr('data-page')) + 1;
                var htmlData = "";
                $.ajax({
                    url: url,
                    data:{'page':page},
                    async: false,
                    type: "GET",
                    dataType: 'json',
                    showLoader: true,
                    success: function (dataResult) {
                        if (dataResult != null) {
                            for (var i = 0; i < dataResult.length; i++) {
                                var img = (dataResult[i]['imageUrl'] != '') ? dataResult[i]['imageUrl'] : urlNoImage;
                                htmlData += ' <div class="item">';
                                htmlData += '<a href="' + dataResult[i]['url'] + '">';
                                htmlData += '<img src="' + img + '" alt="' + dataResult[i]['name'] + '">';
                                if (dataResult[i]['stock_status'] != '') {
                                    htmlData += '<div class="stock unavailable"><span>' + dataResult[i]['stock_status'] + '</span></div>';
                                }
                                htmlData += '</a>';
                                htmlData += ' <div class="product-name">' + dataResult[i]['name'] + '</div>';
                                htmlData += ' </div>';
                            }
                            $('#data-purchaseHistory').html(htmlData).attr('data-page', page);
                            if (dataResult.length < 7) {
                                $('.purchase-history .product-next').hide();
                            }
                            if (page > 1) {
                                $('.purchase-history .product-previous').show();
                            }
                        } else {
                            $('.purchase-history .product-next').hide();
                        }
                    }
                });
            },
            prevProductItem: function() {
                var url = this.url;
                var urlNoImage = this.urlNoImage;
                var page = parseInt($('#data-purchaseHistory').attr('data-page')) - 1;
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
                                htmlData += ' <div class="item">';
                                htmlData += '<a href="' + dataResult[i]['url'] + '">';
                                htmlData += '<img src="' + img + '" alt="' + dataResult[i]['name'] + '">';
                                if (dataResult[i]['stock_status'] != '') {
                                    htmlData += '<div class="stock unavailable"><span>' + dataResult[i]['stock_status'] + '</span></div>';
                                }
                                htmlData += '</a>';
                                htmlData += ' <div class="product-name">' + dataResult[i]['name'] + '</div>';
                                htmlData += ' </div>';
                            }
                            $('#data-purchaseHistory').html(htmlData).attr('data-page', page);
                            if (page <= 1) {
                                $('.purchase-history .product-previous').hide();
                            } else {
                                $('.purchase-history .product-next').show();
                            }
                        } else {
                            $('.purchase-history .product-previous').hide();
                        }
                    }
                });
            }
        });
    }
);