<?php

namespace Bluecom\Paygent\Controller\Adminhtml\Paygent;

class GetUpdate extends \Magento\Backend\App\Action
{
    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;
    /**
     * @var \Magento\Framework\Controller\Result\JsonFactory
     */
    protected $resultJson;
    /**
     * @var \Magento\Framework\HTTP\ZendClientFactory
     */
    protected $clientFactory;
    /**
     * @var \Magento\Framework\Stdlib\DateTime\DateTime
     */
    protected $dateTime;
    /**
     * @var \Magento\Framework\Stdlib\DateTime\TimezoneInterface
     */
    protected $timezone;
    /**
     * @var \Magento\Sales\Api\OrderRepositoryInterface
     */
    protected $orderRepository;
    /**
     * @var \Bluecom\Paygent\Model\HistoryUsed
     */
    protected $historyUsed;
    /**
     * @var \Bluecom\Paygent\Model\AuthorizationHistory
     */
    protected $authorizationHistory;
    /**
     * @var \Bluecom\Paygent\Model\Paygent
     */
    protected $paygentModel;
    /**
     * @var \Bluecom\Paygent\Helper\Data
     */
    protected $dataHelper;
    /**
     * @var \Bluecom\Paygent\Helper\PaygentHelper
     */
    protected $paygentHelper;
    /**
     * @var \Bluecom\Paygent\Logger\IvrLogger
     */
    protected $paygentIvrLogger;

    /**
     * @var \Riki\DelayPayment\Helper\Data
     */
    protected $helperDelayPayment;

    /**
     * GetUpdate constructor.
     *
     * @param \Magento\Backend\App\Action\Context $context
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Framework\Controller\Result\JsonFactory $resultJson
     * @param \Magento\Framework\HTTP\ZendClientFactory $clientFactory
     * @param \Magento\Framework\Stdlib\DateTime\DateTime $dateTime
     * @param \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezone
     * @param \Magento\Sales\Api\OrderRepositoryInterface $orderRepository
     * @param \Bluecom\Paygent\Model\HistoryUsed $historyUsed
     * @param \Bluecom\Paygent\Model\AuthorizationHistory $authorizationHistory
     * @param \Bluecom\Paygent\Model\Paygent $paygentModel
     * @param \Bluecom\Paygent\Helper\Data $dataHelper
     * @param \Bluecom\Paygent\Helper\PaygentHelper $paygentHelper
     * @param \Bluecom\Paygent\Logger\IvrLogger $paygentIvrLogger
     * @param \Riki\DelayPayment\Helper\Data $helperDelayPayment
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Framework\Controller\Result\JsonFactory $resultJson,
        \Magento\Framework\HTTP\ZendClientFactory $clientFactory,
        \Magento\Framework\Stdlib\DateTime\DateTime $dateTime,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezone,
        \Magento\Sales\Api\OrderRepositoryInterface $orderRepository,
        \Bluecom\Paygent\Model\HistoryUsed $historyUsed,
        \Bluecom\Paygent\Model\AuthorizationHistory $authorizationHistory,
        \Bluecom\Paygent\Model\Paygent $paygentModel,
        \Bluecom\Paygent\Helper\Data $dataHelper,
        \Bluecom\Paygent\Helper\PaygentHelper $paygentHelper,
        \Bluecom\Paygent\Logger\IvrLogger $paygentIvrLogger,
        \Riki\DelayPayment\Helper\Data $helperDelayPayment
    ) {
        parent::__construct($context);
        $this->scopeConfig = $scopeConfig;
        $this->resultJson = $resultJson;
        $this->clientFactory = $clientFactory;
        $this->dateTime = $dateTime;
        $this->timezone = $timezone;
        $this->orderRepository = $orderRepository;
        $this->historyUsed = $historyUsed;
        $this->authorizationHistory = $authorizationHistory;
        $this->paygentModel = $paygentModel;
        $this->dataHelper = $dataHelper;
        $this->paygentHelper = $paygentHelper;
        $this->paygentIvrLogger = $paygentIvrLogger;
        $this->paygentIvrLogger->setTimezone(
            new \DateTimeZone($timezone->getConfigTimezone())
        );
        $this->helperDelayPayment = $helperDelayPayment;
    }

    /**
     * Is Allow
     *
     * @return bool
     */
    protected function _isAllowed()
    {
        return true;
    }

