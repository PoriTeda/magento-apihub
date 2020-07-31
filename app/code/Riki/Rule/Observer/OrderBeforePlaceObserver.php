<?php

namespace Riki\Rule\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\CatalogInventory\Api\StockRegistryInterface;
use Magento\Store\Model\ScopeInterface;
use Riki\Customer\Model\AmbCustomerRepository;
use Riki\Rule\Model\CumulatedGift;
use Riki\Rule\Logger\Logger;
use Magento\Framework\Registry;
use Riki\Customer\Model\CustomerRepository;

class OrderBeforePlaceObserver implements ObserverInterface
{
    /**
     * @var Logger
     */
    protected $_logger;

    /**
     * Config
     *
     * @var ScopeConfigInterface
     */
    protected $_config;

    /**
     * Product Repository
     *
     * @var ProductRepositoryInterface
     */
    protected $_productRepository;

    /**
     * Stock
     *
     * @var StockRegistryInterface
     */
    protected $_stockRegistry;

    /**
     * @var CustomerRepository
     */
    protected $_customerRepository;

    /**
     * @var AmbCustomerRepository
     */
    protected $_abmCustomerRepository;

    /**
     * @var CumulatedGift
     */
    protected $_cumulatedGift;

    /** @var \Riki\ShipLeadTime\Api\StockStateInterface  */
    protected $shipLeadTimeStockState;

    /**
     * @var Registry
     */
    protected $_registry;

    /**
     * @var \Riki\StockPoint\Helper\ValidateStockPointProduct
     */
    protected $validateStockPointProduct;

    /**
     * OrderBeforePlaceObserver constructor.
     *
     * @param Logger $logger
     * @param ScopeConfigInterface $config
     * @param ProductRepositoryInterface $productRepository
     * @param StockRegistryInterface $stockRegistry
     * @param AmbCustomerRepository $ambCustomerRepository
     * @param CustomerRepository $customerRepository
     * @param CumulatedGift $cumulatedGift
     * @param Registry $registry
     * @param \Riki\ShipLeadTime\Api\StockStateInterface $stockState
     * @param \Riki\StockPoint\Helper\ValidateStockPointProduct $validateStockPointProduct
     */
    public function __construct(
        Logger $logger,
        ScopeConfigInterface $config,
        ProductRepositoryInterface $productRepository,
        StockRegistryInterface $stockRegistry,
        AmbCustomerRepository $ambCustomerRepository,
        CustomerRepository $customerRepository,
        CumulatedGift $cumulatedGift,
        Registry $registry,
        \Riki\ShipLeadTime\Api\StockStateInterface $stockState,
        \Riki\StockPoint\Helper\ValidateStockPointProduct $validateStockPointProduct
    ) {
        $this->_registry = $registry;
        $this->_cumulatedGift = $cumulatedGift;
        $this->_logger = $logger;
        $this->_config = $config;
        $this->_productRepository = $productRepository;
        $this->_stockRegistry = $stockRegistry;
        $this->_abmCustomerRepository = $ambCustomerRepository;
        $this->_customerRepository = $customerRepository;
        $this->shipLeadTimeStockState = $stockState;
        $this->validateStockPointProduct = $validateStockPointProduct;
    }

    /**
     * @return Registry
     */
    public function getRegistry()
    {
        return $this->_registry;
    }

    /**
     * Save Promotion cumulative - 3.1.1
     *
     * @param \Magento\Framework\Event\Observer $observer
     * @return $this
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $this->_registry->unregister('cumulative_gift');

        /**
         * @var \Magento\Quote\Model\Quote $quote
         */
        $quote = $observer->getEvent()->getQuote();

        if ($quote->getSkipCumulativePromotion()
            || ($this->_registry->registry('skip_cumulative_promotion') == $quote->getId())
        ) {
            return $this;
        }

        $customer = $quote->getCustomer();
        $consumerId = $this->getConsumerID($customer);
        if (!$consumerId) {
            return $this;
        }

