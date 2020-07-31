<?php

namespace Riki\Loyalty\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class Reward extends AbstractDb
{
    /**
     * Initialize connection and define main table
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('riki_reward_point', 'reward_id');
    }

    /**
     * @param string $orderNumber
     * @return int
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getTentative($orderNumber)
    {
        return $this->pointOrderByStatus($orderNumber, \Riki\Loyalty\Model\Reward::STATUS_TENTATIVE);
    }

    /**
     * @param $orderNumber
     * @param $status
     * @param null $pointType
     * @return int
     */
    public function pointOrderByStatus($orderNumber, $status, $pointType = null)
    {
        $connection = $this->getConnection();
        $sqlSelect = $connection->select()->from($this->getMainTable(), array('total' => new \Zend_Db_Expr('SUM(point * qty)')));
        $sqlSelect->where('order_no = ?', $orderNumber);
        $sqlSelect->where('status = ?', $status);
        if($pointType){
            $sqlSelect->where('point_type = ?', $pointType);
        }
        return (int) $connection->fetchOne($sqlSelect);
    }
    
    /**
     * Get shopping point to send consumerDB
     *
     * @param string $orderNumber
     * @param int $status
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getPointToConvert($orderNumber, $status)
    {
        $connection = $this->getConnection();
        $sqlSelect = $connection->select()->from(
            $this->getMainTable(), array(
                'description', 'expiry_period','point_type','wbs_code','account_code',
                'ids' => new \Zend_Db_Expr('reward_id'),
                'total_point' => new \Zend_Db_Expr('point * qty'),
            )
        );
        $sqlSelect->where('order_no = ?', $orderNumber);
        $sqlSelect->where('status = ?', $status);
        return $connection->fetchAll($sqlSelect);
    }

    /**
     * Update point status after convert
     *
     * @param array $ids
     * @param int $status
     * @return int
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function updateStatusFromIds($ids, $status)
    {
        $connection = $this->getConnection();
        return $connection->update(
            $this->getMainTable(),
            array('status' => $status),
            array('reward_id IN(?)' => $ids)
        );
    }

    /**
     * Get order was error when sending shopping point to consumerDB
     *
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getOrderInError()
    {
        $connection = $this->getConnection();

        $sqlSelect = $connection->select()->from(
            $this->getMainTable(), array('order_no')
        );

        $sqlSelect->where('status = ?', \Riki\Loyalty\Model\Reward::STATUS_ERROR);
        $sqlSelect->where($connection->prepareSqlCondition('order_no', ['notnull' => true]));

        $sqlSelect->group('order_no');

        return $connection->fetchCol($sqlSelect);
    }

    /**
     * Cancel the point with status pending approval
     *
     * @param $orderNo
     * @return int
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function cancelRewardPoint($orderNo)
    {
        $connection = $this->getConnection();
        $where = [
            'order_no = ?' => $orderNo,
            'status = ?' => \Riki\Loyalty\Model\Reward::STATUS_PENDING_APPROVAL
        ];
        return $connection->delete($this->getMainTable(), $where);
    }

    /**
     * Approve the pending point
     *
     * @param string $orderNo
     * @return int
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function approveRewardPoint($orderNo)
    {
        $connection = $this->getConnection();
        $where = [
            'order_no = ?' => $orderNo,
            'status = ?' => \Riki\Loyalty\Model\Reward::STATUS_PENDING_APPROVAL
        ];
        $data = ['status' => \Riki\Loyalty\Model\Reward::STATUS_TENTATIVE];
        return $connection->update($this->getMainTable(), $data, $where);
    }

    /**
     * Get tentative point of customer
     *
     * @param string $customerCode
     * @return int
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function customerTentativePoint($customerCode)
    {
        $connection = $this->getConnection();
        $sqlSelect = $connection->select()->from($this->getMainTable(), array('total' => new \Zend_Db_Expr('SUM(point * qty)')));
        $sqlSelect->where('customer_code = ?', $customerCode);
        $sqlSelect->where('status = ?', \Riki\Loyalty\Model\Reward::STATUS_TENTATIVE);
        return (int) $connection->fetchOne($sqlSelect);
    }

    /**
     * Get tentative point history
     *
     * @param string $customerCode
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function tentativePointHistory($customerCode)
    {
        $fields = [
            'order_no', 'description', 'action_date', 'point_type',
            'issued_point' => new \Zend_Db_Expr('SUM(point * qty)')
        ];
        $connection = $this->getConnection();
        $sqlSelect = $connection->select()->from(['main_table' => $this->getMainTable()], $fields);
        $sqlSelect->joinLeft(
            ['sales_order' => $this->getTable('sales_order')],
            'main_table.order_no  = sales_order.increment_id',
            ['order_id' => 'sales_order.entity_id']
        );
        $sqlSelect->where('customer_code = ?', $customerCode);
        $sqlSelect->where('main_table.status = ?', \Riki\Loyalty\Model\Reward::STATUS_TENTATIVE);
        $sqlSelect->where('order_no IS NOT NULL');
        $sqlSelect->group(['order_no', 'description', 'action_date','point_type']);
        return $connection->fetchAll($sqlSelect);
    }

    /**
     * @param $orderNo
     *
     * @return $this
     */
    public function revertRedeem($orderNo)
    {
        $connection = $this->getConnection();
        $connection->update($this->getMainTable(), ['status' => \Riki\Loyalty\Model\Reward::STATUS_CANCEL],
            ['order_no = ?' => $orderNo,
                'status = ?' => \Riki\Loyalty\Model\Reward::STATUS_REDEEMED]);
        $connection->delete($this->getMainTable(),
            ['order_no = ?' => $orderNo,
                'status = ?' => \Riki\Loyalty\Model\Reward::STATUS_TENTATIVE]);

        return $this;
    }

    /**
     * @param array $orderItemIds
     * @return array
     */
    public function getOrderItemsPointEarned(array $orderItemIds)
    {
        if (count($orderItemIds)) {
            $connection = $this->getConnection();

            $selectedStatusString = implode(',',
                [
                    \Riki\Loyalty\Model\Reward::STATUS_SHOPPING_POINT,
                    \Riki\Loyalty\Model\Reward::STATUS_TENTATIVE
                ]
            );

            $pendingApproveStatus = \Riki\Loyalty\Model\Reward::STATUS_PENDING_APPROVAL;

            $purchasedPointType = \Riki\Loyalty\Model\Reward::TYPE_PURCHASE;

            $orWhere = '(' . implode(
                    ') OR (',
                    [
                        "riki_reward_point.status IN ({$selectedStatusString})",
                        "riki_reward_point.status={$pendingApproveStatus} AND riki_reward_point.point_type={$purchasedPointType}"
                    ]
                ) . ')';

            $select = $connection->select()->from(
                'riki_reward_point',
                ['order_item_id', 'point_type', 'point', 'sales_rule_id', 'qty']
            )->where(
                'riki_reward_point.order_item_id IN(?)',
                $orderItemIds
            )->where(
                'riki_reward_point.level=?',
                \Riki\Loyalty\Model\Reward::LEVEL_ITEM
            )->where($orWhere);

            return $connection->fetchAll($select);
        }

        return [];
    }
}
