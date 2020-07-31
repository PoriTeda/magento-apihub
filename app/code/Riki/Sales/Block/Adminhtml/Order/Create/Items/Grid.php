<?php

namespace Riki\Sales\Block\Adminhtml\Order\Create\Items;

use Magento\Catalog\Model\Product\Attribute\Source\Status as ProductStatus;
use Magento\CatalogInventory\Api\StockRegistryInterface;
use Magento\CatalogInventory\Api\StockStateInterface;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Quote\Model\Quote\Item;

class Grid extends \Magento\Sales\Block\Adminhtml\Order\Create\Items\Grid
{
    protected $_rikiSalesAdminHelper;

    protected $_productIdsToQtys = [];

    protected $_promoHelper;

    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Backend\Model\Session\Quote $sessionQuote,
        \Magento\Sales\Model\AdminOrder\Create $orderCreate,
        PriceCurrencyInterface $priceCurrency,
        \Magento\Wishlist\Model\WishlistFactory $wishlistFactory,
        \Magento\GiftMessage\Model\Save $giftMessageSave,
        \Magento\Tax\Model\Config $taxConfig,
        \Magento\Tax\Helper\Data $taxData,
        \Magento\GiftMessage\Helper\Message $messageHelper,
        StockRegistryInterface $stockRegistry,
        StockStateInterface $stockState,
        \Riki\Sales\Helper\Admin $rikiSalesAdminHelper,
        \Riki\Promo\Helper\Data $promoHelper,
        array $data = []
    ){
        $this->_rikiSalesAdminHelper = $rikiSalesAdminHelper;
        $this->_promoHelper = $promoHelper;

        parent::__construct(
            $context,
            $sessionQuote,
            $orderCreate,
            $priceCurrency,
            $wishlistFactory,
            $giftMessageSave,
            $taxConfig,
            $taxData,
            $messageHelper,
            $stockRegistry,
            $stockState,
            $data
        );
    }

    /**
     * Get items
     * Fixed issue for multiple shipping case
     *
     * @return Item[]
     */
    public function getItems()
    {
        $items = $this->getParentBlock()->getItems();
        $oldSuperMode = $this->getQuote()->getIsSuperMode();
        $this->getQuote()->setIsSuperMode(false);
        foreach ($items as $item) {
            // To dispatch inventory event sales_quote_item_qty_set_after, set item qty
            $item->setQty($item->getQty());

            if (!$item->getMessage()) {
                //Getting product ids for stock item last quantity validation before grid display
                $stockItemToCheck = [];

                $childItems = $item->getChildren();
                if (count($childItems)) {
                    foreach ($childItems as $childItem) {
                        $stockItemToCheck[] = $childItem->getProduct()->getId();
                    }

                    foreach ($stockItemToCheck as $productId) {
                        $check = $this->stockState->checkQuoteItemQty(
                            $productId,
                            $item->getQty(),
                            $item->getQty(),
                            $item->getQty(),
                            $this->getQuote()->getStore()->getWebsiteId()
                        );
                        $item->setMessage($check->getMessage());
                        $item->setHasError($check->getHasError());
                    }

                } else {
                    $check = $this->getResultValidateStockByQuoteItem(
                        $item,
                        $item->getQty(),
                        $item->getQty(),
                        $item->getQty()
                    );
                    $item->setMessage($check->getMessage());
                    $item->setHasError($check->getHasError());
                }
            }

            if ($item->getProduct()->getStatus() == ProductStatus::STATUS_DISABLED) {
                $item->setMessage(__('This product is disabled.'));
                $item->setHasError(true);
            }
        }
        $this->getQuote()->setIsSuperMode($oldSuperMode);
        return $items;
    }


    /**
     * @param $quoteItem
     * @param $rowQty
     * @param $qtyForCheck
     * @param $qty
     * @return int
     */
    public function getResultValidateStockByQuoteItem($quoteItem, $rowQty, $qtyForCheck, $qty){

        if($this->_rikiSalesAdminHelper->isMultipleShippingAddressCart()){
            $rowQty = [
                $rowQty,
                $this->getRealQtyByQuoteItem($quoteItem)
            ];
        }

        return $this->stockState->checkQuoteItemQty(
            $quoteItem->getProduct()->getId(),
            $rowQty,
            $qtyForCheck,
            $qty,
            $quoteItem->getProduct()->getStore()->getWebsiteId()
        );
    }

    /**
     * @param \Magento\Quote\Model\Quote\Item $quoteItem
     * @return mixed
     */
    protected function getRealQtyByQuoteItem(\Magento\Quote\Model\Quote\Item $quoteItem){

        $productId = $quoteItem->getProductId();

        if(!isset($this->_productIdsToQtys[$productId])){
            $this->_productIdsToQtys[$productId] = 0;

            /** @var \Magento\Quote\Model\Quote\Item $item */
            foreach($quoteItem->getQuote()->getAllItems() as $item){
                if(
                    !$quoteItem->getParentItemId() &&
                    $item->getProductId() == $productId &&
                    !$this->_promoHelper->isPromoItem($item)
                ){
                    $this->_productIdsToQtys[$productId] += $item->getQty();
                }
            }
        }

        return $this->_productIdsToQtys[$productId];
    }
    /**
     * Get subtotal
     *
     * @return false|float
     */
    public function getSubtotal()
    {
        $address = $this->getQuoteAddress();
        if (!$this->displayTotalsIncludeTax()) {
            return $address->getSubtotal() + $address->getGwItemsPriceInclTax();
        }
        if ($address->getSubtotalInclTax()) {
            return $address->getSubtotalInclTax() + $address->getGwItemsPriceInclTax();
        }
        return $address->getSubtotal() + $address->getTaxAmount() + $address->getGwItemsPriceInclTax();
    }

    /**
     * Get subtotal with discount
     *
     * @return float
     */
    public function getSubtotalWithDiscount()
    {
        $address = $this->getQuoteAddress();
        if ($this->displayTotalsIncludeTax()) {
            return $address->getSubtotal()
            + $address->getTaxAmount()
            + $address->getDiscountAmount()
            + $address->getDiscountTaxCompensationAmount()
            + $address->getGwItemsPrice()    ;
        } else {
            return $address->getSubtotal() + $address->getDiscountAmount() + $address->getGwItemsPriceInclTax();
        }
    }
}
