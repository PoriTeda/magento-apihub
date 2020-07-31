<?php
/**
 *
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Riki\Subscription\Observer;

use Magento\Framework\Event\Observer as EventObserver;
use Magento\Framework\Event\ObserverInterface;
use Riki\Subscription\Model\Constant;

/**
 * Save riki_type for order table
 *
 * Class OrderObserver
 * @package Riki\Subscription\Observer
 */
class OrderObserver implements ObserverInterface
{

    protected  $quoteRepository;
    /**
     * @var \Riki\Subscription\Helper\Hanpukai\Data
     */
    protected $_hanpukaiHelper;

    public function __construct(
        \Magento\Quote\Api\CartRepositoryInterface $quoteRepository,
        \Riki\Subscription\Helper\Hanpukai\Data $helperHanpukai
    )
    {
        $this->quoteRepository = $quoteRepository;
        $this->_hanpukaiHelper =  $helperHanpukai;
    }
    /**
     * Set persistent data into quote
     *
     * @param EventObserver $observer
     * @return void
     */
    public function execute(EventObserver $observer)
    {

        /** @var $objOrder \Magento\Quote\Model\Quote */
        $objOrder = $observer->getEvent()->getOrder();
        if (!$objOrder) {
            return;
        }

        $quoteId = $objOrder->getQuoteId();
        $objQuote = $this->quoteRepository->get($quoteId);

        if(!$objQuote) {
            // Have order do not have quote. Really ?
            return;
        }

        /**
         * The first time is SPOT
         */
        if(!empty($objQuote->getData("riki_course_id"))) {
            $subscriptionCourseType = $this->_hanpukaiHelper->getSubscriptionCourseType($objQuote->getData("riki_course_id"));
            if( $subscriptionCourseType == 'hanpukai'){
                $objOrder->setData("riki_type", "HANPUKAI");
            }elseif($subscriptionCourseType == 'subscription' ){
                $objOrder->setData("riki_type", "SUBSCRIPTION");
            }else {
                $objOrder->setData("riki_type", "SPOT");
            }
        }

    }

}
