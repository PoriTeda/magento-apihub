<?php
/**
 * Framework
 *
 * PHP version 7
 *
 * @category  RIKI
 * @package   Riki\Framework
 * @author    Nestle.co.jp <support@nestle.co.jp>
 * @copyright 2016 Riki
 * @license   https://opensource.org/licenses/MIT MIT License
 * @link      https://github.com/rikibusiness/riki-ecommerce
 */
namespace Riki\Framework\Helper;

/**
 * Class Datetime
 *
 * @category  RIKI
 * @package   Riki\Framework\Helper
 * @author    Nestle.co.jp <support@nestle.co.jp>
 * @copyright 2016 Riki
 * @license   https://opensource.org/licenses/MIT MIT License
 * @link      https://github.com/rikibusiness/riki-ecommerce
 */
class Datetime extends \Magento\Framework\App\Helper\AbstractHelper
{
    /**
     * Timezone
     *
     * @var \Magento\Framework\Stdlib\DateTime\TimezoneInterface
     */
    protected $timezone;

    /**
     * Datetime constructor.
     *
     * @param \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezone timezone
     * @param \Magento\Framework\App\Helper\Context                $context  context
     */
    public function __construct(
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezone,
        \Magento\Framework\App\Helper\Context $context
    ) {
        $this->timezone = $timezone;
        parent::__construct($context);
    }

    /**
     * Proxy to timezone method
     *
     * @param $name
     * @param $arguments
     *
     * @return mixed
     */
    public function __call($name, $arguments)
    {
        if (!method_exists($this, $name) && method_exists($this->timezone, $name)) {
            return $this->timezone->$name(...$arguments);
        }
    }

    /**
     * @return \Magento\Framework\Stdlib\DateTime\TimezoneInterface
     */
    public function getTimeZoneLib()
    {
        return $this->timezone;
    }

    /**
     * Get today
     *
     * @return \DateTime
     */
    public function getToday()
    {
        return $this->timezone->date();
    }

    /**
     * Convert datetime to stored database
     *
     * @param string $datetime datetime
     *
     * @return string
     */
    public function toDb($datetime = null)
    {
        if (is_null($datetime)) {
            $datetime = $this->getToday()->format('Y-m-d H:i:s');
        }
        $date = new \DateTime(
            $datetime,
            new \DateTimeZone($this->timezone->getConfigTimezone())
        );
        $date->setTimezone(
            new \DateTimeZone($this->timezone->getDefaultTimezone() ?: 'UTC')
        );

        return $date->format('Y-m-d H:i:s');
    }

    /**
     * Convert datetime to date get default timezone
     *
     * @param string $datetime datetime
     *
     * @return string
     */
    public function fromDb($datetime)
    {
        $date = $this->getUtcDatetimeObject($datetime);
        $date->setTimezone(new \DateTimeZone($this->timezone->getConfigTimezone()));

        return $date->format('Y-m-d H:i:s');
    }

    /**
     * generate datetime object, timezone is utc
     *
     * @param $datetime
     * @return \DateTime
     */
    public function getUtcDatetimeObject($datetime)
    {
        return new \DateTime(
            $datetime,
            new \DateTimeZone($this->timezone->getDefaultTimezone() ?: 'UTC')
        );
    }

    /**
     * change datetime format
     * @param $datetime
     * @param $format
     * @return string
     */
    public function formatDatetime($datetime, $format)
    {
        $date = $this->getUtcDatetimeObject($datetime);
        return $date->format($format);
    }
}