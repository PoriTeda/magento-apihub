<?php

namespace Riki\Loyalty\Model\ResourceModel\Reward\Collection;

class Grid extends \Riki\Loyalty\Model\ResourceModel\Reward\Collection
{

    /**
     * Initialize db select
     *
     * @return $this
     */
    protected function _initSelect()
    {
        $this->addCustomerFilter(
            $this->_registryManager->registry('current_customer_code')
        );
        $this->getSelect()->joinLeft(
            ['sales_order' => $this->getResource()->getTable('sales_order')],
            'main_table.order_no = sales_order.increment_id',
            ['order_id' => 'sales_order.entity_id']
        );
        $this->addFieldToSelect(new \Zend_Db_Expr('main_table.point * main_table.qty'), 'total_point');
        $this->addOrder('action_date', 'DESC');
        $this->addOrder('main_table.reward_id', 'DESC');
        parent::_initSelect();
        return $this;
    }

    /**
     * @param array|string $field
     * @param null $condition
     * @return $this
     */
    public function addFieldToFilter($field, $condition = null)
    {
        if ($field == 'status') {
            $field = 'main_table.status';
        }
        return parent::addFieldToFilter($field, $condition);
    }
}
