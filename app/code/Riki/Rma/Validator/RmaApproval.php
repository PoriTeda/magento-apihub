<?php

namespace Riki\Rma\Validator;

use Riki\Shipment\Model\ResourceModel\Status\Options\Shipment as ShipmentStatus;
use Magento\OfflinePayments\Model\Cashondelivery;

class RmaApproval extends \Magento\Framework\Validator\AbstractValidator
{
    /**
     * @var \Riki\Rma\Helper\Amount
     */
    protected $rmaAmountHelper;

    /**
     * @var \Riki\Rma\Helper\Data
     */
    protected $rmaHelper;

    /**
     * @var \Riki\Loyalty\Model\RewardManagement
     */
    protected $rewardManagement;

    /**
     * @var float
     */
    protected $earnedPoint = null;

    /**
     * @var \Riki\Rma\Model\Rma
     */
    protected $rma = null;

    /**
     * CustomerPointBalance constructor.
     *
     * @param \Riki\Rma\Helper\Amount $rmaAmountHelper
     */
    public function __construct(
        \Riki\Rma\Helper\Amount $rmaAmountHelper,
        \Riki\Rma\Helper\Data $rmaHelper,
        \Riki\Loyalty\Model\RewardManagement $rewardManagement
    ) {
    
        $this->rmaAmountHelper = $rmaAmountHelper;
        $this->rmaHelper = $rmaHelper;
        $this->rewardManagement = $rewardManagement;
    }

    /**
     * Validate before approving RMA.
     * No need to check without good RMA because it is checked in previous step already.
     * TODO: review performance since we utilize get customer point balance which call to consumer API
     *
     * @param  mixed $value
     *
     * @return boolean
     */
    public function isValid($value)
    {
        $rma = $value;

        $this->rma = $rma;
        $this->earnedPoint = $this->rmaAmountHelper->getEarnedPoint($this->rma);

        $this->_validateCancelPoint();
        $this->_validateCustomerPointBalance();

        return !$this->hasMessages();
    }

    /**
     * Sum of return point shouldn't exceed order's used point.
     */
    public function _validateReturnPoint()
    {
        $capturedPoint = $this->rmaAmountHelper->getCapturedPoint($this->rma);
        $order = $this->rmaHelper->getRmaOrder($this->rma);
        $returnablePoint = max(0, $order->getUsedPoint() - $capturedPoint);

        if ($this->rma->getData('total_return_point') > $returnablePoint) {
            $this->_addMessages(['return point is exceeded ' => __('Return point is exceeded order\'s used point.')]);
        }

        return !$this->hasMessages();
    }

    /**
     * Apply for case user change reason. In this case, cancel point may be changed.
     */
    protected function _validateCancelPoint()
    {
        $savedEarnedPoint = $this->rma->getData('earned_point');

        if ($this->earnedPoint > 0
            && $savedEarnedPoint !== null
            && $savedEarnedPoint != $this->earnedPoint
        ) {
            $this->_addMessages(['cancel point changed' => __('Cancel point has been changed.')]);
        }
    }

