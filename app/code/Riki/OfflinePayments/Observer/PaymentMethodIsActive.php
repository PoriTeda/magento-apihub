<?php
namespace Riki\OfflinePayments\Observer;

class PaymentMethodIsActive implements \Magento\Framework\Event\ObserverInterface
{
    /**
     * @var \Riki\DeliveryType\Model\QuoteItemAddressDdateProcessor
     */
    protected $_quoteItemAddressDdateProcessor;
    /**
     * @var \Riki\Sales\Helper\Admin
     */
    protected $_salesAdminHelper;

    /**
     * PaymentMethodIsActive constructor.
     * @param \Riki\Sales\Helper\Admin $salesAdminHelper
     * @param \Riki\DeliveryType\Model\QuoteItemAddressDdateProcessor $quoteItemAddressDdateProcessor
     */
    public function __construct(
        \Riki\Sales\Helper\Admin $salesAdminHelper,
        \Riki\DeliveryType\Model\QuoteItemAddressDdateProcessor $quoteItemAddressDdateProcessor
    )
    {
        $this->_salesAdminHelper = $salesAdminHelper;
        $this->_quoteItemAddressDdateProcessor = $quoteItemAddressDdateProcessor;
    }

    /**
     * @param \Magento\Framework\Event\Observer $observer
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $this->processForCashOnDelivery($observer);
    }


    /**
     * Logic for CashOnDelivery method
     *
     * @param $observer
     */
    public function processForCashOnDelivery($observer)
    {
        /** @var \Magento\Payment\Model\Method\AbstractMethod $methodInstance */
        $methodInstance = $observer->getMethodInstance();
        if ($methodInstance->getCode() !== \Magento\OfflinePayments\Model\Cashondelivery::PAYMENT_METHOD_CASHONDELIVERY_CODE) {
            return;
        }

        if ($this->_salesAdminHelper->isMultipleShippingAddressCart()) {
            $result = $observer->getResult();
            $result->setData('is_available', false);
            return;
        }

        /** @var \Magento\Quote\Model\Quote $quote */
        $quote = $observer->getQuote();
        if (!$quote || !$quote->getId()) {
            return;
        }

        $items = $quote->getAllVisibleItems();
        if (count($items) < 1) {
            return;
        }

        // Check cart more than 1 delivery type
        $summary = $this->_quoteItemAddressDdateProcessor->splitQuoteByDeliveryType($items);
        // Check cart only DM delivery type
        $dmOnly = $this->_quoteItemAddressDdateProcessor->splitQuoteByDeliveryTypeDm($items);

        $result = $observer->getResult();
        if (count($summary) <= 1) {
            if($dmOnly){
                $result->setData('is_available', false);
            } else {
                return;
            }

        }

        $result->setData('is_available', false);

    }
}