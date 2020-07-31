<?php
namespace Riki\NpAtobarai\Api\Data;

interface TransactionInterface
{

    const NP_TRANSACTION_ID = 'np_transaction_id';
    const REGISTER_ERROR_CODES = 'register_error_codes';
    const AUTHORIZE_PENDING_REASON_CODES = 'authorize_pending_reason_codes';
    const SHIPMENT_ID = 'shipment_id';
    const WAREHOUSE = 'warehouse';
    const CANCEL_ERROR_CODES = 'cancel_error_codes';
    const GOODS = 'goods';
    const CREATED_AT = 'created_at';
    const BILLED_AMOUNT = 'billed_amount';
    const AUTHORIZE_ERROR_CODES = 'authorize_error_codes';
    const AUTHORIZE_REQUIRED_AT = 'authorize_required_at';
    const NP_CUSTOMER_PAYMENT_DATE = 'np_customer_payment_date';
    const ORDER_SHIPPING_ADDRESS_ID = 'order_shipping_address_id';
    const DELIVERY_TYPE = 'delivery_type';
    const NP_TRANSACTION_STATUS = 'np_transaction_status';
    const IS_SHIPPED_OUT_REGISTERED = 'is_shipped_out_registered';
    const NP_CUSTOMER_PAYMENT_STATUS = 'np_customer_payment_status';
    const ORDER_ID = 'order_id';
    const AUTHORI_NG = 'authori_ng';
    const TRANSACTION_ID = 'transaction_id';
    const UPDATED_AT = 'updated_at';
    const SHIPPED_OUT_REGISTER_ERROR_CODES = 'shipped_out_register_error_codes';

    /**
     * Get transaction_id
     * @return string|null
     */
    public function getTransactionId();

    /**
     * Set transaction_id
     *
     * @param string $transactionId
     *
     * @return TransactionInterface
     */
    public function setTransactionId($transactionId);

    /**
     * Get order_id
     * @return string|null
     */
    public function getOrderId();

    /**
     * Set order_id
     *
     * @param string $orderId
     *
     * @return TransactionInterface
     */
    public function setOrderId($orderId);

    /**
     * Get order_shipping_address_id
     * @return string|null
     */
    public function getOrderShippingAddressId();

    /**
     * Set order_shipping_address_id
     *
     * @param string $orderShippingAddressId
     *
     * @return TransactionInterface
     */
    public function setOrderShippingAddressId($orderShippingAddressId);

    /**
     * Get shipment_id
     * @return string|null
     */
    public function getShipmentId();

    /**
     * Set shipment_id
     *
     * @param string $shipmentId
     *
     * @return TransactionInterface
     */
    public function setShipmentId($shipmentId);

    /**
     * Get delivery_type
     * @return string|null
     */
    public function getDeliveryType();

    /**
     * Set delivery_type
     *
     * @param string $deliveryType
     *
     * @return TransactionInterface
     */
    public function setDeliveryType($deliveryType);

    /**
     * Get warehouse
     * @return string|null
     */
    public function getWarehouse();

    /**
     * Set warehouse
     *
     * @param string $warehouse
     *
     * @return TransactionInterface
     */
    public function setWarehouse($warehouse);

    /**
     * Get billed_amount
     * @return string|null
     */
    public function getBilledAmount();

    /**
     * Set billed_amount
     * @param string $billedAmount
     * @return TransactionInterface
     */
    public function setBilledAmount($billedAmount);

    /**
     * Get np_transaction_id
     * @return string|null
     */
    public function getNpTransactionId();

    /**
     * Set np_transaction_id
     *
     * @param string $npTransactionId
     *
     * @return TransactionInterface
     */
    public function setNpTransactionId($npTransactionId);

    /**
     * @return string|null
     */
    public function getRegisterErrorCodes();

    /**
     * @param string $registerErrorCodes
     *
     * @return TransactionInterface
     */
    public function setRegisterErrorCodes($registerErrorCodes);

