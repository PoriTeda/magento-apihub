<?php

namespace Riki\Promo\Observer;

/**
 * Remove all not allowed items
 */

class CollectTotalsAfterObserver extends \Amasty\Promo\Observer\CollectTotalsAfterObserver
{
    /**
     * @var \Riki\Promo\Logger\Logger
     */
    protected $promoLogger;

    /**
     * CollectTotalsAfterObserver constructor.
     * @param \Magento\Framework\Registry $registry
     * @param \Amasty\Promo\Helper\Cart $promoCartHelper
     * @param \Amasty\Promo\Helper\Item $promoItemHelper
     * @param \Amasty\Promo\Model\Registry $promoRegistry
     * @param \Riki\Promo\Logger\Logger $logger
     */
    public function __construct(
        \Magento\Framework\Registry $registry,
        \Amasty\Promo\Helper\Cart $promoCartHelper,
        \Amasty\Promo\Helper\Item $promoItemHelper,
        \Amasty\Promo\Model\Registry $promoRegistry,
        \Riki\Promo\Logger\Logger $logger
    ) {
    
        $this->promoLogger = $logger;

        parent::__construct($registry, $promoCartHelper, $promoItemHelper, $promoRegistry);
    }

    /**
     * @param \Magento\Framework\Event\Observer $observer
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        /** @var \Magento\Quote\Model\Quote $quote */
        $quote = $observer->getQuote();

        $allowedItems = $this->promoRegistry->getPromoItems();

        $toAdd = $this->_coreRegistry->registry('ampromo_to_add');

        if (is_array($toAdd)) {
            foreach ($toAdd as $item) {
                $this->promoCartHelper->addProduct(
                    $item['product'],
                    $item['qty'],
                    isset($item['rule_id']) ? $item['rule_id'] : false,
                    [],
                    $quote
                );
            }
        }

        $this->_coreRegistry->unregister('ampromo_to_add');

        $changedItems = $this->_coreRegistry->registry('ampromo_changed_items');
        $this->_coreRegistry->unregister('ampromo_changed_items');

        /** @var \Magento\Quote\Model\Quote\Item $item */
        foreach ($quote->getAllItems() as $item) {
            if ($this->promoItemHelper->isPromoItem($item)) {
                if ($item->getParentItemId() ||
                    $item->getParentItem() ||
                    (
                        is_array($changedItems) &&
                        !in_array($item->getId(), $changedItems)
                    )
                ) {
                    continue;
                }

                $sku = $item->getProduct()->getData('sku');

                $ruleId = $this->promoItemHelper->getRuleId($item);

                // prevent promo items are removed when collect total is called more than one time
//                if (!$this->promoRegistry->getApplyAttempt($ruleId)) {
//                    continue;
//                }

                if (isset($allowedItems['_groups'][$ruleId])) { // Add one of

                    if ($allowedItems['_groups'][$ruleId]['qty'] <= 0) {
                        $quote->deleteItem($item);
                    } elseif ($item->getQty() > $allowedItems['_groups'][$ruleId]['qty']) {
                        $item->setQty($allowedItems['_groups'][$ruleId]['qty']);
                    }

                    $allowedItems['_groups'][$ruleId]['qty'] -= $item->getQty();
                } elseif (isset($allowedItems[$sku])) { // Add all of

                    if ($allowedItems[$sku]['qty'] <= 0) {
                        $quote->deleteItem($item);
                    } elseif ($item->getQty() > $allowedItems[$sku]['qty']) {
                        $item->setQty($allowedItems[$sku]['qty']);
                    }

                    $allowedItems[$sku]['qty'] -= $item->getQty();
                } else {
                    $quote->deleteItem($item);
                }
            }
        }

        $this->promoCartHelper->updateQuoteTotalQty(
            false,
            $quote
        );
    }
}
