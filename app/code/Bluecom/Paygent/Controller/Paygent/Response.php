<?php

namespace Bluecom\Paygent\Controller\Paygent;

use Magento\Framework\Controller\Result\RawFactory;

class Response extends \Magento\Framework\App\Action\Action
{
    /**
     * @var \Bluecom\Paygent\Logger\Logger
     */
    protected $paygentLogger;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;

    /**
     * @var \Magento\Sales\Model\Order
     */
    protected $orderModel;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var \Bluecom\Paygent\Model\Handle
     */
    protected $paygentHandle;

    /**
     * @var \Magento\Framework\Stdlib\DateTime\TimezoneInterface
     */
    protected $timezone;

    /**
     * @var \Magento\Framework\Controller\Result\RawFactory
     */
    protected $rawFactory;

    /**
     * @var \Magento\Framework\Stdlib\DateTime\DateTime
     */
    protected $dateTime;

    /**
     * @var \Bluecom\Paygent\Model\HistoryUsed
     */
    protected $historyUsed;

    /**
     * @var \Bluecom\Paygent\Model\Paygent
     */
    protected $paygent;

    /**
     * @var \Bluecom\Paygent\Model\AuthorizationHistory
     */
    protected $authorizationHistory;

    /**
     * @var \Bluecom\Paygent\Helper\PaygentHelper
     */
    protected $paygentHelper;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $_storeManager;

