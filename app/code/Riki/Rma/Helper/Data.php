<?php

namespace Riki\Rma\Helper;

use Riki\Rma\Api\ConfigInterface;
use Riki\Rma\Api\Data\Rma\ReturnStatusInterface;
use \Magento\OfflinePayments\Model\Cashondelivery;
use \Riki\NpAtobarai\Model\Payment\NpAtobarai;

class Data extends \Magento\Framework\App\Helper\AbstractHelper
{
    const DEFAULT_CARRIER = 'yupack';
    /**
     * @var \Wyomind\PointOfSale\Model\PointOfSaleFactory
     */
    protected $pointOfSaleFactory;

    /**
     * @var \Riki\Framework\Helper\Cache\FunctionCache
     */
    protected $functionCache;

    /**
     * @var \Magento\Framework\Registry
     */
    protected $registry;

    /**
     * @var \Riki\Framework\Helper\ScopeConfig
     */
    protected $scopeConfigHelper;

    /**
     * @var \Riki\Rma\Api\ReasonRepositoryInterface
     */
    protected $reasonRepository;

    /**
     * @var \Magento\Customer\Api\CustomerRepositoryInterface
     */
    protected $customerRepository;

    /**
     * @var \Riki\Framework\Helper\Search
     */
    protected $searchHelper;

    /**
     * @var \Riki\Rma\Api\ItemRepositoryInterface
     */
    protected $rmaItemRepository;

    /**
     * @var \Riki\Rma\Api\RmaRepositoryInterface
     */
    protected $rmaRepository;

    /**
     * @var \Magento\Sales\Api\ShipmentRepositoryInterface
     */
    protected $shipmentRepository;

    /**
     * @var \Magento\Sales\Api\OrderItemRepositoryInterface
     */
    protected $orderItemRepository;

    /**
     * @var \Riki\Rma\Model\ResourceModel\RmaAmount
     */
    private $rmaAmountResourceModel;

    protected $loadedTotalReturnAmount = [];

    /** @var bool */
    protected $skipNeedSaveAgain = false;

    /**
     * @var \Riki\ShippingCarrier\Helper\CarrierHelper
     */
    protected $carrierHelper;

    /**
     * @var \Riki\Rma\Model\Reason
     */
    protected $rmaReason;

    /**
     * Data constructor.
     * @param \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository
     * @param \Riki\Rma\Api\ReasonRepositoryInterface $reasonRepository
     * @param \Riki\Framework\Helper\ScopeConfig $scopeConfigHelper
     * @param \Magento\Sales\Api\OrderItemRepositoryInterface $orderItemRepository
     * @param \Magento\Sales\Api\ShipmentRepositoryInterface $shipmentRepository
     * @param \Riki\Rma\Api\RmaRepositoryInterface $rmaRepository
     * @param \Riki\Rma\Api\ItemRepositoryInterface $rmaItemRepository
     * @param \Riki\Framework\Helper\Search $searchHelper
     * @param \Magento\Framework\Registry $registry
     * @param \Riki\Framework\Helper\Cache\FunctionCache $functionCache
     * @param \Wyomind\PointOfSale\Model\PointOfSaleFactory $pointOfSaleFactory
     * @param \Riki\Rma\Model\ResourceModel\RmaAmount $rmaAmountResourceModel
     * @param \Magento\Framework\App\Helper\Context $context
     * @param \Riki\ShippingCarrier\Helper\CarrierHelper $carrierHelper
     * @param \Riki\Rma\Model\Reason $rmaReason
     */
    public function __construct(
        \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository,
        \Riki\Rma\Api\ReasonRepositoryInterface $reasonRepository,
        \Riki\Framework\Helper\ScopeConfig $scopeConfigHelper,
        \Magento\Sales\Api\OrderItemRepositoryInterface $orderItemRepository,
        \Magento\Sales\Api\ShipmentRepositoryInterface $shipmentRepository,
        \Riki\Rma\Api\RmaRepositoryInterface $rmaRepository,
        \Riki\Rma\Api\ItemRepositoryInterface $rmaItemRepository,
        \Riki\Framework\Helper\Search $searchHelper,
        \Magento\Framework\Registry $registry,
        \Riki\Framework\Helper\Cache\FunctionCache $functionCache,
        \Wyomind\PointOfSale\Model\PointOfSaleFactory $pointOfSaleFactory,
        \Riki\Rma\Model\ResourceModel\RmaAmount $rmaAmountResourceModel,
        \Magento\Framework\App\Helper\Context $context,
        \Riki\ShippingCarrier\Helper\CarrierHelper $carrierHelper,
        \Riki\Rma\Model\Reason $rmaReason
    ) {
        $this->customerRepository = $customerRepository;
        $this->reasonRepository = $reasonRepository;
        $this->scopeConfigHelper = $scopeConfigHelper;
        $this->orderItemRepository = $orderItemRepository;
        $this->shipmentRepository = $shipmentRepository;
        $this->rmaRepository = $rmaRepository;
        $this->searchHelper = $searchHelper;
        $this->rmaItemRepository = $rmaItemRepository;
        $this->registry = $registry;
        $this->functionCache = $functionCache;
        $this->pointOfSaleFactory = $pointOfSaleFactory;
        $this->rmaAmountResourceModel = $rmaAmountResourceModel;
        $this->carrierHelper = $carrierHelper;
        $this->rmaReason = $rmaReason;
        parent::__construct($context);
    }

