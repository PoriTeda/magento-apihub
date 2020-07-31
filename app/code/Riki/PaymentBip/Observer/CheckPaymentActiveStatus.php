<?php

namespace Riki\PaymentBip\Observer;

use Magento\Framework\DataObject;

class CheckPaymentActiveStatus implements \Magento\Framework\Event\ObserverInterface
{
    /** @var \Magento\Customer\Api\CustomerRepositoryInterface */
    protected $customerRepository;

    /**
     * @var \Riki\CsvOrderMultiple\Logger\LoggerOrder
     */
    protected $csvOrderLogger;

    /**
     * CheckPaymentActiveStatus constructor.
     * @param \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository
     * @param \Riki\CsvOrderMultiple\Logger\LoggerOrder $logger
     */
    public function __construct(
        \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository,
        \Riki\CsvOrderMultiple\Logger\LoggerOrder $logger
    ) {
        $this->customerRepository = $customerRepository;
        $this->csvOrderLogger = $logger;

    }


    /**
     * Set Invoice base payment to inactive if customer is invoice customer
     *
     * @param \Magento\Framework\Event\Observer $observer
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {

        /** @var \Magento\Payment\Model\Method\AbstractMethod $paymentMethod */
        $paymentMethod = $observer->getEvent()->getMethodInstance();
        $quote = $observer->getEvent()->getQuote();
        $result = $observer->getEvent()->getResult();
        if ($quote){
            $importOrder = $quote->getData(\Riki\CsvOrderMultiple\Cron\ImportOrders::CSV_ORDER_IMPORT_FLAG);
            // in case of import order, log if this observer makes the payment method becomes unavailable.
            $doLog = $result->getData('is_available') && $importOrder;
        }

        if (
            $paymentMethod->getCode() == \Riki\PaymentBip\Model\InvoicedBasedPayment::PAYMENT_CODE &&
            $result->getData('is_available')
        ) {

            $result->setData('is_available', false);

            /** @var \Magento\Quote\Model\Quote $quote */
            $quote = $observer->getEvent()->getQuote();

            if (!$quote) {
                return;
            }


            $customerId = $quote->getCustomerId();

            try {
                $customer = $this->customerRepository->getById($customerId);

            } catch (\Exception $e) {
                return;
            }

            if (
                ($b2bFlagAttribute = $customer->getCustomAttribute('b2b_flag')) &&
                ($shoshaBusinessCodeAttribute = $customer->getCustomAttribute('shosha_business_code'))
            ) {
                if (
                    $b2bFlagAttribute->getValue() &&
                    $shoshaBusinessCodeAttribute->getValue()
                ) {
                    $result->setData('is_available', true);
                }
            }
        }

        if (isset($doLog) && $doLog && !$result->getData('is_available')) {
            $this->csvOrderLogger->info(__(
                'original_unique_id [%1]: Payment method is disable by observer %2',
                $quote->getOriginalUniqueId(),
                self::class)
            );
        }
    }
}