<?php

namespace Riki\Customer\Plugin\UiComponent;

class SearchNameKanaRegularFilter
{
    /**
     * @var \Magento\Framework\DB\Adapter\AdapterInterface
     */
    protected $_connection;

    public function __construct(
        \Magento\Framework\App\ResourceConnection $resourceConnection
    )
    {
        $this->_connection = $resourceConnection->getConnection();
    }

    public function aroundApply(\Magento\Framework\View\Element\UiComponent\DataProvider\RegularFilter $subject, $proceed ,\Magento\Framework\Data\Collection\AbstractDb $collection, \Magento\Framework\Api\Filter $filter)
    {
        if('name' == $filter->getField() &&
            $this->_connection->getTableName('customer_grid_flat') == $collection->getMainTable() &&
            $this->_connection->tableColumnExists($this->_connection->getTableName('customer_grid_flat'), 'firstnamekana') &&
            $this->_connection->tableColumnExists($this->_connection->getTableName('customer_grid_flat'), 'lastnamekana')
        ){

            $collection->addFieldToFilter(['name','firstnamekana','lastnamekana'],
                [
                    [$filter->getConditionType() => $filter->getValue()],
                    [$filter->getConditionType() => $filter->getValue()],
                    [$filter->getConditionType() => $filter->getValue()]
                ]
            );
        }
        elseif('membership' == $filter->getField() && $this->_connection->getTableName('customer_grid_flat') == $collection->getMainTable()){

            $collection->addFieldToFilter(['membership'],
                [
                    ['finset'   => $filter->getValue()],
                ]);
        }
        else{
            $proceed($collection,$filter);
        }

        return false;
    }
}
