<?php

namespace Riki\SubscriptionMachine\Observer;

class RemoveFreeMachineItem implements \Magento\Framework\Event\ObserverInterface
{
    /**
     * @var \Magento\Quote\Api\CartRepositoryInterface
     */
    protected $cartRepository;

    /**
     * PaymentInformationSubmitOrderFailed constructor.
     * @param \Magento\Quote\Api\CartRepositoryInterface $cartRepository
     */
    public function __construct(
        \Magento\Quote\Api\CartRepositoryInterface $cartRepository
    ) {
        $this->cartRepository = $cartRepository;
    }

    /**
     * @param \Magento\Framework\Event\Observer $observer
     * @return $this
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        /** @var \Magento\Quote\Model\Quote $quote */
        $quote = $observer->getEvent()->getQuote();
        try {
            $hasFreeMachine = false;
            /** @var \Magento\Quote\Model\Quote\Item $quoteItem */
            foreach ($quote->getAllItems() as $quoteItem) {
                $buyRequest = $quoteItem->getBuyRequest();
                if (isset($buyRequest['options']['free_machine_item'])) {
                    $hasFreeMachine = true;
                    $quoteItem->isDeleted(true);
                }
            }

            if ($hasFreeMachine) {
                $this->cartRepository->save($quote);
            }
            return $this;
        } catch (\Exception $e) {
            return $this;
        }
    }
}
