<?php
namespace Bluecom\Paygent\Ui\Component\DataProvider\SearchResult;

class Items extends \Magento\Framework\View\Element\UiComponent\DataProvider\SearchResult
{
    protected function _initSelect()
    {
        parent::_initSelect();

        $this->addDataFilter();
        return $this;
    }

    protected function _renderFiltersBefore()
    {
        parent::_renderFiltersBefore();
        $wherePart = $this->getSelect()->getPart(\Magento\Framework\DB\Select::WHERE);
        $replace = new \Zend_Db_Expr('CONCAT(customer_lastname," ",customer_firstname)');
        foreach ($wherePart as $key => $cond) {
            $wherePart[$key] = str_replace('`increment_id`','main_table.increment_id', $cond);
            $wherePart[$key] = str_replace('`customer_name`',$replace, $cond);
        }
        $this->getSelect()->setPart(\Magento\Framework\DB\Select::WHERE, $wherePart);
    }

    public function addDataFilter()
    {
        $this->addFilterToMap('customer_id', 'main_table.customer_id');
        $this->getSelect()->columns(["CONCAT(customer_lastname,' ',customer_firstname) as customer_name"]);
        $this->addFieldToFilter('payment_error_code',array('notnull' => true));

        $this->getSelect()->joinLeft(
             array('riki_paygent_error_handling' => 'riki_paygent_error_handling'),
             'riki_paygent_error_handling.error_code = main_table.payment_error_code',
             array('backend_message')
        );

        $this->getSelect()->joinLeft(
            array( 'subscription_profile' => 'subscription_profile'),
            'subscription_profile.profile_id = main_table.subscription_profile_id',
            array('course_name')
        );

        $this->getSelect()->joinLeft(
            array('sales_order_address' => 'sales_order_address'),
            'sales_order_address.parent_id = main_table.entity_id AND sales_order_address.address_type="billing" ',
            array('telephone')
        );

        return $this;
    }

}