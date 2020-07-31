<?php
namespace Riki\Prize\Observer;

class PaymentInformationSubmitOrderFailed implements \Magento\Framework\Event\ObserverInterface
{
    /** @var \Magento\Quote\Api\CartRepositoryInterface  */
    protected $cartRepository;

    /**
     * PaymentInformationSubmitOrderFailed constructor.
     * @param \Magento\Quote\Api\CartRepositoryInterface $cartRepository
     */
    public function __construct(
        \Magento\Quote\Api\CartRepositoryInterface $cartRepository
    )
    {
        $this->cartRepository = $cartRepository;
    }

    /**
     * @param \Magento\Framework\Event\Observer $observer
     * @return $this
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $cartId = $observer->getEvent()->getCartId();

        try {
            /** @var \Magento\Quote\Model\Quote $quote */
            $quote = $this->cartRepository->getActive($cartId);

            $hasPrize = false;

            /** @var \Magento\Quote\Model\Quote\Item $quoteItem */
            foreach ($quote->getAllItems() as $quoteItem) {
                if ($quoteItem->getData('prize_id')) {

                    $hasPrize = true;

                    $quote->deleteItem($quoteItem);
                }
            }

            if ($hasPrize) {
                $this->cartRepository->save($quote);
            }

            return $this;

        } catch (\Exception $e) {
            return $this;
        }
    }
}
