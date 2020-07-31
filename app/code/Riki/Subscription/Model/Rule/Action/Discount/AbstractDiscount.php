<?php

namespace Riki\Subscription\Model\Rule\Action\Discount;

use Magento\Catalog\Model\Product\Type as ProductType;
use Riki\CreateProductAttributes\Model\Product\CaseDisplay;
use Riki\Subscription\Helper\Order\Data as SubscriptionOrderHelper;

/**
 * Class AbstractDiscount
 * @package Riki\Subscription\Model\Rule\Action\Discount
 */
class AbstractDiscount extends \Amasty\Promo\Model\Rule\Action\Discount\AbstractDiscount
{
    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $_scopeConfig;

    /**
     * @var \Magento\Framework\Registry
     */
    protected $_registry;

    /**
     * @var \Magento\Catalog\Api\ProductRepositoryInterface
     */
    protected $_productRepository;

    /**
     * @var \Magento\Catalog\Model\ProductFactory
     */
    protected $_productFactory;

    /**
     * @var \Riki\Subscription\Logger\LoggerOrder
     */
    protected $_logger;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $_storeManager;

    /**
     * @var \Magento\Framework\Event\Manager
     */
    protected $_eventManager;

    /**
     * @var \Riki\Promo\Helper\Adminhtml\Cart
     */
    protected $_promoCartHelper;

    /**
     * @var \Riki\Promo\Helper\Data
     */
    protected $_rikiPromoHelper;

    /**
     * @var \Riki\StockPoint\Helper\ValidateStockPointProduct
     */
    protected $validateStockPointProduct;

    public function __construct(
        \Magento\SalesRule\Model\Validator $validator,
        \Magento\SalesRule\Model\Rule\Action\Discount\DataFactory $discountDataFactory,
        \Magento\Framework\Pricing\PriceCurrencyInterface $priceCurrency,
        \Magento\Framework\ObjectManagerInterface $objectManager,
        \Magento\Framework\Registry $registry,
        \Amasty\Promo\Helper\Item $promoItemHelper,
        \Amasty\Promo\Model\Registry $promoRegistry,
        \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $productCollectionFactory,
        \Magento\Catalog\Api\ProductRepositoryInterface $productRepositoryInterface,
        \Magento\Catalog\Model\ProductFactory $productFactory,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfigInterface,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\Event\Manager $eventManager,
        \Riki\Promo\Helper\Adminhtml\Cart $promoCartHelper,
        \Riki\Subscription\Logger\LoggerOrder $loggerOrder,
        \Riki\Promo\Helper\Data $rikiPromoHelper,
        \Riki\StockPoint\Helper\ValidateStockPointProduct $validateStockPointProduct
    ) {
        parent::__construct(
            $validator,
            $discountDataFactory,
            $priceCurrency,
            $objectManager,
            $promoItemHelper,
            $promoRegistry,
            $productCollectionFactory
        );

        $this->_registry = $registry;
        $this->_scopeConfig = $scopeConfigInterface;
        $this->_productRepository = $productRepositoryInterface;
        $this->_logger = $loggerOrder;
        $this->_storeManager = $storeManager;
        $this->_eventManager = $eventManager;
        $this->_promoCartHelper = $promoCartHelper;
        $this->_rikiPromoHelper = $rikiPromoHelper;
        $this->_productFactory = $productFactory;
        $this->validateStockPointProduct = $validateStockPointProduct;
    }

    /**
     * @param \Magento\SalesRule\Model\Rule $rule
     * @param \Magento\Quote\Model\Quote\Item $item
     * @param $qty
     */
    protected function _addFreeItems(
        \Magento\SalesRule\Model\Rule $rule,
        \Magento\Quote\Model\Quote\Item $item,
        $qty
    ) {

        $quote = $item->getQuote();

        if ($this->checkProcessedRule($quote, $rule->getId())) {
            return;
        }

        $ampromoRule = $this->_objectManager->get('Amasty\Promo\Model\Rule');

        $ampromoRule = $ampromoRule->loadBySalesrule($rule);

        $promoSku = $ampromoRule->getSku();
        if (!$promoSku) {
            return;
        }

        $qty = $this->_getFreeItemsQty($rule, $quote);
        if (!$qty) {
            return;
        }

        $promoSku = preg_split('/\s*,\s*/', $promoSku, -1, PREG_SPLIT_NO_EMPTY);

        if ($promoSku) {
            foreach ($promoSku as $sku) {
                if ($this->ableToAddSkuToQuote($quote, $sku)) {
                    $this->addPromoItem(
                        $quote,
                        $sku,
                        $qty,
                        $rule->getId()
                    );

                    if ($ampromoRule->getType() == \Amasty\Promo\Model\Rule::RULE_TYPE_ONE) {
                        break;
                    }
                }
            }
        }
    }

