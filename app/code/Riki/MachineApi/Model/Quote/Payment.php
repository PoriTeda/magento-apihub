<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Riki\MachineApi\Model\Quote;

use Magento\Quote\Api\Data\PaymentInterface;

class Payment extends \Magento\Quote\Model\Quote\Payment
{
    /**
     * Calculate for machine api
     * @var bool
     */
    public $isProcessCollectTotals = true;

    /**
     * Converts request to payment data
     *
     * @param array $rawData
     * @return array
     */
    private function convertPaymentData(array $rawData)
    {
        $paymentData = [
            PaymentInterface::KEY_METHOD => null,
            PaymentInterface::KEY_PO_NUMBER => null,
            PaymentInterface::KEY_ADDITIONAL_DATA => [],
            'checks' => []
        ];

        foreach (array_keys($rawData) as $requestKey) {
            if (!array_key_exists($requestKey, $paymentData)) {
                $paymentData[PaymentInterface::KEY_ADDITIONAL_DATA][$requestKey] = $rawData[$requestKey];
            } elseif ($requestKey === PaymentInterface::KEY_ADDITIONAL_DATA) {
                $paymentData[PaymentInterface::KEY_ADDITIONAL_DATA] = array_merge(
                    $paymentData[PaymentInterface::KEY_ADDITIONAL_DATA],
                    (array) $rawData[$requestKey]
                );
            } else {
                $paymentData[$requestKey] = $rawData[$requestKey];
            }
        }

        return $paymentData;
    }


    public function setIsProcessCollectTotals($value)
    {
        $this->isProcessCollectTotals = $value;
    }

    public function importData(array $data)
    {

        $data = $this->convertPaymentData($data);
        $data = new \Magento\Framework\DataObject($data);
        $this->_eventManager->dispatch(
            $this->_eventPrefix . '_import_data_before',
            [$this->_eventObject => $this, 'input' => $data]
        );

        $this->setMethod($data->getMethod());
        $method = $this->getMethodInstance();
        $quote = $this->getQuote();

        /**
         * Payment availability related with quote totals.
         * We have to recollect quote totals before checking
         */
        if ($this->isProcessCollectTotals){
            $quote->collectTotals();
        }

        $methodSpecification = $this->methodSpecificationFactory->create($data->getChecks());
        if (!$method->isAvailable($quote) || !$methodSpecification->isApplicable($method, $quote)) {
            throw new \Magento\Framework\Exception\LocalizedException(
                __('The requested Payment Method is not available.')
            );
        }

        $method->assignData($data);
        /*
         * validating the payment data
         */
        $method->validate();
        return $this;
    }


}
