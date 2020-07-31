<?php
/**
 * PHP version 7
 *
 * @category  Bluecom\Scheduler
 * @package   Bluecom\Scheduler\ResourceModel\Jobs
 * @author    Nestle.co.jp <support@nestle.co.jp>
 * @copyright 2016 Riki
 * @license   https://opensource.org/licenses/MIT MIT License
 * @link      https://github.com/rikibusiness/riki-ecommerce
 */
namespace Bluecom\Scheduler\Model\ResourceModel\Jobs;

/**
 * Class Collection
 *
 * @category  Bluecom\Scheduler
 * @package   Bluecom\Scheduler\ResourceModel\Jobs
 * @author    Nestle.co.jp <support@nestle.co.jp>
 * @copyright 2016 Riki
 * @license   https://opensource.org/licenses/MIT MIT License
 * @link      https://github.com/rikibusiness/riki-ecommerce
 */

class Collection extends
    \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
    /**
     * Define variables
     * @var string
     */
    protected $_idFieldName = 'job_id';

    /**
     * Define resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(
            'Bluecom\Scheduler\Model\Jobs',
            'Bluecom\Scheduler\Model\ResourceModel\Jobs'
        );
        $this->_map['fields']['jobs_id'] = 'main_table.jobs_id';
    }
}