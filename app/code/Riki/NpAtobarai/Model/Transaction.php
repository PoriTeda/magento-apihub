<?php

namespace Riki\NpAtobarai\Model;

use Riki\NpAtobarai\Api\Data\TransactionInterface;
use Riki\NpAtobarai\Model\Config\Source\TransactionPaymentStatus;

class Transaction extends \Magento\Framework\Model\AbstractModel implements TransactionInterface
{
    const REGISTERED_SHIPPED_OUT = 1;
    const NOT_REGISTERED_SHIPPED_OUT_YET = 0;

    /**
     * @var string
     */
    protected $_eventPrefix = 'riki_npatobarai_transaction';

    /**
     * @var \Magento\Sales\Model\Order\ShipmentRepository
     */
    protected $shipmentRepository;

    /**
     * @var \Magento\Sales\Model\OrderRepository
     */
    protected $orderRepository;

    /**
     * @var \Magento\Sales\Model\Order\AddressRepository
     */
    protected $orderAddressRepository;

    /**
     * Transaction constructor.
     * @param \Magento\Sales\Model\Order\ShipmentRepository $shipmentRepository
     * @param \Magento\Sales\Model\OrderRepository $orderRepository
     * @param \Magento\Sales\Model\Order\AddressRepository $orderAddressRepository
     * @param \Magento\Framework\Model\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\Model\ResourceModel\AbstractResource|null $resource
     * @param \Magento\Framework\Data\Collection\AbstractDb|null $resourceCollection
     * @param array $data
     */
    public function __construct(
        \Magento\Sales\Model\Order\ShipmentRepository $shipmentRepository,
        \Magento\Sales\Model\OrderRepository $orderRepository,
        \Magento\Sales\Model\Order\AddressRepository $orderAddressRepository,
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        $this->shipmentRepository = $shipmentRepository;
        $this->orderRepository = $orderRepository;
        $this->orderAddressRepository = $orderAddressRepository;
        parent::__construct($context, $registry, $resource, $resourceCollection, $data);
    }

    /**
     * @return void
     */
    protected function _construct()
    {
        $this->_init(\Riki\NpAtobarai\Model\ResourceModel\Transaction::class);
    }

    /**
     * Get transaction_id
     * @return string
     */
    public function getTransactionId()
    {
        return $this->getData(self::TRANSACTION_ID);
    }

    /**
     * Set transaction_id
     *
     * @param string $transactionId
     *
     * @return TransactionInterface
     */
    public function setTransactionId($transactionId)
    {
        return $this->setData(self::TRANSACTION_ID, $transactionId);
    }

    /**
     * Get order_id
     * @return string
     */
    public function getOrderId()
    {
        return $this->getData(self::ORDER_ID);
    }

    /**
     * Set order_id
     *
     * @param string $orderId
     *
     * @return TransactionInterface
     */
    public function setOrderId($orderId)
    {
        return $this->setData(self::ORDER_ID, $orderId);
    }

    /**
     * Get order_shipping_address_id
     * @return string
     */
    public function getOrderShippingAddressId()
    {
        return $this->getData(self::ORDER_SHIPPING_ADDRESS_ID);
    }

    /**
     * Set order_shipping_address_id
     *
     * @param string $orderShippingAddressId
     *
     * @return TransactionInterface
     */
    public function setOrderShippingAddressId($orderShippingAddressId)
    {
        return $this->setData(self::ORDER_SHIPPING_ADDRESS_ID, $orderShippingAddressId);
    }

    /**
     * Get shipment_id
     * @return string
     */
    public function getShipmentId()
    {
        return $this->getData(self::SHIPMENT_ID);
    }

    /**
     * Set shipment_id
     *
     * @param string $shipmentId
     *
     * @return TransactionInterface
     */
    public function setShipmentId($shipmentId)
    {
        return $this->setData(self::SHIPMENT_ID, $shipmentId);
    }

    /**
     * Get delivery_type
     * @return string
     */
    public function getDeliveryType()
    {
        return $this->getData(self::DELIVERY_TYPE);
    }

    /**
     * Set delivery_type
     *
     * @param string $deliveryType
     *
     * @return TransactionInterface
     */
    public function setDeliveryType($deliveryType)
    {
        return $this->setData(self::DELIVERY_TYPE, $deliveryType);
    }

    /**
     * Get warehouse
     * @return string
     */
    public function getWarehouse()
    {
        return $this->getData(self::WAREHOUSE);
    }

