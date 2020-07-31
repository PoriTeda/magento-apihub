<?php

namespace Riki\Subscription\Model;

class Constant
{
    
    const QUOTE_RIKI_COURSE_ID = 'riki_course_id';
    const SUBSCRIPTION_PROFILE_HAS_CHANGED = 'profile_has_changed';

    const RIKI_COURSE_ID = 'riki_course_id';
    const RIKI_FREQUENCY_UNIT = 'riki_frequency_unit';
    const RIKI_FREQUENCY_INTERVAL = 'riki_frequency_interval';
    const RIKI_FREQUENCY_ID = 'riki_frequency_id'; // Inside table frequency id
    const RIKI_HANPUKAI_QTY = 'riki_hanpukai_qty'; // hanpukai multi select qty
    const POINT_FOR_TRIAL = 'point_for_trial'; //RMM-375

    const DISCOUNT_PRICE_SUBSCRIPTION = 'discount_price_subscription';

    const SESSION_PROFILE_EDIT = 'riki_profile_edit';
    const CACHE_PROFILE_PRODUCT_CART = 'product_cart';

    const CACHE_BTN_UPDATE_PRESSED = 'btn_update_all_changes_pressed';
    const CACHE_BTN_CREATE_ORDER_PRESSED = 'btn_create_order_pressed';

    const BO_SAVE_SPOT_POST_PARAM_PROFILE_ID = 'profile_id';
    const BO_SAVE_SPOT_POST_PARAM_NEW_PRODUCT_ID = 'product_id';
    const BO_SAVE_SPOT_POST_PARAM_NEW_PRODUCT_QTY = 'qty';
    const BO_SAVE_SPOT_POST_PARAM_NEW_PRODUCT_OPTIONS = 'product_options';
    const BO_SAVE_SPOT_POST_PARAM_NEW_PRODUCT_UNIT_CASE = 'unit_case';
    const BO_SAVE_SPOT_POST_PARAM_NEW_PRODUCT_UNIT_QTY = 'unit_qty';
    const BO_SAVE_SPOT_POST_PARAM_NEW_PRODUCT_GW_ID = 'gw_id';
    const BO_SAVE_SPOT_POST_PARAM_NEW_PRODUCT_GIFT_MESSAGE_ID = 'gift_message_id';
    const ADD_SPOT_PRODUCT_ERROR_SPOT_PRODUCT_IS_EXIST_LIKE_MAIN_PRODUCT = 1;
    const ADD_SPOT_PRODUCT_ERROR_SPORT_PRODUCT_IS_EXIST_LIKE_SPOT = 2;

    const REGISTRY_EDIT_HANPUKAI_DATA_SUBSCRIPTION_PAYMENT_METHOD = 'subscription-payment-method';
    const REGISTRY_EDIT_HANPUKAI_DATA_SUBSCRIPTION_CHANGE_TYPE = 'subscription-change-type';
    const REGISTRY_EDIT_HANPUKAI_DATA_SUBSCRIPTION_PROFILE_ID = 'subscription-profile-id';
    const REGISTRY_EDIT_HANPUKAI_DATA_SUBSCRIPTION_Preferred_Payment_Method = 'subscription-preferred-payment-method';

    const ERROR_SUBSCRIPTION_COURSE_MUST_SELECT_SKU = 3;
    const ERROR_SUBSCRIPTION_COURSE_MINIMUM_QTY= 4;
    const ERROR_SUBSCRIPTION_COURSE_MUST_HAVE_QTY_CATEGORY = 5;

    const REGISTRY_EDIT_HANPUKAI_DATA_SUBSCRIPTION_COUPON_CODE = 'subscription-profile-hankpukai-coupon-code';

}