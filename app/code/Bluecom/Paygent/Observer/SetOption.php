<?php

namespace Bluecom\Paygent\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

class SetOption implements ObserverInterface
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
     * SetOption constructor.
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
     * @param Observer $observer
     *
     * @return void
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $paymentData = $observer->getInput();
        $additionalData = $paymentData->getAdditionalData();

        $paygentOption = isset($additionalData['extension_attributes'])
            ? $additionalData['extension_attributes']->getPaygentOption() : null;

        if (!is_null($paygentOption)) {
            $quote = $observer->getPayment()->getQuote();
            $customerId = $quote->getCustomerId();

            $currentOption = $this->paygentOption->loadByAttribute('customer_id', $customerId);

            if(!$currentOption->getId()) {
                //Save paygent option
                $data = [
                    'customer_id' => $customerId,
                    'option_checkout' => $paygentOption
                ];

                try {
                    $this->paygentOption->setData($data)->save();
                } catch (\Exception $e) {
                    $this->logger->critical($e);
                }
            } else {
                if($currentOption->getOptionCheckout() != $paygentOption) {
                    $currentOption->setOptionCheckout($paygentOption);

                    try {
                        $currentOption->save();
                    } catch (\Exception $e) {
                        $this->logger->critical($e);
                    }
                }
            }
        }
    }
}