    /**
     * @return bool
     */
    public function getSkipNeedToSaveAgain()
    {
        return $this->skipNeedSaveAgain;
    }

    /**
     * @param $flag
     * @return mixed
     */
    public function setSkipNeedToSaveAgain($flag)
    {
        return $this->skipNeedSaveAgain = $flag;
    }

    /**
     * @return array
     */
    public function getNeedSaveAgainFields()
    {
        return [
            'reason_id', // affect point order level, self and other rma are affected
            'substitution_order', // affect total become zero, self affected
            'rma_shipment_number', // affect payment fee and shipment fee, self affected
            'trigger_cancel_point', // affect point order level, other rma are affected
        ];
    }

    /**
     * Get all statuses before the last approval. These statuses allow user operate on RMA.
     *
     * @return array
     */
    public function getStageOneStatuses()
    {
        return [
            ReturnStatusInterface::CREATED,
            ReturnStatusInterface::REJECTED_BY_CC,
            ReturnStatusInterface::REVIEWED_BY_CC,
            ReturnStatusInterface::CC_FEEDBACK_REJECTED,
        ];
    }

    /**
     * Get all statuses before RMA is completed/closed. These statuses allow user operate on RMA.
     *
     * @return array
     */
    public function getStageTwoStatuses()
    {
        return array_merge($this->getStageOneStatuses(), [ReturnStatusInterface::APPROVED_BY_CC]);
    }

    /**
     * In these statuses, some fields of RMA will be get from DB instead of being calculated.
     *
     * @return array
     */
    public function getClosedStatuses()
    {
        return ['closed'];
    }

    /**
     * Get reason not allowed for COD
     *
     * @return array
     */
    public function getReasonCODNotAllowed()
    {
        $value = (string)$this->scopeConfigHelper->read(ConfigInterface::class)
            ->rma()
            ->reason()
            ->codNotAllowed();
        return explode(',', $value);
    }

    /**
     * Get list warehouses
     *
     * @return array
     */
    public function getWarehouses()
    {
        if ($this->functionCache->has()) {
            return $this->functionCache->load();
        }
        /** @var \Wyomind\PointOfSale\Model\ResourceModel\PointOfSale\Collection $collection */
        $collection = $this->pointOfSaleFactory->create()->getCollection();
        $collection->setOrder('position', $collection::SORT_ORDER_ASC);
        $result = $collection->getItems();
        $this->functionCache->store($result);

        return $result;
    }

    /**
     * @return bool
     */
    public function canShowPartialFullField()
    {
        return true;
    }

    /**
     * @return bool
     */
    public function canShowReturnedWarehouseField()
    {
        return true;
    }

    /**
     * Get order of rma
     *
     * @param \Magento\Rma\Model\Rma $rma
     * @return \Magento\Sales\Model\Order
     */
    public function getRmaOrder(\Magento\Rma\Model\Rma $rma)
    {
        if ($this->functionCache->has($rma->getOrderId())) {
            return $this->functionCache->load($rma->getOrderId());
        }

        $order = $rma->getOrder();
        if (!$order instanceof \Magento\Sales\Model\Order) {
            $order = null;
        }
        $this->functionCache->store($order, $rma->getOrderId());

        return $order;
    }

