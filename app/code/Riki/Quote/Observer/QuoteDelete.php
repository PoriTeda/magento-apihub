<?php

namespace Riki\Quote\Observer;

use Magento\Framework\Event\ObserverInterface;
use Riki\Framework\Helper\Logger\LoggerBuilder;
use Magento\Framework\Exception\LocalizedException;
use Magento\Store\Model\ScopeInterface;

class QuoteDelete implements ObserverInterface
{
    /**
     * @var LoggerBuilder
     */
    protected $loggerBuilder;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * QuoteDelete constructor.
     *
     * @param \Riki\Framework\Helper\Logger\LoggerBuilder $loggerBuilder
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        LoggerBuilder $loggerBuilder,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
    ) {
        $this->loggerBuilder = $loggerBuilder;
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * @param \Magento\Framework\Event\Observer $observer
     * @throws \Magento\Framework\Validator\Exception
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        /** @var \Magento\Quote\Model\Quote $quote */
        $quote = $observer->getEvent()->getQuote();

        // NED-2153 Log detail trace after quote delete
        if (!$this->scopeConfig->getValue(
            'loggersetting/quote_logger/logger_quote_delete_enable_status',
            ScopeInterface::SCOPE_STORE
        )) {
            return;
        }

        $dataTrace = [
            'quote_id' => $quote->getId(),
            'store_id' => $quote->getStoreId(),
            'customer_id' => $quote->getCustomerId(),
            'is_active' => $quote->getIsActive(),
            'created_at' => $quote->getCreatedAt(),
            'updated_at' => $quote->getUpdatedAt()
        ];

        $writer = new \Zend\Log\Writer\Stream(BP . '/var/log/quote_delete.log');
        $logger = new \Zend\Log\Logger();
        $logger->addWriter($writer);
        $logger->info(new LocalizedException(
            __('A quote has been deleted, data: %1', json_encode($dataTrace))
        ));
    }
}