    /**
     * Response constructor.
     *
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Bluecom\Paygent\Logger\Logger $paygentLogger
     * @param \Psr\Log\LoggerInterface $logger
     * @param \Magento\Sales\Model\Order $order
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Bluecom\Paygent\Model\Handle $paygentHandle
     * @param \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezone
     * @param RawFactory $rawFactory
     * @param \Magento\Framework\Stdlib\DateTime\DateTime $dateTime
     * @param \Bluecom\Paygent\Model\HistoryUsed $historyUsed
     * @param \Bluecom\Paygent\Model\Paygent $paygent
     * @param \Bluecom\Paygent\Model\AuthorizationHistory $authorizationHistory
     * @param \Bluecom\Paygent\Helper\PaygentHelper $paygentHelper
     *  @param \Magento\Store\Model\StoreManagerInterface $storeManager
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Bluecom\Paygent\Logger\Logger $paygentLogger,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Sales\Model\Order $order,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Bluecom\Paygent\Model\Handle $paygentHandle,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezone,
        \Magento\Framework\Controller\Result\RawFactory $rawFactory,
        \Magento\Framework\Stdlib\DateTime\DateTime $dateTime,
        \Bluecom\Paygent\Model\HistoryUsed $historyUsed,
        \Bluecom\Paygent\Model\Paygent $paygent,
        \Bluecom\Paygent\Model\AuthorizationHistory $authorizationHistory,
        \Bluecom\Paygent\Helper\PaygentHelper $paygentHelper,
        \Magento\Store\Model\StoreManagerInterface $storeManager
    ) {
        parent::__construct($context);
        $this->paygentLogger = $paygentLogger;
        $this->logger = $logger;
        $this->orderModel = $order;
        $this->scopeConfig = $scopeConfig;
        $this->paygentHandle = $paygentHandle;
        $this->timezone = $timezone;
        $this->paygentLogger->setTimezone(new \DateTimeZone($timezone->getConfigTimezone()));
        $this->rawFactory = $rawFactory;
        $this->dateTime = $dateTime;
        $this->historyUsed = $historyUsed;
        $this->paygent = $paygent;
        $this->authorizationHistory = $authorizationHistory;
        $this->paygentHelper = $paygentHelper;
        $this->_storeManager = $storeManager;
    }

    /**
     * {@inheritdoc}
     *
     * @return \Magento\Framework\Controller\ResultInterface|\Magento\Framework\App\ResponseInterface
     * @throws \Magento\Framework\Exception\NotFoundException
     */
    public function execute()
    {
        $params = $this->getRequest()->getParams();
        $rawResult = $this->rawFactory->create();

        if (empty($params)) {
            return $rawResult->setHttpResponseCode(400);
        }

        //write log to file when enable debug flag
        if ($this->scopeConfig->getValue('payment/paygent/debug')) {
            //write log to file when enable debug flag
            $this->paygentLogger->info(\Zend_Json::encode($params));
        }

        try {
            $result = array();
            $result['payment_id'] = $params['seq_payment_id'];
            $result['trading_id'] = $params['trading_id'];
            $result['payment_type'] = $params['payment_type'];
            $result['payment_amount'] = $params['amount'];
            //$hash = $params['hc'];

            if (strpos($params['trading_id'], "MF") !== false) {
                $splitToArray = explode("-MF",$params['trading_id']);
                $result['trading_id'] = $splitToArray[0];
            }

            $storeId = $this->_storeManager->getStore()->getId();
            $order = $this->orderModel->loadByIncrementIdAndStoreId((int)$result['trading_id'], $storeId);

            if (!$order->getId()) {
                $message = sprintf('Wrong order ID: "%s".', (int)$result['trading_id']);
                $this->logger->addDebug($message);
                $this->logger->critical($message);
                return $rawResult->setHttpResponseCode(404);
            }

            //verify hash string
            //$this->_calcHash($result, $hash);

            $payment = $order->getPayment();
            if (!$payment || $payment->getMethod() != \Bluecom\Paygent\Model\Paygent::CODE) {
                return $rawResult->setHttpResponseCode(404);
            }

            $oldTransactionId = $payment->getCcTransId();

            /*avoid received multi response with same data*/
            if (!empty($oldTransactionId) && $oldTransactionId == $result['payment_id']) {
                $this->paygentLogger->info('Received same payment id ' .$result['payment_id']. ' for order #'. $order->getIncrementId());
                return $rawResult->setHttpResponseCode(404);
            }

            $transactionPaymentAgent = '';

            // Fetch NICOS/JCB information
            $transactionInfo = $payment->getMethodInstance()->fetchTransactionInfo($payment, $result['payment_id']);

            if (!empty($transactionInfo) && isset($transactionInfo['acq_name'])) {
                $transactionPaymentAgent = $transactionInfo['acq_name'];
            }

            //Save paygent history used
            $paygentHistory = [
                'customer_id' => $order->getCustomerId(),
                'order_number' => $order->getIncrementId(),
                'profile_id' => $order->getSubscriptionProfileId(),
                'trading_id' => $result['trading_id'],
                'used_date' => $this->timezone->formatDateTime($this->dateTime->gmtDate(),2),
                'type' => 'authorize',
                'payment_agent' => $transactionPaymentAgent
            ];
            //save history used
            $this->historyUsed->savePaygentHistory($paygentHistory);


            if ($order->getStatus() == \Riki\Sales\Model\ResourceModel\Order\OrderStatus::STATUS_ORDER_CANCELED) {

                /*save reference trading id*/
                $order->setRefTradingId($result['payment_id']);
                /*change order payment status to null for order has been canceled*/
                $order->setPaymentStatus(Null);

                $order->setIsNotified(false);

                $order->addStatusHistoryComment(
                    __('Order authorized success but order has ben canceled before.'),
                    false
                );

                /*cancel authorization*/
                $cancelAuth = $this->paygent->voidByTransactionId($order, $result['payment_id']);

                if ($cancelAuth) {
                    $order->save();
                }

            } else {

                foreach ($params as $k => $v) {
                    $payment->setAdditionalInformation($k, mb_convert_encoding($v, 'UTF-8'));
                }

                $payment->setTransactionId($result['payment_id']);
                $payment->setCcTransId($result['payment_id']);
                $payment->setIsTransactionClosed(0)
                    ->registerAuthorizationNotification($result['payment_amount']);
                /*update order after authorized success*/
                $this->paygentHelper->updateOrderAfterAuthorizeSuccess(
                    $order, $result['trading_id'], __('Customer redirected to Paygent gateway and make authorized')
                );

                $this->authorizationHistory->saveAuthorizationTiming($order);
            }

            if ($oldTransactionId) {
                $this->paygent->voidByTransactionId($order, $oldTransactionId);
            }
        } catch (\Exception $e) {
            $this->logger->critical($e);
            return $rawResult->setHttpResponseCode(400);
        }

        return $rawResult->setContents('result=0');
    }

    /**
     * Calculate hash string
     *
     * @param array  $result Result
     * @param string $hash   HashString
     *
     * @return bool
     *
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    private function _calcHash($result, $hash)
    {
        $str = $result['payment_type'] .
            $result['payment_amount'] .
            $this->scopeConfig->getValue('payment/paygent/hash_key') .
            $result['trading_id'] .
            $result['payment_id'];

        $hashStr = hash("sha256", $str);
        if (substr($hashStr, 0, 63) != substr($hash, 0, 63)) {
            throw new \Magento\Framework\Exception\LocalizedException(__('Request is modified by someone.'));
        }

        return true;
    }
}