    /**
     * Get Payment of Order Of Rma
     *
     * @param \Magento\Rma\Model\Rma $rma
     * @return \Magento\Framework\DataObject|\Magento\Sales\Api\Data\OrderPaymentInterface|mixed|null
     */
    public function getRmaOrderPayment(\Magento\Rma\Model\Rma $rma)
    {
        if ($this->functionCache->has($rma->getOrderId())) {
            return $this->functionCache->load($rma->getOrderId());
        }

        $payment = null;
        $order = $this->getRmaOrder($rma);
        if ($order instanceof \Magento\Sales\Model\Order) {
            $payment = $order->getPayment();
        }
        $this->functionCache->store($payment, $rma->getOrderId());

        return $payment;
    }

    /**
     * Get payment method code of Order of Rma
     *
     * @param \Magento\Rma\Model\Rma $rma
     * @return mixed|null|string
     */
    public function getRmaOrderPaymentMethodCode(\Magento\Rma\Model\Rma $rma)
    {
        $cacheKey = $rma->getId() . '_' . $rma->getOrderId();
        if ($this->functionCache->has($cacheKey)) {
            return $this->functionCache->load($cacheKey);
        }
        $methodCode = null;
        $payment = $this->getRmaOrderPayment($rma);
        if ($payment instanceof \Magento\Sales\Model\Order\Payment) {
            $methodCode = $payment->getMethod();
        }

        $this->functionCache->store($methodCode, $cacheKey);

        return $methodCode;
    }

    /**
     * Get customer of rma
     *
     * @param \Magento\Rma\Model\Rma $rma
     * @return \Magento\Customer\Api\Data\CustomerInterface|null
     */
    public function getRmaCustomer(\Magento\Rma\Model\Rma $rma)
    {
        if ($this->functionCache->has($rma->getCustomerId())) {
            $this->functionCache->load($rma->getCustomerId());
        }
        $customer = null;

        try {
            $customer = $this->customerRepository->getById($rma->getCustomerId());
        } catch (\Exception $e) {
            $this->_logger->critical($e);
        }

        $this->functionCache->store($customer, $rma->getCustomerId());

        return $customer;
    }

    /**
     * Get current rma from registry
     *
     * @return \Riki\Rma\Model\Rma
     */
    public function getCurrentRma()
    {
        return $this->registry->registry('current_rma');
    }

    /**
     * Get items of Rma
     *
     * @param \Magento\Rma\Model\Rma $rma
     *
     * @return \Riki\Rma\Model\Item[]
     */
    public function getRmaItems(\Magento\Rma\Model\Rma $rma)
    {
        if ($this->functionCache->has($rma->getId())) {
            return $this->functionCache->load($rma->getId());
        }

        $result = $this->searchHelper
            ->getByRmaEntityId($rma->getId())
            ->getAll()
            ->execute($this->rmaItemRepository);

        $this->functionCache->store($result, $rma->getId());

        return $result;
    }

    /**
     * Get rma items of Order
     *
     * @param \Magento\Sales\Model\Order $order
     *
     * @return \Riki\Rma\Model\Item[]
     */
    public function getOrderRmaItems(\Magento\Sales\Model\Order $order)
    {
        if ($this->functionCache->has($order->getId())) {
            return $this->functionCache->load($order->getId());
        }

        $result = [];
        $rmaEntities = $this->searchHelper
            ->getByOrderId($order->getId())
            ->getAll()
            ->execute($this->rmaRepository);
        foreach ($rmaEntities as $rmaEntity) {
            foreach ($this->getRmaItems($rmaEntity) as $item) {
                $result[] = $item;
            }
        }

        $this->functionCache->store($result, $order->getId());

        return $result;
    }

    /**
     * Get shipment by rma_shipment_number of rma
     *
     * @param \Magento\Rma\Model\Rma $rma
     * @param bool $useCache
     *
     * @return \Magento\Sales\Model\Order\Shipment
     */
    public function getRmaShipment(\Magento\Rma\Model\Rma $rma, $useCache = true)
    {
        if ($useCache &&
            $this->functionCache->has($rma->getRmaShipmentNumber())
        ) {
            return $this->functionCache->load($rma->getRmaShipmentNumber());
        }

        $result = $this->searchHelper
            ->getByIncrementId($rma->getRmaShipmentNumber())
            ->getOne()
            ->execute($this->shipmentRepository);

        $this->functionCache->store($result, $rma->getRmaShipmentNumber());

        return $result;
    }

