<?php

namespace Riki\Base\Plugin\Logger;

class Monolog
{
    protected $_timezoneInterface;

    protected $configTimezone;

    /**
     * @param \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezoneInterface
     */
    public function __construct(
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezoneInterface
    ){

        $this->_timezoneInterface = $timezoneInterface;
    }

    /**
     * Adds a log record.
     * Set timestamp
     *
     * @param  integer $level   The logging level
     * @param  string  $message The log message
     * @param  array   $context The log context
     * @return Boolean Whether the record has been processed
     */
    public function beforeAddRecord(
        \Monolog\Logger $subject,
        $level,
        $message,
        array $context = []
    )
    {
        $subject->setTimezone($this->getConfigTimezone());

        return [$level, $message, $context];
    }

    /**
     * @return \DateTimeZone
     */
    public function getConfigTimezone()
    {
        if (!$this->configTimezone)
        {
            $this->configTimezone = new \DateTimeZone($this->_timezoneInterface->getConfigTimezone());
        }

        return $this->configTimezone;
    }
}
