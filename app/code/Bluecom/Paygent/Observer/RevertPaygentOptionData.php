<?php

namespace Bluecom\Paygent\Observer;

class RevertPaygentOptionData implements \Magento\Framework\Event\ObserverInterface
{
    /**
     * @var \Bluecom\Paygent\Model\PaygentOption
     */
    protected $paygentOption;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;

    /**
     * RevertPaygentOptionData constructor.
     *
     * @param \Bluecom\Paygent\Model\PaygentOption $paygentOption
     * @param \Psr\Log\LoggerInterface $logger
     */
    public function __construct(
        \Bluecom\Paygent\Model\PaygentOption $paygentOption,
        \Psr\Log\LoggerInterface $logger
    ) {
        $this->paygentOption = $paygentOption;
        $this->logger = $logger;
    }

    /**
     * When place order submit failure
     * We need to revert paygent option data to avoid debit card is charged double
     *
     * @param \Magento\Framework\Event\Observer $observer
     * @return void
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        /** @var \Magento\Sales\Model\Order $order */
        $order = $observer->getEvent()->getOrder();

        if (!$order instanceof \Magento\Sales\Model\Order) {
            return;
        }

        // Get customer id from current order.
        $customerId = $order->getCustomerId();
        $currentOption = $this->paygentOption->loadByAttribute('customer_id', $customerId);

        $redirectUrl = $currentOption->getLinkRedirect();

        if ($redirectUrl) {
            $currentOption->setOptionCheckout(0);
            $currentOption->setLinkRedirect('');
            try {
                $currentOption->save();
            } catch (\Exception $e) {
                $this->logger->critical($e);
            }
        }
    }
}
