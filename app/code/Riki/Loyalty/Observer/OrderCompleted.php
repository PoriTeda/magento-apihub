<?php

namespace Riki\Loyalty\Observer;

use Magento\Framework\Event\ObserverInterface;
use Riki\Shipment\Model\ResourceModel\Status\Options\Payment;
use Riki\Shipment\Model\ResourceModel\Status\Options\Shipment;

class OrderCompleted implements ObserverInterface
{
    const PAYMENT_STATUS = 1;
    const SHIPMENT_STATUS = 2;
    const SHIPMENT_PAYMENT_STATUS = 3;

    /**
     * @var \Riki\Loyalty\Model\Conversion
     */
    protected $conversionModel;

    /**
     * @var \Riki\Loyalty\Model\Reward
     */
    protected $rewardModel;

    /**
     * @var array
     */
    public static $paymentStatusIn = ['payment_collected'];

    /**
     * @var array
     */
    public static $shipmentStatusIn = [
        \Riki\Shipment\Model\ResourceModel\Status\Options\Shipment::SHIPMENT_STATUS_REJECTED,
        \Riki\Shipment\Model\ResourceModel\Status\Options\Shipment::SHIPMENT_STATUS_DELIVERY_COMPLETED,
        \Riki\Shipment\Model\ResourceModel\Status\Options\Shipment::SHIPMENT_STATUS_SHIPPED_OUT
    ];
    /**
     * @var \Magento\Sales\Model\ResourceModel\Order
     */
    private $orderResource;

    /**
     * Conversion constructor.
     *
     * @param \Riki\Loyalty\Model\Conversion $conversionModel
     * @param \Riki\Loyalty\Model\Reward $reward
     */
    public function __construct(
        \Riki\Loyalty\Model\Conversion $conversionModel,
        \Riki\Loyalty\Model\Reward $reward,
        \Magento\Sales\Model\ResourceModel\Order $orderResource
    ) {
        $this->conversionModel = $conversionModel;
        $this->rewardModel = $reward;
        $this->orderResource = $orderResource;
    }

    /**
     * Whether this status is converted to shopping point
     *
     * @param \Magento\Sales\Model\Order $order
     * @return bool
     */
    public function isStatusToConvert(\Magento\Sales\Model\Order $order)
    {
        $dependOn = false;
        $paymentStatusWithMethod = [
            \Bluecom\Paygent\Model\Paygent::CODE,
            \Riki\NpAtobarai\Model\Payment\NpAtobarai::PAYMENT_METHOD_NP_ATOBARAI_CODE
        ];
        $shipmentPaymentStatusWithMethod = [
            \Magento\OfflinePayments\Model\Cashondelivery::PAYMENT_METHOD_CASHONDELIVERY_CODE,
        ];
        $shipmentStatusWithMethod = [
            \Riki\CvsPayment\Model\CvsPayment::PAYMENT_METHOD_CVS_CODE,
            \Riki\PaymentBip\Model\InvoicedBasedPayment::PAYMENT_CODE,
            \Riki\Sales\Model\Order\PaymentMethod::PAYMENT_METHOD_FREE
        ];
        if (!$order->getPayment()) {
            return false; //this order is error
        }
        if (in_array($order->getPayment()->getMethod(), $paymentStatusWithMethod)) {
            $dependOn = self::PAYMENT_STATUS;
        } elseif (in_array($order->getPayment()->getMethod(), $shipmentPaymentStatusWithMethod)) {
            $dependOn = self::SHIPMENT_PAYMENT_STATUS;
        } elseif (in_array($order->getPayment()->getMethod(), $shipmentStatusWithMethod)) {
            $dependOn = self::SHIPMENT_STATUS;
        }
        switch ($dependOn) {
            case self::PAYMENT_STATUS:
                $status = $order->getData('payment_status');
                return in_array($status, self::$paymentStatusIn);
            case self::SHIPMENT_PAYMENT_STATUS:
                $shipments = $order->getShipmentsCollection();
                if (!$shipments || !$shipments->setPageSize(1)->getSize()) {
                    return false;
                }
                foreach ($shipments as $shipment) {
                    if ($shipment->getPaymentStatus() == null) {
                        return false;
                    }
                }
                return true;
            case self::SHIPMENT_STATUS:
                $shipments = $order->getShipmentsCollection();
                if (!$shipments || !$shipments->setPageSize(1)->getSize()) {
                    return false;
                }
                foreach ($shipments as $shipment) {
                    if (!in_array($shipment->getShipmentStatus(), self::$shipmentStatusIn)) {
                        return false;
                    }
                }
                return true;
            default:
                return false;
        }
    }

    /**
     * Update points balance after order becomes completed
     *
     * @param \Magento\Framework\Event\Observer $observer
     * @return $this
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        /**
         * @var  $order \Magento\Sales\Model\Order
         */
        $order = $observer->getEvent()->getOrder();
        if ($order instanceof \Riki\Subscription\Model\Emulator\Order) {
            return $this;
        }
        if ($order->getCustomerIsGuest()) {
            return $this;
        }
        if ($this->isStatusToConvert($order)) {
            if ($order->getPayment()->getMethod()
                == \Magento\OfflinePayments\Model\Cashondelivery::PAYMENT_METHOD_CASHONDELIVERY_CODE) {
                $this->rejectOrderItemShoppingPoint($order);
            }
            $tentativePoint = $this->rewardModel->getResource()->getTentative($order->getIncrementId());
            if ($tentativePoint) {
                $this->conversionModel->toShoppingPoint($order, \Riki\Loyalty\Model\Reward::STATUS_TENTATIVE);
            }
        }
        return $this;
    }

    /**
     * NED-1851 Check if payment method is COD, reject point conversion for items belong to rejected shipment
     * @param \Magento\Sales\Model\Order $order $order
     */
    protected function rejectOrderItemShoppingPoint($order)
    {
        /**
         * @var \Magento\Sales\Model\Order\Shipment $shipment
         * @var \Magento\Sales\Model\Order\Shipment\Item $shipmentItem
         */
        $orderItemIdsToReject = [];
        $shipmentCollection = $order->getShipmentsCollection();
        foreach ($shipmentCollection as $shipment) {
            if ($shipment->getShipmentStatus() == Shipment::SHIPMENT_STATUS_REJECTED
                && $shipment->getPaymentStatus() == Payment::SHIPPING_PAYMENT_STATUS_NOT_APPLICABLE) {
                foreach ($shipment->getAllItems() as $shipmentItem) {
                    $orderItemIdsToReject [] = $shipmentItem->getOrderItemId();
                }
            }
        }
        if (!empty($orderItemIdsToReject)) {
            $rejectedPoint = 0;
            $rewardCollection = $this->rewardModel->getCollection()
                ->addFieldToFilter('order_item_id', ['in' => $orderItemIdsToReject]);
            foreach ($rewardCollection as $rewardRow) {
                if ($rewardRow->getStatus() == \Riki\Loyalty\Model\Reward::STATUS_TENTATIVE) {
                    $rejectedPoint += $rewardRow->getPoint() * $rewardRow->getQty();
                    $rewardRow->setStatus(\Riki\Loyalty\Model\Reward::STATUS_CANCEL);
                    $rewardRow->save();
                }
            }
            // recalculate order bonus_point_amount
            $bonusPointAmount = max(0,$order->getBonusPointAmount() - $rejectedPoint);
            $order->setBonusPointAmount($bonusPointAmount);
            $this->orderResource->saveAttribute($order, ['bonus_point_amount']);

        }
    }
}
