<?php
namespace Bluecom\Paygent\Model;

use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Model\Order;

class PaygentManagement implements \Bluecom\Paygent\Api\PaygentManagementInterface
{
    /**
     * @var \Bluecom\Paygent\Model\Email\ReauthorizeFailure
     */
    protected $reauthorizeFailureEmail;

    /**
     * @var \Bluecom\Paygent\Model\Email\ReauthorizeFailureSubscription
     */
    protected $reauthorizeFailureSubscriptionEmail;

    /**
     * @var \Bluecom\Paygent\Model\Paygent
     */
    protected $paygent;

    /**
     * @var Processor\Cc
     */
    private $processorCc;

    /**
     * @var \Bluecom\Paygent\Logger\Logger
     */
    private $logger;

    /**
     * @var HistoryUsed
     */
    private $historyModel;

    /**
     * @var \Magento\Framework\Stdlib\DateTime\DateTime
     */
    private $dateTime;

    /**
     * @var \Riki\DelayPayment\Helper\Data
     */
    protected $helperDelayPayment;

    /**
     * PaygentManagement constructor.
     * @param Email\ReauthorizeFailure $reauthorizeFailureEmail
     * @param Paygent $paygent
     * @param Email\ReauthorizeFailureSubscription $reauthorizeFailureSubscriptionEmail
     * @param \Magento\Framework\Stdlib\DateTime\DateTime $dateTime
     */
    public function __construct(
        \Bluecom\Paygent\Model\Email\ReauthorizeFailure $reauthorizeFailureEmail,
        \Bluecom\Paygent\Model\Paygent $paygent,
        \Bluecom\Paygent\Model\Email\ReauthorizeFailureSubscription $reauthorizeFailureSubscriptionEmail,
        \Magento\Framework\Stdlib\DateTime\DateTime $dateTime,
        \Riki\DelayPayment\Helper\Data $helperDelayPayment
    ) {
        $this->reauthorizeFailureEmail = $reauthorizeFailureEmail;
        $this->paygent = $paygent;
        $this->reauthorizeFailureSubscriptionEmail = $reauthorizeFailureSubscriptionEmail;
        $this->processorCc = $paygent->getProcessorCc();
        $this->logger = $paygent->getLogger();
        $this->historyModel = $paygent->getHistoryModel();
        $this->dateTime = $dateTime;
        $this->helperDelayPayment = $helperDelayPayment;
    }

    /**
     * @return Paygent
     */
    public function getPaygentModel()
    {
        return $this->paygent;
    }

    /**
     * {@inheritdoc}
     *
     * @param array $params
     *
     * @return bool
     */
    public function sendEmailReauthorizeFailure($params)
    {
        if (isset($params['order'])
            && $params['order'] instanceof \Magento\Sales\Api\Data\OrderInterface
        ) {
            $order = $params['order'];
            if ($order->getData('riki_type') == \Riki\Sales\Helper\Order::RIKI_TYPE_SUBSCRIPTION ||
                $order->getData('riki_type') == \Riki\Sales\Helper\Order::RIKI_TYPE_DELAY_PAYMENT
            ) { // should use const
                return $this->reauthorizeFailureSubscriptionEmail->send($params);
            } else {
                return $this->reauthorizeFailureEmail->send($params);
            }
        }

        return true;
    }

    /**
     * {@inheritdoc}
     *
     * @param array $params
     *
     * @return array
     */
    public function getRedirectAuthorizeLink($params)
    {
        if (!isset($params['trading_id']) || !isset($params['amount'])) {
            return [
                'result' => 1
            ];
        }

        return $this->paygent->initRedirectLink($params['trading_id'], $params['amount'], $params);
    }

    /**
     * {@inheritdoc}
     *
     * @param array $params
     *
     * @return string
     */
    public function getErrorMessage($params)
    {
        $errorCode = isset($params['errorCode']) ? $params['errorCode'] : 'Others';
        $type = isset($params['type']) ? $params['type'] : 'backend_message';

        return $this->paygent->getErrorMessageByErrorCode($errorCode, $type);
    }