        try {
            $counter = $this->_cumulatedGift->getCounterFromCart($quote);

            $filterThreshold = $this->_config->getValue('promotionfilter/filter/filter_threshold', ScopeInterface::SCOPE_STORE);
            $giftSku = $this->_config->getValue('promotionfilter/filter/filter_part_sku', ScopeInterface::SCOPE_STORE);
            $giftWbs = $this->_config->getValue('promotionfilter/filter/filter_wbs', ScopeInterface::SCOPE_STORE);

            if ($counter) {

                if (!$filterThreshold || !$giftSku) {
                    return $this;
                }

                $customerCounter = $this->_cumulatedGift->getCustomerAPICounter($consumerId);
                $customerNewCounter = $customerCounter + $counter;

                $currentThreshold = ((int)($customerCounter / $filterThreshold) + 1) * $filterThreshold;
                $newThreshold = ((int)($customerNewCounter / $filterThreshold) + 1) * $filterThreshold;
                $qty = ($newThreshold - $currentThreshold) / $filterThreshold;

                $gift = $this->_productRepository->get($giftSku);

                $qtyNotAttach = $this->_cumulatedGift->countNotAttachByConsumer($consumerId);

                $availableQty = $this->getAvailableQty($quote, $giftSku, $qty + $qtyNotAttach);
                if ($availableQty < 0) {
                    $availableQty = 0;
                } else {
                    /**
                     * additional logic for stock point order (do not need to validate for simulate flow)
                     *      if cumulative gift is not allowed for stock point,
                     *          do not add it to current cart
                     */
                    if ($quote
                        && !$quote instanceof \Riki\Subscription\Model\Emulator\Cart
                        && $quote->getData(\Riki\Subscription\Helper\Order\Data::IS_STOCK_POINT_ORDER)
                    ) {
                        /*free gift product is not allowed for stock point order*/
                        if (!$this->validateStockPointProduct->isProductAllowedStockPoint($gift)) {
                            $availableQty = 0;
                        }
                    }
                }

                try {
                    // data to save cumulative table
                    $data = [
                        'consumer_db_id' => $consumerId,
                        'sku' => $giftSku,
                        'wbs' => $giftWbs
                    ];
                    if ($availableQty < $qty) {
                        // out of stock
                        $data['qty_success'] = $availableQty;
                        $data['qty_missing'] = $qty - $availableQty;

                        $qtyAddToCart = $availableQty;
                    } else {
                        // happy case
                        $data['qty_success'] = $qty;
                        $data['qty_missing'] = 0;

                        $qtyAddToCart = $qty;
                    }

                    // add gift to cart
                    if ($availableQty) {
                        $this->addProductToQuote($availableQty, $gift, $quote);
                    }

                    // check batch to attach to next order
                    $notAttachAvailableQty = max($availableQty - $qtyAddToCart, 0);

                    // update batch
                    if ($notAttachAvailableQty) {
                        $notAttachedIds = $this->_cumulatedGift->getResource()->getNotAttachedIds($consumerId);
                        $updateIds = array_slice($notAttachedIds, 0, $notAttachAvailableQty);
                        $data['update_ids'] = $updateIds;
                    }

                    $data['new_counter'] = $customerNewCounter;

                    $this->_registry->register('cumulative_gift', $data);

                } catch (\Exception $e) {
                    $this->_logger->info(
                        'Error when add gift to cart. Details: ' . $e->getMessage()
                    );
                }

                //$this->_cumulatedGift->setCustomerAPICounter($consumerId, $customerNewCounter);

            } else {
                $this->sendNotAttachGift($consumerId, $giftSku, $giftWbs, $quote);
            }

        } catch (\Exception $e) {
            $this->_logger->info(
                'Error when calculate promotion gift. Details: ' . $e->getMessage()
            );
        }

        $taxRikiSum = 0;
        $taxDefaultSum = 0;
        foreach ($quote->getAllVisibleItems() as $quoteItem) {
            $taxRikiSum += $quoteItem->getData('tax_riki');
            $taxDefaultSum += $quoteItem->getTaxAmount();
        }
        if ($taxRikiSum == 0 && $taxDefaultSum) {
            try {
                throw new \Exception('debug riki tax zero - place order');
            } catch (\Exception $e) {
                $logger = \Magento\Framework\App\ObjectManager::getInstance()->create(
                    'Riki\Framework\Helper\Logger\LoggerBuilder'
                )->setName(
                    'DebugRikiTax'
                )->setFileName(
                    'file'
                )->pushHandlerByAlias(
                    \Riki\Framework\Helper\Logger\LoggerBuilder::ALIAS_DATE_HANDLER
                )->create();
                $logger->info($quote->getId());
                $logger->critical($e);
            }
        }

