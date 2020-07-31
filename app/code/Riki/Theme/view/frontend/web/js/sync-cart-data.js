define([
    'jquery',
    'ko',
    'Magento_Customer/js/customer-data',
    'Magento_Ui/js/model/messageList',
    'mage/url',
    'underscore',
    'mage/cookies'
], function ($, ko, customerData, messageList, urlBuilder, _) {
    var minicartCheckout = window.minicartCheckout;
    var url = {};
    if (minicartCheckout != undefined) {
        url = {
            add: minicartCheckout.spotAddItem,
            remove: minicartCheckout.removeItemUrl,
            update: minicartCheckout.updateItemQtyCustomUrl,
            updateGiftWrapping: minicartCheckout.updateGiftWrapping
        };
    }

    const syncCartData = {
        /**
         * @param {HTMLElement} elem
         * @param data
         * @param whenSuccess
         * @param whenFail
         * @private
         */
        _updateItemQty: function (elem, data, whenSuccess, whenFail) {
            var updateUrl = url.update;
            var id;
            if (data['product_id'] && !data['item_id'] && !data.item['isInRealCart']){
                updateUrl = url.add;
                id = data['product_id'];
            } else{
                id = data['item_id'];
            }
            if (parseInt(data['item_qty']) === 0) {
                updateUrl = url.remove
            }
            this._ajax(updateUrl, {
                'item': data.item,
                'item_id': id,
                'item_qty': data['item_qty'],
                'show_warning': true
            }, elem, this._updateItemQtyAfter, whenSuccess, whenFail);
        },

        _updateItemQtyAfter: function (elem) {
        },
        /**
         *
         * @param url
         * @param data
         * @param elem
         * @param callback
         * @param whenSuccess
         * @param whenFail
         * @param viewModel
         * @private
         */
        _ajax: function (url, data, elem, callback, whenSuccess, whenFail) {
            $.extend(data, {
                'form_key': $.mage.cookies.get('form_key')
            });

            $.ajax({
                url: url,
                data: data,
                type: 'post',
                dataType: 'json',
                context: this,

                /** @inheritdoc */
                beforeSend: function () {
                    $("body").trigger("processStart");
                    if (_.isArray(elem)) {
                        _.each(elem, function (e) {
                            if (e.length > 0) {
                                e.addClass('disabled');
                                e.attr('disabled', true);
                            }
                        });
                    } else {
                        if (elem.length > 0) {
                            elem.addClass('disabled');
                            elem.attr('disabled', false);
                        }
                    }

                },

                /** @inheritdoc */
                complete: function () {
                    for (var i = 0; i < 10; i++) {
                        $("body").trigger("processStop");
                    }
                    setTimeout(function () {
                        if (_.isArray(elem)) {
                            _.each(elem, function (e) {
                                if (e.length > 0) {
                                    e.removeClass('disabled');
                                    e.attr('disabled', false);
                                }
                            });
                        } else {
                            if (elem.length > 0) {
                                elem.removeClass('disabled');
                                elem.attr('disabled', false);
                            }
                        }
                    }, 1800);
                }
            })
                .done(function (response) {
                    var msg;
                    if (response.success) {
                        callback.call(this, elem, response);
                        if(data.item != null && data.item.qtySelected() != 0){
                            if(response.id) {
                                data.item['item_id'] = response.id;
                            }
                            data.item['lastQtySelected'] = data.item['qtySelected']();
                            data.item['isInRealCart'] = true;
                        } else if(data.item != null && data.item.qtySelected() == 0){
                            delete data.item['item_id'];
                            delete data.item['isInRealCart'];
                        }
                        if (typeof whenSuccess === 'function') {
                            whenSuccess.call(this, elem, response);
                        }
                    } else {
                        msg = response['error_message'];
                        if (msg) {
                            this.showErrorMessage(msg);
                        }
                        if (typeof whenFail === 'function') {
                            whenFail.call(this, elem, response);
                        }
                        data.item['updateFail'](true);
                        console.log(msg);
                    }
                })
                .fail(function (error) {
                    console.log(JSON.stringify(error));
                    data.item['updateFail'](true);
                    if (typeof whenFail === 'function') {
                        whenFail.call(this, elem, error);
                    }
                });
        },
        showErrorMessage: function (msg) {
            const self = this;
            messageList.addErrorMessage({'message': msg});
            const dataPlaceHolderElem = $('[data-placeholder="messages"]');

            dataPlaceHolderElem.html(msg);
            if (msg !== "") {
                $("body").trigger("show_error_message");
                dataPlaceHolderElem.addClass("message error");
            } else {
                dataPlaceHolderElem.removeClass("message error");
            }
        },
    };

    return syncCartData;
});