<?php
namespace Riki\AdvancedInventory\Observer;

use Magento\Quote\Model\Quote\Item;
use Magento\Tax\Model\TaxCalculation;
use Riki\AdvancedInventory\Model\OutOfStock;

class OosSubmitAfter implements \Magento\Framework\Event\ObserverInterface
{
    /**
     * @var array
     */
    protected $submittedOrders = [];

    /**
     * @var \Magento\Framework\MessageQueue\PublisherInterface
     */
    protected $oosPublisher;

    /**
     * @var \Magento\Framework\Registry
     */
    protected $registry;

    /**
     * @var \Riki\AdvancedInventory\Api\OutOfStockRepositoryInterface
     */
    protected $oosRepository;

    /**
     * @var \Riki\AdvancedInventory\Helper\Logger
     */
    protected $loggerHelper;

    /**
     * @var \Riki\AdvancedInventory\Helper\OutOfStock
     */
    protected $oosHelper;

    /**
     * @var \Riki\AdvancedInventory\Model\Queue\Schema\OosQueueSchemaFactory
     */
    protected $oosQueueSchemaFactory;

    /**
     * @var \Riki\AdvancedInventory\Observer\OosCapture
     */
    protected $oosCaptureObserver;

    /**
     * @var \Riki\Tax\Helper\Data
     */
    protected $taxHelper;

    /**
     * @var \Riki\ShipLeadTime\Api\StockStateInterface
     */
    protected $leadTimeStockStatus;

    /**
     * @var \Magento\Framework\Stdlib\DateTime\Timezone
     */
    protected $timezone;

    /**
     * global variable to store out of stock item
     *      just only contain out of stock item which is main product of profile
     *      use for stock point case only
     * @var array
     */
    protected $outOfStockItems = [];

    /**
     * @var TaxCalculation
     */
    protected $taxCalculation;

    /**
     * @var \Riki\SalesRule\Model\ResourceModel\Rule
     */
    protected $salesruleResourceModel;

    /**
     * OosSubmitAfter constructor.
     * @param \Magento\Framework\MessageQueue\PublisherInterface $publisher
     * @param \Magento\Framework\Registry $registry
     * @param \Riki\AdvancedInventory\Api\OutOfStockRepositoryInterface $oosRepository
     * @param \Riki\AdvancedInventory\Helper\Logger $loggerHelper
     * @param \Riki\AdvancedInventory\Helper\OutOfStock $oosHelper
     * @param \Riki\AdvancedInventory\Model\Queue\Schema\OosQueueSchemaFactory $oosQueueSchemaFactory
     * @param OosCapture $oosCaptureObserver
     * @param \Riki\Tax\Helper\Data $taxHelper
     * @param \Riki\ShipLeadTime\Api\StockStateInterface $leadTimeStockStatus
     * @param TaxCalculation $taxCalculation
     */
    public function __construct(
        \Magento\Framework\MessageQueue\PublisherInterface $publisher,
        \Magento\Framework\Registry $registry,
        \Riki\AdvancedInventory\Api\OutOfStockRepositoryInterface $oosRepository,
        \Riki\AdvancedInventory\Helper\Logger $loggerHelper,
        \Riki\AdvancedInventory\Helper\OutOfStock $oosHelper,
        \Riki\AdvancedInventory\Model\Queue\Schema\OosQueueSchemaFactory $oosQueueSchemaFactory,
        \Riki\AdvancedInventory\Observer\OosCapture $oosCaptureObserver,
        \Riki\Tax\Helper\Data $taxHelper,
        \Riki\ShipLeadTime\Api\StockStateInterface $leadTimeStockStatus,
        TaxCalculation $taxCalculation,
        \Magento\Framework\Stdlib\DateTime\Timezone $timezone,
        \Riki\SalesRule\Model\ResourceModel\Rule $salesruleResourceModel
    ) {
        $this->oosPublisher = $publisher;
        $this->registry = $registry;
        $this->oosRepository = $oosRepository;
        $this->loggerHelper = $loggerHelper;
        $this->oosHelper = $oosHelper;
        $this->oosQueueSchemaFactory = $oosQueueSchemaFactory;
        $this->oosCaptureObserver = $oosCaptureObserver;
        $this->taxHelper = $taxHelper;
        $this->leadTimeStockStatus = $leadTimeStockStatus;
        $this->taxCalculation = $taxCalculation;
        $this->timezone = $timezone;
        $this->salesruleResourceModel = $salesruleResourceModel;
    }