    /**
     * Get reason of rma
     *
     * @param \Magento\Rma\Model\Rma $rma
     *
     * @return \Riki\Rma\Model\Reason
     */
    public function getRmaReason(\Magento\Rma\Model\Rma $rma)
    {
        if ($this->functionCache->has($rma->getReasonId())) {
            return $this->functionCache->load($rma->getReasonId());
        }

        $result = $this->searchHelper
            ->getById($rma->getReasonId())
            ->getOne()
            ->execute($this->reasonRepository);

        $this->functionCache->store($result, $rma->getReasonId());

        return $result;
    }

    /**
     * Get due to of reason of rma
     *
     * @param \Magento\Rma\Model\Rma $rma
     *
     * @return mixed|null
     */
    public function getRmaReasonDueTo(\Magento\Rma\Model\Rma $rma)
    {
        if ($this->functionCache->has($rma->getReasonId())) {
            return $this->functionCache->load($rma->getReasonId());
        }

        $reason = $this->getRmaReason($rma);
        $result = ($reason instanceof \Riki\Rma\Model\Reason) ? $reason->getDueTo() : null;

        $this->functionCache->store($result, $rma->getReasonId());

        return $result;
    }

    /**
     * Get status of rma_shipment_number
     *
     * @param \Magento\Rma\Model\Rma $rma
     *
     * @return string
     */
    public function getRmaShipmentStatus(\Magento\Rma\Model\Rma $rma)
    {
        if ($this->functionCache->has($rma->getRmaShipmentNumber())) {
            return $this->functionCache->load($rma->getRmaShipmentNumber());
        }

        $shipment = $this->getRmaShipment($rma);
        $result = ($shipment instanceof \Magento\Sales\Model\Order\Shipment)
            ? $shipment->getShipmentStatus()
            : null;

        $this->functionCache->store($result, $rma->getRmaShipmentNumber());

        return $result;
    }

    /**
     * Get order item of rma item
     *
     * @param \Magento\Rma\Model\Item $item
     *
     * @return \Magento\Sales\Model\Order\Item
     */
    public function getRmaItemOrderItem(\Magento\Rma\Model\Item $item)
    {
        if ($this->functionCache->has($item->getData('order_item_id'))) {
            return $this->functionCache->load($item->getData('order_item_id'));
        }

        $result = $this->searchHelper
            ->getByItemId($item->getData('order_item_id'))
            ->getOne()
            ->execute($this->orderItemRepository);

        $this->functionCache->store($result, $item->getData('order_item_id'));

        return $result;
    }

    /**
     * Get consumer db id of customer of rma
     *
     * @param \Magento\Rma\Model\Rma $rma
     *
     * @return string
     */
    public function getRmaCustomerConsumerDbId(\Magento\Rma\Model\Rma $rma)
    {
        if ($this->functionCache->has($rma->getCustomerId())) {
            return $this->functionCache->load($rma->getCustomerId());
        }

        $customer = $this->getRmaCustomer($rma);
        $result = '';
        if ($customer && ($consumerAttr = $customer->getCustomAttribute('consumer_db_id'))) {
            $result = $consumerAttr->getValue();
        }

        $this->functionCache->store($result, $rma->getCustomerId());

        return $result;
    }

    /**
     * Get shipments of order of rma
     *
     * @param \Magento\Rma\Model\Rma $rma
     *
     * @return mixed[[]
     */
    public function getRmaOrderShipments(\Magento\Rma\Model\Rma $rma)
    {
        if ($this->functionCache->has($rma->getOrderId())) {
            return $this->functionCache->load($rma->getOrderId());
        }

        $result = $this->searchHelper
            ->getByOrderId($rma->getOrderId())
            ->getAll()
            ->execute($this->shipmentRepository);

        return $result;
    }

    /**
     * @param \Magento\Rma\Model\Rma $rma
     * @return int
     */
    public function isCodAndNpAtobaraiShipmentRejected(\Magento\Rma\Model\Rma $rma)
    {
        $methodCode = $this->getRmaOrderPaymentMethodCode($rma);
        $expectedMethodCodes = [
            Cashondelivery::PAYMENT_METHOD_CASHONDELIVERY_CODE,
            NpAtobarai::PAYMENT_METHOD_NP_ATOBARAI_CODE
        ];

        if (!in_array($methodCode, $expectedMethodCodes)) {
            return 0;
        }

        $shipmentStatus = $this->getRmaShipmentStatus($rma);
        if ($shipmentStatus != \Riki\Shipment\Model\ResourceModel\Status\Options\Shipment::SHIPMENT_STATUS_REJECTED) {
            return 0;
        }

        return 1;
    }

