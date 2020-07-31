<?php
namespace Riki\Rma\Model;

use Magento\Framework\Exception\LocalizedException;
use \Riki\Rma\Api\Data\Rma\TypeInterface;

class Rma extends \Magento\Rma\Model\Rma implements \Riki\Rma\Api\Data\RmaInterface
{
    const TYPE_WITHOUT_GOODS = 1;

    const SKIP_VALIDATE_NEED_TO_SAVE_AGAIN_FLAG = 'skip_validate_need_to_save_again';

    protected $_eventPrefix = 'rma';

    protected $_eventObject = 'rma';

    /**
     * @var \Riki\Framework\Helper\Cache\FunctionCache
     */
    protected $functionCache;

    /**
     * @var \Riki\Framework\Helper\Cache\AppCache
     */
    protected $appCache;

    /**
     * @var \Riki\Rma\Model\ItemFactory
     */
    protected $rmaItemFactory;

    /**
     * @var \Magento\Sales\Api\OrderItemRepositoryInterface
     */
    protected $orderItemRepository;

    /**
     * @var \Magento\Customer\Api\CustomerRepositoryInterface
     */
    protected $customerRepository;

    /**
     * @var Repository\ReasonRepository
     */
    protected $reasonRepository;

    /**
     * @var \Riki\Rma\Model\Repository\Rma\Status\HistoryRepository
     */
    protected $historyRepository;

    /**
     * @var \Riki\Rma\Model\Repository\ItemRepository
     */
    protected $rmaItemRepository;

    /**
     * @var \Riki\Rma\Helper\Authorization
     */
    protected $authorizationHelper;

    /**
     * @var \Riki\Rma\Helper\Status
     */
    protected $statusHelper;

    /**
     * @var \Riki\Rma\Helper\Json
     */
    protected $jsonHelper;

    /**
     * @var \Magento\Framework\Api\SearchCriteriaBuilder
     */
    protected $searchCriteriaBuilder;

    /**
     * @var \Magento\Framework\Validator\Factory
     */
    protected $validatorFactory;

    /**
     * @var Rma\BundleItem
     */
    protected $rmaBundleItem;

    /**
     * Loaded sibling collection by ID
     *
     * @var array
     */
    protected $siblingRmasById = [];

