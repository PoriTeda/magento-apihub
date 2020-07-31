<?php

namespace Riki\Sales\Model\Order\Payment\State;

use Magento\Sales\Model\Order;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\Data\OrderPaymentInterface;

class AuthorizeCommand extends \Magento\Sales\Model\Order\Payment\State\AuthorizeCommand
{

    /**
     * @param OrderPaymentInterface $payment
     * @param string|float $amount
     * @param OrderInterface $order
     * @return \Magento\Framework\Phrase
     */
    public function execute(OrderPaymentInterface $payment, $amount, OrderInterface $order)
    {
        $state = Order::STATE_PROCESSING;
        $status = false;
        $formattedAmount = $order->getBaseCurrency()->formatTxt($amount);
        if ($payment->getIsTransactionPending()) {
            $state = Order::STATE_PAYMENT_REVIEW;
            $message = __(
                'We will authorize %1 after the payment is approved at the payment gateway.',
                $formattedAmount
            );
        } else {
            if ($payment->getIsFraudDetected()) {
                $state = Order::STATE_PROCESSING;
                $message = __(
                    'Order is suspended as its authorizing amount %1 is suspected to be fraudulent.',
                    $formattedAmount
                );
            } else {
                $message = __('Authorized amount of %1', $formattedAmount);
            }
        }
        if ($payment->getIsFraudDetected()) {
            $status = Order::STATUS_FRAUD;
        }
        $this->setOrderStateAndStatus($order, $status, $state);

        return $message;
    }

    /**
     * @param Order $order
     * @param string $status
     * @param string $state
     * @return void
     */
    protected function setOrderStateAndStatus(Order $order, $status, $state)
    {
        if ($status || $state != Order::STATE_PROCESSING) {
            parent::setOrderStateAndStatus($order, $status, $state);
        }
    }
}
