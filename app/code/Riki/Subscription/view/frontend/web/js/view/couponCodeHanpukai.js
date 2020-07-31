define([
    'jquery',
    'ko',
    'uiComponent',
    'mage/storage',
    'mage/loader',
    'Magento_Ui/js/model/messageList',
    'mage/translate',
    'Magento_Ui/js/modal/modal',
    'mage/url'
], function ($, ko, Component, storage, loader, messageList, $t, modal, urlBuilder) {
    return Component.extend({
        initialize: function (config, element) {
            this._super();

            $('body').on('click', '.delete-coupon', function () {
                var profileId = $(this).attr('data-profile-id');
                var couponCode = $(this).attr('data-coupon-code');
                if (couponCode != '') {
                    $('body').trigger('processStart');
                    $.ajax({
                        url: urlBuilder.build('subscriptions/profile/validateCouponCode'),
                        method: 'POST',
                        dataType: 'json',
                        data: {
                            'profile_id': profileId,
                            'coupon_code': couponCode,
                            'action': 'delete'
                        },
                        async: true,
                        success: function (result) {
                            var data = JSON.parse(result);
                            $('#listCouponApplied').html(data.dataHtml);
                            $('#coupon_code_fake').val('');
                            $('#messageAppliedCouponSuccess').html('');
                            $('#messageAppliedCouponError').html('');

                            if (data.is_validate) {
                                $('#messageAppliedCouponSuccess').html(data.message).css("color", "green");
                                if (data.showInputCoupon)
                                {
                                    $('#showHidenCouponInput').show();

                                }else {
                                    $('#showHidenCouponInput').hide();
                                }
                            } else {
                                $('#messageAppliedCouponError').html(data.message).css("color", "red");
                            }
                            $('body').trigger('processStop');
                        },
                        error: function () {
                            $('body').trigger('processStop');
                        }
                    });
                }
            })
        },

        loadAjax: function (profileId, couponCode, action) {
            $('body').trigger('processStart');
            $.ajax({
                url: urlBuilder.build('/subscriptions/profile/validateCouponCode'),
                method: 'POST',
                dataType: 'json',
                data: {
                    'profile_id': profileId,
                    'coupon_code': couponCode,
                    'action': action
                },
                async: true,
                success: function (result) {
                    var data = JSON.parse(result);
                    $('#listCouponApplied').html(data.dataHtml);
                    $('#coupon_code_fake').val('');
                    $('#messageAppliedCouponSuccess').html('');
                    $('#messageAppliedCouponError').html('');
                    if (data.is_validate) {
                        $('#messageAppliedCouponSuccess').html(data.message).css("color", "green");
                        if (data.showInputCoupon)
                        {
                            $('#showHidenCouponInput').show();
                        }else {
                            $('#showHidenCouponInput').hide();
                        }
                    } else {
                        $('#messageAppliedCouponError').html(data.message).css("color", "red");
                    }
                    $('body').trigger('processStop');
                },
                error: function () {
                    $('body').trigger('processStop');
                }
            });
        },

        checkKeyPress: function (data, event) {
            if (event.keyCode == 13 || event.which == 13) {
                event.preventDefault();
                $('.actions-toolbar-coupon button.applyCoupon').trigger('click');
            }
            return true
        },

        applyCoupon: function (profileId) {
            var couponCode = $('#coupon_code_fake').val();
            if (couponCode != '') {
                this.loadAjax(profileId, couponCode, 'add');
            }
        },

        deleteCouponCode: function (profileId, couponCode) {
            if (couponCode != '') {
                this.loadAjax(profileId, couponCode, 'delete');
            }
        }

    });
});