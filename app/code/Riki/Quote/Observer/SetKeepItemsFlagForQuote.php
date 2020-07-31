<?php

namespace Riki\Quote\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Quote\Model\QuoteRepository;

class SetKeepItemsFlagForQuote implements ObserverInterface
{
    protected $checkoutSession;

    protected $quoteRepository;

    protected $quoteFactory;

    public function __construct(
        \Magento\Checkout\Model\Session $checkoutSession,
        QuoteRepository $quoteRepository,
        \Magento\Quote\Model\QuoteFactory $quoteFactory
    )
    {
        $this->checkoutSession = $checkoutSession;
        $this->quoteRepository = $quoteRepository;
        $this->quoteFactory = $quoteFactory;
    }

    /**
     * @param Observer $observer
     *
     * @return void
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        try {
            $customerQuote = $this->quoteRepository->getForCustomer($observer->getCustomer()->getId());
        } catch (\Magento\Framework\Exception\NoSuchEntityException $e) {
            $customerQuote = $this->quoteFactory->create();
        }

        if (!$customerQuote->getId()) {
            $quote = $this->checkoutSession->getQuote();

            if ($quote->getId() && $quote->hasItems()) {
                $quote->setKeepItemsFlag(true);
            }
        }
    }
}
