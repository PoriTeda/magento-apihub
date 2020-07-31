<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2016 Amasty (https://www.amasty.com)
 * @package Amasty_Promo
 */


namespace Amasty\Promo\Observer;

/**
 * Class Observer
 *
 * Hack to skip $order->getDiscountAmount() == 0 condition check
 * and update coupon usages
 *
 * @package Amasty\Promo\Model
 */
class FixCouponsUsageObserver extends \Nestle\SalesRule\Observer\SalesOrderAfterPlaceObserver
{
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $order = $observer->getEvent()->getOrder();
/**
         * Check Simulator order
         */
        if($order instanceof \Riki\Subscription\Model\Emulator\Order){
            return $this ;
        }
        if (!$order || $order->getDiscountAmount() != 0) {
            return $this; // Default Magento logic was executed
        }
        
        $order->setDiscountAmount(0.00001);
        parent::execute($observer);
        $order->setDiscountAmount(0);

        return $this;
    }
}
