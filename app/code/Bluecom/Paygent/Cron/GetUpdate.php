<?php

namespace Bluecom\Paygent\Cron;

use Magento\Framework\Exception\LocalizedException;

class GetUpdate
{
    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;
    /**
     * @var \Magento\Framework\Event\Manager
     */
    protected $eventManager;
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
     * @var \Magento\Framework\Api\FilterBuilder
     */
    protected $filterBuilder;
    /**
     * @var \Magento\Framework\Api\Search\FilterGroupBuilder
     */
    protected $filterGroupBuilder;
    /**
     * @var \Magento\Framework\Api\SearchCriteriaBuilder
     */
    protected $searchCriteriaBuilder;
    /**
     * @var \Magento\Sales\Api\OrderRepositoryInterface
     */
    protected $orderRepository;
    /**
     * @var \Bluecom\Paygent\Model\HistoryUsed
     */
    protected $historyUsed;
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
     * @var \Bluecom\Paygent\Model\AuthorizationHistory
     */
    protected $authorizationHistory;
    /**
     * @var \Bluecom\Paygent\Model\Paygent
     */
    protected $paygentModel;

    /**
     * @var \Riki\DelayPayment\Helper\Data
     */
    protected $helperDelayPayment;

    /**
     * GetUpdate constructor.
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Framework\Event\Manager $eventManager
     * @param \Magento\Framework\HTTP\ZendClientFactory $clientFactory
     * @param \Magento\Framework\Stdlib\DateTime\DateTime $dateTime
     * @param \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezone
     * @param \Magento\Framework\Api\FilterBuilder $filterBuilder
     * @param \Magento\Framework\Api\Search\FilterGroupBuilder $filterGroupBuilder
     * @param \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder
     * @param \Magento\Sales\Api\OrderRepositoryInterface $orderRepository
     * @param \Bluecom\Paygent\Model\HistoryUsed $historyUsed
     * @param \Bluecom\Paygent\Helper\Data $dataHelper
     * @param \Bluecom\Paygent\Helper\PaygentHelper $paygentHelper
     * @param \Bluecom\Paygent\Logger\IvrLogger $paygentIvrLogger
     * @param \Bluecom\Paygent\Model\AuthorizationHistory $authorizationHistory
     * @param \Bluecom\Paygent\Model\Paygent $paygentModel
     * @param \Riki\DelayPayment\Helper\Data $helperDelayPayment
     */
    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Framework\Event\Manager $eventManager,
        \Magento\Framework\HTTP\ZendClientFactory $clientFactory,
        \Magento\Framework\Stdlib\DateTime\DateTime $dateTime,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezone,
        \Magento\Framework\Api\FilterBuilder $filterBuilder,
        \Magento\Framework\Api\Search\FilterGroupBuilder $filterGroupBuilder,
        \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder,
        \Magento\Sales\Api\OrderRepositoryInterface $orderRepository,
        \Bluecom\Paygent\Model\HistoryUsed $historyUsed,
        \Bluecom\Paygent\Helper\Data $dataHelper,
        \Bluecom\Paygent\Helper\PaygentHelper $paygentHelper,
        \Bluecom\Paygent\Logger\IvrLogger $paygentIvrLogger,
        \Bluecom\Paygent\Model\AuthorizationHistory $authorizationHistory,
        \Bluecom\Paygent\Model\Paygent $paygentModel,
        \Riki\DelayPayment\Helper\Data $helperDelayPayment
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->eventManager = $eventManager;
        $this->clientFactory = $clientFactory;
        $this->dateTime = $dateTime;
        $this->timezone = $timezone;
        $this->filterBuilder = $filterBuilder;
        $this->filterGroupBuilder = $filterGroupBuilder;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->orderRepository = $orderRepository;
        $this->historyUsed = $historyUsed;
        $this->dataHelper = $dataHelper;
        $this->paygentHelper = $paygentHelper;
        $this->paygentIvrLogger = $paygentIvrLogger;
        $this->paygentIvrLogger->setTimezone(
            new \DateTimeZone($timezone->getConfigTimezone())
        );
        $this->authorizationHistory = $authorizationHistory;
        $this->paygentModel = $paygentModel;
        $this->helperDelayPayment = $helperDelayPayment;
    }

    public function execute()
    {
        $isEnabled = $this->scopeConfig->getValue('paygent_config/ivr/active');
        if(!$isEnabled) {
            return ;
        }
        $this->paygentIvrLogger->info('======== START =========');
        $this->paygentIvrLogger->info('Cron get update for order used IVR');

        $collection = $this->getAllIvrOrder();
        if($collection) {
            foreach ($collection as $order) {
                $this->getUpdateIvr($order);
            }
        }
        $this->paygentIvrLogger->info('======== END =========');
        return $this;
    }


    /**
     * Get all order IVR
     *
     * @return $this|bool
     */
    public function getAllIvrOrder()
    {
        $searchCriteria = $this->searchCriteriaBuilder->addFilter(
            'use_ivr','1'
        )->addFilter(
            'ivr_transaction', NULL, 'neq'
        )->addFilter(
            'ivr_transaction',[
                \Bluecom\Paygent\Controller\Adminhtml\Paygent\Ivr::FAILED_RESPONSE_CODE,
                \Bluecom\Paygent\Controller\Adminhtml\Paygent\Ivr::ERROR_CODE,
                \Bluecom\Paygent\Controller\Adminhtml\Paygent\Ivr::CANCELED_CODE,
            ], 'nin'
        )->addFilter(
            'state',[
                \Magento\Sales\Model\Order::STATE_PENDING_PAYMENT,
                \Magento\Sales\Model\Order::STATE_CANCELED
            ], 'in'
        )->create();

        $searchResults = $this->orderRepository->getList($searchCriteria);
        
        if (!$searchResults->getTotalCount()) {
            return false;
        }
        

        return $searchResults->getItems();
    }

    /**
     * Get update ivr result
     * 
     * @param $orderData
     *
     * @return $this|bool
     * @throws \Zend_Http_Client_Exception
     */
    public function getUpdateIvr($orderData)
    {
        $this->paygentIvrLogger->info('Order #: '.$orderData->getIncrementId(). ' - Get Ivr Data');

        if (!$orderData->getIvrTransaction()
            || $orderData->getIvrTransaction() == \Bluecom\Paygent\Controller\Adminhtml\Paygent\Ivr::ERROR_CODE
            || $orderData->getIvrTransaction() == \Bluecom\Paygent\Controller\Adminhtml\Paygent\Ivr::CANCELED_CODE
        ) {
            $this->paygentIvrLogger->info('Order #: '.$orderData->getIncrementId().' - Ivr Transaction '.$orderData->getIvrTransaction().' is not allowed.');
            return false;
        }

        /*get order data again to avoid order data has been changed*/
        $order = $this->getOrderById($orderData->getId());

        $payment = $order->getPayment();
        if ($payment->getCcTransId()) {
            $this->paygentIvrLogger->info('Order #: '.$order->getIncrementId().' - Transaction id is already exist - '.$payment->getCcTransId());
            return false;
        }

        /*call api to get authorization data from IVR*/
        $returnValue = $this->getIvrDataByOrderIncrementId($orderData->getIncrementId());
        $this->paygentIvrLogger->info('Order #: '.$orderData->getIncrementId().' - Response data: '.\Zend_Json::encode($returnValue));
        if (isset($returnValue['error']) && !empty($returnValue['error'])) {
            return false;
        }

        $this->paygentIvrLogger->info('Order #: '.$order->getIncrementId().' - Current status: '.$order->getStatus());
        $this->paygentIvrLogger->info('Order #: '.$order->getIncrementId().' - Current payment status: '.$order->getPaymentStatus());

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

                $this->paygentIvrLogger->info('Order #: '.$order->getIncrementId().' - Order authorized success but order has ben canceled before.');

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

                $this->paygentIvrLogger->info('Order #: '.$order->getIncrementId().' - Get Result from IVR successfully.');

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

            $this->eventManager->dispatch('update_subscription_profile_after_authorized', ['order' => $order]);

        } else {

            $this->paygentIvrLogger->info('Order #: '.$order->getIncrementId().' - IVR response data is invalid.');

            /*add error message to order history*/
            $addMessage = true;

            $paymentStatus = $order->getPaymentStatus();
            $ivrTransaction = $order->getIvrTransaction();

            /*this payment status means this order has been got an error ivr data */
            if ($paymentStatus == \Riki\ArReconciliation\Model\ResourceModel\Status\PaymentStatus::PAYMENT_AUTHORIZED_FAILED) {
                /*do not need to add more error message for this case*/
                $addMessage = false;
                /*replace ivr transaction to avoid get update cron run again*/
                $ivrTransaction = \Bluecom\Paygent\Controller\Adminhtml\Paygent\Ivr::FAILED_RESPONSE_CODE;
            }

            if ($returnValue['statusCode'] == 3) {

                $addMessage = true;

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

            if ($addMessage == true) {
                $message = $this->getIvrMessageByResponseData($returnValue);
                $order->setIsNotified(false);
                $order->addStatusHistoryComment($message, false);
            }

            try {
                $order->save();
            } catch (LocalizedException $e) {
                $this->paygentIvrLogger->info($e->getMessage());
            } catch (\Exception $e) {
                $this->paygentIvrLogger->critical($e);
            }
        }

        return $this;
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
     * Get order by id
     *
     * @param $orderId
     * @return bool|\Magento\Sales\Api\Data\OrderInterface
     */
    public function getOrderById($orderId)
    {
        $order = $this->orderRepository->get($orderId);

        if ($order->getEntityId()) {
            return $order;
        }

        return false;
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