    /**
     * @param $quote
     * @param $sku
     * @param $qty
     * @param $ruleId
     * @return $this
     */
    public function addPromoItem($quote, $sku, $qty, $ruleId){

        if (!$qty) {
            return $this;
        }

        $addAutomatically = $this->_scopeConfig->isSetFlag(
            'ampromo/general/auto_add',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );

        try {
            // Load product to avoid the process add same products
            $product = $this->_productFactory->create()->loadByAttribute('sku', $sku);
            if (!$product) {
                $this->_logger->error(__('Amasty free gift: %1', 'No such entity with sku ' . $sku));
                return $this;
            }
        } catch (\Magento\Framework\Exception\NoSuchEntityException $e) {
            $this->_logger->error(__('Amasty free gift: %1', $e->getMessage()));
            return $this;
        } catch (\Exception $e) {
            $this->_logger->critical($e);
            return $this;
        }

        $unitQty = 1;
        if ($product->getCaseDisplay() == \Riki\CreateProductAttributes\Model\Product\CaseDisplay::CD_CASE_ONLY) {
            $unitQty = (null != $product->getUnitQty())?$product->getUnitQty():1;
        }

        if ($addAutomatically) {
            if (!$product->getId() || $product->getSku() != $sku) {
                return $this;
            }

            $currentWebsiteId = $this->_storeManager->getWebsite()->getId();
            if (!is_array($product->getWebsiteIds())
                || !in_array($currentWebsiteId, $product->getWebsiteIds())) {
                // Ignore products from other websites
                return $this;
            }

            if (in_array($product->getTypeId(), [ProductType::TYPE_SIMPLE, ProductType::TYPE_BUNDLE])) {
                /*for the case free gift is chirashi, quantity is always 1*/
                if ($product->getChirashi()) {
                    $qty = 1;
                }

                /*get correct qty for the case that product is case product*/
                $qty = $qty * $unitQty;

                if ($this->validateProductInStock($product)) {
                    if (!$this->canAddProduct($quote, [
                        'sku' => $sku,
                        'rule_id'   =>  $ruleId,
                        'qty' => $qty,
                        'is_chirashi'   =>  $product->getChirashi()
                    ])) {
                        return $this;
                    }

                    $this->addProduct(
                        $product,
                        $qty,
                        $quote,
                        $ruleId,
                        []
                    );

                    $this->_promoCartHelper->updateQuoteTotalQty(
                        false,
                        $quote
                    );
                } else {
                    $this->_eventManager->dispatch(\Riki\AdvancedInventory\Observer\OosCapture::EVENT, [
                    'quote' => $quote,
                    'product' => $product,
                    'qty' => $qty,
                    'salesrule_id' => $ruleId,
                    ]);
                }
            }
        }

        return $this;
    }

