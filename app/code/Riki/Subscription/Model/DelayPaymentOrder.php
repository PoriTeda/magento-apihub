<?php
namespace Riki\Subscription\Model;

use Magento\Framework\Api\SortOrder;
use Magento\Framework\DB\Transaction;
use Magento\Framework\DB\TransactionFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Api\Data\ShipmentInterface;
use Magento\Sales\Model\Order;
use Riki\Loyalty\Model\Reward;
use Riki\Sales\Model\ResourceModel\Order\OrderStatus;
use Riki\Subscription\Exception\DelayPaymentPointException;
use Riki\Subscription\Exception\DelayPaymentReAuthorizeException;
use Riki\Subscription\Exception\DelayPaymentSaveReAuthorizeDataException;
use Riki\SubscriptionCourse\Model\Course;

class DelayPaymentOrder
{
    const IS_APPLIED_DELAY_PAYMENT_POINT = 'is_applied_delay_payment_point';

    /**
     * @var \Magento\Sales\Model\Order
     */
    private $order;

    /**
     * @var Profile\ProfileFactory
     */
    private $profileFactory;
    /**
     * @var \Riki\SubscriptionCourse\Api\CourseRepositoryInterface
     */
    private $courseRepository;
    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @var \Riki\Loyalty\Model\RewardFactory
     */
    private $rewardFactory;

    /**
     * @var \Riki\Loyalty\Helper\Data
     */
    private $loyaltyHelper;

    /**
     * @var \Bluecom\Paygent\Model\PaygentManagement
     */
    private $paygentManagement;

    /**
     * @var \Magento\Sales\Model\Order\PaymentFactory
     */
    private $paymentFactory;

    /**
     * @var \Bluecom\Paygent\Helper\Data
     */
    private $paygentHelper;

    /**
     * @var \Riki\Subscription\Logger\DelayPayment
     */
    private $logger;

    /**
     * @var \Magento\Customer\Api\CustomerRepositoryInterface
     */
    private $customerRepository;

    /**
     * @var \Riki\Sales\Helper\Data
     */
    private $salesHelper;

    /**
     * @var \Riki\Subscription\Helper\Data
     */
    private $subscriptionHelper;

    /**
     * @var \Riki\AdvancedInventory\Model\ResourceModel\OutOfStock\CollectionFactory
     */
    private $outOfStockCollectionFactory;

    /**
     * list of calculated removed items
     *
     * @var
     */
    protected $totalAmountRemovedItem;
    /**
     * list of removed items
     *
     * @var
     */
    protected $totalRemovedItems;

    /**
     * @var \Magento\Framework\App\State
     */
    private $appState;
    /**
     * @var \Magento\Sales\Api\OrderRepositoryInterface
     */
    private $orderRepository;
    /**
     * @var \Magento\Framework\Api\SearchCriteriaBuilder
     */
    private $seachCriteriaBuilder;

    /**
     * @var TransactionFactory
     */
    private $transactionFactory;

    /**
     * [per removed items SKU, per order amount]
     *
     * @var int
     */
    private $calculatePointType;

    /**
     * @var int
     */
    private $calculatedPoint;

