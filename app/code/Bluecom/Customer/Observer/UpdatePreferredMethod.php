<?php

namespace Bluecom\Customer\Observer;

use Magento\Framework\Event\ObserverInterface;

class UpdatePreferredMethod implements ObserverInterface
{
    /**
     * @var \Magento\Customer\Model\Session
     */
    protected $customerSession;
    /**
     * @var \Magento\Framework\Message\ManagerInterface
     */
    protected $managerInterface;
    /**
     * @var \Magento\Customer\Api\CustomerRepositoryInterface
     */
    protected $customerRepository;
    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;

    /**
     * UpdatePreferredMethod constructor.
     *
     * @param \Magento\Customer\Model\Session                       $customerSession         Session
     * @param \Magento\Framework\Message\ManagerInterface           $managerInterface        ManagerInterface
     * @param \Magento\Customer\Api\CustomerRepositoryInterface     $customerRepository      CustomerRepositoryInterface
     * @param \Psr\Log\LoggerInterface                              $logger                  Logger
     */
    public function __construct(
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Framework\Message\ManagerInterface $managerInterface,
        \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository,
        \Psr\Log\LoggerInterface $logger
    ) {
        $this->customerSession = $customerSession;
        $this->managerInterface = $managerInterface;
        $this->customerRepository = $customerRepository;
        $this->logger = $logger;
    }

    /**
     * Update preferred payment method for customer after place order
     *
     * @param \Magento\Framework\Event\Observer $observer Observer
     *
     * @return bool
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $order = $observer->getEvent()->getOrder();
        /*Simulate don't need to update prefers payment method */
        if ($order instanceof \Riki\Subscription\Model\Emulator\Order) {
            return;
        }
        if (!$order) {
            return false;
        }
        $isFrom = $order->getCreatedBy();
        //get order payment method
        $methodOrder = $order->getPayment()->getMethod();
        //get customer order
        $customerId = $order->getCustomerId();

        if (!$customerId || !$methodOrder || $methodOrder == 'free' || $isFrom == 'admin') {
            return false;
        }

        $customerOrder = $this->customerRepository->getById($customerId);
        $preferredMethod = $customerOrder->getCustomAttribute('preferred_payment_method');
        //get current value preferred method of customer
        $preferredCustomer = $preferredMethod ? $preferredMethod->getValue(): false;
        
        if ($preferredCustomer == $methodOrder ) {
            return false;
        }

        try {
            $customerOrder->setCustomAttribute('preferred_payment_method', $methodOrder);
            $this->customerRepository->save($customerOrder);

        } catch (\Exception $e) {
            $message = __('We can\'t update preferred method.')
                . $e->getMessage()
                . '<pre>' . $e->getTraceAsString() . '</pre>';
            $this->managerInterface->addException($e, $message);
            $this->logger->critical($e);
        }

        return true;
    }

}
