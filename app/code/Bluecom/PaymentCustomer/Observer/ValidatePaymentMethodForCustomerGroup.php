<?php

namespace Bluecom\PaymentCustomer\Observer;

class ValidatePaymentMethodForCustomerGroup implements \Magento\Framework\Event\ObserverInterface
{
    /**
     * @var \Bluecom\PaymentCustomer\Helper\Data
     */
    protected $helperData;

    protected $csvOrderLogger;

    /**
     * ValidatePaymentMethodForCustomerGroup constructor.
     * @param \Bluecom\PaymentCustomer\Helper\Data $helperData
     */
    public function __construct(
        \Bluecom\PaymentCustomer\Helper\Data $helperData,
        \Riki\CsvOrderMultiple\Logger\LoggerOrder $logger
    ) {
        $this->helperData = $helperData;
        $this->csvOrderLogger = $logger;
    }

    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $result = $observer->getEvent()->getResult();

        $payment = $observer->getEvent()->getMethodInstance();
        $quote = $observer->getEvent()->getQuote();
        if ($quote){
            $importOrder = $quote->getData(\Riki\CsvOrderMultiple\Cron\ImportOrders::CSV_ORDER_IMPORT_FLAG);
            // in case of import order, log if this observer makes the payment method becomes unavailable.
            $doLog = $result->getData('is_available') && $importOrder;
        }

        if (empty($payment)) {
            return;
        }

        if ($payment->getCode() == 'free') {
            return;
        }

        $quote = $observer->getEvent()->getQuote();

        $currentCustomerGroup = $quote->getData('customer_group_id');
        $customerGroups = $this->helperData->getCustomerGroup($payment->getCode());
        $dataGroups = $this->helperData->toArrayCustomerGroup($customerGroups);

        if (!in_array($currentCustomerGroup, $dataGroups)) {
            $result->setData('is_available', false);
        }

        if (isset($doLog) && $doLog && !$result->getData('is_available')) {
            $this->csvOrderLogger->info(__(
                    'original_unique_id [%1]: Payment method is disable by observer %2',
                    $quote->getOriginalUniqueId(),
                    self::class)
            );
        }
        return;
    }
}
