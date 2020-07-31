<?php
/**
 * @author Riki Team
 * @copyright Copyright (c) 2016 Riki (https://www.Riki.com)
 * @package Riki_Preorder
 */

/**
 * Copyright © 2016 Riki. All rights reserved.
 */

namespace Riki\Preorder\Model\ResourceModel;


class OrderPreorder extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    protected function _construct()
    {
        $this->_init('riki_preorder_order_preorder', 'id');
    }

    /**
     * @param $orderId
     * @return mixed
     */
    public function getWarningByOrderId($orderId)
    {
        $connection = $this->getConnection();
        $table = $this->getMainTable();

        $select = $connection->select()->from($table)->where('order_id = ?' ,  $orderId);

        $result = $connection->fetchRow($select);
        return $result['warning'];
    }

    /**
     * @param $orderId
     * @return bool
     */
    public function getIsOrderProcessed($orderId)
    {
        $connection = $this->getConnection();
        $table = $this->getMainTable();

        $select = $connection->select()->from($table)->columns('id')->where('order_id = ?', $orderId);
        $record = $connection->fetchRow($select);
        return !!$record;
    }

    /**
     * @param $orderId
     * @return bool
     */
    public function getOrderIsPreorderFlag($orderId)
    {
        $connection = $this->getConnection();
        $table = $this->getMainTable();

        $select = $connection->select()->from($table, 'is_preorder')->where('order_id = ?', $orderId);
        $isPreorder = $connection->fetchOne($select);

        if( !empty($isPreorder) && $isPreorder == \Riki\Preorder\Model\Order\OrderType::PREORDER){
            return true;
        } else {
            return false;
        }
    }
}
