<?php

namespace Riki\Sales\Model\Order;

class OrderAdditionalInformation extends \Magento\Framework\Model\AbstractModel
{
    const ORDER_ID = 'order_id';
    const MONTHLY_FEE_LABEL = 'monthly_fee_label';
    const SHIPPING_REASON = 'shipping_reason';
    const SHIPPING_CAUSE = 'shipping_cause';

    /**
     * @return void
     */
    protected function _construct()
    {
        $this->_init(\Riki\Sales\Model\ResourceModel\Order\OrderAdditionalInformation::class);
    }

    public function getOrderId()
    {
        return $this->_getData(self::ORDER_ID);
    }

    public function getMonthlyFeeLabel()
    {
        return $this->_getData(self::MONTHLY_FEE_LABEL);
    }

    public function getShippingReason()
    {
        return $this->_getData(self::SHIPPING_REASON);
    }

    public function getShippingCause()
    {
        return $this->_getData(self::SHIPPING_CAUSE);
    }

    public function setOrderId($orderId)
    {
        return $this->setData(self::ORDER_ID, $orderId);
    }

    public function setMonthlyFeeLabel($monthlyFeeLabel)
    {
        return $this->setData(self::MONTHLY_FEE_LABEL, $monthlyFeeLabel);
    }

    public function setShippingReason($shippingReason)
    {
        return $this->setData(self::SHIPPING_REASON, $shippingReason);
    }

    public function setShippingCause($shippingCause)
    {
        return $this->setData(self::SHIPPING_CAUSE, $shippingCause);
    }
}