    /**
     * {@inheritdoc}
     *
     * @param \Magento\Framework\Event\Observer $observer
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        /** @var \Magento\Quote\Model\Quote $quote */
        $quote = $observer->getEvent()->getQuote();
        if (!$quote instanceof \Magento\Quote\Model\Quote) {
            return;
        }

        /** @var \Magento\Sales\Model\Order $order */
        $order = $observer->getEvent()->getOrder();
        if (!$order instanceof \Magento\Sales\Model\Order || !$order->getId()) {
            return;
        }

        $commissionPercent = $this->taxHelper->getCustomerCommissionPercent($quote);

        $oosItems = $this->registry->registry('current_oos_generating');

        if ($oosItems) {
            foreach ($oosItems as $oos) {
                if ($oos instanceof OutOfStock) {
                    $origOrder = $oos->getOriginalOrder();
                    if ($origOrder instanceof \Magento\Sales\Model\Order) {
                        /*sync data from original order for out of stock order*/
                        $order->setRikiType($origOrder->getRikiType());
                        $order->setSubscriptionProfileId($origOrder->getSubscriptionProfileId());
                        break;
                    }
                }
            }
        }

        $this->submittedOrders[$order->getId()] = true;

        $outOfStocks = $this->oosCaptureObserver->getOutOfStocks($quote->getId());
        if (!$outOfStocks) {
            return;
        }

        $oosQuote = $this->oosHelper->getOutOfStockQuote();
        if (!$oosQuote instanceof \Magento\Quote\Model\Quote
            || !$oosQuote->getId()
        ) {
            return;
        }

        /**
         * flag to check the order that has been generated by this quote, is stock point order
         *      if true, out of stock item will be push to queue immediately if item is in stock at other warehouse
         *      also, if more than one item is in stock, out of stock order will be contain more than one item
         *      only apply for main product of profile,
         *          do not apply for free item like free gift, winner prize, free machine
         */
        $isStockPointOrder = $quote->getData(\Riki\Subscription\Helper\Order\Data::IS_STOCK_POINT_ORDER);