    /**
     * @param \Magento\Sales\Model\Order $order
     * @param null|float $amount
     * @param null $refTradingId
     * @return array
     * @throws LocalizedException
     */
    public function authorize(Order $order, $amount = null, $refTradingId = null)
    {
        if (!$order->getPayment() || $order->getPayment()->getMethod() != Paygent::CODE) {
            throw new LocalizedException(__('This payment method is not allowed to authorize.'));
        }

        $tradingId = preg_replace('/\-/', '_', $order->getIncrementId());
        $refTradingId = $refTradingId ? $refTradingId : $order->getIncrementId();
        $amount = $amount ? $amount : (float)$order->getTotalDue();

        list(
            $status,
            $result,
            $paymentObject
            ) = $this->executeAuthorize($amount, $tradingId, $refTradingId);

        if ($status) {
            $payment = $order->getPayment();
            $payment->setCcTransId($result['payment_id']);
            $payment->setTransactionId($result['payment_id']);
            $payment->setIsTransactionClosed(false);
            $payment->registerAuthorizationNotification((int)$order->getGrandTotal());
            $payment->accept();

            $paymentAgentCode = Paygent::PAYMENT_AGENT_CODE;
            $paymentAgentValue = isset($result[$paymentAgentCode]) ?
                $result[$paymentAgentCode] : Paygent::DEFAULT_PAYMENT_AGENT;

            if ($this->helperDelayPayment->checkOrderDelayPayment($order)) {
                $paymentAgentValue = $this->helperDelayPayment->convertPaymentAgentDelayPayment($paymentAgentValue);
            }

            if (!$payment->getAdditionalInformation($paymentAgentCode)) {
                $payment->setAdditionalInformation($paymentAgentCode, $paymentAgentValue);
            }

            $paygentHistory = [
                'customer_id' => $order->getCustomerId(),
                'order_number' => $order->getIncrementId(),
                'profile_id' => $order->getSubscriptionProfileId(),
                'trading_id' => $order->getIncrementId(),
                'used_date' => $this->dateTime->gmtDate(),
                'type' => 'authorize',
                'payment_agent' => $paymentAgentValue
            ];
            $this->saveTransactionHistory($paygentHistory);

            $order->setPayment($payment);
        }

        return [
            $status,
            $result,
            $paymentObject
        ];
    }

    /**
     * @param Order\Payment\Transaction $transaction
     * @param bool $isSaved
     * @return $this
     * @throws LocalizedException
     */
    public function voidTransaction(Order\Payment\Transaction $transaction, $isSaved = false)
    {
        if ($transaction->hasChildTransaction()) {
            return $this;
        }

        $order = $transaction->setOrder()->getOrder();
        $processor = $this->processorCc->init();

        //telegram 021 for cancel authorize
        $processor->setParam('telegram_kind', '021');
        $processor->setParam('payment_id', $transaction->getTxnId());
        $processor->setParam('trading_id', $order->getIncrementId());

        $result = $processor->process();
        $processor->getResult();
        $paymentObject = $processor->getPaymentObject();

        $status = null;

        if ($result == '1') {
            $status = $paymentObject->getResultStatus();
        }
        if ($status != '0') {
            $errorCode = $paymentObject->getResponseCode();
            $errorMessage = $this->paygent->getErrorMessageByErrorCode($errorCode);
            $errorDetail = ($errorCode != $errorMessage)
                ? $errorMessage
                : $paymentObject->getResponseDetail();

            $errorDetail = mb_convert_encoding($errorDetail, 'utf-8', 'sjis');

            $order->addStatusHistoryComment(__(
                'The transaction "%1" void failure from Paygent after re-auth. error code is %2, error detail is %3',
                $transaction->getTxnId(),
                $errorCode,
                $errorDetail
            ), false);

            $this->logger->error(__(
                'Order #%1 | Transaction ID #%2 Void failed | ERROR CODE: %3 | ERROR MESSAGE: %4',
                $order->getIncrementId(),
                $transaction->getTxnId(),
                $errorCode,
                $errorDetail
            ));
        }

        try {
            $transaction->close(true, $isSaved);

            $order->addStatusHistoryComment(
                __('The transaction "%1" (%2) is closed.', $transaction->getTxnId(), $transaction->getTxnType()),
                false
            );
        } catch (\Exception $e) {
            $order->addStatusHistoryComment(__($e->getMessage()));
        }

        $order->setIsNotified(false);

        return $this;
    }