    /**
     * Rma constructor.
     *
     * @param \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder
     * @param \Riki\Rma\Helper\Json $jsonHelper
     * @param \Riki\Rma\Helper\Status $statusHelper
     * @param \Riki\Rma\Helper\Authorization $authorizationHelper
     * @param Repository\ItemRepository $rmaItemRepository
     * @param Repository\Rma\Status\HistoryRepository $historyRepository
     * @param Repository\ReasonRepository $reasonRepository
     * @param \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository
     * @param \Magento\Sales\Api\OrderItemRepositoryInterface $orderItemRepository
     * @param ItemFactory $newRmaItemFactory
     * @param \Riki\Framework\Helper\Cache\AppCache $appCache
     * @param \Riki\Framework\Helper\Cache\FunctionCache $functionCache
     * @param \Magento\Framework\Model\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\Api\ExtensionAttributesFactory $extensionFactory
     * @param \Magento\Framework\Api\AttributeValueFactory $customAttributeFactory
     * @param \Magento\Rma\Helper\Data $rmaData
     * @param \Magento\Framework\Session\Generic $session
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Eav\Model\Config $eavConfig
     * @param \Magento\Rma\Model\ItemFactory $rmaItemFactory
     * @param \Magento\Rma\Model\Item\Attribute\Source\StatusFactory $attrSourceFactory
     * @param \Magento\Rma\Model\GridFactory $rmaGridFactory
     * @param \Magento\Rma\Model\Rma\Source\StatusFactory $statusFactory
     * @param \Magento\Rma\Model\ResourceModel\ItemFactory $itemFactory
     * @param \Magento\Rma\Model\ResourceModel\Item\CollectionFactory $itemsFactory
     * @param \Magento\Rma\Model\ResourceModel\Shipping\CollectionFactory $rmaShippingFactory
     * @param \Magento\Quote\Model\QuoteFactory $quoteFactory
     * @param \Magento\Quote\Model\Quote\Address\RateFactory $quoteRateFactory
     * @param \Magento\Quote\Model\Quote\ItemFactory $quoteItemFactory
     * @param \Magento\Sales\Model\OrderFactory $orderFactory
     * @param \Magento\Sales\Model\ResourceModel\Order\Item\CollectionFactory $ordersFactory
     * @param \Magento\Quote\Model\Quote\Address\RateRequestFactory $rateRequestFactory
     * @param \Magento\Shipping\Model\ShippingFactory $shippingFactory
     * @param \Magento\Framework\Escaper $escaper
     * @param \Magento\Framework\Stdlib\DateTime\TimezoneInterface $localeDate
     * @param \Magento\Framework\Message\ManagerInterface $messageManager
     * @param \Magento\Rma\Api\RmaAttributesManagementInterface $metadataService
     * @param \Magento\Framework\Validator\Factory $validatorFactory
     * @param Rma\BundleItem $rmaBundleItem
     * @param \Magento\Framework\Model\ResourceModel\AbstractResource|null $resource
     * @param \Magento\Framework\Data\Collection\AbstractDb|null $resourceCollection
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder,
        \Riki\Rma\Helper\Json $jsonHelper,
        \Riki\Rma\Helper\Status $statusHelper,
        \Riki\Rma\Helper\Authorization $authorizationHelper,
        \Riki\Rma\Model\Repository\ItemRepository $rmaItemRepository,
        \Riki\Rma\Model\Repository\Rma\Status\HistoryRepository $historyRepository,
        \Riki\Rma\Model\Repository\ReasonRepository $reasonRepository,
        \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository,
        \Magento\Sales\Api\OrderItemRepositoryInterface $orderItemRepository,
        \Riki\Rma\Model\ItemFactory $newRmaItemFactory,
        \Riki\Framework\Helper\Cache\AppCache $appCache,
        \Riki\Framework\Helper\Cache\FunctionCache $functionCache,
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Api\ExtensionAttributesFactory $extensionFactory,
        \Magento\Framework\Api\AttributeValueFactory $customAttributeFactory,
        \Magento\Rma\Helper\Data $rmaData,
        \Magento\Framework\Session\Generic $session,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Eav\Model\Config $eavConfig,
        \Magento\Rma\Model\ItemFactory $rmaItemFactory,
        \Magento\Rma\Model\Item\Attribute\Source\StatusFactory $attrSourceFactory,
        \Magento\Rma\Model\GridFactory $rmaGridFactory,
        \Magento\Rma\Model\Rma\Source\StatusFactory $statusFactory,
        \Magento\Rma\Model\ResourceModel\ItemFactory $itemFactory,
        \Magento\Rma\Model\ResourceModel\Item\CollectionFactory $itemsFactory,
        \Magento\Rma\Model\ResourceModel\Shipping\CollectionFactory $rmaShippingFactory,
        \Magento\Quote\Model\QuoteFactory $quoteFactory,
        \Magento\Quote\Model\Quote\Address\RateFactory $quoteRateFactory,
        \Magento\Quote\Model\Quote\ItemFactory $quoteItemFactory,
        \Magento\Sales\Model\OrderFactory $orderFactory,
        \Magento\Sales\Model\ResourceModel\Order\Item\CollectionFactory $ordersFactory,
        \Magento\Quote\Model\Quote\Address\RateRequestFactory $rateRequestFactory,
        \Magento\Shipping\Model\ShippingFactory $shippingFactory,
        \Magento\Framework\Escaper $escaper,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $localeDate,
        \Magento\Framework\Message\ManagerInterface $messageManager,
        \Magento\Rma\Api\RmaAttributesManagementInterface $metadataService,
        \Magento\Framework\Validator\Factory $validatorFactory,
        \Riki\Rma\Model\Rma\BundleItem $rmaBundleItem,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->jsonHelper = $jsonHelper;
        $this->statusHelper = $statusHelper;
        $this->authorizationHelper = $authorizationHelper;
        $this->rmaItemRepository = $rmaItemRepository;
        $this->historyRepository = $historyRepository;
        $this->reasonRepository = $reasonRepository;
        $this->customerRepository = $customerRepository;
        $this->orderItemRepository = $orderItemRepository;
        $this->appCache = $appCache;
        $this->rmaItemFactory = $newRmaItemFactory;
        $this->functionCache = $functionCache;
        $this->validatorFactory = $validatorFactory;
        $this->rmaBundleItem = $rmaBundleItem;
        parent::__construct(
            $context,
            $registry,
            $extensionFactory,
            $customAttributeFactory,
            $rmaData,
            $session,
            $storeManager,
            $eavConfig,
            $rmaItemFactory,
            $attrSourceFactory,
            $rmaGridFactory,
            $statusFactory,
            $itemFactory,
            $itemsFactory,
            $rmaShippingFactory,
            $quoteFactory,
            $quoteRateFactory,
            $quoteItemFactory,
            $orderFactory,
            $ordersFactory,
            $rateRequestFactory,
            $shippingFactory,
            $escaper,
            $localeDate,
            $messageManager,
            $metadataService,
            $resource,
            $resourceCollection,
            $data
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function _init($resourceModel)
    {
        parent::_init($resourceModel);
        $this->_collectionName = 'Riki\Rma\Model\ResourceModel\Rma\Collection';
        $this->_resourceName = 'Riki\Rma\Model\ResourceModel\Rma';
    }

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    public function getRefundMethod()
    {
        return $this->getData('refund_method');
    }

    /**
     * {@inheritdoc}
     *
     * @param string $method
     *
     * @return $this
     */
    public function setRefundMethod($method)
    {
        $this->setData('refund_method', $method);
        return $this;
    }

