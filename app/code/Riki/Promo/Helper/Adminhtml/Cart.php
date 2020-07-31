<?php
namespace Riki\Promo\Helper\Adminhtml;

use Magento\CatalogInventory\Model\Spi\StockRegistryProviderInterface;
use Magento\CatalogInventory\Model\Spi\StockStateProviderInterface;
use Magento\Catalog\Model\Product\Type as ProductType;

class Cart extends \Amasty\Promo\Helper\Cart
{

    /**
     * @var \Magento\Framework\Message\ManagerInterface
     */
    protected $messageManager;

    /** @var \Magento\Sales\Model\AdminOrder\Create  */
    protected $_adminOrderCreate;

    /** @var \Riki\ShipLeadTime\Api\StockStateInterface  */
    protected $shipLeadTimeStockState;

    /**
     * @var \Magento\Framework\Registry
     */
    protected $registry;

    /**
     * @var \Riki\StockPoint\Helper\ValidateStockPointProduct
     */
    protected $validateStockPointProduct;

    /**
     * Cart constructor.
     * @param \Magento\Framework\App\Helper\Context $context
     * @param \Magento\Checkout\Model\Cart $cart
     * @param \Amasty\Promo\Model\Registry $promoRegistry
     * @param \Magento\Framework\ObjectManagerInterface $objectManager
     * @param StockRegistryProviderInterface $stockRegistry
     * @param \Amasty\Promo\Helper\Messages $promoMessagesHelper
     * @param StockStateProviderInterface $stockStateProvider
     * @param \Magento\Framework\Message\ManagerInterface $messageManager
     * @param \Magento\Sales\Model\AdminOrder\Create $adminOrderCreate
     * @param \Riki\ShipLeadTime\Api\StockStateInterface $stockState
     * @param \Magento\Framework\Registry $registry
     * @param \Riki\StockPoint\Helper\ValidateStockPointProduct $validateStockPointProduct
     */
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Checkout\Model\Cart $cart,
        \Amasty\Promo\Model\Registry $promoRegistry,
        \Magento\Framework\ObjectManagerInterface $objectManager,
        StockRegistryProviderInterface $stockRegistry,
        \Amasty\Promo\Helper\Messages $promoMessagesHelper,
        StockStateProviderInterface $stockStateProvider,
        \Magento\Framework\Message\ManagerInterface $messageManager,
        \Magento\Sales\Model\AdminOrder\Create $adminOrderCreate,
        \Riki\ShipLeadTime\Api\StockStateInterface $stockState,
        \Magento\Framework\Registry $registry,
        \Riki\StockPoint\Helper\ValidateStockPointProduct $validateStockPointProduct
    ) {

        $this->messageManager = $messageManager;
        $this->_adminOrderCreate = $adminOrderCreate;
        $this->shipLeadTimeStockState = $stockState;
        $this->registry = $registry;
        $this->validateStockPointProduct = $validateStockPointProduct;
        parent::__construct(
            $context,
            $cart,
            $promoRegistry,
            $objectManager,
            $stockRegistry,
            $promoMessagesHelper,
            $stockStateProvider
        );
    }

    /**
     * @param \Magento\Catalog\Model\Product $product
     * @param $qty
     * @param bool|false $ruleId
     * @param array $requestParams
     * @param null $quote
     */
    public function addProduct(
        \Magento\Catalog\Model\Product $product,
        $qty,
        $ruleId = false,
        $requestParams = [],
        $quote = null
    ) {

        if (!$quote ||
            !$quote->getId() ||
            (
                !$this->registry->registry('quote_admin') &&
                !$this->registry->registry('cron_store_id') &&
                !$quote->getData('is_csv_import_order_flag') &&
                $quote->getId() != $this->_adminOrderCreate->getQuote()->getId()
            )
        ) {
            return;
        }

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

        $availableQty = $this->checkAvailableQty($product, $qty, $quote);

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
        $requestInfo['options']['is_free_attachment'] = '1';

        $requestInfo = $this->prepareRequiredOptions($product, $requestInfo);

        try {
            $product->setData('ampromo_rule_id', $ruleId);

            if (!$quote instanceof \Magento\Quote\Model\Quote) {
                $quote = $this->_adminOrderCreate->getQuote();
            }

            $quote->addProduct($product, new \Magento\Framework\DataObject($requestInfo));

            $this->promoRegistry->restore($product->getData('sku'));
        } catch (\Exception $e) {
            $this->messageManager->addError($e->getMessage());
        }
    }

    /**
     * @param \Magento\Catalog\Model\Product $product
     * @param $qtyRequested
     * @param null $quote
     * @return float
     */
    public function checkAvailableQty(
        \Magento\Catalog\Model\Product $product,
        $qtyRequested,
        $quote = null
    ) {

        if (!$quote instanceof \Magento\Quote\Model\Quote) {
            $quote = $this->_adminOrderCreate->getQuote();
        }

        return $this->shipLeadTimeStockState->checkAvailableQty($quote, $product->getSku(), $qtyRequested);
    }

    /**
     * @param \Magento\Catalog\Model\Product $product
     * @param $requestInfo
     * @return mixed
     */
    protected function prepareRequiredOptions(\Magento\Catalog\Model\Product $product, $requestInfo)
    {
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

        return $requestInfo;
    }
}
