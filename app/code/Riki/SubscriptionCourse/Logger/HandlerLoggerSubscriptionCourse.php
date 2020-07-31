<?php


namespace Riki\SubscriptionCourse\Logger;

use Magento\Framework\Filesystem\DriverInterface;

class HandlerLoggerSubscriptionCourse extends \Magento\Framework\Logger\Handler\Base
{
    /**
     * @var int
     */
    protected $loggerType = \Monolog\Logger::INFO;
    /**
     * @var string
     */
    protected $fileName = '/var/log/import_subscription_course/subscription_course';

    /**
     * @var \Magento\Framework\Stdlib\DateTime\DateTime
     */
    protected $dateTime;
    /**
     * @var \Magento\Framework\Stdlib\DateTime\TimezoneInterface
     */
    protected $timeZone;

    /**
     * HandlerLoggerSubscriptionCourse constructor.
     * @param \Magento\Framework\Stdlib\DateTime\DateTime $dateTime
     * @param \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timeZone
     * @param DriverInterface $filesystem
     * @param null $filePath
     */
    public function __construct(
        \Magento\Framework\Stdlib\DateTime\DateTime $dateTime,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timeZone,
        DriverInterface $filesystem,
        $filePath = null
    ) {
        parent::__construct($filesystem, $filePath);
        $this->timeZone = $timeZone;
        $this->dateTime = $dateTime;

        $now = $this->dateTime->gmtDate();
        $nowTimezone = $this->timeZone->date($now)->format('YmdHis');
        $newFileName = $this->fileName . "-" . $nowTimezone . '.log';

        $this->url = str_replace($this->fileName, $newFileName, $this->url);
        $this->fileName = str_replace($this->fileName, $newFileName, $this->fileName);
    }
}
