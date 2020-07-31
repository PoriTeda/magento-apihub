<?php

namespace Riki\Fraud\Model\ResourceModel\SuspectedFraud\Grid;

class Collection extends \Magento\Framework\View\Element\UiComponent\DataProvider\SearchResult
{
    /**
     * Collection constructor.
     * @param \Magento\Framework\Data\Collection\EntityFactoryInterface $entityFactory
     * @param \Psr\Log\LoggerInterface $logger
     * @param \Magento\Framework\Data\Collection\Db\FetchStrategyInterface $fetchStrategy
     * @param \Magento\Framework\Event\ManagerInterface $eventManager
     * @param string $mainTable
     * @param string $resourceModel
     */
    public function __construct(
        \Magento\Framework\Data\Collection\EntityFactoryInterface $entityFactory,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Framework\Data\Collection\Db\FetchStrategyInterface $fetchStrategy,
        \Magento\Framework\Event\ManagerInterface $eventManager,
        $mainTable,
        $resourceModel
    ) {
        parent::__construct( $entityFactory, $logger, $fetchStrategy, $eventManager, $mainTable, $resourceModel );
    }

    /**
     * @return $this
     */
    protected function _initSelect()
    {
        parent::_initSelect();

        $this->getSelect()->join(
            array('order'=>$this->getTable('sales_order')),
            'order.entity_id = main_table.order_id', [
                'status'=> 'order.status'
            ]
        );
        
        return $this;
    }

    /**
     * Add field filter to collection
     * 
     * @param array|string $field
     * @param null $condition
     * 
     * @return $this
     */
    public function addFieldToFilter($field, $condition = null)
    {
        switch($field){
            case 'customer_id':
                $field = 'main_table.customer_id';
                break;
        }
        return parent::addFieldToFilter($field, $condition); 
    }
}