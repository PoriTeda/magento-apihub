<?php
/**
 * PHP version 7
 *
 * @category  Bluecom\Scheduler
 * @package   Bluecom\Scheduler\ResourceModel\Schedules
 * @author    Nestle.co.jp <support@nestle.co.jp>
 * @copyright 2016 Riki
 * @license   https://opensource.org/licenses/MIT MIT License
 * @link      https://github.com/rikibusiness/riki-ecommerce
 */
namespace Bluecom\Scheduler\Model\ResourceModel\Schedules;

/**
 * Class Collection
 *
 * @category  Bluecom\Scheduler
 * @package   Bluecom\Scheduler\ResourceModel\Schedules
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
    protected $_idFieldName = 'schedule_id';

    /**
     * Define resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(
            'Bluecom\Scheduler\Model\Schedules',
            'Bluecom\Scheduler\Model\ResourceModel\Schedules'
        );
        $this->_map['fields']['schedule_id'] = 'main_table.schedule_id';
    }
}