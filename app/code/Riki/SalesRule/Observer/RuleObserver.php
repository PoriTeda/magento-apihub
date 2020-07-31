<?php

namespace Riki\SalesRule\Observer;

use Magento\Framework\Event\ObserverInterface;

class RuleObserver implements ObserverInterface
{
    /**
     * @var \Riki\Quote\Model\QuoteManagement
     */
    protected $quoteManagement;

    /**
     * @var \Magento\Framework\Registry
     */
    protected $registry;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;

    /**
     * RuleObserver constructor.
     *
     * @param \Riki\Quote\Model\QuoteManagement $quoteManagement
     * @param \Magento\Framework\Registry $registry
     */
    public function __construct(
        \Riki\Quote\Model\QuoteManagement $quoteManagement,
        \Magento\Framework\Registry $registry,
        \Psr\Log\LoggerInterface $logger
    )
    {
        $this->quoteManagement = $quoteManagement;
        $this->registry = $registry;
        $this->logger = $logger;
    }

    /**
     * Set trigger_recollect to all active quotes to force Magento cart re-collect
     * total price, Promotion when the AJAX total-information does not work
     *
     * @param \Magento\Framework\Event\Observer $observer Observer
     *
     * @return void
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $rule = $observer->getRule();
        if ($rule->getId() && $rule->getData('trigger_recollect_quote')) {
            try {
                $this->quoteManagement->triggerRecollectActiveQuote(['find_in_set(?, applied_rule_ids) > 0' => $rule->getId()]);
            } catch (\Exception $e) {
                $this->logger->critical($e);
            }
        }
    }
}