    /**
     * Get rma items
     *
     * @return \Riki\Rma\Model\Item[]
     */
    public function getRmaItems()
    {
        $cacheKey = ['rma_items', $this->getId()];
        if ($this->appCache->has($cacheKey)) {
            return $this->appCache->load($cacheKey);
        }

        /** @var \Riki\Rma\Model\ResourceModel\Item\Collection $collection */
        $collection = $this->rmaItemFactory->create()->getCollection();
        $collection->addFieldToFilter('rma_entity_id', ['eq' => $this->getId()]);


        /** @var \Riki\Rma\Model\Item[] $result */
        $result = $collection->getItems();
        $this->appCache->store($result, $cacheKey);

        return $result;
    }

    /**
     * Get rma item ids
     *
     * @return string[]
     */
    public function getRmaItemIds()
    {
        if ($this->functionCache->has($this->getId())) {
            return $this->functionCache->load($this->getId());
        }

        $result = [];
        foreach ($this->getRmaItems() as $item) {
            $result[] = $item->getId();
        }
        $this->functionCache->store($result, $this->getId());

        return $result;
    }

    /**
     * Get customer
     *
     * @return \Magento\Customer\Api\Data\CustomerInterface|null
     */
    public function getCustomer()
    {
        try {
            return $this->customerRepository->getById($this->getCustomerId());
        } catch (\Magento\Framework\Exception\NoSuchEntityException $e) {
            return null;
        }
    }

    /**
     * Get consumer_db_id of customer
     *
     * @return string
     */
    public function getConsumerDbId()
    {
        $cacheKey = ['customer_consumer_db_id', $this->getCustomerId()];
        if ($this->appCache->has($cacheKey)) {
            return $this->appCache->load($cacheKey);
        }

        $result = '';
        $customer = $this->getCustomer();
        if ($customer instanceof \Magento\Customer\Api\Data\CustomerInterface) {
            $consumerDbIdAttr = $customer->getCustomAttribute('consumer_db_id');
            if ($consumerDbIdAttr) {
                $result = $consumerDbIdAttr->getValue();
            }
        }

        $this->appCache->store($result, $cacheKey);

        return $result;
    }

    /**
     * Get order item ids
     *
     * @param $options
     *
     * @return string[]
     */
    public function getOrderItemIds($options = [])
    {
        $cacheKey = $options;
        $cacheKey[] = $this->getId();
        if ($this->functionCache->has($cacheKey)) {
            return $this->functionCache->load($cacheKey);
        }

        $inclParent = isset($options['inclParent']) ? $options['inclParent'] : false;
        $inclQty = isset($options['inclQty']) ? $options['inclQty'] : false;

        $result = [];
        foreach ($this->getRmaItems() as $rmaItem) {
            if ($inclParent) {
                $parentItem = $rmaItem->getParentOrderItem();
                if ($parentItem) {
                    if ($inclQty) {
                        $orderItem = $rmaItem->getOrderItem();
                        $qty = intval($rmaItem->getQtyRequested()) * ($parentItem->getQtyOrdered()/$orderItem->getQtyOrdered());
                        $result[$parentItem->getItemId()] = $qty;
                    } else {
                        $result[] = $parentItem->getItemId();
                    }
                }
            }

            if ($inclQty) {
                $result[$rmaItem->getOrderItemId()] = $rmaItem->getQtyRequested();
            } else {
                $result[] = $rmaItem->getOrderItemId();
            }
        }

        $this->functionCache->store($result, $cacheKey);
        return $result;
    }

