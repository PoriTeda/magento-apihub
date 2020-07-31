<?php
namespace Riki\Promo\Plugin\Riki\Sales\Model\CatalogInventory\Quote\Item\QuantityValidator\Initializer;

class StockItem
{
    protected $_helper;

    protected $_objectFactory;

    /**
     * @param \Riki\Promo\Helper\Data $helper
     * @param \Magento\Framework\DataObjectFactory $objectFactory
     */
    public function __construct(
        \Riki\Promo\Helper\Data $helper,
        \Magento\Framework\DataObjectFactory $objectFactory
    ){
        $this->_helper = $helper;
        $this->_objectFactory = $objectFactory;
    }

    /**
     * do not validate qty for free gift item
     *
     * @param \Riki\Sales\Model\CatalogInventory\Quote\Item\QuantityValidator\Initializer\StockItem $subject
     * @param \Closure $proceed
     * @param \Magento\Quote\Model\Quote\Item $quoteItem
     * @param $rowQty
     * @param $qtyForCheck
     * @param $qty
     * @return mixed
     */
    public function aroundGetResultValidateStockByQuoteItem(
        \Riki\Sales\Model\CatalogInventory\Quote\Item\QuantityValidator\Initializer\StockItem $subject,
        \Closure $proceed,
        \Magento\Quote\Model\Quote\Item $quoteItem,
        $rowQty,
        $qtyForCheck,
        $qty
    ) {
        if($this->_helper->isPromoItem($quoteItem)){
            $result = $this->_objectFactory->create();
            $result->setHasError(false);

            return $result;
        }

        return $proceed(
            $quoteItem,
            $rowQty,
            $qtyForCheck,
            $qty
        );
    }
}