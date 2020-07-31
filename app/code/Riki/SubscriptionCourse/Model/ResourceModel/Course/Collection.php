<?php
namespace Riki\SubscriptionCourse\Model\ResourceModel\Course;

class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{

    /**
     * @var string
     */
    protected $_idFieldName = 'course_id';

    /**
     * Define resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('Riki\SubscriptionCourse\Model\Course', 'Riki\SubscriptionCourse\Model\ResourceModel\Course');
        $this->_map['fields']['course_id'] = 'main_table.course_id';
    }
}