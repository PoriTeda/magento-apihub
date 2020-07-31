<?php
/**
 * PHP version 7
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 *
 * @category    Riki_Sales
 * @package     Riki\Sales\Model\Order
 * @author      Nestle <support@nestle.co.jp>
 * @license     http://nestle.co.jp/policy.html GNU General Public License
 * @link        http://shop.nestle.jp
 */
namespace Riki\Sales\Model\Order;

use Riki\Sales\Model\ResourceModel\Order\OrderStatus;
use Magento\Sales\Model\Order\Payment\Transaction;

/**
 * Class Payment
 *
 * @category    Riki_Sales
 * @package     Riki\Sales\Model\Order
 * @author      Nestle <support@nestle.co.jp>
 * @license     http://nestle.co.jp/policy.html GNU General Public License
 * @link        http://shop.nestle.jp
 */
class Payment extends \Magento\Sales\Model\Order\Payment
{
    /**
     * @param $transaction
     * @param $message
     */
    public function addTransactionCommentsToOrder($transaction, $message)
    {
        $order = $this->getOrder();
        $message = $this->_appendTransactionToMessage($transaction, $message);
        $paymentMethod = $order->getPayment()->getMethod();

        if (!empty($transaction) && $transaction->getTxnType() == Transaction::TYPE_CAPTURE) {
            switch ($paymentMethod) {
                case 'paygent':
                    $status = OrderStatus::STATUS_ORDER_SHIPPED_ALL;
                    break;
                default:
                    $status = OrderStatus::STATUS_ORDER_COMPLETE;
                    break;
            }
            $order->addStatusHistoryComment($message, $status);
        } else {
            $order->addStatusHistoryComment($message);
        }
    }

    /**
     * overwrite to prevent order status change to NOT_SHIPPED
     *
     * @param \Magento\Sales\Model\Order\Creditmemo $creditMemo
     * @return $this
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function refund($creditMemo)
    {
        $baseAmountToRefund = $this->formatAmount($creditMemo->getBaseGrandTotal());
        $this->setTransactionId(
            $this->transactionManager->generateTransactionId($this, Transaction::TYPE_REFUND)
        );

        // call refund from gateway if required
        $isOnline = false;
        $gateway = $this->getMethodInstance();
        $invoice = null;
        if ($gateway->canRefund() && $creditMemo->getDoTransaction()) {
            $this->setCreditmemo($creditMemo);
            $invoice = $creditMemo->getInvoice();
            if ($invoice) {
                $isOnline = true;
                $captureTxn = $this->transactionRepository->getByTransactionId(
                    $invoice->getTransactionId(),
                    $this->getId(),
                    $this->getOrder()->getId()
                );
                if ($captureTxn) {
                    $this->setTransactionIdsForRefund($captureTxn);
                }
                $this->setShouldCloseParentTransaction(true);
                // TODO: implement multiple refunds per capture
                try {
                    $gateway->setStore(
                        $this->getOrder()->getStoreId()
                    );
                    $this->setRefundTransactionId($invoice->getTransactionId());
                    $gateway->refund($this, $baseAmountToRefund);

                    $creditMemo->setTransactionId($this->getLastTransId());
                } catch (\Magento\Framework\Exception\LocalizedException $e) {
                    if (!$captureTxn) {
                        throw new \Magento\Framework\Exception\LocalizedException(
                            __('If the invoice was created offline, try creating an offline credit memo.'),
                            $e
                        );
                    }
                    throw $e;
                }
            }
        }

        // update self totals from creditmemo
        $this->_updateTotals(
            [
                'amount_refunded' => $creditMemo->getGrandTotal(),
                'base_amount_refunded' => $baseAmountToRefund,
                'base_amount_refunded_online' => $isOnline ? $baseAmountToRefund : null,
                'shipping_refunded' => $creditMemo->getShippingAmount(),
                'base_shipping_refunded' => $creditMemo->getBaseShippingAmount(),
            ]
        );

        // update transactions and order state
        $transaction = $this->addTransaction(
            Transaction::TYPE_REFUND,
            $creditMemo,
            $isOnline
        );
        if ($invoice) {
            $message = __('We refunded %1 online.', $this->formatPrice($baseAmountToRefund));
        } else {
            $message = $this->hasMessage() ? $this->getMessage() : __(
                'We refunded %1 offline.',
                $this->formatPrice($baseAmountToRefund)
            );
        }
        $message = $message = $this->prependMessage($message);
        $message = $this->_appendTransactionToMessage($transaction, $message);
        $this->getOrder()->addStatusHistoryComment($message);
        $this->_eventManager->dispatch(
            'sales_order_payment_refund',
            ['payment' => $this, 'creditmemo' => $creditMemo]
        );
        return $this;
    }

    /**
     * @param string $message
     * @param int|null $transactionId
     */
    public function setOrderStatePaymentReview($message, $transactionId)
    {
        if ($this->getMethod() == \Bluecom\Paygent\Model\Paygent::CODE
            && $this->getOrder()->getState() != \Magento\Sales\Model\Order::STATE_PAYMENT_REVIEW
        ) {
            $this->getOrder()->addStatusHistoryComment($message);
            if ($this->getIsFraudDetected()) {
                $this->getOrder()->setStatus(\Magento\Sales\Model\Order::STATUS_FRAUD);
            }

            if ($transactionId) {
                $this->setLastTransId($transactionId);
            }
        } else {
            parent::setOrderStatePaymentReview($message, $transactionId);
        }
    }
    /**
     * Sets order state to 'processing' with appropriate message
     *
     * @param \Magento\Framework\Phrase|string $message
     */
    protected function setOrderStateProcessing($message)
    {
        $this->getOrder()->addStatusHistoryComment($message);
    }
}
