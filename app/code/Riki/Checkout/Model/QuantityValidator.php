<?php
/**
 * Product inventory data validator
 *
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Riki\Checkout\Model;

use Magento\CatalogInventory\Api\StockRegistryInterface;
use Magento\CatalogInventory\Api\StockStateInterface;

class QuantityValidator extends \Magento\CatalogInventory\Model\Quote\Item\QuantityValidator
{
    /** @var \Riki\Promo\Helper\Data  */
    protected $_promoHelper;

    /** @var \Psr\Log\LoggerInterface  */
    protected $_logger;

    /** @var \Magento\Framework\Registry  */
    protected $registry;

    /**
     * QuantityValidator constructor.
     * @param \Magento\CatalogInventory\Model\Quote\Item\QuantityValidator\Initializer\Option $optionInitializer
     * @param \Magento\CatalogInventory\Model\Quote\Item\QuantityValidator\Initializer\StockItem $stockItemInitializer
     * @param StockRegistryInterface $stockRegistry
     * @param StockStateInterface $stockState
     * @param \Riki\Promo\Helper\Data $promoHelper
     * @param \Magento\Framework\Registry $registry
     * @param \Psr\Log\LoggerInterface $logger
     */
    public function __construct(
        \Magento\CatalogInventory\Model\Quote\Item\QuantityValidator\Initializer\Option $optionInitializer,
        \Magento\CatalogInventory\Model\Quote\Item\QuantityValidator\Initializer\StockItem $stockItemInitializer,
        StockRegistryInterface $stockRegistry,
        StockStateInterface $stockState,
        \Riki\Promo\Helper\Data $promoHelper,
        \Magento\Framework\Registry $registry,
        \Psr\Log\LoggerInterface $logger
    ){

        $this->_promoHelper = $promoHelper;
        $this->_logger = $logger;
        $this->registry = $registry;

        parent::__construct(
            $optionInitializer,
            $stockItemInitializer,
            $stockRegistry,
            $stockState
        );
    }

    /**
     * @param \Magento\Framework\Event\Observer $observer
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function validate(\Magento\Framework\Event\Observer $observer)
    {
        if ($this->registry->registry('skip_validate_by_oos_order_generating')) {
            return $this;
        }
        /* @var $quoteItem \Magento\Quote\Model\Quote\Item */
        $quoteItem = $observer->getEvent()->getItem();

        if ($quoteItem instanceof \Riki\Subscription\Model\Emulator\Cart\Item) {
            return;
        }
        if (!$quoteItem ||
            !$quoteItem->getProductId() ||
            !$quoteItem->getQuote() ||
            $quoteItem->getQuote()->getIsSuperMode()
        ) {
            return;
        }

        $this->registry->unregister('current_quote');
        $this->registry->register('current_quote', $quoteItem->getQuote());
        $qty = $quoteItem->getQty();

        /** @var skip validate for multi machine $buyRequest */
        $buyRequest = $quoteItem->getBuyRequest();
        if (isset($buyRequest['is_multiple_machine']) && $buyRequest['is_multiple_machine'] == 1) {
            return;
        }

        /** @var \Magento\CatalogInventory\Model\Stock\Item $stockItem */
        $stockItem = $this->stockRegistry->getStockItem(
            $quoteItem->getProduct()->getId(),
            $quoteItem->getProduct()->getStore()->getWebsiteId()
        );
        /* @var $stockItem \Magento\CatalogInventory\Api\Data\StockItemInterface */
        if (!$stockItem instanceof \Magento\CatalogInventory\Api\Data\StockItemInterface) {
            throw new \Magento\Framework\Exception\LocalizedException(__('The stock item for Product is not valid.'));
        }

        $parentStockItem = false;

        /**
         * Check if product in stock. For composite products check base (parent) item stock status
         */
        if ($quoteItem->getParentItem()) {
            $product = $quoteItem->getParentItem()->getProduct();
            $parentStockItem = $this->stockRegistry->getStockItem(
                $product->getId(),
                $product->getStore()->getWebsiteId()
            );
        }

        /**
         * check allow spot order = 0.do not place order
         * Do not check subscription generate order
         */

        if ($quoteItem->getQuote()->getData('is_generate') != 1)
        {
            $product = $quoteItem->getProduct();
            if (!$product->getData(\Riki\StockSpotOrder\Helper\Data::SKIP_CHECk_ALLOW_SPOT_ORDER_NAME) && $product->getAllowSpotOrder() !=1){
                $messageError =  __('I am sorry. Before you finish placing order, %1 has become out of stock. If you do not mind, please consider another product.' , $quoteItem->getName());
                $quoteItem->addErrorInfo(
                    'cataloginventory',
                    \Magento\CatalogInventory\Helper\Data::ERROR_QTY,
                    $messageError
                );
                $quoteItem->getQuote()->addErrorInfo(
                    'stock',
                    'cataloginventory',
                    \Magento\CatalogInventory\Helper\Data::ERROR_QTY,
                    $messageError
                );
                return;
            }else{
                $this->_removeErrorsFromQuoteAndItem($quoteItem, \Magento\CatalogInventory\Helper\Data::ERROR_QTY);
            }
        }

        if ($stockItem) {
            if (!$stockItem->getIsInStock() || $parentStockItem && !$parentStockItem->getIsInStock()) {

                if($this->_promoHelper->isPromoItem($quoteItem)){
                    try{
                        $quoteItem->getQuote()->deleteItem($quoteItem);
                    }catch (\Exception $e){
                        $this->_logger->critical($e);
                    }
                }else{
                    $messageError = __(
                        'We don\'t have as many "%1" as you requested.',
                        $quoteItem->getName()
                    );

                    $quoteItem->addErrorInfo(
                        'cataloginventory',
                        \Magento\CatalogInventory\Helper\Data::ERROR_QTY,
                        $messageError
                    );
                    $quoteItem->getQuote()->addErrorInfo(
                        'stock',
                        'cataloginventory',
                        \Magento\CatalogInventory\Helper\Data::ERROR_QTY,
                        $messageError
                    );
                }

                return;

            } else {
                // Delete error from item and its quote, if it was set due to item out of stock
                $this->_removeErrorsFromQuoteAndItem($quoteItem, \Magento\CatalogInventory\Helper\Data::ERROR_QTY);
            }
        }

        /**
         * Check item for options
         */

        if (($options = $quoteItem->getQtyOptions()) && $qty > 0) {
            $qty = $quoteItem->getProduct()->getTypeInstance()->prepareQuoteItemQty($qty, $quoteItem->getProduct());
            $quoteItem->setData('qty', $qty);
            if ($stockItem) {
                $result = $this->stockState->checkQtyIncrements(
                    $quoteItem->getProduct()->getId(),
                    $qty,
                    $quoteItem->getProduct()->getStore()->getWebsiteId()
                );
                if ($result->getHasError()) {
                    $messageError = __(
                        'We don\'t have as many "%1" as you requested.',
                        $quoteItem->getName()
                    );

                    $quoteItem->addErrorInfo(
                        'cataloginventory',
                        \Magento\CatalogInventory\Helper\Data::ERROR_QTY_INCREMENTS,
                        $messageError
                    );

                    $quoteItem->getQuote()->addErrorInfo(
                        $result->getQuoteMessageIndex(),
                        'cataloginventory',
                        \Magento\CatalogInventory\Helper\Data::ERROR_QTY_INCREMENTS,
                        $messageError
                    );
                } else {
                    // Delete error from item and its quote, if it was set due to qty problems
                    $this->_removeErrorsFromQuoteAndItem(
                        $quoteItem,
                        \Magento\CatalogInventory\Helper\Data::ERROR_QTY_INCREMENTS
                    );
                }
            }

            foreach ($options as $option) {
                $result = $this->optionInitializer->initialize($option, $quoteItem, $qty);
                if ($result->getHasError()) {
                    $messageError = __(
                        'We don\'t have as many "%1" as you requested.',
                        $quoteItem->getName()
                    );
                    $option->setHasError(true);

                    $quoteItem->addErrorInfo(
                        'cataloginventory',
                        \Magento\CatalogInventory\Helper\Data::ERROR_QTY,
                        $messageError
                    );

                    $quoteItem->getQuote()->addErrorInfo(
                        $result->getQuoteMessageIndex(),
                        'cataloginventory',
                        \Magento\CatalogInventory\Helper\Data::ERROR_QTY,
                        $messageError
                    );
                } else {
                    // Delete error from item and its quote, if it was set due to qty lack
                    $this->_removeErrorsFromQuoteAndItem($quoteItem, \Magento\CatalogInventory\Helper\Data::ERROR_QTY);
                }
            }
        } else {
            $result = $this->stockItemInitializer->initialize($stockItem, $quoteItem, $qty);
            if ($result->getHasError()) {
                $messageError = __(
                    'We don\'t have as many "%1" as you requested.',
                    $quoteItem->getName()
                );
                $quoteItem->addErrorInfo(
                    'cataloginventory',
                    \Magento\CatalogInventory\Helper\Data::ERROR_QTY,
                    $messageError
                );

                $quoteItem->getQuote()->addErrorInfo(
                    $result->getQuoteMessageIndex(),
                    'cataloginventory',
                    \Magento\CatalogInventory\Helper\Data::ERROR_QTY,
                    $messageError
                );
            } else {
                // Delete error from item and its quote, if it was set due to qty lack
                $this->_removeErrorsFromQuoteAndItem($quoteItem, \Magento\CatalogInventory\Helper\Data::ERROR_QTY);
            }
        }
    }


}
