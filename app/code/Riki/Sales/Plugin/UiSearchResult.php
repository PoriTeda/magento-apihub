<?php

namespace Riki\Sales\Plugin;

class UiSearchResult
{
    /**
     * UiSearchResult constructor.
     * @param \Magento\Framework\Registry $coreRegistry
     */
    public function __construct(
        \Magento\Framework\Registry $coreRegistry
    )
    {
        $this->_coreRegistry = $coreRegistry;
    }

    /**
     *beforeLoad
     *
     * @param \Magento\Framework\View\Element\UiComponent\DataProvider\SearchResult $subject
     */
    public function beforeLoad(\Magento\Framework\View\Element\UiComponent\DataProvider\SearchResult $subject)
    {
        if($subject->getMainTable() == 'sales_order_grid')
        {
            $this->_injectSelect($subject);
            return;
        }
    }

    /**
     * beforeGetSelectCountSql
     *
     * @param \Magento\Framework\View\Element\UiComponent\DataProvider\SearchResult $subject
     */
    public function beforeGetSelectCountSql(\Magento\Framework\View\Element\UiComponent\DataProvider\SearchResult $subject)
    {
        if($subject->getMainTable() == 'sales_order_grid') {
            $this->_injectCountSelect($subject);
        }
    }

    /**
     *_injectSelect
     *
     * @param \Magento\Framework\View\Element\UiComponent\DataProvider\SearchResult $subject
     */
    protected function _injectSelect(\Magento\Framework\View\Element\UiComponent\DataProvider\SearchResult $subject)
    {
        $select = $subject->getSelect();

        if($this->_coreRegistry->registry('is_load_subscription_order')){

            $aConditions = $select->getPart('where');
            $aConditions = $this->changeConditionFilter($aConditions);
            $select->setPart('where',$aConditions);
            if (strpos((string)$select, 'riki_type') === false) {
                $select->join(
                    array('sales_order'=>$subject->getTable('sales_order')),
                    'sales_order.entity_id=main_table.entity_id and sales_order.riki_type IN ("'
                    .\Riki\Sales\Helper\Order::RIKI_TYPE_HANPUKAI.'","'
                    .\Riki\Sales\Helper\Order::RIKI_TYPE_SUBSCRIPTION.'", "'
                    .\Riki\Sales\Helper\Order::RIKI_TYPE_DELAY_PAYMENT .'")',
                    array('riki_type'=>'riki_type')
                );
            }

            if (strpos((string)$select, 'subscription_profile') === false) {
                $select->joinLeft(
                    array('subscription_profile'=>$subject->getTable('subscription_profile')),
                    'sales_order.subscription_profile_id = subscription_profile.profile_id',
                    array(
                        'frequency_unit'=>'frequency_unit',
                        'frequency_interval'=>'frequency_interval',
                        'next_order_date'=>'next_order_date',
                        'next_delivery_date'=>'next_delivery_date'
                    )
                );
            }
        }
    }

    /**
     * _injectCountSelect
     *
     * @param \Magento\Framework\View\Element\UiComponent\DataProvider\SearchResult $subject
     */
    protected function _injectCountSelect(\Magento\Framework\View\Element\UiComponent\DataProvider\SearchResult $subject)
    {
        $select = $subject->getSelect();

        if($this->_coreRegistry->registry('is_load_subscription_order')){
            $aConditions = $select->getPart('where');
            $aConditions = $this->changeConditionFilter($aConditions);
            $select->setPart('where',$aConditions);
            if (strpos((string)$select, 'riki_type') === false) {
                $select->join(
                    array('sales_order'=>$subject->getTable('sales_order')),
                    'sales_order.entity_id=main_table.entity_id and sales_order.riki_type IN ("'
                    .\Riki\Sales\Helper\Order::RIKI_TYPE_HANPUKAI.'","'
                    .\Riki\Sales\Helper\Order::RIKI_TYPE_SUBSCRIPTION.'","'
                    .\Riki\Sales\Helper\Order::RIKI_TYPE_DELAY_PAYMENT.'")',
                    array('riki_type'=>'riki_type')
                );
            }
            if (strpos((string)$select, 'subscription_profile') === false) {
                $select->joinLeft(
                    array('subscription_profile'=>$subject->getTable('subscription_profile')),
                    'sales_order.subscription_profile_id = subscription_profile.profile_id',
                    array(
                        'frequency_unit'=>'frequency_unit',
                        'frequency_interval'=>'frequency_interval',
                        'next_order_date'=>'next_order_date',
                        'next_delivery_date'=>'next_delivery_date'
                    )
                );
            }
        }

    }

    /**
     * ChangeConditionFilter
     *
     * @param $select
     * @param $aConditions
     * @return mixed
     */
    public function changeConditionFilter($aConditions){

        if(count($aConditions)){
            foreach($aConditions as $keyCondition =>  $aCondition){
                if( strpos($aCondition,"main_table") === false
                    &&  strpos($aCondition,"frequency_unit") === false
                    &&  strpos($aCondition,"frequency_interval") === false
                    &&  strpos($aCondition,"next_order_date") === false
                    &&  strpos($aCondition,"next_delivery_date") === false
                ) {
                    preg_match("/\((\`[a-z_]+\`)/s", $aCondition, $match);
                    if (isset($match[1])) {
                        $sField = $match[1];
                        $aConditions[$keyCondition] = str_replace($sField, '`main_table`.' . $sField, $aCondition);
                    }
                    preg_match("/MATCH\(([a-z_,]+)\)/s", $aCondition, $match);
                    if (isset($match[1])) {
                        $sFields = $match[1];
                        $aFields = explode(",",$sFields);
                        $aFieldNews = [];
                        foreach($aFields as $aField){
                            $aFieldNews[] = '`main_table`.`' . $aField.'`';
                        }
                        $sFieldNews = implode(",",$aFieldNews);
                        $aConditions[$keyCondition] = str_replace($sFields, $sFieldNews, $aCondition);
                    }
                }

                if( strpos($aCondition,"main_table") === false
                    && strpos($aCondition,"customer_membership") !== false
                ) {
                    preg_match("/\`[a-z_]+\`/s", $aCondition, $match);
                    if (isset($match[0])) {
                        $sField = $match[0];
                        $aConditions[$keyCondition] = str_replace($sField, '`main_table`.' . $sField, $aCondition);
                    }
                }
            }
        }

        return $aConditions;
    }

}