    /**
     * @param \Magento\Quote\Model\Quote $quote
     * @param array $itemData
     *
     * @return boolean
     */
    protected function canAddProduct(\Magento\Quote\Model\Quote $quote, array $itemData)
    {
        if (isset($itemData['is_chirashi']) &&
            $itemData['is_chirashi']
        ) {
            /** @var \Magento\Quote\Model\Quote\Item $item */
            foreach ($quote->getAllItems() as $item) {
                if ($this->promoItemHelper->isPromoItem($item) &&
                    $item->getSku() == $itemData['sku']
                ) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * @param \Magento\Catalog\Model\Product $product
     * @param $qty
     * @param bool|false $ruleId
     * @param array $requestParams
     * @param \Magento\Quote\Model\Quote $quote
     * @return $this|void
     */
    public function addProduct(
        \Magento\Catalog\Model\Product $product,
        $qty,
        \Magento\Quote\Model\Quote $quote,
        $ruleId = false,
        $requestParams = []
    ) {
        /**
         * additional logic for stock point order (do not need to validate for simulate flow)
         *      if free gift is not allowed for stock point, process it like out of stock item
         */
        if ($quote
            && !$quote instanceof \Riki\Subscription\Model\Emulator\Cart
            && $quote->getData(\Riki\Subscription\Helper\Order\Data::IS_STOCK_POINT_ORDER)
        ) {
            /*free gift product is not allowed for stock point order*/
            if (!$this->validateStockPointProduct->isProductAllowedStockPoint($product)) {
                $this->_eventManager->dispatch(\Riki\AdvancedInventory\Observer\OosCapture::EVENT, [
                    'quote' => $quote,
                    'product' => $product,
                    'qty' => $qty,
                    'salesrule_id' => $ruleId,
                ]);
                return;
            }
        }

        $availableQty = $this->_promoCartHelper->checkAvailableQty($product, $qty, $quote);

        if ($availableQty <= 0) {
            $this->_eventManager->dispatch(\Riki\AdvancedInventory\Observer\OosCapture::EVENT, [
                'quote' => $quote,
                'product' => $product,
                'qty' => $qty,
                'salesrule_id' => $ruleId,
            ]);
            return;
        } elseif ($availableQty < $qty) {
            $this->_eventManager->dispatch(\Riki\AdvancedInventory\Observer\OosCapture::EVENT, [
                'quote' => $quote,
                'product' => $product,
                'qty' => ($qty - $availableQty),
                'salesrule_id' => $ruleId,
            ]);
        }

        $qty = $availableQty;

        $requestInfo = [
            'qty' => $qty,
            'options' => []
        ];

        if (!empty($requestParams)) {
            $requestInfo = array_merge_recursive($requestParams, $requestInfo);
        }

        $requestInfo['options']['ampromo_rule_id'] = $ruleId;
        $requestInfo['options']['is_free_attachment'] = 1;

        if ($product->getTypeId() == ProductType::TYPE_BUNDLE) {
            $typeInstance = $product->getTypeInstance();
            $typeInstance->setStoreFilter($product->getStoreId(), $product);
            $optionCollection = $typeInstance->getOptionsCollection($product);
            $selectionCollection = $typeInstance->getSelectionsCollection($typeInstance->getOptionsIds($product), $product);
            $bundleOptions = [];
            $bundleOptionsQty = [];
            /** @var $option \Magento\Bundle\Model\Option */
            foreach ($optionCollection as $option) {

                /** @var $selection \Magento\Bundle\Model\Selection */
                foreach ($selectionCollection as $selection) {
                    if ($option->getId() == $selection->getOptionId()) {
                        $bundleOptions[$option->getId()] = $selection->getSelectionId();
                        $bundleOptionsQty[$option->getId()] = $selection->getSelectionQty() * 1;
                        break;
                    }
                }
            }

            $requestInfo['bundle_option'] = $bundleOptions;
            $requestInfo['bundle_option_qty'] = $bundleOptionsQty;
        }

        try {
            $product->setData('ampromo_rule_id', $ruleId);

            $item = $quote->addProduct($product, new \Magento\Framework\DataObject($requestInfo));

            if ($item instanceof \Magento\Quote\Model\Quote\Item) {
                $this->initItemData($quote, $item);
            }
        } catch (\Exception $e) {
            $this->_logger->error($e);
        }

        return $this;
    }

    /**
     * @param \Magento\Quote\Model\Quote $quote
     * @param \Magento\Quote\Model\Quote\Item $item
     * @return $this
     */
    protected function initItemData(
        \Magento\Quote\Model\Quote $quote,
        \Magento\Quote\Model\Quote\Item $item
    ) {
        $initFields = [
            'delivery_date',
            'delivery_time',
            'delivery_timeslot_id',
            'delivery_timeslot_from',
            'delivery_timeslot_to'
        ];

        $deliveryType = $item->getDeliveryType();

        /** @var \Magento\Quote\Model\Quote\Item $quoteItem */
        foreach ($quote->getAllItems() as $quoteItem) {
            if ($quoteItem->getDeliveryType() == $deliveryType) {
                foreach ($initFields as $initField) {
                    if ($newValue = $quoteItem->getData($initField)) {
                        $item->setData($initField, $newValue);
                    }
                }
            }
        }

        // Set values to zero to support BI_EXPORT data
        $item->setData('base_price_incl_tax', 0);
        $item->setData('price_incl_tax', 0);
        $item->setData('base_row_total_incl_tax', 0);
        $item->setData('row_total_incl_tax', 0);
        if ($item->getData('chirashi')) {
            $unitQty = 1;
            if ($item->getProduct()->getCaseDisplay() == CaseDisplay::CD_CASE_ONLY) {
                $unitQty = max(intval($item->getProduct()->getUnitQty()), 1);
            }

            $item->setQty($unitQty);
        }

        return $this;
    }

    /**
     * @param \Magento\Quote\Model\Quote $quote
     * @param $ruleId
     * @return bool
     */
    protected function checkProcessedRule(\Magento\Quote\Model\Quote $quote, $ruleId)
    {
        $processedRuleIds = $quote->getData('processed_promo_rule_ids');

        if (!is_array($processedRuleIds)) {
            $processedRuleIds = [];
        }

        $result = in_array($ruleId, $processedRuleIds);

        $processedRuleIds[] = $ruleId;

        $quote->setData('processed_promo_rule_ids', $processedRuleIds);

        return $result;
    }

    /**
     * @param \Magento\Catalog\Model\Product $product
     * @return bool
     */
    public function validateProductInStock(\Magento\Catalog\Model\Product $product)
    {
        if ($product->isInStock() && $product->isSalable()) {
            return true;
        }

        return false;
    }

    /**
     * @param \Magento\Quote\Model\Quote $quote
     * @param $sku
     * @return bool
     */
    private function ableToAddSkuToQuote(\Magento\Quote\Model\Quote $quote, $sku)
    {
        if (!$this->_rikiPromoHelper->ableToAddSkuToQuote($quote, $sku)) {
            return false;
        }

        /*do not need to add chirashi free gift to stock point order */
        if ($quote->getData(SubscriptionOrderHelper::IS_STOCK_POINT_ORDER)) {
            try {
                $product = $this->_productRepository->get($sku);
            } catch (\Exception $e) {
                return false;
            }

            if ($product->getChirashi()) {
                return false;
            }
        }

        return true;
    }
}