    /**
     * Apply for case customer point balance has been changed.
     */
    protected function _validateCustomerPointBalance()
    {
        $savedCustomerPointBalance = $this->rma->getData('customer_point_balance');

        /**
         * Normal case. We refer return point to take money
         * (it means remain of not retractable points are not considered in validation).
         */
        if ($savedCustomerPointBalance !== null) {
            // TODO: Point balance is cached, does it work in mass action when multiple RMAs are processed in batch?
            $customerPointBalance = $this->rmaAmountHelper->getPointsBalance($this->rma, false);

            $totalCancelPoint = $this->rma->getData('total_cancel_point');

            if ($savedCustomerPointBalance >= $this->earnedPoint) {
                if ($customerPointBalance < $savedCustomerPointBalance && $customerPointBalance < $totalCancelPoint) {
                    $this->_addMessages(['customer point balance decreased' => __('Customer point balance is not enough to cancel.')]);
                }
            } elseif ($customerPointBalance != $savedCustomerPointBalance) {
                $this->_addMessages(['customer point balance changed' => __('Customer point balance has been changed.')]);
            }
        } /**
         * Worst case. This case is applied for old RMAs which customer point balance is null.
         */
        else {
            $realRemainOfNotRetractablePoint = $this->_getRealTimeRemainOfNotRetractablePoint();
            $pointToReturnBeforePointAdjustment = $this->_getPointToReturnBeforePointAdjustment();
            $remainOfNotRetractablePoint = max(
                0,
                $pointToReturnBeforePointAdjustment - $this->rma->getData('total_return_point')
            );

            if ($remainOfNotRetractablePoint == 0 && $realRemainOfNotRetractablePoint == 0) {
                return;
            }

            if ($remainOfNotRetractablePoint != $realRemainOfNotRetractablePoint) {
                $this->_addMessages(['customer point balance changed' => __('Customer point balance has been changed.')]);
            }
        }
    }

    /**
     * @return float
     */
    protected function _getPointToReturnBeforePointAdjustment()
    {
        if ($this->rmaAmountHelper->isFreeReturn($this->rma)) {
            return 0;
        }

        $shipment = $this->rmaAmountHelper->getShipment($this->rma);
        $order = $this->rma->getOrder();
        $paymentMethod = $order->getPayment()->getMethodInstance()->getCode();
        $usedPoints = $order->getUsedPointAmount();

        if ($shipment->getShipmentStatus() == ShipmentStatus::SHIPMENT_STATUS_REJECTED
            && $paymentMethod == Cashondelivery::PAYMENT_METHOD_CASHONDELIVERY_CODE
        ) {
            return $shipment->getShoppingPointAmount();
        }

        $result = floatval(min($this->_getRmaTotal(), $usedPoints));

        return $result;
    }

    /**
     * Get RMA total.
     *
     * @return float
     */
    protected function _getRmaTotal()
    {
        return $this->rmaAmountHelper->getReturnedGoodsAmount($this->rma)
            + $this->rma->getData('return_shipping_fee_adjusted')
            + $this->rma->getData('return_payment_fee_adjusted');
    }

    /**
     * @return float
     */
    protected function _getReturnablePointAmount()
    {
        $methodCode = $this->rmaHelper->getRmaOrderPaymentMethodCode($this->rma);
        $shipmentStatus = $this->rmaHelper->getRmaShipmentStatus($this->rma);

        if ($methodCode == \Magento\OfflinePayments\Model\Cashondelivery::PAYMENT_METHOD_CASHONDELIVERY_CODE
            && $shipmentStatus == \Riki\Shipment\Model\ResourceModel\Status\Options\Shipment::SHIPMENT_STATUS_REJECTED
        ) {
            $shipment = $this->rmaHelper->getRmaShipment($this->rma);
            return intval($shipment->getShoppingPointAmount());
        } else {
            $order = $this->rmaHelper->getRmaOrder($this->rma);
            $returnedPointAmount = $this->rewardManagement->convertPointToAmount(
                $this->rmaAmountHelper->getReturnedPoint($this->rma)
            );
            return min(($order->getUsedPointAmount() - $returnedPointAmount), $this->_getRmaTotal());
        }
    }

    /**
     * Get remain of not retractable point calculated with real-time customer point balance.
     *
     * @return float
     */
    protected function _getRealTimeRemainOfNotRetractablePoint()
    {
        $notRetractablePoint = $this->rmaAmountHelper->getNotRetractablePoints($this->rma, false);
        $remainRmaTotal = $this->_getRmaTotal() - $this->_getReturnablePointAmount();
        $remainNotRetractablePoint = $notRetractablePoint - $remainRmaTotal;

        return max(
            0,
            $remainNotRetractablePoint
        );
    }
}
