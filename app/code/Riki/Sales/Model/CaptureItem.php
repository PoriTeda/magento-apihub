<?php
namespace Riki\Sales\Model;

use Riki\Sales\Api\CaptureItemInterface;

class CaptureItem implements CaptureItemInterface
{
    protected $orderId;

    protected $isAppliedDelayPaymentPoint;

    /**
     * @param int $orderId
     * @return $this
     */
    public function setOrderId($orderId)
    {
        $this->orderId = $orderId;
        return $this;
    }

    /**
     * @return int
     */
    public function getOrderId()
    {
        return $this->orderId;
    }

    /**
     * @param boolean $value
     * @return self
     */
    public function setIsAppliedDelayPaymentPoint($value)
    {
        $this->isAppliedDelayPaymentPoint = $value;
        return $this;
    }

    /**
     * @return boolean
     */
    public function getIsAppliedDelayPaymentPoint()
    {
        return $this->isAppliedDelayPaymentPoint;
    }
}
