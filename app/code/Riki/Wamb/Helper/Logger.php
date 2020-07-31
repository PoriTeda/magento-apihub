<?php
namespace Riki\Wamb\Helper;

use Riki\Framework\Helper\Logger\LoggerBuilder;

class Logger extends \Magento\Framework\App\Helper\AbstractHelper
{
    /**
     * @var \Riki\Framework\Helper\Logger\Monolog
     */
    protected $cronLogger;

    /**
     * @var \Riki\Framework\Helper\Logger\Monolog
     */
    protected $generalLogger;

    /**
     * @var \Riki\Framework\Helper\Logger\LoggerBuilder
     */
    protected $loggerBuilder;

    /**
     * Logger constructor.
     *
     * @param LoggerBuilder $loggerBuilder
     *
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
     * Get logger for cron
     *
     * @return \Riki\Framework\Helper\Logger\Monolog
     */
    public function getCronLogger()
    {
        if (!$this->cronLogger) {
            $this->cronLogger = $this->loggerBuilder
                ->setName('Riki_Wamb')
                ->setFileName('cron')
                ->pushHandlerByAlias(LoggerBuilder::ALIAS_DATE_HANDLER)
                ->create();
        }

        return $this->cronLogger;
    }

    /**
     * Get general logger
     *
     * @return \Riki\Framework\Helper\Logger\Monolog
     */
    public function getGeneralLogger()
    {
        if (!$this->generalLogger) {
            $this->generalLogger = $this->loggerBuilder
                ->setName('Riki_Wamb')
                ->setFileName('report')
                ->pushHandlerByAlias(LoggerBuilder::ALIAS_DATE_HANDLER)
                ->create();
        }

        return $this->generalLogger;
    }
}