<?php

namespace Riki\Promo\Observer;

use Magento\Framework\Event\ObserverInterface;

class RemoveFreeGift implements ObserverInterface
{
    /**
     * @var \Riki\Promo\Helper\Item
     */
    protected $promoHelper;

    /**
     * RemoveFreeGift constructor.
     *
     * @param \Riki\Promo\Helper\Item $promoHelper
     */
    public function __construct(
        \Riki\Promo\Helper\Item $promoHelper
    )
    {
        $this->promoHelper = $promoHelper;
    }

    /**
     * @inheritdoc
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        if (($address = $observer->getOriginalModel())
            && $address instanceof \Magento\Quote\Model\Quote\Address
        ) {
            $items = $address->getAllItems();

            if (!count($items)) {
                return;
            }

            foreach ($items as $k => $item) {
                if ($this->promoHelper->isPromoItem($item)) {
                    unset($items[$k]);
                }
            }

            $validateAddress = $observer->getValidateData()->getModel();
            $validateAddress->setData('cached_items_all', $items);

            $observer->getValidateData()->setModel($validateAddress);
        }
    }
}