    /**
     * Is full return
     *
     * @return bool
     */
    public function getIsFull()
    {
        return $this->getData('full_partial') == TypeInterface::FULL;
    }

    /**
     * Is partial return
     *
     * @return bool
     */
    public function getIsPartial()
    {
        return $this->getData('full_partial') == TypeInterface::PARTIAL;
    }

    /**
     * Get reason
     *
     * @return \Riki\Rma\Model\Reason
     */
    public function getReason()
    {
        $cacheKey = ['rma_reason_', $this->getData('reason_id')];
        if ($this->appCache->has($cacheKey)) {
            return $this->appCache->load($cacheKey);
        }

        try {
            $result = $this->reasonRepository->getById($this->getData('reason_id'));
        } catch (\Magento\Framework\Exception\NoSuchEntityException $e) {
            $result = null;
        }

        $this->appCache->store($result, $cacheKey);

        return $result;
    }

    /**
     * Get reason code due to nestle
     *
     * @return bool
     */
    public function getDueToNestle()
    {
        $reason = $this->getReason();

        return $reason
            ? $reason->getData('due_to') == \Riki\Rma\Api\Data\Reason\DuetoInterface::NESTLE
            : false;
    }

    /**
     * Get reason code due to consumer
     *
     * @return bool
     */
    public function getDueToConsumer()
    {
        $reason = $this->getReason();

        return $reason
            ? $reason->getData('due_to') == \Riki\Rma\Api\Data\Reason\DuetoInterface::CONSUMER
            : false;
    }

    /**
     * Get payment method of order
     *
     * @return null|string
     */
    public function getOrderPaymentMethod()
    {
        $order = $this->getOrder();
        if (!$order instanceof \Magento\Sales\Model\Order) {
            return null;
        }

        $payment = $order->getPayment();
        if (!$payment instanceof \Magento\Sales\Model\Order\Payment) {
            return null;
        }

        return $payment->getMethod();
    }

    /**
     * Get extension data
     *
     * @param $path
     *
     * @return mixed
     */
    public function getExtensionData($path = null)
    {
        return $this->jsonHelper->getNode($this->getData('extension_data'), $path);
    }

    /**
     * Add extension data
     *
     * @param $data
     *
     * @return $this
     */
    public function addExtensionData($data)
    {
        $extData = $this->jsonHelper->addNode($this->getData('extension_data'), $data);
        $this->setData('extension_data', $extData);

        return $this;
    }

    /**
     * Remove extension data by key
     *
     * @param $path
     *
     * @return $this
     */
    public function removeExtensionData($path)
    {
        $extData = $this->jsonHelper->removeNode($this->getData('extension_data'), $path);
        $this->setData('extension_data', $extData);

        return $this;
    }

    /**
     * Save extension data directly
     *
     * @return $this
     */
    public function saveExtensionData()
    {
        $conn = $this->getResource()->getConnection();
        $conn->update($conn->getTableName('magento_rma'), [
            'extension_data' => $this->getData('extension_data')
        ], "entity_id = {$this->getId()}");

        $this->_eventManager->dispatch('rma_save_extension_data', ['rma' => $this]);

        return $this;
    }

    /**
     * Add status history comment
     *
     * @param $comment
     *
     * @return $this
     */
    public function addHistoryComment($comment)
    {
        $history = $this->historyRepository->createFromArray([
            'rma_entity_id' => $this->getId(),
            'comment' => $comment,
            'is_admin' => true,
        ]);

        $this->historyRepository->save($history);

        return $this;
    }

    /**
     * Add return status history comment
     *
     * @return $this
     */
    public function addReturnStatusHistoryComment()
    {
        if ($adminUser = $this->authorizationHelper->getCurrentUser()) {
            $comment = __('Return status changed to %1 by %2', $this->statusHelper->getLabel($this->getReturnStatus()), $adminUser->getUserName());
        } else {
            $comment = __('Return status changed to %1', $this->statusHelper->getLabel($this->getReturnStatus()));
        }

        $this->addHistoryComment($comment);

        return $this;
    }

