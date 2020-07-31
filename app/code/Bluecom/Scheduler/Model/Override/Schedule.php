<?php
/**
 * Schedule Class
 *
 * PHP version 7
 *
 * @category  Bluecom\Scheduler
 * @package   Bluecom\Scheduler\Model\Override
 * @author    Nestle.co.jp <support@nestle.co.jp>
 * @copyright 2016 Riki
 * @license   https://opensource.org/licenses/MIT MIT License
 * @link      https://github.com/rikibusiness/riki-ecommerce
 */
namespace Bluecom\Scheduler\Model\Override;
/**
 * Class Schedule
 *
 * @category  Bluecom\Scheduler
 * @package   Bluecom\Scheduler\Model\Override
 * @author    Nestle.co.jp <support@nestle.co.jp>
 * @copyright 2016 Riki
 * @license   https://opensource.org/licenses/MIT MIT License
 * @link      https://github.com/rikibusiness/riki-ecommerce
 */
class Schedule extends \Magento\Cron\Model\Schedule
{
    /**
     * @return bool
     */
    public function tryLockJob()
    {
        if ($this->_getResource()->trySetJobUniqueStatusAtomic(
            $this->getId(),
            self::STATUS_RUNNING,
            self::STATUS_PENDING
        )) {
            $this->setPid(getmypid());
            $this->setStatus(self::STATUS_RUNNING);
            return true;
        }
        return false;
    }
}