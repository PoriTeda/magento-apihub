<?php

namespace Riki\Customer\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

class DeleteCustomerCartAfterLogOut implements ObserverInterface
{
    /**
     * @var \Magento\Checkout\Model\Session
     */
    protected $session;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;

    /**
     * DeleteCustomerCartAfterLogOut constructor.
     *
     * @param \Magento\Checkout\Model\Session $session
     * @param \Psr\Log\LoggerInterface $logger
     */
    public function __construct(
        \Magento\Checkout\Model\Session $session,
        \Psr\Log\LoggerInterface $logger
    )
    {
        $this->session = $session;
        $this->logger = $logger;
    }

    /**
     * @param Observer $observer
     *
     * @return void
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        try {
            $this->session->getQuote()->delete();
        } catch (\Exception $e) {
            $this->logger->critical($e);
        }
    }
}