    /**
     * Add refund status history comment
     *
     * @return $this
     */
    public function addRefundStatusHistoryComment()
    {
        if ($adminUser = $this->authorizationHelper->getCurrentUser()) {
            $comment = __(
                'Refund status changed to %1 by %2',
                $this->statusHelper->getRefundStatusLabel($this->getRefundStatus()),
                $adminUser->getUserName()
            );
        } else {
            $comment = __(
                'Refund status changed to %1',
                $this->statusHelper->getRefundStatusLabel($this->getRefundStatus())
            );
        }

        $this->addHistoryComment($comment);

        return $this;
    }

    /**
     * Can hold order level earned point
     * (if any other sibling RMAs is holding order level earned point, it will return false)
     *
     * @return bool
     */
    public function canTriggerCancelPoint()
    {
        $id = $this->getResource()->getTriggerCancelPointRma($this->getOrderId());
        return !$id || $id == $this->getId();
    }

    /**
     * Get rma which same order with it
     *
     * @return \Riki\Rma\Model\Rma[]
     */
    public function getSiblingRmas()
    {
        if (!isset($this->siblingRmasById[$this->getId()])) {
            /** @var \Riki\Rma\Model\Rma[] $result */
            $this->siblingRmasById[$this->getId()] = $this->getCollection()
                ->addFieldToFilter('order_id', ['eq' => $this->getOrderId()])
                ->addFieldToFilter('entity_id', ['neq' => $this->getId()])
                ->getItems();
        }


        return $this->siblingRmasById[$this->getId()];
    }

    /**
     * Check need to save again
     *
     * @throws \Magento\Framework\Exception\LocalizedException
     * @return self
     */
    public function validateSaveAgain()
    {
        if ($this->getData(self::SKIP_VALIDATE_NEED_TO_SAVE_AGAIN_FLAG)) {
            return $this;
        }

        if (($changedFields = $this->getExtensionData('need_save_again'))) {
            if (isset($changedFields['trigger_cancel_point'])) {
                $message = __('Some changes in RMA #%1 affect this RMA.', $changedFields['trigger_cancel_point']);
            } else {
                $message = __('Data has been changed for: %1.', implode(', ', $changedFields));
            }

            throw new LocalizedException($message);
        }
    }

    /**
     * @param null $flag
     *
     * @return mixed
     */
    public function isTriggerCancelPoint($flag = null)
    {
        if (!is_null($flag)) {
            $this->setData('trigger_cancel_point', $flag);
        }

        return $this->getData('trigger_cancel_point');
    }

    /**
     * @inheritdoc
     */
    protected function _createItemsCollection($data)
    {
        $rmaData = new \Magento\Framework\DataObject($data);

        $this->_eventManager->dispatch(
            'riki_rma_create_items_collection_before',
            ['rma_data' => $rmaData]
        );

        $data = $rmaData->getData();

        if (isset($data['skip_create_items_collection']) && $data['skip_create_items_collection']) {
            return true;
        }
        // set bundle_item_earned_point, return_wrapping_fee value to items
        $rmaItems = parent::_createItemsCollection($data);
        $wrappingFeeData = $this->rmaBundleItem->getWrappingFeeData($this, $data, $rmaItems);
        foreach ($rmaItems as $item) {
            $orderItemId = $item->getOrderItemId();
            $orderItem = $this->rmaBundleItem->getOrderItemById($orderItemId);
            if ($parentItemId = $orderItem->getParentItemId()) {
                $bundleItemEarnedPoint = $this->rmaBundleItem->getBundleItemEarnedPoint($orderItemId, $item);
                $item->setData('bundle_item_earned_point', $bundleItemEarnedPoint);
                if (isset($wrappingFeeData[$parentItemId][$orderItemId])) {
                    $item->setData('return_wrapping_fee', $wrappingFeeData[$parentItemId][$orderItemId]);
                }
            }

        }
        return $rmaItems;
    }

    /**
     * @return $this
     * @throws LocalizedException
     */
    public function beforeSave()
    {
        parent::beforeSave();
        $validator = $this->validatorFactory->createValidator('rma', 'rma_before_save_validation');
        if (!$validator->isValid($this)) {
            throw new LocalizedException(__(
                'Please review and save the RMA again. Error detail: %1',
                implode('; ', $validator->getMessages())
            ));
        }
        return $this;
    }

