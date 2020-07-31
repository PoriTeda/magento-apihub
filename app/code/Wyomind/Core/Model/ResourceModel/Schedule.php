<?php

/**
 * Copyright © 2016 Wyomind. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Wyomind\Core\Model\ResourceModel;

/**
 *
 */
class Schedule extends \Magento\Cron\Model\ResourceModel\Schedule
{
    
    public function getLastHeartbeat()
    {
        $connection = $this->getConnection();
        $result = $connection->select()
                ->from($this->getMainTable(), ['executed_at'])
                ->where("status = ?", \Magento\Cron\Model\Schedule::STATUS_SUCCESS)
                ->order('executed_at DESC')
                ->limit(1);
        $executedAt = $connection->fetchOne($result);
        if ($executedAt !== false) {
            return $executedAt;
        } else {
            return null;
        }
    }
}
