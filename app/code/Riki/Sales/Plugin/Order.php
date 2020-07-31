<?php

namespace Riki\Sales\Plugin;

use Magento\Sales\Model\Order as MageOrder;
use Riki\NpAtobarai\Model\Payment\NpAtobarai;
use Riki\Sales\Model\ResourceModel\Order\OrderStatus;
use Riki\Sales\Model\ResourceModel\Order\OrderStatus as OrderStatusResourceModel;

class Order
{
    /**
     * @var \Riki\SubscriptionMachine\Model\MonthlyFee\DouMachineChecker
     */
    protected $duoMachineChecker;

    /**
     * Order constructor.
     *
     * @param \Riki\SubscriptionMachine\Model\MonthlyFee\DouMachineChecker $duoMachineChecker
     */
    public function __construct(
        \Riki\SubscriptionMachine\Model\MonthlyFee\DouMachineChecker $duoMachineChecker
    ) {
        $this->duoMachineChecker = $duoMachineChecker;
    }

    /**
     * @param \Magento\Sales\Model\Order $subject
     * @param $result
     * @return mixed
     */
    public function afterUnhold(
        $subject,
        $result
    ) {
        if(is_null($subject->getStatus()) || is_null($subject->getState())){

            $paymentMethod = $subject->getPayment()->getMethod();

            switch($paymentMethod){
                case \Riki\CvsPayment\Model\CvsPayment::PAYMENT_METHOD_CVS_CODE:
                    $status = OrderStatus::STATUS_ORDER_PENDING_CVS;
                    $state = \Magento\Sales\Model\Order::STATE_NEW;
                    break;
                case \Riki\Sales\Model\Order\PaymentMethod::PAYMENT_METHOD_PAYGENT:
                    if($subject->getPaymentStatus() == \Riki\Shipment\Model\ResourceModel\Status\Options\Payment::SHIPPING_PAYMENT_STATUS_AUTHORIZED){
                        $status = OrderStatus::STATUS_ORDER_NOT_SHIPPED;
                        $state = \Magento\Sales\Model\Order::STATE_PROCESSING;
                    }else{
                        $status = OrderStatus::STATUS_ORDER_PENDING_CC;
                        $state = \Magento\Sales\Model\Order::STATE_PENDING_PAYMENT;
                    }
                    break;
                case NpAtobarai::PAYMENT_METHOD_NP_ATOBARAI_CODE:
                    $status = OrderStatusResourceModel::STATUS_ORDER_PENDING_NP;
                    $state = MageOrder::STATE_NEW;
                    break;
                default:
                    $state = \Magento\Sales\Model\Order::STATE_PROCESSING;
                    $status = OrderStatus::STATUS_ORDER_NOT_SHIPPED;
            }

            $subject->setStatus($status);
            $subject->setState($state);
        }else{
            if(
                // free order with paygent
                $subject->getPayment()->getMethod() == \Riki\Sales\Model\Order\PaymentMethod::PAYMENT_METHOD_PAYGENT &&
                $subject->getStatus() == OrderStatus::STATUS_ORDER_PENDING_CC &&
                $subject->getPaymentStatus() == \Riki\Shipment\Model\ResourceModel\Status\Options\Payment::SHIPPING_PAYMENT_STATUS_AUTHORIZED
            ){
                $subject->setStatus(OrderStatus::STATUS_ORDER_NOT_SHIPPED);
                $subject->setState(\Magento\Sales\Model\Order::STATE_PROCESSING);
            }
        }

        // PENDING_FOR_MACHINE case
        // If this order has order item or oos item is duo machine, change status to PENDING_FOR_MACHINE
        $statusesToKeep = [
            OrderStatus::STATUS_ORDER_PENDING_CVS,
            OrderStatus::STATUS_ORDER_PENDING_CC
        ];
        if (!in_array($subject->getStatus(), $statusesToKeep) &&
            ($this->duoMachineChecker->isOrderHasFreeDuoMachine($subject) ||
            $this->duoMachineChecker->isOrderHasOosItemDuoMachine($subject))
        ) {
            $subject->setStatus(OrderStatus::STATUS_ORDER_PENDING_FOR_MACHINE);
            $subject->setState(\Magento\Sales\Model\Order::STATE_PROCESSING);
        }

        /*add status history and do not push notification*/
        $subject->setIsNotified(false);
        $subject->addStatusHistoryComment(
            __('Order has been unhold.'), false
        );

        return $result;
    }

    /**
     * @param \Magento\Sales\Model\Order $subject
     * @param \Closure $proceed
     * @return mixed
     */
    public function aroundCanCancel(
        $subject,
        \Closure $proceed
    ){
        if($subject->getIsCancelByEditAction()){
            return true;
        }

        return $proceed();
    }

    /**
     * @param \Magento\Sales\Model\Order $subject
     * @param \Closure $proceed
     * @return bool
     */
    public function aroundCanEdit(
        $subject,
        \Closure $proceed
    ){
        if($subject->hasShipments())
            return false;

        return $proceed();
    }
}
