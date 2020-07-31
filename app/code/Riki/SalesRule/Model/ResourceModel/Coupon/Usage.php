<?php

namespace Riki\SalesRule\Model\ResourceModel\Coupon;

class Usage extends \Magento\SalesRule\Model\ResourceModel\Coupon\Usage
{
    /**
     * Get customer coupon times used
     *
     * @param $customerId
     * @param $couponId
     * @return array
     */
    public function getCustomerCouponTimesUsed($customerId, $couponId)
    {
        /** @var \Magento\Framework\DB\Adapter\AdapterInterface $connection */
        $connection = $this->getConnection();
        $select = $connection->select();
        $select->from(
            $this->getMainTable()
        )->where(
            'coupon_id = ?', $couponId
        )->where(
            'customer_id = ?', $customerId
        )->limitPage(1, 1)->limit(1);

        return $connection->fetchRow($select, [':coupon_id' => $couponId, ':customer_id' => $customerId]);
    }

    /**
     * Update customer coupon times used
     *
     * @param $customerId
     * @param $couponId
     * @param $timesUsed
     * @return bool
     */
    public function updateCustomerCouponByTimesUsed($customerId, $couponId, $timesUsed)
    {
        if ($timesUsed == 0) {
            $result = $this->getConnection()->delete(
                $this->getMainTable(),
                ['coupon_id = ?' => $couponId, 'customer_id = ?' => $customerId]
            );
        } else {
            $result = $this->getConnection()->update(
                $this->getMainTable(),
                ['times_used' => $timesUsed],
                ['coupon_id = ?' => $couponId, 'customer_id = ?' => $customerId]
            );
        }

        if ($result) {
            return true;
        }
        return false;
    }
}
