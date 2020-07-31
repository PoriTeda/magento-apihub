<?php
namespace Riki\Promo\Plugin\Quote\Item;

class ToOrderItem
{
    const FREEOFCHARGE = 1;
    /**
     * @var \Riki\Promo\Helper\Data
     */
    protected $_promoHelper;

    /**
     * ToOrderItem constructor.
     *
     * @param \Riki\Promo\Helper\Data $promoHelper
     */
    public function __construct(
        \Riki\Promo\Helper\Data $promoHelper
    ) {
        $this->_promoHelper = $promoHelper;
    }

    public function aroundConvert(
        \Magento\Quote\Model\Quote\Item\ToOrderItem $subject,
        \Closure $proceed,
        \Magento\Quote\Model\Quote\Item\AbstractItem $item,
        $additional = []
    ) {
        $orderItem = $proceed($item, $additional);

        $orderItem->setVisibleUserAccount($item->getVisibleUserAccount());

        /*get free of charge from quote item*/
        $freeOfCharge = $item->getFreeOfCharge();

        if ($freeOfCharge != self::FREEOFCHARGE) {
            /*if this item is promo item -> free_of_charge = 1*/
            $freeOfCharge = $this->_promoHelper->isPromoItem($item);
        }

        /*set free of charge data for sales order item*/
        if ($freeOfCharge) {
            $orderItem->setFreeOfCharge($freeOfCharge);
        }

        return $orderItem;
    }
}