    /**
     * Connect to IVR system
     * 
     * @return mixed
     *
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Zend_Http_Client_Exception
     */
    public function execute()
    {
        $orderId = $this->getRequest()->getParam('id');

        if (!(int)$orderId) {
            return false;
        }

        $order = $this->orderRepository->get($orderId);

        if (!$order->getEntityId()) {
            return false;
        }

        $this->paygentIvrLogger->info('Call Center - Order #: '.$order->getIncrementId().' - Get IVR response data.');

        $ivrTransaction = $order->getIvrTransaction();

        $payment = $order->getPayment();

        if ($ivrTransaction == \Bluecom\Paygent\Controller\Adminhtml\Paygent\Ivr::ERROR_CODE
            || $ivrTransaction == \Bluecom\Paygent\Controller\Adminhtml\Paygent\Ivr::CANCELED_CODE
            || $ivrTransaction == \Bluecom\Paygent\Controller\Adminhtml\Paygent\Ivr::FAILED_RESPONSE_CODE
        ) {
            $this->paygentIvrLogger->info('Call Center - Order #: '.$order->getIncrementId().' - Ivr transaction is not allowed - '. $ivrTransaction);
            return false;
        }

        if ($order->getState() != \Magento\Sales\Model\Order::STATE_PENDING_PAYMENT
                && $order->getState() != \Magento\Sales\Model\Order::STATE_CANCELED
        ) {
            $this->paygentIvrLogger->info('Call Center - Order #: '.$order->getIncrementId().' - Order status is not allowed - '. $order->getStatus());
            return false;
        }

        if ($payment->getCcTransId()) {
            $this->paygentIvrLogger->info('Call Center - Order #: '.$order->getIncrementId().' - Transaction id is already exist - '. $payment->getCcTransId());
            return false;
        }

        $returnValue = $this->getIvrDataByOrderIncrementId($order->getIncrementId());

        $this->paygentIvrLogger->info('Call Center - Order #: '.$order->getIncrementId().' - Response data: '.\Zend_Json::encode($returnValue));

        $resultJson = $this->resultJson->create();

        if (isset($returnValue['error']) && !empty($returnValue['errror'])) {
            return $resultJson->setData($returnValue);
        }

        if ($returnValue['statusCode'] == 3
            && ( isset($returnValue['card_auth_result']) && $returnValue['card_auth_result'] == 0 )
        ) {
            $paymentAgent = '';
            foreach ($returnValue as $k => $v) {

                if ($k == 'acq_name') {
                    if ($this->helperDelayPayment->checkOrderDelayPayment($order)) {
                        $v = $this->helperDelayPayment->convertPaymentAgentDelayPayment($v);
                    }
                    $order->setPaymentAgent($v);
                    $paymentAgent = $v;
                }

                if($k !== 'message') {
                    $payment->setAdditionalInformation($k, mb_convert_encoding($v, 'UTF-8'));
                }
            }

            //Save paygent history used
            $paygentHistory = [
                'customer_id' => $order->getCustomerId(),
                'order_number' => $order->getIncrementId(),
                'profile_id' => '',
                'trading_id' => $order->getIncrementId(),
                'used_date' => $this->timezone->formatDateTime($this->dateTime->gmtDate(),2),
                'type' => 'authorize',
                'payment_agent' => $paymentAgent
            ];
            //save history used
            $this->historyUsed->savePaygentHistory($paygentHistory);

            if ($order->getStatus() == \Riki\Sales\Model\ResourceModel\Order\OrderStatus::STATUS_ORDER_CANCELED) {

                $this->paygentIvrLogger->info('Call Center - Order #: '.$order->getIncrementId().' - Order authorized success but order has ben canceled before.');

                /*save reference trading id*/
                $order->setRefTradingId($returnValue['payment_id']);
                /*change order payment status to null for order has been canceled*/
                $order->setPaymentStatus(Null);

                $order->setIsNotified(false);

                $order->addStatusHistoryComment(
                    __('Order authorized success but order has ben canceled before.'),
                    false
                );

                /*cancel authorization*/
                $cancelAuth = $this->paygentModel->voidByTransactionId($order, $returnValue['payment_id']);

                if ($cancelAuth) {
                    try {
                        $order->setIvrTransaction(\Bluecom\Paygent\Controller\Adminhtml\Paygent\Ivr::CANCELED_CODE);
                        $order->save();
                    } catch (\Exception $e) {
                        $this->paygentIvrLogger->critical($e);
                    }
                }
            } else {

                $this->paygentIvrLogger->info('Call Center - Order #: '.$order->getIncrementId().' - Get Result from IVR successfully.');
                // Fetch NICOS/JCB information
                $payment->getMethodInstance()->fetchTransactionInfo($payment, $returnValue['payment_id']);

                $payment->setTransactionId($returnValue['payment_id']);
                $payment->setCcTransId($returnValue['payment_id']);
                $payment->setIsTransactionClosed(0)
                    ->registerAuthorizationNotification((int)$order->getGrandTotal());

                /*update order status after authorize success*/
                $this->paygentHelper->updateOrderAfterAuthorizeSuccess(
                    $order, $returnValue['trading_id'], __('Get Result from IVR successfully.')
                );

                $this->authorizationHistory->saveAuthorizationTiming($order);
            }

            $this->_eventManager->dispatch('update_subscription_profile_after_authorized', ['order' => $order]);

        } else {

            $this->paygentIvrLogger->info('Call Center - Order #: '.$order->getIncrementId().' - IVR response data is invalid.');

            $ivrTransaction = $order->getIvrTransaction();

            $message = $this->getIvrMessageByResponseData($returnValue);

            if ( $returnValue['statusCode'] == 3 ) {

                $ivrTransaction = \Bluecom\Paygent\Controller\Adminhtml\Paygent\Ivr::FAILED_RESPONSE_CODE;

                if ($order->getStatus() == \Riki\Sales\Model\ResourceModel\Order\OrderStatus::STATUS_ORDER_CANCELED) {
                    $ivrTransaction = \Bluecom\Paygent\Controller\Adminhtml\Paygent\Ivr::CANCELED_CODE;
                }
            }

            /*replace ivr transaction to avoid get update cron run again after get update has been error or order has been canceled*/
            $order->setIvrTransaction($ivrTransaction);

            /*set payment status to authorized failed to avoid get update cron run more than 2 times*/
            $order->setPaymentStatus(
                \Riki\ArReconciliation\Model\ResourceModel\Status\PaymentStatus::PAYMENT_AUTHORIZED_FAILED
            );

            $order->setIsNotified(false);
            $order->addStatusHistoryComment($message, false);

            try {
                $order->save();
            } catch (\Exception $e) {
                $this->paygentIvrLogger->critical($e);
            }
        }

        return $resultJson->setData($returnValue);
    }