    public function afterSave()
    {
        parent::afterSave();
        $refundAllowValues = [
            0 => 'NO',
            1 => 'YES'
        ];
        try {
            $refundAllowed = $this->getData('refund_allowed');
            $origRefundAllowed = $this->getOrigData('refund_allowed');
            if ($refundAllowed != $origRefundAllowed) {
                if (isset($refundAllowValues[$refundAllowed]) && isset($refundAllowValues[$origRefundAllowed])) {
                    $writer = new \Zend\Log\Writer\Stream(BP . '/var/log/NED-1129.log');
                    $logger = new \Zend\Log\Logger();
                    $logger->addWriter($writer);
                    $objManager = \Magento\Framework\App\ObjectManager::getInstance();
                    $authSession = $objManager->get('\Magento\Backend\Model\Auth\Session');
                    $logInfo = __('ReturnID: %1 "Refund allowed" change from %2 to %3',
                        $this->getId(),
                        $refundAllowValues[$origRefundAllowed],
                        $refundAllowValues[$refundAllowed]
                    );
                    if ($user = $authSession->getUser()) {
                        if ($email = $user->getEmail()) {
                            $logInfo = __(
                                'ReturnID: %1 "Refund allowed" change from %2 to %3 by %4',
                                $this->getId(),
                                $refundAllowValues[$origRefundAllowed],
                                $refundAllowValues[$refundAllowed],
                                $email
                            );
                        }
                    }
                    throw new LocalizedException($logInfo);
                }
            }
        } catch (LocalizedException $e) {
            $logger->info($e);
        }
        return $this;
    }

    /**
     * Get reason ID
     *
     * @return int
     */
    public function getReasonId()
    {
        return $this->getData('reason_id');
    }

    /**
     * Set reason ID
     *
     * @param int $reasonId
     * @return \Riki\Rma\Api\Data\RmaInterface
     */
    public function setReasonId($reasonId)
    {
        return $this->setData('reason_id', $reasonId);
    }

    /**
     * Get shipment number
     *
     * @return string
     */
    public function getRmaShipmentNumber()
    {
        return $this->getData('rma_shipment_number');
    }

    /**
     * Set shipment number
     *
     * @param string $shipmentIncrementId
     * @return \Riki\Rma\Api\Data\RmaInterface
     */
    public function setRmaShipmentNumber($shipmentIncrementId)
    {
        return $this->setData('rma_shipment_number', $shipmentIncrementId);
    }

    /**
     * Get returned warehouse
     *
     * @return string
     */
    public function getReturnedWarehouse()
    {
        return $this->getData('returned_warehouse');
    }

    /**
     * Set returned warehouse
     *
     * @param string $warehouse
     * @return \Riki\Rma\Api\Data\RmaInterface
     */
    public function setReturnedWarehouse($warehouse)
    {
        return $this->setData('returned_warehouse', $warehouse);
    }

    /**
     * Get returned warehouse
     *
     * @return string
     */
    public function getFullPartial()
    {
        return $this->getData('full_partial');
    }

    /**
     * Set returned warehouse
     *
     * @param string $fullPartial
     * @return \Riki\Rma\Api\Data\RmaInterface
     */
    public function setFullPartial($fullPartial)
    {
        return $this->setData('full_partial', $fullPartial);
    }

    /**
     * Get returned_date
     *
     * @return string
     */
    public function getReturnedDate()
    {
        return $this->getData('returned_date');
    }

    /**
     * Set returned_date
     *
     * @param string $dateRequested
     * @return \Riki\Rma\Api\Data\RmaInterface
     */
    public function setReturnedDate($dateRequested)
    {
        return $this->setData('returned_date', $dateRequested);
    }

    /**
     * Get substitution order number
     *
     * @return string
     */
    public function getSubstitutionOrder()
    {
        return $this->getData('substitution_order');
    }

    /**
     * Set substitution order number
     *
     * @param string $orderNumber
     * @return \Riki\Rma\Api\Data\RmaInterface
     */
    public function setSubstitutionOrder($orderNumber)
    {
        return $this->setData('substitution_order', $orderNumber);
    }

    /**
     * Get refund allowed
     *
     * @return int
     */
    public function getRefundAllowed()
    {
        return $this->getData('refund_allowed');
    }

    /**
     * Set refund allowed
     *
     * @param int $refundAllowed
     * @return \Riki\Rma\Api\Data\RmaInterface
     */
    public function setRefundAllowed($refundAllowed)
    {
        return $this->setData('refund_allowed', $refundAllowed);
    }
}
