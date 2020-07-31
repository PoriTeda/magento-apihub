<?php

namespace Riki\CsvOrderMultiple\Observer;

use \Magento\Framework\Exception\LocalizedException;

class ValidatePaymentMethodBeforeSubmitQuote implements \Magento\Framework\Event\ObserverInterface
{
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $quote = $observer->getEvent()->getQuote();

        $importOrder = $quote->getData(\Riki\CsvOrderMultiple\Cron\ImportOrders::CSV_ORDER_IMPORT_FLAG);

        if (!$importOrder) {
            return $this;
        }

        $order = $observer->getEvent()->getOrder();

        $payment = $order->getPayment();

        if (empty($payment)) {
            return $this;
        }

        if (!empty($payment)) {

            $methodInstance = $payment->getMethodInstance();

            if (!$methodInstance->isAvailable($quote)) {
                throw new LocalizedException(
                    __('The requested Payment Method is not allowed for your shopping cart.')
                );
            }
        }

        return $this;
    }
}
