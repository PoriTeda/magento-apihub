<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Riki\GoogleTagManager\Observer\Frontend;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

/**
 * Customer log observer.
 */
class SaveGaClientIdObserver implements ObserverInterface
{
	
	/**
	 * @var \Magento\Framework\Session\SessionManagerInterface
	 */
	protected $_sessionManager;
	
	public function  __construct(
		\Magento\Framework\Session\SessionManagerInterface $sessionManager
	)
	{
		$this->_sessionManager = $sessionManager;
	}
	
	
	/**
     * Handler for 'customer_login' event.Add value of gaclient value
     *
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer)
    {
		$customer = $observer->getEvent()->getCustomer();
		if ($customer) {
			$gaClientId = $this->_sessionManager->getData('gaClientId');
			if ($gaClientId !=null) {
				$customer->setData('ga_client_id',$gaClientId)->save();
			}
		}
    }
}
