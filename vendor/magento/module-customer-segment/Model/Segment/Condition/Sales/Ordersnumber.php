<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\CustomerSegment\Model\Segment\Condition\Sales;

/**
 * Order numbers condition
 */
class Ordersnumber extends \Magento\CustomerSegment\Model\Segment\Condition\Sales\Combine
{
    /**
     * Name of condition for displaying as html
     *
     * @var string
     */
    protected $frontConditionName = 'Number of Orders';

    /**
     * Get condintion sql for number of orders
     *
     * @param string $operator
     * @param string $value
     * @return \Zend_Db_Expr
     */
    protected function getConditionSql($operator, $value)
    {
        $condition = $this->getResource()
            ->getConnection()
            ->getCheckSql("COUNT(*) {$operator} {$value}", 1, 0);
        return new \Zend_Db_Expr($condition);
    }
}