    /**
     * @var \Magento\Sales\Model\ResourceModel\Order\Shipment\CollectionFactory
     */
    protected $_shipmentCollectionFactory;
    /**

     * DelayPaymentOrder constructor.
     * @param \Magento\Sales\Model\Order $order
     * @param Profile\ProfileFactory $profileFactory
     * @param \Riki\SubscriptionCourse\Api\CourseRepositoryInterface $courseRepository
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Riki\Loyalty\Model\RewardFactory $rewardFactory
     * @param \Riki\Loyalty\Helper\Data $loyaltyHelper
     * @param \Bluecom\Paygent\Model\PaygentManagement $paygentManagement
     * @param \Bluecom\Paygent\Helper\Data $paygentHelper
     * @param \Magento\Sales\Model\Order\PaymentFactory $paymentFactory
     * @param \Riki\Subscription\Logger\DelayPayment $logger
     * @param \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository
     * @param \Riki\Sales\Helper\Data $salesHelper
     * @param \Riki\Subscription\Helper\Data $subscriptionHelper
     * @param \Riki\AdvancedInventory\Model\ResourceModel\OutOfStock\CollectionFactory $outOfStockCollectionFactory
     * @param \Magento\Framework\App\State $appState
     * @param \Magento\Framework\Api\SearchCriteriaBuilder $seachCriteriaBuilder
     * @param \Magento\Sales\Api\OrderRepositoryInterface $orderRepository
     * @param TransactionFactory $transactionFactory
     */
    public function __construct(
        \Magento\Sales\Model\Order $order,
        \Riki\Subscription\Model\Profile\ProfileFactory $profileFactory,
        \Riki\SubscriptionCourse\Api\CourseRepositoryInterface $courseRepository,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Riki\Loyalty\Model\RewardFactory $rewardFactory,
        \Riki\Loyalty\Helper\Data $loyaltyHelper,
        \Bluecom\Paygent\Model\PaygentManagement $paygentManagement,
        \Bluecom\Paygent\Helper\Data $paygentHelper,
        \Magento\Sales\Model\Order\PaymentFactory $paymentFactory,
        \Riki\Subscription\Logger\DelayPayment $logger,
        \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository,
        \Riki\Sales\Helper\Data $salesHelper,
        \Riki\Subscription\Helper\Data $subscriptionHelper,
        \Riki\AdvancedInventory\Model\ResourceModel\OutOfStock\CollectionFactory $outOfStockCollectionFactory,
        \Magento\Framework\App\State $appState,
        \Magento\Framework\Api\SearchCriteriaBuilder $seachCriteriaBuilder,
        \Magento\Sales\Api\OrderRepositoryInterface $orderRepository,
        \Magento\Sales\Model\ResourceModel\Order\Shipment\CollectionFactory $shipmentCollectionFactory,
        TransactionFactory $transactionFactory
    ) {
        $this->order = $order;
        $this->profileFactory = $profileFactory;
        $this->scopeConfig = $scopeConfig;
        $this->rewardFactory = $rewardFactory;
        $this->loyaltyHelper = $loyaltyHelper;
        $this->paygentManagement = $paygentManagement;
        $this->paymentFactory = $paymentFactory;
        $this->paygentHelper = $paygentHelper;
        $this->logger = $logger;
        $this->customerRepository = $customerRepository;
        $this->salesHelper = $salesHelper;
        $this->subscriptionHelper = $subscriptionHelper;
        $this->outOfStockCollectionFactory = $outOfStockCollectionFactory;
        $this->appState = $appState;
        $this->courseRepository = $courseRepository;
        $this->orderRepository = $orderRepository;
        $this->seachCriteriaBuilder = $seachCriteriaBuilder;
        $this->_shipmentCollectionFactory = $shipmentCollectionFactory;
        $this->transactionFactory = $transactionFactory;
    }

    /**
     * @return \Magento\Sales\Model\Order
     */
    public function getOrder()
    {
        return $this->order;
    }

    /**
     * @return \Magento\Customer\Api\Data\CustomerInterface
     * @throws \Exception
     */
    public function getCustomer()
    {
        try {
            return $this->customerRepository->getById($this->getOrder()->getCustomerId());
        } catch (\Exception $e) {
            $this->logger->critical($e);
            throw $e;
        }
    }

    /**
     * @return mixed
     */
    public function getCustomerId()
    {
        return $this->getCustomer()->getId();
    }

    /**
     * @return mixed|null
     */
    public function getCustomerCode()
    {
        if ($consumerDbIdAttr = $this->getCustomer()->getCustomAttribute('consumer_db_id')) {
            return $consumerDbIdAttr->getValue();
        }

        return null;
    }

