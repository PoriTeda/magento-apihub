<?php

namespace Riki\Subscription\Plugin\Promo\Observer;

class CollectTotalsAfterObserver
{
    /**
     * @var \Magento\Framework\Registry
     */
    protected $_registry;

    /**
     * @param \Magento\Framework\Registry $registry
     */
    public function __construct(\Magento\Framework\Registry $registry){
        $this->_registry = $registry;
    }

    /**
     * @param \Amasty\Promo\Observer\CollectTotalsAfterObserver $subject
     * @param \Closure $proceed
     * @param \Magento\Framework\Event\Observer $observer
     */
    public function aroundExecute(
        \Amasty\Promo\Observer\CollectTotalsAfterObserver $subject,
        \Closure $proceed,
        \Magento\Framework\Event\Observer $observer
    ){
        if(!$this->_registry->registry(\Riki\Subscription\Helper\Order\Data::PROFILE_GENERATE_STATE_REGISTRY_NAME)){
            /**
             * for generate order
             * for import csv order
             */
            if(!$this->_registry->registry(\Riki\CsvOrderMultiple\Cron\ImportOrders::CSV_ORDER_IMPORT_FLAG))
            {
                return $proceed($observer);
            }
        }
    }
}