    /**
     * Get np_transaction_status
     * @return string|null
     */
    public function getNpTransactionStatus();

    /**
     * Set np_transaction_status
     * @param string $npTransactionStatus
     * @return TransactionInterface
     */
    public function setNpTransactionStatus($npTransactionStatus);

    /**
     * Get authorize_required_at
     * @return string|null
     */
    public function getAuthorizeRequiredAt();

    /**
     * Set authorize_required_at
     * @param string $authorizeRequiredAt
     * @return TransactionInterface
     */
    public function setAuthorizeRequiredAt($authorizeRequiredAt);

    /**
     * Get authori_ng
     * @return string|null
     */
    public function getAuthoriNg();

    /**
     * Set authori_ng
     *
     * @param string $authoriNg
     *
     * @return TransactionInterface
     */
    public function setAuthoriNg($authoriNg);

    /**
     * Get authorize_pending_reason_codes
     * @return string|null
     */
    public function getAuthorizePendingReasonCodes();

    /**
     * Set authorize_pending_reason_codes
     * @param string $authorizePendingReasonCodes
     * @return TransactionInterface
     */
    public function setAuthorizePendingReasonCodes($authorizePendingReasonCodes);

    /**
     * Get authorize_error_codes
     * @return string|null
     */
    public function getAuthorizeErrorCodes();

    /**
     * Set authorize_error_codes
     * @param string $authorizeErrorCodes
     * @return TransactionInterface
     */
    public function setAuthorizeErrorCodes($authorizeErrorCodes);

    /**
     * Get cancel_error_codes
     * @return string|null
     */
    public function getCancelErrorCodes();

    /**
     * Set cancel_error_codes
     * @param string $cancelErrorCodes
     * @return TransactionInterface
     */
    public function setCancelErrorCodes($cancelErrorCodes);

    /**
     * Get is_shipped_out_registered
     * @return string|null
     */
    public function getIsShippedOutRegistered();

    /**
     * Set is_shipped_out_registered
     * @param string $isShippedOutRegistered
     * @return TransactionInterface
     */
    public function setIsShippedOutRegistered($isShippedOutRegistered);

    /**
     * Get shipped_out_register_error_codes
     * @return string|null
     */
    public function getShippedOutRegisterErrorCodes();

    /**
     * Set shipped_out_register_error_codes
     *
     * @param string $shippedOutRegisterErrorCodes
     *
     * @return TransactionInterface
     */
    public function setShippedOutRegisterErrorCodes($shippedOutRegisterErrorCodes);

    /**
     * Get np_customer_payment_status
     * @return string|null
     */
    public function getNpCustomerPaymentStatus();

    /**
     * Set np_customer_payment_status
     * @param string $npCustomerPaymentStatus
     * @return TransactionInterface
     */
    public function setNpCustomerPaymentStatus($npCustomerPaymentStatus);

    /**
     * Get np_customer_payment_date
     * @return string|null
     */
    public function getNpCustomerPaymentDate();

    /**
     * Set np_customer_payment_date
     * @param string $npCustomerPaymentDate
     * @return TransactionInterface
     */
    public function setNpCustomerPaymentDate($npCustomerPaymentDate);

    /**
     * Get goods
     * @return string|null
     */
    public function getGoods();

    /**
     * Set goods
     * @param string $goods
     * @return TransactionInterface
     */
    public function setGoods($goods);

    /**
     * Get updated_at
     * @return string|null
     */
    public function getUpdatedAt();

    /**
     * Set updated_at
     * @param string $updatedAt
     * @return TransactionInterface
     */
    public function setUpdatedAt($updatedAt);

    /**
     * Get created_at
     * @return string|null
     */
    public function getCreatedAt();

    /**
     * Set created_at
     * @param string $createdAt
     * @return TransactionInterface
     */
    public function setCreatedAt($createdAt);
}