    /**
     * Need to re-authorize first for DELAY PAYMENT order
     *
     * @return $this
     * @throws LocalizedException
     */
    public function prepare()
    {
        $order = $this->getOrder();
        $subscriptionProfileId = $order->getSubscriptionProfileId();
        $subscriptionCourse = $this->getSubscriptionCourse($subscriptionProfileId);

        $this->logger->info('=== Preparing data Order #'. $order->getIncrementId().' ====');
        $this->logger->info('Order #'. $order->getIncrementId(). ' order subscription time : '.$order->getSubscriptionOrderTime());
        $this->logger->info('Order #'. $order->getIncrementId(). ' is shopping point deduction : '.$subscriptionCourse->getIsShoppingPointDeduction());

        if ($order->getSubscriptionOrderTime() == 1
            && $subscriptionCourse->getIsShoppingPointDeduction()
            && !$order->getData(self::IS_APPLIED_DELAY_PAYMENT_POINT)
        ) {
            $this->calculatePointType = $subscriptionCourse->getCapturedAmountCalculationOption();

            $order->getResource()->beginTransaction();
            try {
                $point = $this->calculateDelayPaymentPoint();
                if ($point) {
                    $this->logger->info(__(
                        'Order #%1 point amount to be converted : %2',
                        $order->getIncrementId(),
                        $point
                    ));
                    $this->addShoppingPoint();
                    $this->redeemShoppingPoint();
                    $this->correctOrderTotal();
                    $this->correctShipments();
                    $order->save();

                    $order->setData(self::IS_APPLIED_DELAY_PAYMENT_POINT, true);
                }

                $order->getResource()->commit();
            } catch (\Exception $e) {
                $order->getResource()->rollBack();
                $this->logger->info(__(
                    'Can not correct total order #%1. Error message: %2',
                    $order->getIncrementId(),
                    $e->getMessage()
                ));
                $this->logger->critical($e);
                throw $e;
            }
        }

        if ($this->getOrder()->getGrandTotal()) {
            try {
                $this->reAuthorize();

                /** @var Transaction $transaction */
                $transaction = $this->transactionFactory->create();
                $transaction->addObject($order)
                    ->save();
            } catch (DelayPaymentReAuthorizeException $e) {
                throw $e;
            } catch (\Exception $e) {
                throw new DelayPaymentSaveReAuthorizeDataException(__($e->getMessage()));
            }
        }
        $this->logger->info('=== Finish data change ====');
        return $this;
    }

    /**
     * @return int
     * @throws LocalizedException
     */
    private function calculateDelayPaymentPoint()
    {
        if ($this->calculatedPoint === null) {
            switch ($this->calculatePointType) {
                case Course::CAPTURE_AMOUNT_PER_SKU:
                    $point = $this->getPointFromRemovedProducts();
                    break;
                case Course::CAPTURE_AMOUNT_PER_ORDER_AMOUNT:
                    $point = $this->getPointFromOrderAmount();
                    break;
                default:
                    throw new LocalizedException(__('Captured amount calculation option is not defined'));
            }

            $this->calculatedPoint = $point;
        }

        return $this->calculatedPoint;
    }

    /**
     * @return $this
     * @throws LocalizedException
     */
    public function reAuthorize()
    {
        $order = $this->getOrder();

        list($status, $result, $paymentObject) = $this->paygentManagement->authorize($order);

        if (!$status) {
            $errorDetail = $paymentObject->getResponseDetail() ?: 'Others';
            $errorMessage = $this->paygentManagement->getPaygentModel()
                ->getErrorMessageByErrorCode($paymentObject->getResponseDetail());

            $message = __(
                'Order %1 cannot authorized successfully due to issue from Paygent: %2',
                $order->getIncrementId(),
                $errorMessage
            );

            $order->setPaymentErrorCode($errorDetail);
            $order->setPaymentStatus(
                \Riki\ArReconciliation\Model\ResourceModel\Status\PaymentStatus::PAYMENT_AUTHORIZED_FAILED
            );
            $order->setState(\Magento\Sales\Model\Order::STATE_PROCESSING);
            $order->addStatusHistoryComment(
                __('Authorize failed: %1', $errorMessage),
                OrderStatus::STATUS_ORDER_CAPTURE_FAILED
            );

            /** @var Transaction $transaction */
            $transaction = $this->transactionFactory->create();
            $transaction->addObject($order)
                ->save();

            try {
                $this->appState->emulateAreaCode(
                    \Magento\Framework\App\Area::AREA_FRONTEND,
                    [$this->paygentHelper, 'sendCaptureFailedMail'],
                    [$order, $errorDetail]
                );
            } catch (\Exception $e) {
                $this->logger->critical($e);
            }

            $this->logger->info($message);

            throw new DelayPaymentReAuthorizeException($message);
        }

        return $this;
    }

    /**
     * @param $amount
     * @return mixed
     */
    protected function convertAmountToPoint($amount)
    {
        return $amount;
    }

