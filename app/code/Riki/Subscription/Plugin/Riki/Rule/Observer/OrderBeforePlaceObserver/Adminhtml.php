<?php

namespace Riki\Subscription\Plugin\Riki\Rule\Observer\OrderBeforePlaceObserver;

class Adminhtml extends \Riki\Subscription\Plugin\Riki\Rule\Observer\OrderBeforePlaceObserver
{
    /**
     * @var \Magento\Framework\Registry
     */
    protected $registry;

    /**
     * Adminhtml constructor.
     * @param \Magento\Framework\Registry $registry
     */
    public function __construct(\Magento\Framework\Registry $registry)
    {
        $this->registry = $registry;
    }

    /**
     * Ignore for simulate order case that is not profile view page
     *
     * @param \Riki\Rule\Observer\OrderBeforePlaceObserver $subject
     * @param $observer
     * @return array
     */
    public function beforeExecute(
        \Riki\Rule\Observer\OrderBeforePlaceObserver $subject,
        $observer
    )
    {
        $quote = $observer->getQuote();

        if(
            $quote instanceof \Riki\Subscription\Model\Emulator\Cart &&
            !$subject->getRegistry()->registry(\Riki\Subscription\Controller\Adminhtml\Profile\Edit::ADMINHTML_EDIT_PROFILE_FLAG)
        ){
            $quote->setSkipCumulativePromotion(true);
        }

        return [$observer];
    }

    /**
     * @param \Riki\Rule\Observer\OrderBeforePlaceObserver $subject
     * @param $result
     * @return mixed
     */
    public function afterExecute(
        \Riki\Rule\Observer\OrderBeforePlaceObserver $subject,
        $result
    )
    {

        if($subject->getRegistry()->registry(\Riki\Subscription\Controller\Adminhtml\Profile\Edit::ADMINHTML_EDIT_PROFILE_FLAG)){
            $subject->getRegistry()->unregister('cumulative_gift');
        }

        return $result;
    }

    /**
     * Force cumulative item to IN Stock for case edit profile in adminhtml
     *
     * @param \Riki\Rule\Observer\OrderBeforePlaceObserver $subject
     * @param \Closure $proceed
     * @param \Magento\Quote\Model\Quote $quote
     * @param $giftSku
     * @param $qtyRequested
     * @return mixed
     */
    public function aroundGetAvailableQty(
        \Riki\Rule\Observer\OrderBeforePlaceObserver $subject,
        \Closure $proceed,
        \Magento\Quote\Model\Quote $quote,
        $giftSku,
        $qtyRequested
    )
    {
        if ($this->registry->registry(\Riki\Subscription\Controller\Adminhtml\Profile\Edit::ADMINHTML_EDIT_PROFILE_FLAG)) {
            return $qtyRequested;
        }

        return $proceed($quote, $giftSku, $qtyRequested);
    }
}
