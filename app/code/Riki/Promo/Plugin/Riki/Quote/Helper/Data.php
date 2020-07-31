<?php
namespace Riki\Promo\Plugin\Riki\Quote\Helper;

class Data
{
    protected $_helper;

    /**
     * @param \Riki\Promo\Helper\Data $helper
     */
    public function __construct(
        \Riki\Promo\Helper\Data $helper
    ){
        $this->_helper = $helper;
    }

    /**
     * product buy request ty should without free gift
     *
     * @param \Riki\Quote\Helper\Data $subject
     * @param \Closure $proceed
     * @param \Magento\Quote\Model\Quote\Item $item
     * @return float|int|mixed
     */
    public function aroundGetProductBuyRequestQtyByQuoteItem(
        \Riki\Quote\Helper\Data $subject,
        \Closure $proceed,
        \Magento\Quote\Model\Quote\Item $item
    ) {

        $quote = $item->getQuote();
        $productId = $item->getProductId();

        $result = $proceed($item);

        /** @var \Magento\Quote\Model\Quote\Item $quoteItem */
        foreach($quote->getAllItems() as $quoteItem){
            if(
                $quoteItem->getProductId() == $productId &&
                (
                    $this->_helper->isPromoItem($quoteItem) ||
                    $quoteItem->getIsRikiMachine() ||
                    $quoteItem->getPrizeId()
                )
            )
                $result -= $quoteItem->getQty();
        }

        return $result;
    }
}