    /**
     * Set warehouse
     *
     * @param string $warehouse
     *
     * @return TransactionInterface
     */
    public function setWarehouse($warehouse)
    {
        return $this->setData(self::WAREHOUSE, $warehouse);
    }

    /**
     * Get billed_amount
     * @return string
     */
    public function getBilledAmount()
    {
        return $this->getData(self::BILLED_AMOUNT);
    }

    /**
     * Set billed_amount
     * @param string $billedAmount
     * @return TransactionInterface
     */
    public function setBilledAmount($billedAmount)
    {
        return $this->setData(self::BILLED_AMOUNT, $billedAmount);
    }

    /**
     * Get np_transaction_id
     * @return string
     */
    public function getNpTransactionId()
    {
        return $this->getData(self::NP_TRANSACTION_ID);
    }

    /**
     * Set np_transaction_id
     *
     * @param string $npTransactionId
     *
     * @return TransactionInterface
     */
    public function setNpTransactionId($npTransactionId)
    {
        return $this->setData(self::NP_TRANSACTION_ID, $npTransactionId);
    }

    /**
     * @return string
     */
    public function getRegisterErrorCodes()
    {
        return $this->getData(self::REGISTER_ERROR_CODES);
    }

    /**
     * @param string $registerErrorCodes
     *
     * @return TransactionInterface
     */
    public function setRegisterErrorCodes($registerErrorCodes)
    {
        return $this->setData(self::REGISTER_ERROR_CODES, $registerErrorCodes);
    }

    /**
     * Get np_transaction_status
     * @return string
     */
    public function getNpTransactionStatus()
    {
        return $this->getData(self::NP_TRANSACTION_STATUS);
    }

    /**
     * Set np_transaction_status
     * @param string $npTransactionStatus
     * @return TransactionInterface
     */
    public function setNpTransactionStatus($npTransactionStatus)
    {
        return $this->setData(self::NP_TRANSACTION_STATUS, $npTransactionStatus);
    }

    /**
     * Get authorize_required_at
     * @return string
     */
    public function getAuthorizeRequiredAt()
    {
        return $this->getData(self::AUTHORIZE_REQUIRED_AT);
    }

    /**
     * Set authorize_required_at
     * @param string $authorizeRequiredAt
     * @return TransactionInterface
     */
    public function setAuthorizeRequiredAt($authorizeRequiredAt)
    {
        return $this->setData(self::AUTHORIZE_REQUIRED_AT, $authorizeRequiredAt);
    }

    /**
     * Get authori_ng
     * @return string
     */
    public function getAuthoriNg()
    {
        return $this->getData(self::AUTHORI_NG);
    }

    /**
     * Set authori_ng
     *
     * @param string $authoriNg
     *
     * @return TransactionInterface
     */
    public function setAuthoriNg($authoriNg)
    {
        return $this->setData(self::AUTHORI_NG, $authoriNg);
    }

    /**
     * Get authorize_pending_reason_codes
     * @return string
     */
    public function getAuthorizePendingReasonCodes()
    {
        return $this->getData(self::AUTHORIZE_PENDING_REASON_CODES);
    }

    /**
     * Set authorize_pending_reason_codes
     * @param string $authorizePendingReasonCodes
     * @return TransactionInterface
     */
    public function setAuthorizePendingReasonCodes($authorizePendingReasonCodes)
    {
        return $this->setData(self::AUTHORIZE_PENDING_REASON_CODES, $authorizePendingReasonCodes);
    }

    /**
     * Get authorize_error_codes
     * @return string
     */
    public function getAuthorizeErrorCodes()
    {
        return $this->getData(self::AUTHORIZE_ERROR_CODES);
    }

    /**
     * Set authorize_error_codes
     * @param string $authorizeErrorCodes
     * @return TransactionInterface
     */
    public function setAuthorizeErrorCodes($authorizeErrorCodes)
    {
        return $this->setData(self::AUTHORIZE_ERROR_CODES, $authorizeErrorCodes);
    }

    /**
     * Get cancel_error_codes
     * @return string
     */
    public function getCancelErrorCodes()
    {
        return $this->getData(self::CANCEL_ERROR_CODES);
    }

    /**
     * Set cancel_error_codes
     * @param string $cancelErrorCodes
     * @return TransactionInterface
     */
    public function setCancelErrorCodes($cancelErrorCodes)
    {
        return $this->setData(self::CANCEL_ERROR_CODES, $cancelErrorCodes);
    }