        /** @var OutOfStock $outOfStock */
        foreach ($outOfStocks as $outOfStock) {
            $logData = [
                'quoteId' => $quote->getId(),
                'orderId' => $order->getId(),
                'outOfStockProductId' => $outOfStock->getProductId(),
                'outOfStockQty' => $outOfStock->getQty(),
                'outOfStockProfileId' => $outOfStock->getSubscriptionProfileId(),
                'outOfStockSaleruleId' => $outOfStock->getSalesruleId(),
                'outOfStockPrizeId' => $outOfStock->getPrizeId(),
                'outOfStockMachineSkuId' => $outOfStock->getMachineSkuId(),
                'outOfStockIsDuoMachine' => $outOfStock->getIsDuoMachine(),
            ];

            try {
                /** @var Item $quoteItem */
                $quoteItem = $outOfStock->getData('quote_item');
                if (!$quoteItem instanceof Item
                    || $quoteItem->getOosUniqKey() != $outOfStock->getUniqKey()
                ) {
                    $msg = array_merge($logData, ['msg' => 'Oos can not be captured']);
                    $this->loggerHelper->getOosLogger()->warning(\Zend_Json::encode($msg));
                    continue;
                }

                if ($quoteItem->getProductId() != $outOfStock->getProductId()) {
                    $msg = array_merge($logData, [
                        'msg' => 'Oos capture incorrect product',
                        'quoteItemProductId' => $quoteItem->getProductId()
                    ]);
                    $this->loggerHelper->getOosLogger()->warning(\Zend_Json::encode($msg));
                    continue;
                }

                //NED-7460: In case promotion gift is added to wrong subscription course
                if ($outOfStock->getSalesruleId()
                && $outOfStock->getSubscriptionProfileId()
                &&  $this->checkPromotionRuleIsNotRightForCourse(
                        $outOfStock->getSalesruleId(),
                        $quote->getData('riki_course_id'),
                        $quote->getData('riki_frequency_id')
                )) {
                    $msg = array_merge($logData, [
                        'msg' => 'Oos free gift is not right for course',
                        'quoteItemProductId' => $quoteItem->getProductId(),

                    ]);
                    $this->loggerHelper->getOosLogger()->warning(\Zend_Json::encode($msg));
                    continue;
                }


                $quoteItem->setQuoteId($oosQuote->getId());
                $quoteItem->setQuote($oosQuote);
                $this->updateFreeGiftTaxPercent($outOfStock, $quoteItem);
                $this->taxHelper->renderTaxRiki($quoteItem, $commissionPercent);
                $quoteItem->save();
                foreach ($quoteItem->getChildren() as $quoteItemChild) {
                    $quoteItemChild->setQuoteId($oosQuote->getId());
                    $quoteItemChild->setQuote($oosQuote);
                    $quoteItemChild->setParentItemId($quoteItem->getId());
                    $quoteItemChild->save();
                    if (!$quoteItemChild->getId()) {
                        continue;
                    }
                    $quoteItemChild->load($quoteItemChild->getId());
                }

                if (!$quoteItem->getId()) {
                    continue;
                }

                $quoteItem->load($quoteItem->getId());

                $quoteItemData = $quoteItem->getOrigData();
                foreach ($quoteItem->getOptions() as $quoteItemOption) {
                    $quoteItemData['options'][] = $quoteItemOption->getData();
                }

                $childrenQty = [];

                foreach ($quoteItem->getChildren() as $quoteItemChild) {
                    $childrenQty[] = [$quoteItemChild->getProductId()    =>  $quoteItemChild->getQty()];

                    $childItemData = $quoteItemChild->getOrigData();
                    foreach ($quoteItemChild->getOptions() as $quoteItemOption) {
                        $childItemData['options'][] = $quoteItemOption->getData();
                    }
                    $quoteItemData['children'][] = $childItemData;
                }

                try {
                    $quoteItemData = \Zend_Json::encode([$quoteItemData]);
                } catch (\Zend_Json_Exception $e) {
                    $quoteItemData = \Zend_Json::encode([]);
                }

                $outOfStock->setData('quote_item_children_qty', $childrenQty);
                $outOfStock->setQuoteItemData($quoteItemData);

                if (!$outOfStock->getSubscriptionProfileId()) {
                    $outOfStock->setSubscriptionProfileId($order->getSubscriptionProfileId());
                }
                $outOfStock->setOriginalOrderId($order->getId());
                $outOfStock->setQuoteItemId($quoteItem->getId());

                /** only profile stockpoint have original_delivery_date in additional oos table*/
                if ($order->getIsStockPoint()) {
                    $originalDeliveryDate = $this->registry->registry(
                        \Riki\Subscription\Helper\Order\Data::STOCK_POINT_ORIGINAL_DELIVERY_DATE
                    );
                    $originalDeliveryTimeSlot = $this->registry->registry(
                        \Riki\Subscription\Helper\Order\Data::STOCK_POINT_ORIGINAL_DELIVERY_TIME_SLOT
                    );

                    $additionalData = $this->getAdditionalDataOOS($outOfStock);
                    $additionalData['original_delivery_date'] = $originalDeliveryDate;
                    $additionalData['original_delivery_time_slot'] = $originalDeliveryTimeSlot;
                    $outOfStock->setAdditionalData(\Zend_Json::encode($additionalData));
                }
                $this->oosRepository->save($outOfStock);

                /*push out of stock item to global variable, then process it for stock point logic*/
                if ($isStockPointOrder) {
                    if (empty($this->outOfStockItems[$quote->getId()])) {
                        $this->outOfStockItems[$quote->getId()] = [];
                    }

                    $this->outOfStockItems[$quote->getId()][] = $outOfStock;
                }
            } catch (\Exception $e) {
                $this->loggerHelper->getOosLogger()->critical($e);
            }
        }

