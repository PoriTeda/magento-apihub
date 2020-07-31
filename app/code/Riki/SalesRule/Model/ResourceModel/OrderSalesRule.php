<?php

namespace Riki\SalesRule\Model\ResourceModel;

class OrderSalesRule extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{

    protected $connectionName = 'sales';

    /**
     * Resource initialization
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_setMainTable('riki_order_salesrule');
    }

    /**
     * @param $orderId
     * @param array $rulesData
     * @return $this
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function saveOrderSalesRule($orderId, array $rulesData){
        if($orderId instanceof \Magento\Sales\Model\Order)
            $orderId = $orderId->getId();

        if($orderId && count($rulesData)){
            $conn = $this->getConnection();

            $insertData = [];

            /** @var \Magento\SalesRule\Model\Rule $ruleData */
            foreach($rulesData as $ruleData){
                $insertData[] = [
                    'order_id'  =>  $orderId,
                    'salesrule_id'  =>  $ruleData->getRuleId(),
                    'description'   =>  (string)$ruleData->getDescription()
                ];
            }

            $conn->insertMultiple($this->getMainTable(), $insertData);
        }

        return $this;
    }

    /**
     * @param $orderId
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getRulesByOrder($orderId){
        if($orderId instanceof \Magento\Sales\Model\Order)
            $orderId = $orderId->getId();

        $conn = $this->getConnection();

        if($orderId){
            $select = $conn->select()->from(
                $this->getMainTable(),
                ['salesrule_id', 'description']
            )->where(
                'order_id = ?',
                (int)$orderId
            );

            return $conn->fetchAll($select);
        }

        return [];
    }
}
