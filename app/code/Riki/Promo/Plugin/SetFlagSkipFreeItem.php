<?php

namespace Riki\Promo\Plugin;

class SetFlagSkipFreeItem
{
    protected $promoItemHelper;

    /**
     * UpdatePromotionPrice constructor.
     *
     * @param \Riki\Promo\Helper\Item $promoItemHelper
     */
    public function __construct(
        \Riki\Promo\Helper\Item $promoItemHelper
    )
    {
        $this->promoItemHelper = $promoItemHelper;
    }

    /**
     * Free gifts are skipped from promotion rules processing.
     * See ticket RIKI-6636.
     *
     * @param $subject
     * @param $item
     *
     * @return array
     */
    public function beforeProcess($subject, $item)
    {
        if ($this->promoItemHelper->isPromoItem($item)) {
            $item->setDiscountCalculationPrice(-1);
        }

        return [$item];
    }
}
