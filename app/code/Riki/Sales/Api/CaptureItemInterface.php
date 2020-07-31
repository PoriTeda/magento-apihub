<?php
namespace Riki\Sales\Api;

interface CaptureItemInterface
{
    /**
     * @param int $orderId
     * @return $this
     */
    public function setOrderId($orderId);

    /**
     * @return int
     */
    public function getOrderId();

    /**
     * @param boolean $value
     * @return self
     */
    public function setIsAppliedDelayPaymentPoint($value);

    /**
     * @return boolean
     */
    public function getIsAppliedDelayPaymentPoint();
}
