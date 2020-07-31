<?php

namespace Riki\Quote\Observer;

class EmptyCart implements \Magento\Framework\Event\ObserverInterface
{
    /**
     * @var \Magento\Customer\Model\Session
     */
    protected $_customerSession;

    /**
     * @var \Magento\Quote\Api\CartRepositoryInterface
     */
    protected $quoteRepository;

    /**
     * @var \Magento\Quote\Model\QuoteFactory
     */
    protected $quoteFactory;

    /**
     * EmptyCart constructor.
     *
     * @param \Magento\Quote\Api\CartRepositoryInterface $quoteRepository
     * @param \Magento\Customer\Model\Session\Proxy $customerSession
     * @param \Magento\Quote\Model\QuoteFactory $quoteFactory
     */
    public function __construct(
        \Magento\Quote\Api\CartRepositoryInterface $quoteRepository,
        \Magento\Customer\Model\Session\Proxy $customerSession,
        \Magento\Quote\Model\QuoteFactory $quoteFactory
    )
    {
        $this->quoteRepository = $quoteRepository;
        $this->_customerSession = $customerSession;
        $this->quoteFactory = $quoteFactory;
    }

    /**
     * @param \Magento\Framework\Event\Observer $observer
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $checkoutSession = $observer->getCheckoutSession();
        $lastQuote = $checkoutSession->getQuote();

        try {
            $customerQuote = $this->quoteRepository->getForCustomer($this->_customerSession->getCustomerId());
        } catch (\Magento\Framework\Exception\NoSuchEntityException $e) {
            $customerQuote = $this->quoteFactory->create();
        }

        if ($customerQuote->getId() && $customerQuote->getId() != $lastQuote->getId()) {
            while ($customerQuote->getId()){
                $this->quoteRepository->delete($customerQuote);
                try {
                    $customerQuote = $this->quoteRepository->getForCustomer($this->_customerSession->getCustomerId());
                } catch (\Magento\Framework\Exception\NoSuchEntityException $e) {
                    break;
                }
            }
        } elseif ($lastQuote->getId() && !$lastQuote->getKeepItemsFlag()) {
            $lastQuote->removeAllItems();
        }
    }
}
