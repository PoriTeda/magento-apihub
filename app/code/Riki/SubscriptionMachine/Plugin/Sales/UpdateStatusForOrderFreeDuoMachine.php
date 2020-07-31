<?php

namespace Riki\SubscriptionMachine\Plugin\Sales;

use \Riki\Sales\Model\ResourceModel\Order\OrderStatus;
use \Riki\SubscriptionMachine\Model\MachineConditionRule;
use \Riki\Customer\Model\StatusMachine;

class UpdateStatusForOrderFreeDuoMachine
{
    /**
     * @var \Magento\Framework\Registry
     */
    protected $registry;

    /**
     * @var \Riki\Subscription\Helper\Order\Data
     */
    protected $helperOrderData;

    /**
     * @var \Riki\SubscriptionMachine\Model\MonthlyFee\DouMachineChecker
     */
    protected $duoMachineChecker;

    /**
     * CheckoutSubmitAllAfter constructor.
     *
     * @param \Magento\Framework\Registry $registry
     * @param \Riki\Subscription\Helper\Order\Data $helperOrderData
     * @param \Riki\SubscriptionMachine\Model\MonthlyFee\DouMachineChecker $duoMachineChecker
     */
    public function __construct(
        \Magento\Framework\Registry $registry,
        \Riki\Subscription\Helper\Order\Data $helperOrderData,
        \Riki\SubscriptionMachine\Model\MonthlyFee\DouMachineChecker $duoMachineChecker
    ) {
        $this->registry = $registry;
        $this->helperOrderData = $helperOrderData;
        $this->duoMachineChecker = $duoMachineChecker;
    }

    /**
     * Update status for order free duo machine
     *
     * @param \Riki\Sales\Model\OrderCutoffDate $subject
     * @param \Closure $proceed
     * @param \Magento\Sales\Model\Order $order
     * @param bool $saveLog
     * @return void
     */
    public function aroundUpdateOrderStatus(
        \Riki\Sales\Model\OrderCutoffDate $subject,
        \Closure $proceed,
        \Magento\Sales\Model\Order $order,
        $saveLog = true
    ) {
        $result = $proceed($order, $saveLog);

        $statusesToKeep = [OrderStatus::STATUS_ORDER_SUSPICIOUS, OrderStatus::STATUS_ORDER_PENDING_CRD_REVIEW];

        if (!in_array($order->getStatus(), $statusesToKeep)) {
            // For case update order status of oos order
            if ($order->getData('is_oos_order')) {
                $this->updateOrderStatusOfOosOrder($order);
            } else {
                $this->updateOrderStatusOfMainOrder($order);
            }
        }
    }

    /**
     * Update order status of oos order
     *
     * @param \Magento\Sales\Model\Order $order
     */
    private function updateOrderStatusOfOosOrder($order)
    {
        $originalOrder = $newStatus = $newState = null;
        $statusesToValidateOrderHasFreeDuoMachine = [
            OrderStatus::STATUS_ORDER_SUSPICIOUS,
            OrderStatus::STATUS_ORDER_PENDING_CRD_REVIEW
        ];
        $oosItems = $this->registry->registry('current_oos_generating');

        if ($oosItems) {
            foreach ($oosItems as $oos) {
                if ($oos instanceof \Riki\AdvancedInventory\Model\OutOfStock) {
                    $originalOrder = $oos->getOriginalOrder();
                    if (!$originalOrder instanceof \Magento\Sales\Model\Order) {
                        break;
                    }
                }
            }
        }

        if (!$originalOrder instanceof \Magento\Sales\Model\Order) {
            return;
        }

        // Check status of original order
        // If original order status is "PENDING_FOR_MACHINE", the OOS order also create with "PENDING_FOR_MACHINE".
        if ($originalOrder->getData('status') == OrderStatus::STATUS_ORDER_PENDING_FOR_MACHINE) {
            $newState = \Magento\Sales\Model\Order::STATE_PROCESSING;
            $newStatus = OrderStatus::STATUS_ORDER_PENDING_FOR_MACHINE;
        } elseif (in_array(
            $originalOrder->getData('status'),
            $statusesToValidateOrderHasFreeDuoMachine
        )) {
            $result = $this->duoMachineChecker->isOrderHasFreeDuoMachine($order);
            if ($result) {
                $newState = \Magento\Sales\Model\Order::STATE_PROCESSING;
                $newStatus = OrderStatus::STATUS_ORDER_PENDING_FOR_MACHINE;
            }
        }

        if ($newStatus !== null) {
            $order->setState($newState);
            $order->setStatus($newStatus);
            $order->addStatusToHistory(
                OrderStatus::STATUS_ORDER_PENDING_FOR_MACHINE,
                __('Update oos order status to pending for machine')
            );
        }
    }

    /**
     * Update order status of main order
     *
     * @param \Magento\Sales\Model\Order $order
     */
    private function updateOrderStatusOfMainOrder($order)
    {
        $machineData = $this->registry->registry('free_machine_added_to_cart');

        if (empty($machineData)) {
            return;
        }

        // Get current payment method
        $payment = $order->getPayment();

        // In the case redirect to paygent ,
        // status of order will check and update after received response from Paygent
        if (!$payment->getPaygentUrl() && !$order->getUseIvr()) {
            foreach ($machineData as $machineTypeCode => $machine) {
                // If this is duo machine
                // and KSS[profilesub:2570] = "3：Need to be attached with 'PENDING_FOR_MACHINE'"
                // then the order will create with status "PENDING_FOR_MACHINE".
                if ($machineTypeCode == MachineConditionRule::MACHINE_CODE_DUO &&
                    isset($machine['status']) && $machine['status'] == StatusMachine::MACHINE_STATUS_VALUE_IN_RENTAL
                ) {
                    if ($this->duoMachineChecker->isOrderHasFreeDuoMachine($order) ||
                        $this->duoMachineChecker->isOrderHasOosItemDuoMachine($order)
                    ) {
                        $order->setState(\Magento\Sales\Model\Order::STATE_PROCESSING);
                        $order->setStatus(OrderStatus::STATUS_ORDER_PENDING_FOR_MACHINE);
                        $order->addStatusToHistory(
                            OrderStatus::STATUS_ORDER_PENDING_FOR_MACHINE,
                            __('Update order status to pending for machine')
                        );
                        break;
                    }
                }
            }
        }
    }
}
