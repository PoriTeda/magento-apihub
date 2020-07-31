<?php

namespace Riki\Subscription\Model\Emulator;

class Config
{
    CONST TMP_TABLE_PREFIX = 'emulator_';

    CONST TMP_TABLE_SUFFIX = '_tmp';

    CONST CART_TABLE_NAME = 'quote';

    CONST CART_ITEM_TABLE_NAME = 'quote_item';

    CONST CART_ADDRESS_ITEM_TABLE_NAME = 'quote_address_item';

    CONST CART_ADDRESS_TABLE_NAME = 'quote_address';

    CONST CART_ITEM_OPTION = 'quote_item_option';

    CONST CART_PAYMENT = 'quote_payment';

    CONST CART_SHIPPING_RATE = 'quote_shipping_rate';

    CONST ORDER_TABLE_NAME = 'sales_order';

    CONST ORDER_ADDRESS_TABLE_NAME = 'sales_order_address';

    CONST ORDER_ITEM_TABLE_NAME = 'sales_order_item';

    CONST ORDER_PAYMENT_TABLE_NAME = 'sales_order_payment';

    CONST ORDER_TAX_TABLE_NAME = 'sales_order_tax';

    CONST ORDER_TAX_ITEM_TABLE_NAME = 'sales_order_tax_item';

    CONST SHIPMENT_TABLE_NAME = 'sales_shipment';

    CONST SHIPMENT_ITEM_TABLE_NAME = 'sales_shipment_item';

    CONST SHIPMENT_TRACK_TABLE_NAME = 'sales_shipment_track';

    CONST SHIPMENT_COMMENT_TABLE_NAME = 'sales_shipment_comment';

    CONST INVOICE_TABLE_NAME = 'sales_invoice';

    CONST INVOICE_ITEM_TABLE_NAME = 'sales_invoice_item';

    CONST INVOICE_COMMENT_TABLE_NAME = 'sales_invoice_comment';

    CONST ORDER_ADDRESS_ITEM_TABLE_NAME = 'order_address_item';

    CONST ORDER_STATUS_HISTORY_TABLE_NAME = 'sales_order_status_history';

    CONST RIKI_REWARD_QUOTE = 'riki_reward_quote';

    public static function getOrderStatusHistoryTmpTableName()
    {
        return self::TMP_TABLE_PREFIX . self::ORDER_STATUS_HISTORY_TABLE_NAME . self::TMP_TABLE_SUFFIX;
    }

    public static function getOrderAddressItemTmpTableName()
    {
        return self::TMP_TABLE_PREFIX . self::ORDER_ADDRESS_ITEM_TABLE_NAME . self::TMP_TABLE_SUFFIX;
    }

    public static function getOrderTmpTableName()
    {
        return self::TMP_TABLE_PREFIX . self::ORDER_TABLE_NAME . self::TMP_TABLE_SUFFIX;
    }

    public static function getOrderAddressTmpTableName()
    {
        return self::TMP_TABLE_PREFIX . self::ORDER_ADDRESS_TABLE_NAME . self::TMP_TABLE_SUFFIX;
    }

    public static function getOrderItemTmpTableName()
    {
        return self::TMP_TABLE_PREFIX . self::ORDER_ITEM_TABLE_NAME . self::TMP_TABLE_SUFFIX;
    }

    public static function getOrderPaymentTmpTableName()
    {
        return self::TMP_TABLE_PREFIX . self::ORDER_PAYMENT_TABLE_NAME . self::TMP_TABLE_SUFFIX;
    }

    public static function getOrderTaxTmpTableName()
    {
        return self::TMP_TABLE_PREFIX . self::ORDER_TAX_TABLE_NAME . self::TMP_TABLE_SUFFIX;
    }

    public static function getOrderTaxItemTmpTableName()
    {
        return self::TMP_TABLE_PREFIX . self::ORDER_TAX_ITEM_TABLE_NAME . self::TMP_TABLE_SUFFIX;
    }

    public static function getShipmentTmpTableName()
    {
        return self::TMP_TABLE_PREFIX . self::SHIPMENT_TABLE_NAME . self::TMP_TABLE_SUFFIX;
    }

    public static function getShipmentItemTmpTableName()
    {
        return self::TMP_TABLE_PREFIX . self::SHIPMENT_ITEM_TABLE_NAME . self::TMP_TABLE_SUFFIX;
    }

    public static function getShipmentTrackTmpTableName()
    {
        return self::TMP_TABLE_PREFIX . self::SHIPMENT_TRACK_TABLE_NAME . self::TMP_TABLE_SUFFIX;
    }

    public static function getShipmentCommentTmpTableName()
    {
        return self::TMP_TABLE_PREFIX . self::SHIPMENT_COMMENT_TABLE_NAME . self::TMP_TABLE_SUFFIX;
    }

    public static function getInvoiceTmpTableName()
    {
        return self::TMP_TABLE_PREFIX . self::INVOICE_TABLE_NAME . self::TMP_TABLE_SUFFIX;
    }

    public static function getInvoiceItemTmpTableName()
    {
        return self::TMP_TABLE_PREFIX . self::INVOICE_ITEM_TABLE_NAME . self::TMP_TABLE_SUFFIX;
    }

    public static function getInvoiceCommentTmpTableName()
    {
        return self::TMP_TABLE_PREFIX . self::INVOICE_COMMENT_TABLE_NAME . self::TMP_TABLE_SUFFIX;
    }

    public static function getCartPaymentTmpTableName()
    {
        return self::TMP_TABLE_PREFIX . self::CART_PAYMENT . self::TMP_TABLE_SUFFIX;
    }

    public static function getCartShippingRateTmpTableName()
    {
        return self::TMP_TABLE_PREFIX . self::CART_SHIPPING_RATE . self::TMP_TABLE_SUFFIX;
    }

    public static function getCartItemTmpTableName()
    {
        return self::TMP_TABLE_PREFIX . self::CART_ITEM_TABLE_NAME . self::TMP_TABLE_SUFFIX;
    }

    public static function getCartItemOptionTmpTableName()
    {
        return self::TMP_TABLE_PREFIX . self::CART_ITEM_OPTION . self::TMP_TABLE_SUFFIX;
    }

    public static function getCartTmpTableName()
    {
        return self::TMP_TABLE_PREFIX . self::CART_TABLE_NAME . self::TMP_TABLE_SUFFIX;
    }

    public static function getAddressTmpTableName()
    {
        return self::TMP_TABLE_PREFIX . self::CART_ADDRESS_TABLE_NAME . self::TMP_TABLE_SUFFIX;
    }

    public static function getAddressItemTmpTableName()
    {
        return self::TMP_TABLE_PREFIX . self::CART_ADDRESS_ITEM_TABLE_NAME . self::TMP_TABLE_SUFFIX;
    }

    public static function getRikiRewardQuoteTmpTableName()
    {
        return self::TMP_TABLE_PREFIX . self::RIKI_REWARD_QUOTE . self::TMP_TABLE_SUFFIX;
    }
}