    /**
     * @return float|int
     * @throws LocalizedException
     */
    protected function getTotalRemovedItems()
    {
        $this->logger->info('Total amount removed Item  #'. $this->totalAmountRemovedItem);
        if ($this->totalAmountRemovedItem === null) {
            $this->totalAmountRemovedItem = 0;

            $removedItems = $this->getRemovedItems();

            /** @var \Magento\Sales\Model\Order\Item $item */
            foreach ($removedItems as $item) {
                $this->totalAmountRemovedItem += (float)$this->getOrderItemFinalTotal($item);
            }

            $this->totalAmountRemovedItem = min($this->totalAmountRemovedItem, $this->getOrder()->getTotalDue());
        }

        return $this->totalAmountRemovedItem;
    }

    /**
     * @param \Magento\Sales\Model\Order\Item $item
     * @return float|int|null
     */
    protected function getOrderItemFinalTotal(\Magento\Sales\Model\Order\Item $item)
    {
        $totalAmount = $item->getRowTotal()
            - $item->getDiscountAmount()
            + $item->getTaxAmount()
            + $item->getDiscountTaxCompensationAmount();

        $unitQty = $item->getUnitQty()? $item->getUnitQty() : 1;

        $qty = $item->getQtyOrdered() / $unitQty;

        return $totalAmount + ($item->getGwPrice() + $item->getGwTaxAmount()) * $qty;
    }

    /**
     * @param \Magento\Sales\Model\Order\Item $item
     * @return float|int|null
     */
    protected function getOrderItemFinalTotalFromArray(array $itemData)
    {
        $totalAmount = $itemData['row_total']
            - $itemData['discount_amount']
            + $itemData['tax_amount']
            + $itemData['discount_tax_compensation_amount'];

        $unitQty = $itemData['unit_qty'] ? $itemData['unit_qty'] : 1;

        $qty = $itemData['qty'] / $unitQty;

        return $totalAmount + ($itemData['gw_price'] + $itemData['gw_tax_amount']) * $qty;
    }

    /**
     * @return array
     * @throws LocalizedException
     */
    protected function getRemovedItems()
    {
        if ($this->totalRemovedItems === null) {
            $this->totalRemovedItems = [];
            $order = $this->getOrder();

            // NED-1589 Use next order items instead of current profile items
            $profileId = $order->getSubscriptionProfileId();
            $nextOrderTime = $order->getSubscriptionOrderTime() + 1;
            $nextOrder = $this->subscriptionHelper->getProfileOrderAtSpecificTime(
                $order->getSubscriptionProfileId(),
                $nextOrderTime
            );

            if ($nextOrder) {
                $this->logger->info('Next order number is  #'. $nextOrder->getIncrementId());
                $nextOrderItems = $nextOrder->getAllItems();
            } else {
                $this->logger->info('Next order number not found');
                // If profile is disengaged
                $profile = $this->profileFactory->create()->load($profileId);

                if (!$profile || !$profile->getId()) {
                    throw new LocalizedException(__('Profile #%1 do not exist.', $profileId));
                }
                // If profile is waiting for disengage or status is deactive
                if (($profile->getDisengagementDate() &&
                        $profile->getDisengagementReason() &&
                        $profile->getDisengagementUser() &&
                        $profile->getStatus()) || !$profile->getStatus()
                ) {
                    $this->logger->info('Profile is disengaged. Assume no change and will process fully capture');
                    // If profile is disengage - assume no change - return []
                    return [];
                } else {
                    throw new LocalizedException(
                        __('Next order after order Number #%1 does not exist.', $order->getIncrementId())
                    );
                }
            }
            // NED-1589 Get out of stock items
            /** @var \Riki\AdvancedInventory\Model\ResourceModel\OutOfStock\Collection $outofStockItemsCollection */
            $outofStockItemsCollection = $this->outOfStockCollectionFactory->create()
                ->addFieldToFilter('original_order_id', $nextOrder->getEntityId());
            foreach ($order->getAllItems() as $item) {
                if ($item->getParentItemId()
                    || $this->salesHelper->isAttachmentItem($item)
                ) {
                    continue;
                }

                foreach ($nextOrderItems as $nextOrderItem) {
                    if ($item->getProductId() == $nextOrderItem->getProductId()) {
                        continue 2;
                    }
                }

                foreach ($outofStockItemsCollection as $outofStockItem) {
                    if ($item->getProductId() == $outofStockItem->getProductId()) {
                        continue 2;
                    }
                }
                $this->logger->info('Removed SKU : '. $item->getSku());
                $this->totalRemovedItems[] = $item;
            }
        }

        return $this->totalRemovedItems;
    }

