<?php
namespace Riki\Quote\Helper;

class Data extends \Magento\Framework\App\Helper\AbstractHelper
{

    protected $_promoItemHelper;

    /**
     * @param \Magento\Framework\App\Helper\Context $context
     * @param \Amasty\Promo\Helper\Item $promoItemHelper
     */
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Amasty\Promo\Helper\Item $promoItemHelper
    ){

        $this->_promoItemHelper = $promoItemHelper;

        parent::__construct(
            $context
        );
    }

    /**
     *
     */
    public function getPromoItemHelper(){
        return $this->_promoItemHelper;
    }

    /**
     * @param \Magento\Quote\Model\Quote\Item $quoteItem
     * @return float|int|mixed
     */
    public function getProductBuyRequestQtyByQuoteItem(\Magento\Quote\Model\Quote\Item $quoteItem){
        $qty = 0;

        $quote = $quoteItem->getQuote();

        $productId = $quoteItem->getProductId();

        /** @var \Magento\Quote\Model\Quote\Item $item */
        foreach($quote->getAllItems() as $item){
            if($item->getProductId() == $productId)
                $qty += $item->getQty();
        }

        return $qty;
    }

    /**
     * Check item free gift
     *
     * @param \Magento\Quote\Model\Quote\Item $item
     * @return bool
     */
    public function isFreeGift(\Magento\Quote\Model\Quote\Item $item)
    {
        if ( $item->getData('prize_id')
            || (($item->getData('is_riki_machine') && $item->getData('price') == 0))
            || $this->_promoItemHelper->isPromoItem($item)
        ) {
            return true;
        }
        return false;
    }
}
