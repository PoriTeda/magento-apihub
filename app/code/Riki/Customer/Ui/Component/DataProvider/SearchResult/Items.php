<?php

namespace Riki\Customer\Ui\Component\DataProvider\SearchResult;

class Items extends \Magento\Framework\View\Element\UiComponent\DataProvider\SearchResult
{
    /**
     * @return $this
     */
    protected function _initSelect()
    {
        parent::_initSelect();
        $this->addFilterToMap('increment_id', 'main_table.increment_id');
        $this->addDataFilter();
        return $this;
    }

    /**
     * Customer filter
     */
    protected function _renderFiltersBefore()
    {
        parent::_renderFiltersBefore();
        $wherePart = $this->getSelect()->getPart(\Magento\Framework\DB\Select::WHERE);
        $replace = new \Zend_Db_Expr("CONCAT(customer_address.lastname,' ',customer_address.firstname )");
        foreach ($wherePart as $key => $cond) {
            $wherePart[$key] = str_replace('`customer_name`', $replace, $cond);
        }
        $this->getSelect()->setPart(\Magento\Framework\DB\Select::WHERE, $wherePart);
    }


    /**
     * Add filter
     *
     * @return $this
     */
    public function addDataFilter()
    {
        $this->getSelect()->columns(["CONCAT(customer_address.lastname,' ',customer_address.firstname ) as customer_name"]);
        $this->getSelect()->joinLeft(
            array('customer' => 'customer_entity'),
            'customer.entity_id = main_table.customer_id',
            array('email')
        );
        $this->getSelect()->joinLeft(
            array('customer_address' => 'customer_address_entity'),
            'customer.default_billing = customer_address.entity_id',
            array('customer_address.telephone', "CONCAT(customer_address.lastname,' ',customer_address.firstname ) as customer_name")
        );
        return $this;
    }

}