    /**
     * @return mixed
     */
    protected function getPointFromRemovedProducts()
    {
        return $this->convertAmountToPoint($this->getTotalRemovedItems());
    }

    /**
     * @return $this
     * @throws LocalizedException
     */
    protected function addShoppingPoint()
    {
        $orderNumber = $this->order->getIncrementId();

        if ($point = $this->calculateDelayPaymentPoint()) {
            $this->createNewRewardPointItem(
                $point,
                Reward::TYPE_CAMPAIGN,
                __('Added shopping point for Delay payment: %1', $orderNumber),
                Reward::STATUS_SHOPPING_POINT
            );
        }

        return $this;
    }

    /**
     * @return $this
     * @throws LocalizedException
     */
    protected function redeemShoppingPoint()
    {
        $orderNumber = $this->order->getIncrementId();

        if ($point = $this->calculateDelayPaymentPoint()) {
            $this->createNewRewardPointItem(
                $point,
                Reward::TYPE_ORDER_DISCOUNT,
                __('Redemption for Delay payment: %1', $orderNumber),
                Reward::STATUS_REDEEMED
            );
        }

        return $this;
    }

    /**
     * @param $point
     * @param $pointType
     * @param $description
     * @param $status
     * @return Reward
     * @throws LocalizedException
     */
    protected function createNewRewardPointItem($point, $pointType, $description, $status)
    {
        $orderNumber = $this->getOrder()->getIncrementId();
        /** @var \Riki\Loyalty\Model\Reward $model */
        $model = $this->rewardFactory->create();
        $arrData = [
            'point_type' => $pointType,
            'point' => $point,
            'description' => $description,
            'wbs_code' => $this->getConfig('wbs_code'),
            'account_code' => $this->getConfig('account_code'),
            'customer_id' => $this->getCustomerId(),
            'customer_code' => $this->getCustomerCode(),
            'order_no' => $orderNumber,
            'status'    =>  $status,
            'expiry_period' => $this->loyaltyHelper->getDefaultExpiryPeriod(),
            'action_date' => $this->loyaltyHelper->pointActionDate()
        ];

        $model->setData($arrData);

        try {
            $model->save();
        } catch (\Exception $e) {
            throw new LocalizedException(__(
                'Delay payment cannot create new reward point item. Error message: %1',
                $e->getMessage()
            ));
        }

        return $model;
    }

    /**
     * @return Order
     * @throws LocalizedException
     */
    protected function correctOrderTotal()
    {
        $order = $this->getOrder();
        $point = $this->calculateDelayPaymentPoint();

        $order->setGrandTotal(max($order->getGrandTotal() - $point, 0));
        $order->setBaseGrandTotal(max($order->getBaseGrandTotal() - $point, 0));

        if ($order->getFee() >= $order->getGrandTotal()) {
            $order->setFee(0);
            $order->setBaseFee(0);
            $order->setGrandTotal(0);
            $order->setBaseGrandTotal(0);
        }

        $order->setTotalDue(max($order->getTotalDue() - $point, 0));
        $order->setBaseTotalDue(max($order->getBaseTotalDue() - $point, 0));

        $order->setUsedPoint($order->getUsedPoint() + $point);
        $order->setUsedPointAmount($order->getUsedPointAmount() + $point);
        $order->setBaseUsedPointAmount($order->getBaseUsedPointAmount() + $point);

        $order->addStatusHistoryComment(__(
            'Added and redeemed %1 shopping points to pay for the order',
            $point
        ));

        return $order;
    }

