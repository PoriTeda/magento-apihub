<?php
namespace Riki\OfflinePayments\Observer;

use Magento\Framework\DataObject;

class CheckPaymentActiveGiftOrderRule implements \Magento\Framework\Event\ObserverInterface
{
    /** @var \Magento\Customer\Api\CustomerRepositoryInterface  */
    protected $customerRepository;

    /** @var \Riki\OfflinePayments\Helper\Data  */
    protected $helper;

    /**
     * CheckPaymentActiveStatus constructor.
     * @param \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository
     */
    public function __construct(
        \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository,
        \Riki\OfflinePayments\Helper\Data $helper
    )
    {
        $this->customerRepository = $customerRepository;
        $this->helper = $helper;
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

        /** @var DataObject $result */
        $result = $observer->getEvent()->getResult();

        if (
            $paymentMethod->getCode() == \Magento\OfflinePayments\Model\Cashondelivery::PAYMENT_METHOD_CASHONDELIVERY_CODE &&
            $result->getData('is_available')
        ) {

            /** @var \Magento\Quote\Model\Quote $quote */
            $quote = $observer->getEvent()->getQuote();

            if ($quote) {
                if ($this->helper->isGiftOrderQuote($quote)) {
                    $result->setData('is_available', false);
                }
            }
        }
    }
}