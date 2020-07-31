<?php
namespace Riki\Subscription\Model\ResourceModel\Landing\Page;

/**
 * Class Collection
 * @package Riki\Subscription\Model\ResourceModel\Landing\Page
 */
class Collection extends
 \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
    /**
     * Define   variables
     * @var     string
     */
    protected $_idFieldName = 'landing_page_id';

    /**
     * Define   resource model
     * @return  void
     */
    protected function _construct()
    {
        $this->_init(
            \Riki\Subscription\Model\Landing\Page::class,
            \Riki\Subscription\Model\ResourceModel\Landing\Page::class
        );
        $this->_map['fields']['id'] = 'main_table.landing_page_id';
    }
}