    protected function getShipmentsCollectionIncludeSort($orderId){
        return $this->_shipmentCollectionFactory->create()
            ->setOrderFilter($orderId)
            ->setOrder('grand_total', SortOrder::SORT_ASC)
            ->load();
    }
    /**
     * @return $this
     * @throws LocalizedException
     */
    protected function correctShipments()
    {
        $orderId = $this->getOrder()->getEntityId();
        if ($this->calculateDelayPaymentPoint()) {
            $shipments = $this->getShipmentsCollectionIncludeSort($orderId)->getItems();

            switch ($this->calculatePointType) {
                case Course::CAPTURE_AMOUNT_PER_SKU:
                    $this->correctShipmentsByRemovedItems($shipments);
                    break;
                case Course::CAPTURE_AMOUNT_PER_ORDER_AMOUNT:
                    $this->correctShipmentsByOrderAmount($shipments);
                    break;
                default:
                    throw new LocalizedException(__('Captured amount calculation option is not defined'));
            }
        }

        return $this;
    }

    /**
     * @param ShipmentInterface[] $shipments
     * @return $this
     * @throws LocalizedException
     */
    protected function correctShipmentsByRemovedItems($shipments)
    {
        $removedItems = $this->getRemovedItems();

        if (!empty($removedItems)) {
            /** @var Order\Shipment $shipment */
            foreach ($shipments as $shipment) {
                $pointAmount = 0;

                /** @var Order\Shipment\Item $item */
                foreach ($shipment->getAllItems() as $item) {
                    /** @var \Magento\Sales\Model\Order\Item $removedItem */
                    foreach ($removedItems as $removedItem) {
                        if ($item->getOrderItemId() == $removedItem->getId()) {
                            $pointAmount += $removedItem->getRowTotalInclTax();
                        }
                    }
                }

                $this->correctShipment($shipment, $pointAmount);
            }
        }

        return $this;
    }

    /**
     * @param ShipmentInterface[] $shipments
     * @return $this
     * @throws LocalizedException
     */
    protected function correctShipmentsByOrderAmount($shipments)
    {
        if ($this->calculateDelayPaymentPoint()) {
            $shipmentsGrandTotal = 0;

            $lastShipmentId = null;

            foreach ($shipments as $shipment) {
                $shipmentsGrandTotal += (float)$shipment->getGrandTotal();
                $lastShipmentId = $shipment->getId();
            }

            $point = $this->calculateDelayPaymentPoint();

            /** @var Order\Shipment $shipment */
            foreach ($shipments as $shipment) {
                if ($shipment->getId() == $lastShipmentId) {
                    $shipmentPoint = $point;
                } else {
                    $pointRate = $shipmentsGrandTotal ? floatval($shipment->getGrandTotal()) / $shipmentsGrandTotal : 1;

                    $shipmentPoint = min(floor($point * $pointRate), (float)$shipment->getGrandTotal());

                    $point -= $shipmentPoint;

                    $point = max($point, 0);
                }

                $this->correctShipment($shipment, $shipmentPoint);
            }
        }

        return $this;
    }

    /**
     * @param Order\Shipment $shipment
     * @param int $pointAmount
     * @return $this
     * @throws LocalizedException
     */
    protected function correctShipment(
        Order\Shipment $shipment,
        $pointAmount
    ) {
        $isFreeOrder = $this->getOrder()->getGrandTotal() == 0;

        if ($isFreeOrder) {
            $pointAmount += $shipment->getShipmentFee();
        }

        $pointAmount = (int)(min($pointAmount, $shipment->getGrandTotal()));

        if ($pointAmount) {
            $shipment->setBaseShoppingPointAmount(
                $shipment->getBaseShoppingPointAmount() + $pointAmount
            );
            $shipment->setShoppingPointAmount(
                $shipment->getShoppingPointAmount() + $pointAmount
            );

            $shipment->setGrandTotal(
                $shipment->getGrandTotal() - $pointAmount
            );
        }

        if ($isFreeOrder) {
            $shipment->setGrandTotal(
                max($shipment->getGrandTotal() - $shipment->getPaymentFee(), 0)
            );

            $shipment->setBasePaymentFee(0);
            $shipment->setPaymentFee(0);
        }

        try {
            $shipment->save();
        } catch (\Exception $e) {
            throw new LocalizedException(__(
                'Can not correct total for the shipment %1. Error message: %2',
                $shipment->getIncrementId(),
                $e->getMessage()
            ));
        }

        return $this;
    }

