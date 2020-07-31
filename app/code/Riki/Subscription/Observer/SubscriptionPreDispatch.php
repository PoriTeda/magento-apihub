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
 * Whhen cart is empty. remove riki_course_id
 *
 * Class QuoteObserver
 * @property \Magento\Customer\Model\Session customerSession
 * @package Riki\Subscription\Observer
 */
class SubscriptionPreDispatch implements ObserverInterface
{

    protected $objQuote;
    protected $_boSession;
    protected $_sessionManager;
    public function __construct(
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Backend\Model\Auth\Session $boSession,
        \Magento\Framework\Session\SessionManagerInterface $sessionManager
    )
    {
        $this->_boSession = $boSession;
        $this->customerSession = $customerSession;
        $this->_sessionManager = $sessionManager;
    }

    /**
     * Set persistent data into quote
     *
     * @param EventObserver $observer
     * @return void
     */
    public function execute(EventObserver $observer)
    {
        $objRequest = $observer->getEvent()->getData("request");

        if($objRequest->isXmlHttpRequest()) return; /* Ajax we do nothing */

        $fullActionName = $objRequest->getFullActionName();

        $arrAllowAction = [
          'subscriptions_profile_edit',
          'subscriptions_profile_save',
          'subscriptions_profile_delete',
          'subscriptions_profile_add',
          'subscriptions_profile_saveAddress',
          'profile_profile_edit',
          'profile_profile_save',
          'profile_profile_delete',
          'profile_profile_add',
          'profile_order_create',
        ];
        if(!in_array($fullActionName, $arrAllowAction))
        {
            $this->_sessionManager->unsProfileData();
        }
    }

}
