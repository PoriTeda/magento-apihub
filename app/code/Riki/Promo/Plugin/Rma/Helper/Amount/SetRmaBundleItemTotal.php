<?php
namespace Riki\Promo\Plugin\Rma\Helper\Amount;

class SetRmaBundleItemTotal
{
    /**
     * @var \Riki\Promo\Helper\Data
     */
    protected $promoHelper;

    /**
     * SetRmaBundleItemTotal constructor.
     * @param \Riki\Promo\Helper\Data $promoHelper
     */
    public function __construct(
        \Riki\Promo\Helper\Data $promoHelper
    ) {
        $this->promoHelper = $promoHelper;
    }

    /**
     * @param \Riki\Rma\Helper\Amount $subject
     * @param \Closure $proceed
     * @param \Magento\Sales\Model\Order\Item $orderItem
     * @param $returnRequestQty
     * @return int|mixed
     */
    public function aroundGetBundleChildItemTotal(
        \Riki\Rma\Helper\Amount $subject,
        \Closure $proceed,
        \Magento\Sales\Model\Order\Item $orderItem,
        $returnRequestQty
    ) {
        if ($this->promoHelper->isPromoOrderItem($orderItem)) {
            return 0;
        }

        return $proceed($orderItem, $returnRequestQty);
    }
}
