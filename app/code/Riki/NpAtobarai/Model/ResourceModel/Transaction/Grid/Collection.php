<?php

namespace Riki\NpAtobarai\Model\ResourceModel\Transaction\Grid;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use Magento\Framework\View\Element\UiComponent\DataProvider\SearchResult;
use Riki\NpAtobarai\Model\Config\Source\TransactionStatus;

class Collection extends SearchResult
{
    /**
     * @return $this|AbstractCollection
     */
    protected function _initSelect()
    {
        $this->addFilterToMap('created_at', 'main_table.created_at');
        parent::_initSelect();
        $this->getSelect()->joinLeft(
            ['ce' => $this->getTable('sales_shipment')],
            'main_table.shipment_id = ce.entity_id',
            ['shipment_increment_id' => 'ce.increment_id']
        );
        $this->getSelect()->joinLeft(
            ['so' => $this->getTable('sales_order')],
            'main_table.order_id = so.entity_id',
            ['order_increment_id' => 'so.increment_id']
        );
        return $this;
    }
}