        return $this;
    }

    /**
     * @param $consumerId
     * @param $giftSku
     * @param $giftWbs
     * @param $quote
     * @return $this
     */
    public function sendNotAttachGift($consumerId, $giftSku, $giftWbs, $quote)
    {
        $notAttachedIds = $this->_cumulatedGift->getResource()->getNotAttachedIds($consumerId);
        $numItemNotAttached = count($notAttachedIds);

        if ($numItemNotAttached) {
            if ($giftSku) {
                $gift = $this->_productRepository->get($giftSku);
                $availableQty = $this->shipLeadTimeStockState->checkAvailableQty($quote, $giftSku, $numItemNotAttached);
                if ($availableQty < 0) {
                    $availableQty = 0;
                } else {
                    /**
                     * additional logic for stock point order (do not need to validate for simualte flow)
                     *      if cumulative gift is not allowed for stock point,
                     *          do not add it to current cart
                     */
                    if ($quote
                        && !$quote instanceof \Riki\Subscription\Model\Emulator\Cart
                        && $quote->getData(\Riki\Subscription\Helper\Order\Data::IS_STOCK_POINT_ORDER)
                    ) {
                        /*free gift product is not allowed for stock point order*/
                        if (!$this->validateStockPointProduct->isProductAllowedStockPoint($gift)) {
                            $availableQty = 0;
                        }
                    }
                }
                $qty = min($availableQty, $numItemNotAttached);

                if ($qty) {

                    $this->addProductToQuote($qty, $gift, $quote);

                    $updateIds = array_slice($notAttachedIds, 0, $qty);
                    $data = [
                        'qty_success' => 0,
                        'qty_missing' => 0,
                        'update_ids' => $updateIds,
                        'sku' => $giftSku,
                        'wbs' => $giftWbs
                    ];
                    $this->_registry->register('cumulative_gift', $data);
                }
            }
        }
    }

    /**
     * @param \Magento\Quote\Model\Quote $quote
     * @param $giftSku
     * @param $qtyRequested
     * @return mixed
     */
    public function getAvailableQty(\Magento\Quote\Model\Quote $quote, $giftSku, $qtyRequested)
    {
        return $this->shipLeadTimeStockState->checkAvailableQty($quote, $giftSku, $qtyRequested);
    }

    /**
     * @param $customer
     * @return null
     */
    public function getConsumerID($customer)
    {
        $attribute = $customer->getCustomAttribute('consumer_db_id');
        if ($attribute && $attribute->getValue()) {
            return $attribute->getValue();
        }
        return null;
    }

    /**
     * @param \Magento\Quote\Model\Quote $quote
     * @return mixed|null|string
     */
    protected function getAddressIdForFreeItem(\Magento\Quote\Model\Quote $quote)
    {
        $customer = $quote->getCustomer();

        if ($customer->getDefaultShipping()) {
            return $customer->getDefaultShipping();
        }

        return $quote->getBillingAddress()->getId();
    }

    /**
     * @param $qty
     * @param $gift
     * @param $quote
     */
    protected function addProductToQuote($qty, $gift, $quote)
    {
        $requestInfo = [
            'qty' => $qty,
            'options' => [
                'ampromo_rule_id' => 'cumulative'
            ]
        ];

        $gift->setData('ampromo_rule_id', 'cumulative');

        $freeItem = $quote->addProduct($gift, new \Magento\Framework\DataObject($requestInfo));

        if ($freeItem instanceof \Magento\Quote\Model\Quote\Item) {
            $freeItem->setAddressId($this->getAddressIdForFreeItem($quote));
            $minDeliveryOrderItem = $this->getMinDeliveryItem($quote, $freeItem->getSku());
            if ($minDeliveryOrderItem) {
                $freeItem->setData('delivery_date', $minDeliveryOrderItem->getData('delivery_date'));
                $freeItem->setData('delivery_time', $minDeliveryOrderItem->getData('delivery_time'));
                $freeItem->setData('delivery_timeslot_id', $minDeliveryOrderItem->getData('delivery_timeslot_id'));
                $freeItem->setData('delivery_timeslot_from', $minDeliveryOrderItem->getData('delivery_timeslot_from'));
                $freeItem->setData('delivery_timeslot_to', $minDeliveryOrderItem->getData('delivery_timeslot_to'));
            }
        }
    }

    /**
     * @param \Magento\Quote\Model\Quote $quote
     * @param $giftSku
     * @return \Magento\Quote\Model\Quote\Item
     */
    protected function getMinDeliveryItem(\Magento\Quote\Model\Quote $quote, $giftSku)
    {
        $items = $quote->getAllItems();
        /**
         * @var \Magento\Quote\Model\Quote\Item $item
         */
        $minItem = $items[0];
        $minDD = $minItem->getData('delivery_date');
        foreach ($items as $item) {
            if ($item->getSku() != $giftSku && $item->getData('delivery_date') < $minDD) {
                $minDD = $item->getData('delivery_date');
                $minItem = $item;
            }
        }
        return $minItem;
    }
}