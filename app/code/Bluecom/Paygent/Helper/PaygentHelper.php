<?php

namespace Bluecom\Paygent\Helper;

use Riki\Sales\Model\ResourceModel\Order\OrderStatus;

class PaygentHelper extends \Magento\Framework\App\Helper\AbstractHelper
{
    /**
     * @var \Riki\Fraud\Model\ScoreFactory
     */
    protected $scoreFactory;
    /**
     * @var \Riki\Loyalty\Helper\Data
     */
    protected $rewardPointHelper;
    /**
     * @var \Riki\Loyalty\Helper\Email
     */
    protected $rewardPointEmail;

    /**
     * @var \Riki\SubscriptionMachine\Model\MonthlyFee\DouMachineChecker
     */
    protected $duoMachineChecker;

    /**
     * PaygentHelper constructor.
     *
     * @param \Magento\Framework\App\Helper\Context $context
     * @param \Riki\Fraud\Model\ScoreFactory $scoreFactory
     * @param \Riki\Loyalty\Helper\Data $rewardPointHelper
     * @param \Riki\Loyalty\Helper\Email $rewardpointEmail
     * @param \Riki\SubscriptionMachine\Model\MonthlyFee\DouMachineChecker $duoMachineChecker
     */
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Riki\Fraud\Model\ScoreFactory $scoreFactory,
        \Riki\Loyalty\Helper\Data $rewardPointHelper,
        \Riki\Loyalty\Helper\Email $rewardpointEmail,
        \Riki\SubscriptionMachine\Model\MonthlyFee\DouMachineChecker $duoMachineChecker
    ) {
        parent::__construct($context);
        $this->scoreFactory = $scoreFactory;
        $this->rewardPointHelper = $rewardPointHelper;
        $this->rewardPointEmail = $rewardpointEmail;
        $this->duoMachineChecker = $duoMachineChecker;
    }

    /**
     * update order status after authorize success
     *      change status to SUSPICIOUS if this is fraud order
     *      change status to PENDING_CRD_REVIEW for
     *              1. free payment charge by admin
     *              2. free shipping by admin
     *              3. this order need to approved earn point
     *      change status to NOT_SHIPPED for remaining case
     *
     * @param \Magento\Sales\Model\\Order $order
     * @param $tradingId
     * @param $message
     */
    public function updateOrderAfterAuthorizeSuccess($order, $tradingId, $message)
    {
        $result = false;

        /*flag to check fraud order*/
        $isFraudOrder = $this->isFraudOrder($order);

        /*flag to check this order need to approved earn point*/
        $isWaitingPointApprove = $this->rewardPointHelper->waitingPointApprove($order);

        while (!$result) {
            /*
             * enable sending confirmation email for redirected url paygent only
             * $order->setCanSendNewEmailFlag(false);
             * app/code/Bluecom/Paygent/Model/Paygent.php line 374
             */
            $order->setSendEmail(true);
            /*save reference trading id*/
            $order->setRefTradingId($tradingId);
            /*change order payment status to authorized*/
            $order->setPaymentStatus(
                \Riki\ArReconciliation\Model\ResourceModel\Status\PaymentStatus::PAYMENT_AUTHORIZED
            );

            if (!$isWaitingPointApprove &&
                !$order->getIsFreePaymentChargeByAdmin() &&
                !$order->getIsFreeShippingByAdmin()
            ) {
                // PENDING_FOR_MACHINE case
                // If this order has order item or oos item is duo machine, change status to PENDING_FOR_MACHINE
                if (!$isFraudOrder && ($this->duoMachineChecker->isOrderHasFreeDuoMachine($order) ||
                    $this->duoMachineChecker->isOrderHasOosItemDuoMachine($order))
                ) {
                    $order->setState(\Magento\Sales\Model\Order::STATE_PROCESSING);
                    $order->setStatus(OrderStatus::STATUS_ORDER_PENDING_FOR_MACHINE);
                } elseif ($order->getStatus() != OrderStatus::STATUS_ORDER_IN_PROCESSING) {
                    /*REMAINING case*/
                    $order->setState(\Magento\Sales\Model\Order::STATE_PROCESSING);
                    $order->setStatus(OrderStatus::STATUS_ORDER_NOT_SHIPPED);
                }
            } else {
                /*PENDING_CRD_REVIEW case*/

                /*if this is fraud order, change status to suspicious. After remove suspicious (by manually from BO), this order will be change to pending crd review*/
                if ($isFraudOrder) {
                    /*change order status to suspicious*/
                    $order->setStatus(
                        \Riki\Sales\Model\ResourceModel\Order\OrderStatus::STATUS_ORDER_SUSPICIOUS
                    );
                    /*change order status to processing*/
                    $order->setState(
                        \Magento\Sales\Model\Order::STATE_PROCESSING
                    );
                } else {
                    /*change order status to pending crd review for not fraud order*/
                    $order->setStatus(
                        \Riki\Sales\Model\ResourceModel\Order\OrderStatus::STATUS_ORDER_PENDING_CRD_REVIEW
                    );
                    $order->setState(
                        \Magento\Sales\Model\Order::STATE_HOLDED
                    );
                }
            }

            /*addition process for order that need to approved for earn point*/
            if($isWaitingPointApprove){
                $order->setData('point_pending_status', $order->getStatus());
            }

            $order->setIsNotified(false);
            $order->addStatusHistoryComment($message, false);

            try {
                $order->save();
                $this->_eventManager->dispatch('paygent_update_order_after_authorize_success_after', ['order' => $order]);
                $result = true;
            } catch (\Exception $e) {
                if( // deadlock
                    preg_match('#SQLSTATE\[HY000\]: [^:]+: 1205[^\d]#', $e->getMessage()) ||
                    preg_match('#SQLSTATE\[40001\]: [^:]+: 1213[^\d]#', $e->getMessage())
                ){
                    $result = false;
                }else{
                    $this->_logger->critical($e);
                    $result = true;
                }
            }
        }

        /*addition process for order that need to approved for earn point*/
        if($isWaitingPointApprove){
            $this->rewardPointEmail->requestApproval($order);
        }

        if ($isFraudOrder) {
            /*addition process for fraud order*/
            $this->processForFraudOrder($order);
        }
    }

    /**
     * Check order is fraud order
     *
     * @param $order
     * @return bool
     */
    public function isFraudOrder($order)
    {
        if($order->getData('is_generate') ==1){
            return false;
        }
        /** @var \Riki\Fraud\Model\Score $score */
        $score = $this->scoreFactory->create();
        return $score->isFraudOrder($order);
    }

    /**
     * addition process for fraud order
     *      change status to suspicious
     *      send notification email
     *      add status history "Fraud system detects a fraud"
     * @param $order
     */
    public function processForFraudOrder($order)
    {
        /** @var \Riki\Fraud\Model\Score $score */
        $score = $this->scoreFactory->create();
        $score->checkFraudScore($order);
    }
}
