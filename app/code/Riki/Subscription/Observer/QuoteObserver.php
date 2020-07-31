<?php
/**
 *
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Riki\Subscription\Observer;

use Magento\Framework\Event\Observer as EventObserver;
use Magento\Framework\Event\ObserverInterface;
use Riki\Subscription\Model\Constant;

/**
 * Whhen cart is empty. remove riki_course_id
 *
 * Class QuoteObserver
 * @package Riki\Subscription\Observer
 */
class QuoteObserver implements ObserverInterface
{
    protected $objQuote;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;

    /**
     * The flag resource.
     *
     * @var \Magento\Framework\FlagManager
     */
    private $flagManager;

    public function __construct(
        \Psr\Log\LoggerInterface $logger,
        \Magento\Framework\FlagManager $flagManager
    )
    {
        $this->logger = $logger;
        $this->flagManager = $flagManager;
    }

    /**
     * Set persistent data into quote
     *
     * @param EventObserver $observer
     * @return void
     */
    public function execute(EventObserver $observer)
    {
        /** @var $objQuote \Magento\Quote\Model\Quote */
        $objQuote = $observer->getEvent()->getQuote();
        $this->objQuote = $objQuote;
        if (!$objQuote) {
            return;
        }

        $this->_clearRIkiCourseIdWhenQuoteIsEmpty();
    }

    /**
     * Clear riki_course_id when cart item is empty
     */
    private function _clearRIkiCourseIdWhenQuoteIsEmpty()
    {
        if ($this->objQuote->isSaveAllowed() && empty($this->objQuote->getAllItems())) {
            /**
             * @TODO: disable clear riki_cource_id when cart is empty
             */
            // NED-5510 Remove flag data for trial point upon quote submit success
            if ($this->objQuote->getData(Constant::POINT_FOR_TRIAL) > 0
                && $this->objQuote->getCustomerId()
                && $this->objQuote->getData(Constant::QUOTE_RIKI_COURSE_ID)) {
                $flagcode = $this->objQuote->getId() . '_'
                    . $this->objQuote->getCustomerId() . '_'
                    . $this->objQuote->getData(Constant::QUOTE_RIKI_COURSE_ID);
                if ($this->flagManager->getFlagData($flagcode)) {
                    $this->flagManager->deleteFlag($flagcode);
                    $this->logger->info("NED-5510 Remove flag data code when cart is clear: ".$flagcode);
                }
            }
            $this->objQuote->setData(Constant::QUOTE_RIKI_COURSE_ID, null);
            $this->objQuote->setData(Constant::RIKI_FREQUENCY_ID, null);
            $this->objQuote->setData(Constant::RIKI_HANPUKAI_QTY, null);
            $this->objQuote->setData(Constant::POINT_FOR_TRIAL, null);
        }
    }
}