    /**
     * call api to get authorization information for order
     *
     * @param $orderIncrementId
     * @return array
     */
    public function getIvrDataByOrderIncrementId($orderIncrementId)
    {
        $rs = [];

        $client = $this->clientFactory->create();

        $urlAPI = $this->scopeConfig->getValue('paygent_config/ivr/request_data');

        $client->setUri($urlAPI);

        $data = [
            'tradingId' => $orderIncrementId
        ];

        foreach ($data as $k => $v) {
            $client->setParameterPost($k, $v);
        }

        //send http request to paygent
        $response = $client->request('POST');

        if ($response->isError() && !$response->getBody()) {
            $rs['error'] = 1;
            $rs['resultCode'] = 1;
            $rs['message'] = __('Network error. Could not reach gateway server.');
        } else {
            $rs = $this->dataHelper->parseResponseBodyIvr($response->getBody());
        }

        return $rs;
    }

    /**
     * Get ivr message by response data
     * @param $res
     * @return string
     */
    private function getIvrMessageByResponseData($res)
    {
        $msg = '';

        if ($res['statusCode'] == 0) {
            $msg = __('No corresponding reception number');
        } else if ($res['statusCode'] == 1) {
            $msg = __('Designated ID has not been entered. The call was hung up in the middle');
        } else if ($res['statusCode'] == 2) {
            $msg = __('The call was hung up after the entry of designated ID and before settlement');
        } else if ( $res['statusCode'] == 3 ) {
            if( $res['card_auth_result'] == 1) {
                $msg = __('Completed (Settled) ! The call was hung up after settlement. AUTHORIZATION FAILED');
            } else if ($res['card_auth_result'] == 7) {
                $msg = __('Completed (Settled) ! The call was hung up after settlement.3D AUTHORIZATION is required');
            } else {
                $msg = __('Completed (Settled) ! The call was hung up after settlement.');
            }
        }

        return $msg;
    }
}