    /**
     * can get return shipping fee from shipment fee
     *
     * @param \Magento\Rma\Model\Rma $rma
     * @return bool
     */
    public function canGetReturnShippingFeeFromShipmentFee(\Magento\Rma\Model\Rma $rma)
    {
        if (!$rma->getRmaShipmentNumber()) {
            return false;
        }

        $methodCode = $this->getRmaOrderPaymentMethodCode($rma);
        $expectedMethodCodes = [
            Cashondelivery::PAYMENT_METHOD_CASHONDELIVERY_CODE,
            NpAtobarai::PAYMENT_METHOD_NP_ATOBARAI_CODE
        ];

        if (!in_array($methodCode, $expectedMethodCodes)) {
            return false;
        }

        $shipment = $this->getRmaShipment($rma);

        if (!$shipment || !$shipment instanceof \Magento\Sales\Model\Order\Shipment) {
            return false;
        }

        $shipmentStatus = $shipment->getShipmentStatus();
        if ($shipmentStatus == \Riki\Shipment\Model\ResourceModel\Status\Options\Shipment::SHIPMENT_STATUS_REJECTED) {
            return true;
        }

        /*get rma reason data*/
        $reasonData = $this->getRmaReason($rma);
        if ($reasonData && $reasonData instanceof \Riki\Rma\Model\Reason) {
            if (in_array($reasonData->getId(), $this->getRejectedReasonForCod()) && $methodCode != NpAtobarai::PAYMENT_METHOD_NP_ATOBARAI_CODE) {
                return true;
            }
        }

        return false;
    }

    public function checkNpAtobaraiReasonCode(\Magento\Rma\Model\Rma $rma)
    {
        /*get rma reason data*/
        $reasonData = $this->getRmaReason($rma);
        if ($reasonData && $reasonData instanceof \Riki\Rma\Model\Reason) {
            if (in_array($reasonData->getId(), $this->getRejectedReasonForCod())) {
                return true;
            }
        }
    }

    /**
     * get rejected reason for COD
     *
     * @return array
     */
    public function getRejectedReasonForCod()
    {
        $reasonList = [];
        $reasonIds = $this->scopeConfig->getValue(
            \Riki\Rma\Api\ConfigInterface::XML_PATH_RMA_REASON_COD_REJECTED,
            \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE
        );

        if (!empty($reasonIds)) {
            $reasonList = explode(',', $reasonIds);
        }

        return $reasonList;
    }

    /**
     * @param $orderId
     * @return mixed
     */
    public function getReturnsAmountTotalByOrder($orderId)
    {
        if ($orderId instanceof \Magento\Sales\Model\Order) {
            $orderId = $orderId->getId();
        }

        if (!isset($this->loadedTotalReturnAmount[$orderId])) {
            try {
                $total = $this->rmaAmountResourceModel->getTotalReturnsByOrder(
                    $orderId
                );
            } catch (\Exception $e) {
                $total = 0;
            }

            $this->loadedTotalReturnAmount[$orderId] = $total;
        }

        return $this->loadedTotalReturnAmount[$orderId];
    }

    /**
     * get default carrier data for COD
     *
     * @return array
     */
    public function getDefaultCarrierDataForCod()
    {
        $defaultCarrierCode = $this->getDefaultCarrierForCod();
        $defaultCarrierTitle = $this->carrierHelper->getTitleByCarrierCode($defaultCarrierCode);

        return [
            'carrier_code' => $defaultCarrierCode,
            'carrier_title' => $defaultCarrierTitle
        ];
    }

    /**
     * get default carrier for COD
     *
     * @return string
     */
    public function getDefaultCarrierForCod()
    {
        $carrierCode = $this->scopeConfig->getValue(
            \Riki\Rma\Api\ConfigInterface::RMA_CARRIER_COD,
            \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE
        );

        if (empty($carrierCode)) {
            $carrierCode = self::DEFAULT_CARRIER;
        }

        return $carrierCode;
    }

    /**
     * Get return without goods reason id
     * @param int $returnCode
     * @return mixed|string
     */
    public function getReturnWithoutGoodsReasonId($returnCode = 60)
    {
        $rmaReason = $this->rmaReason->loadByCode($returnCode);
        if ($rmaReason->getId()) {
            return $rmaReason->getId();
        }
        return '';
    }
}
