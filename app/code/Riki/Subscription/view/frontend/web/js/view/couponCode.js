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
                            $('#form-submit-profile').submit();
                            localStorage.setItem('isReloadPageEdit','isReloadPageEdit');
                        }
                    });
                }
            })

            if($('#messageAppliedCoupon').length)
            {
                //show or remove message when load page
                if(localStorage.getItem('isReloadPageEdit')=='isReloadPageEdit')
                {
                    localStorage.removeItem('isReloadPageEdit');
                    $('html,body').animate({
                        scrollTop: $('#listCouponApplied').offset().top-200
                    }, 700);
                    $('#messageAppliedCoupon').show();
                }else{
                    $('#messageAppliedCoupon').html('');
                }
            }
        },

        checkKeyPress: function (data, event) {
            if (event.keyCode == 13 || event.which == 13) {
                event.preventDefault();
                $('.actions-toolbar-coupon button.applyCoupon').trigger('click');
            }
            return true
        },

        loadAjax: function (profileId, couponCode, action) {
            $('body').trigger('processStart');
            $.ajax({
                url: urlBuilder.build('subscriptions/profile/validateCouponCode'),
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
                    if (data.is_validate) {
                        localStorage.setItem('isReloadPageEdit','isReloadPageEdit');
                        $('#form-submit-profile').append('<input type="hidden" name="not_validate_amount" value="1" />');
                        $('#form-submit-profile').submit();
                    } else {
                        $('#messageAppliedCoupon').html('');
                        $('#messageAppliedCoupon').html(data.message).css("color", "red").show();
                        $('#coupon_code_fake').val('');
                        $('body').trigger('processStop');
                    }
                }
            });
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