    /**
     * @param float $amount
     * @param string $tradingId
     * @param string|null $refTradingId
     * @param array $history
     * @return array
     */
    public function executeAuthorize($amount, $tradingId, $refTradingId = null, $history = [])
    {
        $processor = $this->processorCc->init();
        $processor->setParam('telegram_kind', Paygent::TELEGRAM_KIND_AUTHORIZE);
        $processor->setParam('payment_amount', $amount);
        // cover for case increment ID of edit order include prefix
        $processor->setParam('trading_id', preg_replace('/-/', '_', $tradingId));
        $processor->setParam('ref_trading_id', $refTradingId);
        // pay one time , 10 => one time , 61 => split count
        //currently in RIKI we have no to use split pay
        $processor->setParam('payment_class', '10');
        $processor->setParam('3dsecure_ryaku', 1);

        $processStatus = $processor->process();
        $result = $processor->getResult();
        $paymentObject = $processor->getPaymentObject();

        if ($processStatus == 1 && $paymentObject->getResultStatus() === '0') {
            $status = true;
            if (!empty($history)) {
                $paymentAgentCode = \Bluecom\Paygent\Model\Paygent::PAYMENT_AGENT_CODE;
                $paymentAgentValue = \Bluecom\Paygent\Model\Paygent::DEFAULT_PAYMENT_AGENT;
                if (isset($result[$paymentAgentCode])) {
                    $paymentAgentValue = $result[$paymentAgentCode];
                }
                $history['type'] = 'authorize';
                $history['payment_agent'] = $paymentAgentValue;
                $this->saveTransactionHistory($history);
            }
        } else {
            $status = false;
            $this->logger->info(\Zend_Json::encode($result));
        }

        return [
            $status,
            $result,
            $paymentObject
        ];
    }

    /**
     * @param array $history
     */
    public function saveTransactionHistory($history)
    {
        $history['used_date'] = $this->dateTime->gmtDate();
        $this->historyModel->savePaygentHistory($history);
    }

    /**
     * @param string $paymentId
     * @param string $tradingId
     * @param array $history
     * @return array
     */
    public function executeVoid($paymentId, $tradingId, $history = [])
    {
        $status = null;
        $result = null;
        $paymentObject = null;

        try {
            $processor = $this->processorCc->init();

            //telegram 021 for cancel authorize
            $processor->setParam('telegram_kind', '021');
            $processor->setParam('payment_id', $paymentId);
            $processor->setParam('trading_id', $tradingId);

            $result = $processor->process();
            $processor->getResult();
            $paymentObject = $processor->getPaymentObject();

            if ($result == '1') {
                $status = $paymentObject->getResultStatus();
            }

            if ($status != '0') {
                //failed
                $errorCode = $paymentObject->getResponseCode();
                $errorMessage = $this->paygent->getErrorMessageByErrorCode($errorCode);
                $errorDetail = ($errorCode != $errorMessage) ? $errorMessage : $paymentObject->getResponseDetail();
                $errorDetail = mb_convert_encoding($errorDetail, 'utf-8', 'sjis');
                $this->logger->error(__(
                    'Payment ID #%1 | Trading ID #%2 Void failed | ERROR CODE: %3 | ERROR MESSAGE: %4',
                    $paymentId,
                    $tradingId,
                    $errorCode,
                    $errorDetail
                ));
                $status = false;
            } else {
                //success
                $status = true;
                if (!empty($history)) {
                    $history['type'] = 'void';
                    $this->saveTransactionHistory($history);
                }
            }
        } catch (\Exception $e) {
            $this->logger->critical($e);
            $status = false;
        }

        return [
            $status,
            $result,
            $paymentObject
        ];
    }
}