<?php
namespace Riki\ThirdPartyImportExport\Model\ResourceModel\Grid\Order;


class Collection extends \Magento\Framework\View\Element\UiComponent\DataProvider\SearchResult
{
    /**
     * @return $this
     */
    protected function _initSelect()
    {
        parent::_initSelect();

        $this->getSelect()->joinLeft(
            ['shipping' => 'riki_shipping'],
            'shipping.order_no = main_table.order_no' ,
            ['address_first_name', 'address_last_name']
        );
        $this->getSelect()
            ->columns(['bill_name' => new \Zend_Db_Expr("CONCAT(main_table.last_name,' ',main_table.first_name)")])
            ->columns(['ship_name' => new \Zend_Db_Expr("CONCAT(shipping.address_last_name,' ',shipping.address_first_name)")])
            ->group('main_table.order_no');

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
            case 'order_no':
                $field = 'main_table.order_no';
                break;
            case 'customer_code':
                $field = 'main_table.customer_code';
                break;
            case 'email':
                $field = 'main_table.email';
                break;
            case 'bill_name':
                $field = new \Zend_Db_Expr("CONCAT(main_table.last_name,' ',main_table.first_name)");
                break;
            case 'ship_name':
                $field = new \Zend_Db_Expr("CONCAT(shipping.address_last_name,' ',shipping.address_first_name)");
                break;
        }
        return parent::addFieldToFilter($field, $condition);
    }
}