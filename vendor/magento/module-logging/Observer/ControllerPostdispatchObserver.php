<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\Logging\Observer;

use Magento\Framework\Event\ObserverInterface;

class ControllerPostdispatchObserver implements ObserverInterface
{
    /**
     * Instance of \Magento\Logging\Model\Logging
     *
     * @var \Magento\Logging\Model\Processor
     */
    protected $_processor;

    /**
     * @param \Magento\Logging\Model\Processor $processor
     */
    public function __construct(
        \Magento\Logging\Model\Processor $processor
    ) {
        $this->_processor = $processor;
    }

    /**
     * Log marked actions
     *
     * @param \Magento\Framework\Event\Observer $observer
     * @return void
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        if ($observer->getEvent()->getControllerAction()->getRequest()->isDispatched()) {
            $this->_processor->logAction();
        }
    }
}
