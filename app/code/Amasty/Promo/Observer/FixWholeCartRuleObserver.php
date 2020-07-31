<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2016 Amasty (https://www.amasty.com)
 * @package Amasty_Promo
 */


namespace Amasty\Promo\Observer;

use Magento\Framework\Event\ObserverInterface;

/**
 * Fix issue with whole cart rules.
 * Customer shouldn't have only promo items in cart
 */

class FixWholeCartRuleObserver implements ObserverInterface
{
    /**
     * @var \Amasty\Promo\Helper\Item
     */
    protected $promoItemHelper;

    /**
     * @var \Amasty\Promo\Model\Registry
     */
    protected $promoRegistry;

    public function __construct(
        \Amasty\Promo\Helper\Item $promoItemHelper,
        \Amasty\Promo\Model\Registry $promoRegistry
    )
    {
        $this->promoItemHelper = $promoItemHelper;
        $this->promoRegistry = $promoRegistry;
    }

    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $this->promoRegistry->reset();

        $hasNonfreeItems = false;
        foreach ($observer->getQuote()->getAllItems() as $item) {
            if (!$this->promoItemHelper->isPromoItem($item)) {
                $hasNonfreeItems = true;
                break;
            }
        }

        if (!$hasNonfreeItems) {
            $observer->getQuote()->removeAllItems();
        }
    }
}
