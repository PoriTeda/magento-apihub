<?php
namespace Riki\Promo\Plugin\Sales\Block\Adminhtml\Order\Create\Items;

class Grid
{
    /**
     * @var \Riki\Promo\Helper\Item
     */
    protected $itemHelper;

    /**
     * Grid constructor.
     * @param \Riki\Promo\Helper\Item $itemHelper
     */
    public function __construct(
        \Riki\Promo\Helper\Item $itemHelper
    )
    {
        $this->itemHelper = $itemHelper;
    }

    /**
     * Not allow use custom price for free gift item
     *
     * @param \Magento\Sales\Block\Adminhtml\Order\Create\Items\Grid $subject
     * @param \Closure $proceed
     * @param \Magento\Quote\Model\Quote\Item $item
     * @return bool|mixed
     */
    public function aroundCanApplyCustomPrice(
        \Magento\Sales\Block\Adminhtml\Order\Create\Items\Grid $subject,
        \Closure $proceed,
        \Magento\Quote\Model\Quote\Item $item
    )
    {
        if ($this->itemHelper->isPromoItem($item)) {
            return false;
        }

        return $proceed($item);
    }
}