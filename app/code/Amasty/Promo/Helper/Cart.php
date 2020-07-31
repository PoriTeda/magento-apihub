<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2016 Amasty (https://www.amasty.com)
 * @package Amasty_Promo
 */


namespace Amasty\Promo\Helper;

use Magento\CatalogInventory\Model\Spi\StockRegistryProviderInterface;
use Magento\CatalogInventory\Model\Spi\StockStateProviderInterface;

class Cart extends \Magento\Framework\App\Helper\AbstractHelper
{
    /**
     * @var \Magento\Checkout\Model\Cart
     */
    protected $cart;

    /**
     * @var \Amasty\Promo\Model\Registry
     */
    protected $promoRegistry;

    /**
     * @var \Magento\Framework\ObjectManagerInterface
     */
    protected $_objectManager;

    /**
     * @var \Magento\CatalogInventory\Api\StockRegistryInterface
     */
    protected $stockRegistry;

    /**
     * @var \Amasty\Promo\Helper\Messages
     */
    protected $promoMessagesHelper;

    /**
     * @var StockStateProviderInterface
     */
    protected $stockStateProvider;

    /**
     * Cart constructor.
     *
     * @param \Magento\Framework\App\Helper\Context     $context
     * @param \Magento\Checkout\Model\Cart              $cart
     * @param \Amasty\Promo\Model\Registry              $promoRegistry
     * @param \Magento\Framework\ObjectManagerInterface $objectManager
     * @param StockRegistryProviderInterface            $stockRegistry
     * @param Messages                                  $promoMessagesHelper
     * @param StockStateProviderInterface               $stockStateProvider
     */
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Checkout\Model\Cart $cart,
        \Amasty\Promo\Model\Registry $promoRegistry,
        \Magento\Framework\ObjectManagerInterface $objectManager, // @codingStandardsIgnoreLine
        StockRegistryProviderInterface $stockRegistry,
        \Amasty\Promo\Helper\Messages $promoMessagesHelper,
        StockStateProviderInterface $stockStateProvider
    ) {
        parent::__construct($context);

        $this->cart = $cart;
        $this->promoRegistry = $promoRegistry;
        $this->_objectManager = $objectManager;
        $this->stockRegistry = $stockRegistry;
        $this->promoMessagesHelper = $promoMessagesHelper;
        $this->stockStateProvider = $stockStateProvider;
    }

    public function addProduct(
        \Magento\Catalog\Model\Product $product,
        $qty,
        $ruleId = false,
        $requestParams = [],
        $quote = null
    ) {
        if ($product->getTypeId() == 'simple') {
            $availableQty = $this->checkAvailableQty($product, $qty, $quote);

            if ($availableQty <= 0) {
                $this->promoMessagesHelper->addAvailabilityError($product);

                return;
            } else {
                if ($availableQty < $qty) {
                    $this->promoMessagesHelper->showMessage(
                        __(
                            "We apologize, but requested quantity of free gift <strong>%1</strong> is not available at the moment",
                            $product->getName()
                        ), false, true
                    );
                }
            }

            $qty = $availableQty;
        }

        $requestInfo = [
            'qty' => $qty,
            'options' => []
        ];

        if (!empty($requestParams)) {
            $requestInfo = array_merge_recursive($requestParams, $requestInfo);
        }

        $requestInfo['options']['ampromo_rule_id'] = $ruleId;

        try
        {
            $product->setData('ampromo_rule_id', $ruleId);
            if ($quote instanceof \Magento\Quote\Model\Quote) {
                $this->cart->setQuote($quote);  //prevent quote afterload event in cart::addProduct()
            }
            $this->cart->addProduct($product, $requestInfo);

            $this->promoRegistry->restore($product->getData('sku'));

            $this->promoMessagesHelper->showMessage(
                __(
                    "<strong>%1</strong> was added to your shopping cart",
                    $product->getName()
                ), false, true
            );
        }
        catch (\Exception $e)
        {
            $this->promoMessagesHelper->showMessage(
                $e->getMessage(),
                true,
                true
            );
        }
    }

    public function updateQuoteTotalQty(
        $saveCart = false,
        \Magento\Quote\Model\Quote $quote = null
    ) {
        if (!$quote) {
            $quote = $this->cart->getQuote();
        }

        $quote->setItemsCount(0);
        $quote->setItemsQty(0);
        $quote->setVirtualItemsQty(0);

        foreach ($quote->getAllVisibleItems() as $item) {
            if ($item->getParentItem()) {
                continue;
            }

            $children = $item->getChildren();
            if ($children && $item->isShipSeparately()) {
                foreach ($children as $child) {
                    if ($child->getProduct()->getIsVirtual()) {
                        $qty = $quote->getVirtualItemsQty() + $child->getQty() * $item->getQty();
                        $quote->setVirtualItemsQty($qty);
                    }
                }
            }

            if ($item->getProduct()->getIsVirtual()) {
                $quote->setVirtualItemsQty($quote->getVirtualItemsQty() + $item->getQty());
            }
            $quote->setItemsCount($quote->getItemsCount()+1);
            $quote->setItemsQty((float) $quote->getItemsQty()+$item->getQty());
        }

        if ($saveCart) {
            $this->cart->save();
        }
    }

    public function checkAvailableQty(
        \Magento\Catalog\Model\Product $product,
        $qtyRequested,
        $quote = null
    ) {
        $stockItem = $this->stockRegistry->getStockItem(
            $product->getId(),
            $product->getStore()->getWebsiteId()
        );

        $qtyAdded = 0;
        $items = $quote ? $quote->getItemsCollection() : $this->cart->getItems();
        foreach ($items as $item) {
            if ($item->getProductId() == $product->getId()) {
                $qtyAdded += $item->getQty();
            }
        }

        $totalQty = $qtyRequested + $qtyAdded;

        $checkResult = $this->stockStateProvider->checkQuoteItemQty(
            $stockItem, $qtyRequested, $totalQty, $qtyRequested
        );

        if ($checkResult->getData('has_error')) {
            if (!$this->stockStateProvider->checkQty($stockItem, $totalQty)) {
                return $stockItem->getQty() - $qtyAdded;
            }

            return 0;
        }
        else
            return $qtyRequested;
    }
}
