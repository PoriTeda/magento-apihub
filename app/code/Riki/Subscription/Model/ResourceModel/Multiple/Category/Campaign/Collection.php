<?php
namespace Riki\Subscription\Model\ResourceModel\Multiple\Category\Campaign;

/**
 * Class Collection
 * @package Riki\Subscription\Model\ResourceModel\Multiple\Category\Campaign
 */
class Collection extends
 \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
    /**
     * Define   variables
     * @var     string
     */
    protected $_idFieldName = 'campaign_id';

    /**
     * Define   resource model
     * @return  void
     */
    protected function _construct()
    {
        $this->_init(
            \Riki\Subscription\Model\Multiple\Category\Campaign::class,
            \Riki\Subscription\Model\ResourceModel\Multiple\Category\Campaign::class
        );
        $this->_map['fields']['id'] = 'main_table.campaign_id';
    }
}
