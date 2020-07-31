<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2016 Amasty (https://www.amasty.com)
 * @package Amasty_Promo
 */


namespace Amasty\Promo\Observer;

use Magento\Framework\Event\ObserverInterface;

/**
 * Remove all not allowed items
 */

class CollectTotalsAfterObserver implements ObserverInterface
{
    /**
     * Core registry
     *
     * @var \Magento\Framework\Registry
     */
    protected $_coreRegistry;

    /**
     * @var \Amasty\Promo\Helper\Cart
     */
    protected $promoCartHelper;

    /**
     * @var \Amasty\Promo\Helper\Item
     */
    protected $promoItemHelper;

    /**
     * @var \Amasty\Promo\Model\Registry
     */
    protected $promoRegistry;

    public function __construct(
        \Magento\Framework\Registry $registry,
        \Amasty\Promo\Helper\Cart $promoCartHelper,
        \Amasty\Promo\Helper\Item $promoItemHelper,
        \Amasty\Promo\Model\Registry $promoRegistry
    )
    {
        $this->_coreRegistry = $registry;
        $this->promoCartHelper = $promoCartHelper;
        $this->promoItemHelper = $promoItemHelper;
        $this->promoRegistry = $promoRegistry;
    }


    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $allowedItems = $this->promoRegistry->getPromoItems();

        $toAdd = $this->_coreRegistry->registry('ampromo_to_add');

        if (is_array($toAdd)) {
            foreach ($toAdd as $item) {
                $this->promoCartHelper->addProduct(
                    $item['product'],
                    $item['qty'],
                    false,
                    [],
                    $observer->getQuote()
                );
            }
        }

        $this->_coreRegistry->unregister('ampromo_to_add');

        foreach ($observer->getQuote()->getAllItems() as $item) {
            if ($this->promoItemHelper->isPromoItem($item)) {
                if ($item->getParentItemId())
                    continue;

                $sku = $item->getProduct()->getData('sku');

                $ruleId = $this->promoItemHelper->getRuleId($item);

                if (isset($allowedItems['_groups'][$ruleId])) { // Add one of

                    if ($allowedItems['_groups'][$ruleId]['qty'] <= 0) {
                        $observer->getQuote()->removeItem($item->getId());
                    }
                    else if ($item->getQty() > $allowedItems['_groups'][$ruleId]['qty']) {
                        $item->setQty($allowedItems['_groups'][$ruleId]['qty']);
                    }

                    $allowedItems['_groups'][$ruleId]['qty'] -= $item->getQty();
                }
                else if (isset($allowedItems[$sku])) { // Add all of

                    if ($allowedItems[$sku]['qty'] <= 0) {
                        $observer->getQuote()->removeItem($item->getId());
                    }
                    else if ($item->getQty() > $allowedItems[$sku]['qty']) {
                        $item->setQty($allowedItems[$sku]['qty']);
                    }

                    $allowedItems[$sku]['qty'] -= $item->getQty();
                }
                else {
                    $observer->getQuote()->removeItem($item->getId());
                }
            }
        }

        $this->promoCartHelper->updateQuoteTotalQty(
            false, $observer->getQuote()
        );
    }
}
