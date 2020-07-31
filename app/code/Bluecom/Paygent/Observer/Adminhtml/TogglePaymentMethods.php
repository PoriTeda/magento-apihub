<?php

namespace Bluecom\Paygent\Observer\Adminhtml;

use Magento\Framework\Event\ObserverInterface;

class TogglePaymentMethods implements ObserverInterface
{

    protected $_quoteIdToItemCreditCardOnly = [];

    protected $csvOrderLogger;

    public function __construct(\Riki\CsvOrderMultiple\Logger\LoggerOrder $logger)
    {
        $this->csvOrderLogger = $logger;
    }

    /**
     * Disable other payment methods if cart include only credit card only product
     *
     * @param \Magento\Framework\Event\Observer $observer
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {

        $result = $observer->getEvent()->getResult();

        $paymentMethod = $observer->getEvent()->getMethodInstance()->getCode();

        $quote = $observer->getEvent()->getQuote();
        if (
            !$quote ||
            !$result ||
            $paymentMethod == \Bluecom\Paygent\Model\ConfigProvider::PAYGENT_CODE
        ) {
            return;
        }

        $importOrder = $quote->getData(\Riki\CsvOrderMultiple\Cron\ImportOrders::CSV_ORDER_IMPORT_FLAG);
        // in case of import order, log if this observer makes the payment method becomes unavailable.
        $doLog = $result->getData('is_available') && $importOrder;

        if (!isset($this->_quoteIdToItemCreditCardOnly[$quote->getId()])) {

            $this->_quoteIdToItemCreditCardOnly[$quote->getId()] = false;

            if($quote instanceof \Magento\Quote\Model\Quote){
                $items = $quote->getAllItems();
            }else{
                $items = $quote->getItems();
            }


            if($items){
                /** @var \Magento\Quote\Model\Quote\Item $item */
                foreach($items as $item){
                    if($item->getProduct()->getCreditCardOnly()){
                        $this->_quoteIdToItemCreditCardOnly[$quote->getId()] = true;
                        break;
                    }
                }
            }
        }

        if($this->_quoteIdToItemCreditCardOnly[$quote->getId()]){
            $result->setData('is_available', false);
        }

        if ($doLog && !$result->getData('is_available')) {
            $this->csvOrderLogger->info(__(
                    'original_unique_id [%1]: Payment method is disable by observer %2',
                    $quote->getOriginalUniqueId(),
                    self::class)
            );
        }
    }
}
