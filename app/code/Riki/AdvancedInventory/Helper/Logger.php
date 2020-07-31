<?php
namespace Riki\AdvancedInventory\Helper;

use Riki\Framework\Helper\Logger\LoggerBuilder;

class Logger extends \Magento\Framework\App\Helper\AbstractHelper
{
    /** @var  \Riki\Framework\Helper\Logger\Monolog */
    protected $oosLogger;

    protected $triggerOosLogger;

    /**
     * @var \Riki\Framework\Helper\Logger\LoggerBuilder
     */
    protected $loggerBuilder;

    /**
     * Logger constructor.
     *
     * @param LoggerBuilder $loggerBuilder
     * @param \Magento\Framework\App\Helper\Context $context
     */
    public function __construct(
        \Riki\Framework\Helper\Logger\LoggerBuilder $loggerBuilder,
        \Magento\Framework\App\Helper\Context $context
    ) {
        $this->loggerBuilder = $loggerBuilder;
        parent::__construct($context);
    }

    /**
     * Get logger for out of stock
     *
     * @return \Riki\Framework\Helper\Logger\Monolog
     */
    public function getOosLogger()
    {
        if (!$this->oosLogger) {
            $this->oosLogger = $this->loggerBuilder
                ->setName('Riki_AdvancedInventory')
                ->setFileName('oos_' . date('Y-m-d') . '.log')
                ->pushHandlerByAlias(LoggerBuilder::ALIAS_DATE_HANDLER)
                ->create();
        }

        return $this->oosLogger;
    }

    public function getOosTriggerLogger()
    {
        if (!$this->triggerOosLogger) {
            $this->triggerOosLogger = $this->loggerBuilder
                ->setName('Riki_AdvancedInventory')
                ->setFileName('trigger_oos_' . date('Y-m-d') . '.log')
                ->pushHandlerByAlias(LoggerBuilder::ALIAS_DATE_HANDLER)
                ->create();
        }

        return $this->triggerOosLogger;
    }
}