<?php
/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Wyomind\Core\Observer;

class ControllerActionPredispatch implements \Magento\Framework\Event\ObserverInterface
{
    protected $_feedFactory;

    protected $_backendAuthSession;

    public function __construct(
        \Wyomind\Core\Model\FeedFactory $feedFactory,
        \Magento\Backend\Model\Auth\Session $backendAuthSession
    ) {
        $this->_feedFactory = $feedFactory;
        $this->_backendAuthSession = $backendAuthSession;
    }

    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        if ($this->_backendAuthSession->isLoggedIn()) {
            $feedModel = $this->_feedFactory->create();
            $feedModel->checkUpdate();
        }
    }
}
