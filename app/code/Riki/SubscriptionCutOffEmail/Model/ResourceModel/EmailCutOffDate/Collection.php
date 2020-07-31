<?php
namespace Riki\SubscriptionCutOffEmail\Model\ResourceModel\EmailCutOffDate;

class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
    /**
     * @var string
     */
    protected $_idFieldName = 'id';

    /**
     * Define resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(
            'Riki\SubscriptionCutOffEmail\Model\EmailCutOffDate',
            'Riki\SubscriptionCutOffEmail\Model\ResourceModel\EmailCutOffDate'
        );
        $this->_map['fields']['id'] = 'main_table.id';
    }
    

}