    /**
     * Get is_shipped_out_registered
     * @return string
     */
    public function getIsShippedOutRegistered()
    {
        return $this->getData(self::IS_SHIPPED_OUT_REGISTERED);
    }

    /**
     * Set is_shipped_out_registered
     * @param string $isShippedOutRegistered
     * @return TransactionInterface
     */
    public function setIsShippedOutRegistered($isShippedOutRegistered)
    {
        return $this->setData(self::IS_SHIPPED_OUT_REGISTERED, $isShippedOutRegistered);
    }

    /**
     * Get shipped_out_register_error_codes
     * @return string
     */
    public function getShippedOutRegisterErrorCodes()
    {
        return $this->getData(self::SHIPPED_OUT_REGISTER_ERROR_CODES);
    }

    /**
     * Set shipped_out_register_errors_codes
     *
     * @param string $shippedOutRegisterErrorCodes
     *
     * @return TransactionInterface
     */
    public function setShippedOutRegisterErrorCodes($shippedOutRegisterErrorCodes)
    {
        return $this->setData(self::SHIPPED_OUT_REGISTER_ERROR_CODES, $shippedOutRegisterErrorCodes);
    }

    /**
     * Get np_customer_payment_status
     * @return string
     */
    public function getNpCustomerPaymentStatus()
    {
        return $this->getData(self::NP_CUSTOMER_PAYMENT_STATUS);
    }

    /**
     * Set np_customer_payment_status
     * @param string $npCustomerPaymentStatus
     * @return TransactionInterface
     */
    public function setNpCustomerPaymentStatus($npCustomerPaymentStatus)
    {
        return $this->setData(self::NP_CUSTOMER_PAYMENT_STATUS, $npCustomerPaymentStatus);
    }

    /**
     * Get np_customer_payment_date
     * @return string
     */
    public function getNpCustomerPaymentDate()
    {
        return $this->getData(self::NP_CUSTOMER_PAYMENT_DATE);
    }

    /**
     * Set np_customer_payment_date
     * @param string $npCustomerPaymentDate
     * @return TransactionInterface
     */
    public function setNpCustomerPaymentDate($npCustomerPaymentDate)
    {
        return $this->setData(self::NP_CUSTOMER_PAYMENT_DATE, $npCustomerPaymentDate);
    }

    /**
     * Get goods
     * @return string
     */
    public function getGoods()
    {
        return $this->getData(self::GOODS);
    }

    /**
     * Set goods
     * @param string $goods
     * @return TransactionInterface
     */
    public function setGoods($goods)
    {
        return $this->setData(self::GOODS, $goods);
    }

    /**
     * Get updated_at
     * @return string
     */
    public function getUpdatedAt()
    {
        return $this->getData(self::UPDATED_AT);
    }

    /**
     * Set updated_at
     * @param string $updatedAt
     * @return TransactionInterface
     */
    public function setUpdatedAt($updatedAt)
    {
        return $this->setData(self::UPDATED_AT, $updatedAt);
    }

    /**
     * Get created_at
     * @return string
     */
    public function getCreatedAt()
    {
        return $this->getData(self::CREATED_AT);
    }

    /**
     * Set created_at
     * @param string $createdAt
     * @return TransactionInterface
     */
    public function setCreatedAt($createdAt)
    {
        return $this->setData(self::CREATED_AT, $createdAt);
    }

    /**
     * @return \Magento\Sales\Api\Data\OrderInterface
     * @throws \Magento\Framework\Exception\InputException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getOrder()
    {
        return $this->orderRepository->get($this->getOrderId());
    }

    /**
     * @return \Magento\Sales\Api\Data\ShipmentInterface
     * @throws \Magento\Framework\Exception\InputException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getShipment()
    {
        return $this->shipmentRepository->get($this->getShipmentId());
    }

    /**
     * @return \Magento\Sales\Api\Data\OrderAddressInterface
     * @throws \Magento\Framework\Exception\InputException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getShippingAddress()
    {
        return $this->orderAddressRepository->get($this->getOrderShippingAddressId());
    }

    /**
     * Is Transaction paid
     *
     * @return bool
     */
    public function isTransactionPaid()
    {
        $paymentStatus = $this->getNpCustomerPaymentStatus();
        if ($paymentStatus == TransactionPaymentStatus::PAID_STATUS_VALUE) {
            return true;
        }

        return false;
    }
}
