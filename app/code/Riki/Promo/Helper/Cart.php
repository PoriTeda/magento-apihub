<?php
namespace Riki\Promo\Helper;

use Magento\CatalogInventory\Model\Spi\StockRegistryProviderInterface;
use Magento\CatalogInventory\Model\Spi\StockStateProviderInterface;
use Magento\Catalog\Model\Product\Type as ProductType;

class Cart extends \Amasty\Promo\Helper\Cart
{

    /** @var Data  */
    private $promoHelper;

    /** @var \Riki\ShipLeadTime\Api\StockStateInterface  */
    private $shipLeadTimeStockState;

    /** @var \Magento\Framework\App\State  */
    private $state;

    /** @var \Riki\AdvancedInventory\Helper\Assignation  */
    private $assignationHelper;

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
     * @param \Magento\Framework\App\State $state
     * @param Data $promoHelper
     * @param \Riki\ShipLeadTime\Api\StockStateInterface $stockState
     * @param \Riki\AdvancedInventory\Helper\Assignation $assignationHelper
     * @param \Riki\StockPoint\Helper\ValidateStockPointProduct $validateStockPointProduct
     */
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Checkout\Model\Cart $cart,
        \Amasty\Promo\Model\Registry $promoRegistry,
        \Magento\Framework\ObjectManagerInterface $objectManager, // @codingStandardsIgnoreLine
        StockRegistryProviderInterface $stockRegistry,
        \Amasty\Promo\Helper\Messages $promoMessagesHelper,
        StockStateProviderInterface $stockStateProvider,
        \Magento\Framework\App\State $state,
        \Riki\Promo\Helper\Data $promoHelper,
        \Riki\ShipLeadTime\Api\StockStateInterface $stockState,
        \Riki\AdvancedInventory\Helper\Assignation $assignationHelper,
        \Riki\StockPoint\Helper\ValidateStockPointProduct $validateStockPointProduct
    ) {
        $this->state = $state;
        $this->promoHelper = $promoHelper;
        $this->shipLeadTimeStockState = $stockState;
        $this->assignationHelper = $assignationHelper;
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
     * @param bool $ruleId
     * @param array $requestParams
     * @param null $quote
     * @return Cart
     */
    public function addProduct(
        \Magento\Catalog\Model\Product $product,
        $qty,
        $ruleId = false,
        $requestParams = [],
        $quote = null
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
                return $this->captureOos($quote, $product, $qty, $ruleId);
            }
        }

        $availableQty = $this->checkAvailableQty($product, $qty, $quote);

        if ($availableQty <= 0) {
            $this->showErrorMessage($quote, $ruleId, $product->getName());
            return $this->captureOos($quote, $product, $qty, $ruleId);
        } else {
            if ($availableQty < $qty) {
                $this->captureOos($quote, $product, $qty - $availableQty, $ruleId);

                $this->showErrorMessage($quote, $ruleId, $product->getName());
            }
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

        if ($product->getTypeId() == ProductType::TYPE_BUNDLE) {
            list($bundleOptions, $bundleOptionsQty) = $this->prepareBundleOptions($product);

            $requestInfo['bundle_option'] = $bundleOptions;
            $requestInfo['bundle_option_qty'] = $bundleOptionsQty;
        }

        try {
            $product->setData('ampromo_rule_id', $ruleId);
            /**
             * This will set a different quote object for promo item which cause a missing quote_id for promo items in transaction when creating new quote & quote items
             *
             */
//            if ($quote instanceof \Magento\Quote\Model\Quote) {
//                $this->cart->setQuote($quote);  //prevent quote afterload event in cart::addProduct()
//            }

            $this->cart->addProduct($product, $requestInfo);

            $this->promoRegistry->restore($product->getData('sku'));

            $this->showMessage(
                $quote,
                $ruleId,
                __("<strong>%1</strong> was added to your shopping cart", $product->getName()),
                false
            );
        } catch (\Exception $e) {
            $this->showMessage($quote, $ruleId, $e->getMessage(), true);
        }
    }

    /**
     * @param \Magento\Catalog\Model\Product $product
     * @return array
     */
    private function prepareBundleOptions(\Magento\Catalog\Model\Product $product)
    {
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

        return [$bundleOptions, $bundleOptionsQty];
    }

    /**
     * @param $quote
     * @param $ruleId
     * @param $productName
     * @return $this
     */
    private function showErrorMessage($quote, $ruleId, $productName)
    {
        return $this->showMessage(
            $quote,
            $ruleId,
            __("We apologize, but requested quantity of free gift <strong>%1</strong> " .
                "is not available at the moment", $productName),
            true
        );
    }

    /**
     * @param $quote
     * @param $ruleId
     * @param $message
     * @param bool $isError
     * @return $this
     */
    private function showMessage($quote, $ruleId, $message, $isError = false)
    {
        if ($this->promoHelper->isFreeGiftVisibleInCart($ruleId)) {
            if ($quote instanceof \Magento\Quote\Model\Quote && $quote->getData('is_simulator') !== true) {
                $this->promoMessagesHelper->showMessage($message, $isError);
            }
        }

        return $this;
    }

    /**
     * @param $quote
     * @param $product
     * @param $qty
     * @param $ruleId
     * @return $this
     */
    private function captureOos($quote, $product, $qty, $ruleId)
    {
        $this->_eventManager->dispatch(\Riki\AdvancedInventory\Observer\OosCapture::EVENT, [
            'quote' => $quote,
            'product' => $product,
            'qty' => $qty,
            'salesrule_id' => $ruleId
        ]);

        return $this;
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
            $quote = $this->cart->getQuote();
        }

        return $this->shipLeadTimeStockState->checkAvailableQty($quote, $product->getSku(), $qtyRequested);
    }
}
