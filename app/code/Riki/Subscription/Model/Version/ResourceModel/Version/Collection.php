<?php
namespace Riki\Subscription\Model\Version\ResourceModel\Version;

class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
    /**
     * Define resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('Riki\Subscription\Model\Version\Version', 'Riki\Subscription\Model\Version\ResourceModel\Version');
        $this->_map['fields']['id'] = 'main_table.id';
    }
}