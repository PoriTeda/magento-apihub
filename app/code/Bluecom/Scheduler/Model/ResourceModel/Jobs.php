<?php
/**
 * PHP version 7
 *
 * @category  Bluecom\Scheduler
 * @package   Bluecom\Scheduler\Model\ResourceModel
 * @author    Nestle.co.jp <support@nestle.co.jp>
 * @copyright 2016 Riki
 * @license   https://opensource.org/licenses/MIT MIT License
 * @link      https://github.com/rikibusiness/riki-ecommerce
 */

namespace Bluecom\Scheduler\Model\ResourceModel;
/**
 * Class Jobs
 *
 * @category  Bluecom\Scheduler
 * @package   Bluecom\Scheduler\Model\ResourceModel
 * @author    Nestle.co.jp <support@nestle.co.jp>
 * @copyright 2016 Riki
 * @license   https://opensource.org/licenses/MIT MIT License
 * @link      https://github.com/rikibusiness/riki-ecommerce
 */
class Jobs extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    /**
     * Initialize resource model
     *
     * @return void
     */
    protected function _construct()
    {

        $this->_init('mast_scheduler_jobs', 'job_id');
    }
}