        $this->pushOutOfStockItemsToQueue($quote);

        $this->oosHelper->updateOutOfStockQuote();
        $this->oosCaptureObserver->cleanOutOfStocks($quote->getId());
    }

    /**
     * @param \Riki\AdvancedInventory\Model\OutOfStock $outOfStock
     * @return array|mixed
     */
    private function getAdditionalDataOOS($outOfStock)
    {
        try {
            $additionalData = \Zend_Json::decode($outOfStock->getData('additional_data') ?: '{}');
        } catch (\Zend_Json_Exception $e) {
            $this->loggerHelper->getOosLogger()->warning($e);
            $additionalData = [];
        }
        return $additionalData;
    }

    /**
     * @param string $time
     * @return bool
     */
    private function compareWithPresent($time)
    {
        $now = $this->timezone->scopeDate(
            null,
            date('Y-m-d H:i:s', strtotime($this->timezone->date()->format('Y-m-d H:i:s')))
        );

        $time = $this->timezone->scopeDate(null, date('Y-m-d H:i:s', strtotime($time)));
        if ($now <= $time) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Get orderId is submitted by checkout/generate
     *
     * @param $orderId
     *
     * @return bool
     */
    public function getIsSubmittedOrder($orderId)
    {
        return isset($this->submittedOrders[$orderId]);
    }

    /**
     * validate out of stock item again, and push to queue immediately if available stock in other warehouse
     *
     * @param \Magento\Quote\Model\Quote $quote
     */
    private function pushOutOfStockItemsToQueue(
        \Magento\Quote\Model\Quote $quote
    ) {
        $quoteId = $quote->getId();

        if (!empty($this->outOfStockItems[$quoteId])) {
            $this->repairQuoteForOutOfStockItem($quote);

            /*list of main product item which is in stock at any warehouse, need to be push to queue immediately*/
            $listOfMainProductItemNeedPushToQueue = [];
            /** @var OutOfStock $outOfStockItem */
            foreach ($this->outOfStockItems[$quoteId] as $outOfStockItem) {
                $qtyAssign = $outOfStockItem->getQty();

                $availableQty = $this->leadTimeStockStatus->checkAvailableQty(
                    $quote,
                    $outOfStockItem->getProductSku(),
                    $qtyAssign
                );

                /*stock point - out of stock item - available stock in other warehouse*/
                if ($availableQty >= $qtyAssign) {
                    /*for the case out of stock is free item, push item to queue immediately*/
                    if ($outOfStockItem->getIsFree()) {
                        $this->addCustomAdditionDataForOOSItem($quote, $outOfStockItem);
                        $outOfStockItem->pushIntoQueue();
                        continue;
                    }

                    /**
                     * for the case out of stock is not free item (main product)
                     *      push item to tmp array to process for multiple in stock item at the same time
                     */
                    $listOfMainProductItemNeedPushToQueue[$outOfStockItem->getId()] = $outOfStockItem;
                }
            }

            if ($listOfMainProductItemNeedPushToQueue) {
                try {
                    $outOfStockItemIds = implode(',', array_keys($listOfMainProductItemNeedPushToQueue));
                    /** @var \Riki\AdvancedInventory\Model\Queue\OosQueueSchemaInterface $outOfStockSchema */
                    $outOfStockSchema = $this->oosQueueSchemaFactory->create();
                    $outOfStockSchema->setOosModelId($outOfStockItemIds);
                    $this->oosPublisher->publish('oos.order.generate', $outOfStockSchema);
                    $this->loggerHelper->getOosLogger()->info(
                        'The oos entity #' . $outOfStockItemIds . ' was pushed into queue successfully.'
                    );
                } catch (\Exception $e) {
                    $this->loggerHelper->getOosLogger()->critical($e);
                    return;
                }

                /**/
                foreach ($listOfMainProductItemNeedPushToQueue as $outOfStock) {
                    /*change flag to waiting to avoid other process will push it to queue again*/
                    $outOfStock->setQueueExecute(
                        \Riki\AdvancedInventory\Api\Data\OutOfStock\QueueExecuteInterface::WAITING
                    );
                    $this->addCustomAdditionDataForOOSItem($quote, $outOfStock);

                    try {
                        $outOfStock->save();
                        $this->loggerHelper->getOosLogger()->info(
                            'The oos entity #' . $outOfStock->getId() . ' has changed status to waiting queue process.'
                        );
                    } catch (\Exception $e) {
                        $this->loggerHelper->getOosLogger()->critical($e);
                    }
                }
            }
        }
    }

    /**
     * repair quote for out of stock item
     *      remove stock point warehouse
     *      replace customer shipping address again
     *
     * @param \Magento\Quote\Model\Quote $quote
     */
    private function repairQuoteForOutOfStockItem(
        \Magento\Quote\Model\Quote $quote
    ) {
        /*remove specified warehouse*/
        $quote->setData(
            \Riki\AdvancedInventory\Model\Assignation::ASSIGNED_WAREHOUSE_ID,
            null
        );

        /*get customer shipping address via quote*/
        $customerShippingAddress = $quote->getCustomerShippingAddress();

        $customerShippingAddress->setType(\Magento\Customer\Model\Address\AbstractAddress::TYPE_SHIPPING);

        /*reset stock point address by customer address*/
        $quote->setShippingAddress($customerShippingAddress);
    }

    /**
     * Free gift is added while quote discount collect from vendor/magento/module-sales-rule/Model/Quote/Discount.php:62
     * So quote item of free gift will not be collected again
     * It cause quote item of free gift missing tax_percent
     * @param OutOfStock $outOfStock
     * @param Item $quoteItem
     * @return void
     */
    private function updateFreeGiftTaxPercent($outOfStock, $quoteItem)
    {
        if ($outOfStock->getSalesruleId()) {
            $product = $quoteItem->getProduct();
            $taxClassId = $product->getData('tax_class_id');
            $taxRate = $this->taxCalculation->getCalculatedRate($taxClassId);
            $quoteItem->setData('tax_percent', $taxRate);
        }
    }

    /**
     * @param \Magento\Quote\Model\Quote $quote
     * @param \Riki\AdvancedInventory\Model\OutOfStock $oosItem
     * @return mixed
     */
    private function addCustomAdditionDataForOOSItem($quote, $oosItem)
    {
        $isStockPointOrder = $quote->getData(\Riki\Subscription\Helper\Order\Data::IS_STOCK_POINT_ORDER);
        if ($isStockPointOrder) {
            $originalDeliverydate = $this->registry->registry(
                \Riki\Subscription\Helper\Order\Data::STOCK_POINT_ORIGINAL_DELIVERY_DATE
            );
            if (!$this->compareWithPresent($originalDeliverydate)) {
                $additionalData = $this->getAdditionalDataOOS($oosItem);
                unset($additionalData['original_delivery_date']);
                unset($additionalData['original_delivery_time_slot']);
                $oosItem->setAdditionalData(\Zend_Json::encode($additionalData));
            }
        }
        return $oosItem;
    }

    private function checkPromotionRuleIsNotRightForCourse($ruleId, $courseId, $frequencyId)
    {
        $courseId = $this->salesruleResourceModel->getSubscriptionRule($ruleId, $courseId, $frequencyId);

        return $courseId ? false : true;
    }
}