    /**
     * @param $field
     * @return mixed
     */
    protected function getConfig($field)
    {
        return $this->scopeConfig->getValue('riki_loyalty/delay_payment/' . $field);
    }

    /**
     * @param $profileId
     * @return \Riki\SubscriptionCourse\Api\Data\SubscriptionCourseInterface
     * @throws LocalizedException
     */
    protected function getSubscriptionCourse($profileId)
    {
        $profile = $this->profileFactory->create()->load($profileId);
        if (!$profile || !$profile->getId()) {
            throw new LocalizedException(__('Profile #%1 do not exist.', $profileId));
        }
        $courseId = $profile->getCourseId();
        if ($courseId) {
            try {
                $courseObject = $this->courseRepository->get($courseId);
                return $courseObject;
            } catch (\Exception $e) {
                throw new LocalizedException(__('Subscription Course #%1 do not exist.', $courseId));
            }
        }
    }

    /**
     * @param int $profileId
     * @return Profile\Profile
     * @throws LocalizedException
     */
    protected function getProfileById($profileId)
    {
        try {
            return $profile = $this->profileFactory->create()->load($profileId);
        } catch (\Exception $e) {
            throw new LocalizedException(__('Profile #%1 do not exist.', $profileId));
        }
    }

    /**
     * Get total amount if seccond order has OOS orders
     * @param $originalOrderId
     * @return float|int
     */
    public function getTotalAmountOOS($originalOrderId)
    {
        $outofStockItemsCollection = $this->outOfStockCollectionFactory->create()
            ->addFieldToFilter('original_order_id', $originalOrderId);
        $outStockSKU = [];
        $totalOOSAmount = 0;
        if ($outofStockItemsCollection->getItems()) {
            foreach ($outofStockItemsCollection->getItems() as $item) {
                // find generated OOS order
                // If oos order is already generated - get from oos order grandtotal.
                if ($item->getData('generated_order_id')) {
                    $oosOrder = $this->orderRepository->get($item->getData('generated_order_id'));
                    $totalOOSAmount += $oosOrder->getGrandTotal();
                } else {
                // If oos order is not generated - get from oos quote_item_data
                    $itemOption = json_decode($item->getData('quote_item_data'),true)[0];
                    if (!empty($itemOption)) {
                        $totalOOSAmount += $this->getOrderItemFinalTotalFromArray($itemOption);
                    }
                }
            }
        }
        return $totalOOSAmount;
    }

    /**
     * @return int
     * @throws LocalizedException
     */
    protected function getPointFromOrderAmount()
    {
        $order = $this->getOrder();
        $subscriptionProfileId = $order->getSubscriptionProfileId();
        $firstOrderTotalAmount  = $order->getGrandTotal();
        $point = 0;
        $subscriptionProfile = $this->getProfileById($subscriptionProfileId);
        $secondOrder = $this->subscriptionHelper->getProfileOrderAtSpecificTime(
            $subscriptionProfileId,
            2
        );
        // If profile is waiting for disengage or status is deactive
        if (($subscriptionProfile->getDisengagementDate() &&
                $subscriptionProfile->getDisengagementReason() &&
                $subscriptionProfile->getDisengagementUser() &&
                $subscriptionProfile->getStatus()) || !$subscriptionProfile->getStatus()
        ) {
            if (!$secondOrder) {
                $point = 0;
            } else {
                $secondOrderTotalAmount = $secondOrder->getGrandTotal();
                $secondOrderTotalAmount += $this->getTotalAmountOOS($secondOrder->getEntityId());
                if ($firstOrderTotalAmount > $secondOrderTotalAmount) {
                    $point = $firstOrderTotalAmount - $secondOrderTotalAmount;
                }
            }
        } else {
            if (!$secondOrder) {
                $errorMessage = __('Second order of this profile :%1 does not exits', $subscriptionProfileId);
                $this->logger->info($errorMessage);
                throw new LocalizedException($errorMessage);
            } else {
                $secondOrderTotalAmount = $secondOrder->getGrandTotal();
                $secondOrderTotalAmount += $this->getTotalAmountOOS($secondOrder->getEntityId());
                if ($firstOrderTotalAmount > $secondOrderTotalAmount) {
                    $point = $firstOrderTotalAmount - $secondOrderTotalAmount;
                }
            }
        }
        return $point;
    }
}