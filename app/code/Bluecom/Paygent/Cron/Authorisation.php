<?php

namespace Bluecom\Paygent\Cron;

use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Payment\Transaction;
use Riki\ArReconciliation\Model\ResourceModel\Status\PaymentStatus;
use Riki\Sales\Model\ResourceModel\Order\OrderStatus;
use Riki\SubscriptionCourse\Model\Course\Type;

class Authorisation
{
    const DEFAULT_PAYMENT_AGENT = 'NICOS';
    const PAYMENT_AGENT_CODE = 'acq_name';
    /**
     * Failed reauthorize
     *
     * @var array
     */
    protected $failOrders = [];

    /**
     * @var array
     */
    protected $skipOrders = [];

    /**
     * @var \Bluecom\Paygent\Model\Reauthorize
     */
    protected $reauthorize;
    /**
     * @var \Bluecom\Paygent\Logger\Logger
     */
    protected $paygentLogger;
    /**
     * @var \Bluecom\Paygent\Helper\Data
     */
    protected $dataHelper;

    /**
     * @var \Magento\Framework\Stdlib\DateTime\DateTime
     */
    protected $dateTime;
    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;
    /**
     * @var \Magento\Framework\Stdlib\DateTime\TimezoneInterface
     */
    protected $timezone;
    /**
     * @var \Magento\Sales\Model\OrderRepository
     */
    protected $orderRepository;
    /**
     * @var \Bluecom\Paygent\Model\Processor\Cc
     */
    protected $cc;
    /**
     * @var \Bluecom\Paygent\Model\Processor\Cclink
     */
    protected $cclink;
    /**
     * @var \Magento\Sales\Api\TransactionRepositoryInterface
     */
    protected $transactionRepository;
    /**
     * @var \Magento\Framework\Api\SearchCriteriaBuilder
     */
    protected $searchCriteriaBuilder;
    /**
     * @var \Bluecom\Paygent\Helper\Data
     */
    protected $paygentHelper;
    /**
     * @var \Bluecom\Paygent\Model\HistoryUsed
     */
    protected $historyUsed;

    /**
     * @var \Bluecom\Paygent\Model\Email\ReauthorizeFailureBusiness
     */
    protected $reauthorizeFailureBusinessEmail;

    /**
     * @var \Bluecom\Paygent\Model\Email\ReauthorizeFailure
     */
    protected $reauthorizeFailEmail;

    /**
     * @var \Bluecom\Paygent\Model\Email\ReauthorizeFailureSubscription
     */
    protected $reauthorizeFailSubscriptionEmail;

    /**
     * @var \Bluecom\Paygent\Model\Paygent
     */
    protected $paygent;

    /**
     * @var \Bluecom\Paygent\Model\PaygentManagement
     */
    protected $paygentManagement;

    protected $notAllowedReauthorizeStatus;

    /**
     * @var \Magento\Framework\DB\TransactionFactory
     */
    protected $dbTransactionFactory;

    /**
     * Authorisation constructor.
     * @param \Bluecom\Paygent\Model\Paygent $paygent
     * @param \Bluecom\Paygent\Model\Email\ReauthorizeFailureSubscription $reauthorizeFailureSubscriptionEmail
     * @param \Bluecom\Paygent\Model\Email\ReauthorizeFailure $reauthorizeFailureEmail
     * @param \Bluecom\Paygent\Model\Email\ReauthorizeFailureBusiness $reauthorizeFailureBusinessEmail
     * @param \Bluecom\Paygent\Helper\Data $dataHelper
     * @param \Magento\Framework\Stdlib\DateTime\DateTime $dateTime
     * @param \Bluecom\Paygent\Model\Reauthorize $reauthorize
     * @param \Bluecom\Paygent\Logger\Logger $paygentLogger
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezone
     * @param \Magento\Sales\Model\OrderRepository $orderRepository
     * @param \Bluecom\Paygent\Model\Processor\Cc $cc
     * @param \Bluecom\Paygent\Model\Processor\Cclink $cclink
     * @param \Magento\Sales\Api\TransactionRepositoryInterface $transactionRepository
     * @param \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder
     * @param \Bluecom\Paygent\Helper\Data $paygentHelper
     * @param \Bluecom\Paygent\Model\HistoryUsed $historyUsed
     * @param \Bluecom\Paygent\Model\PaygentManagement $paygentManagement
     * @param \Magento\Framework\DB\TransactionFactory $dbTransactionFactory
     */
    public function __construct(
        \Bluecom\Paygent\Model\Paygent $paygent,
        \Bluecom\Paygent\Model\Email\ReauthorizeFailureSubscription $reauthorizeFailureSubscriptionEmail,
        \Bluecom\Paygent\Model\Email\ReauthorizeFailure $reauthorizeFailureEmail,
        \Bluecom\Paygent\Model\Email\ReauthorizeFailureBusiness $reauthorizeFailureBusinessEmail,
        \Bluecom\Paygent\Helper\Data $dataHelper,
        \Magento\Framework\Stdlib\DateTime\DateTime $dateTime,
        \Bluecom\Paygent\Model\Reauthorize $reauthorize,
        \Bluecom\Paygent\Logger\Logger $paygentLogger,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezone,
        \Magento\Sales\Model\OrderRepository $orderRepository,
        \Bluecom\Paygent\Model\Processor\Cc $cc,
        \Bluecom\Paygent\Model\Processor\Cclink $cclink,
        \Magento\Sales\Api\TransactionRepositoryInterface $transactionRepository,
        \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder,
        \Bluecom\Paygent\Helper\Data $paygentHelper,
        \Bluecom\Paygent\Model\HistoryUsed $historyUsed,
        \Bluecom\Paygent\Model\PaygentManagement $paygentManagement,
        \Magento\Framework\DB\TransactionFactory $dbTransactionFactory
    ) {
        $this->paygent = $paygent;
        $this->reauthorizeFailSubscriptionEmail = $reauthorizeFailureSubscriptionEmail;
        $this->reauthorizeFailEmail = $reauthorizeFailureEmail;
        $this->reauthorizeFailureBusinessEmail = $reauthorizeFailureBusinessEmail;
        $this->dataHelper = $dataHelper;
        $this->dateTime = $dateTime;
        $this->reauthorize = $reauthorize;
        $this->paygentLogger = $paygentLogger;
        $this->timezone = $timezone;
        $this->paygentLogger->setTimezone(new \DateTimeZone($timezone->getConfigTimezone()));
        $this->scopeConfig = $scopeConfig;
        $this->orderRepository = $orderRepository;
        $this->cc = $cc;
        $this->cclink = $cclink;
        $this->transactionRepository = $transactionRepository;
        $this->searchCriteriaBuilder    = $searchCriteriaBuilder;
        $this->paygentHelper = $paygentHelper;
        $this->historyUsed = $historyUsed;
        $this->paygentManagement = $paygentManagement;
        $this->dbTransactionFactory = $dbTransactionFactory;
        $this->generateNotAllowedStatus();
    }

    /**
     * generate not allowed re authorize status
     */
    public function generateNotAllowedStatus()
    {
        if (!$this->notAllowedReauthorizeStatus) {
            $this->notAllowedReauthorizeStatus = [
                /*order was canceled*/
                \Riki\Sales\Model\ResourceModel\Order\OrderStatus::STATUS_ORDER_CANCELED,
                /*order was shipped all - another job use this status to capture order*/
                \Riki\Sales\Model\ResourceModel\Order\OrderStatus::STATUS_ORDER_SHIPPED_ALL,
                /*order was capture failed - after capture failed, this order must be process by call center*/
                \Riki\Sales\Model\ResourceModel\Order\OrderStatus::STATUS_ORDER_CAPTURE_FAILED,
                /*order was completed*/
                \Riki\Sales\Model\ResourceModel\Order\OrderStatus::STATUS_ORDER_COMPLETE
            ];
        }
    }

    public function execute()
    {
        $isEnabled = $this->scopeConfig->getValue('paygent_config/authorisation/active');
        if(!$isEnabled) {
            return ;
        }
        $this->paygentLogger->info('======== START =========');
        $this->paygentLogger->info('Cron Re-Authorisation Paygent Running');
        //re-authorization for pre-order
        $this->_reauthorizePreOrder();
        //re-authorization for SPOT and Subscription Order
        $this->_reauthorizeSpotAndSubscription();
        //re-authorization for order failure re-auth
        $this->_reauthorizeOrderFailAgain();

        $this->reauthorizeFailureBusinessEmail->send();

        $this->paygentLogger->info('======== END =========');

        return $this;
    }

    private function _reauthorizePreOrder()
    {
        $collectionPreOrder = $this->getAllPreOrderCC();

        if($collectionPreOrder) {
            foreach ($collectionPreOrder as $data) {
                $this->reAuthorize($data);
            }
        }
        return $this;
    }
    private function _reauthorizeSpotAndSubscription()
    {
        $collection = $this->getAllNormalOrder();
        if($collection) {
            foreach ($collection as $data) {
                $this->reAuthorize($data);
            }
        }
        return $this;
    }
    private function _reauthorizeOrderFailAgain()
    {
        $collection = $this->getAllOrderReAuthorizeFail();
        if($collection) {
            foreach ($collection as $data) {
                $this->reAuthorize($data);
            }
        }
        return $this;
    }

    /**
     * Get all pre-order used PAYGENT , filter by available day fulfillment product (3days before fulfillment day)
     *
     * @return \Bluecom\Paygent\Model\ResourceModel\Reauthorize\Collection|bool
     */
    public function getAllPreOrderCC()
    {
        $now = $this->timezone->formatDateTime($this->dateTime->gmtDate(),2);
        $nextDay = $this->scopeConfig->getValue('paygent_config/authorisation/afterdays_preorder');
        $dayBeforeAuthorization = date('Y-m-d', strtotime($now .' +'.$nextDay.' day'));

        /* @var $collection \Bluecom\Paygent\Model\ResourceModel\Reauthorize\Collection */
        $collection = $this->reauthorize->getCollection();

        $collection->join(
            $collection->getTable('sales_order'),
            "main_table.order_id = sales_order.entity_id",
            "status"
        )->addFieldToFilter('status', [
            'nin' => $this->notAllowedReauthorizeStatus
        ])->addFieldToFilter(
            'pre_order', 1
        )->addFieldToFilter(
            'available_date_of_product', ['lteq' => $dayBeforeAuthorization]
        )->addFieldToFilter(
            're_authorization_date', ['null' => true]
        );

        if (!$collection->getSize()) {
            return false;
        }

        return $collection;
    }

    /**
     * Get all spot and subscription order used paygent , filter by created_at (45days after created)
     *
     * @return \Bluecom\Paygent\Model\ResourceModel\Reauthorize\Collection|bool
     */
    public function getAllNormalOrder()
    {
        $day = $this->scopeConfig->getValue('paygent_config/authorisation/afterdays');
        $toDate = $this->timezone->date()->setTimestamp(time() - $day*86400);
        $timezoneOffset = $toDate->getTimezone()->getOffset($toDate);

        /* @var $collection \Bluecom\Paygent\Model\ResourceModel\Reauthorize\Collection */
        $collection = $this->reauthorize->getCollection();
        $collection->join(
            $collection->getTable('sales_order'),
            "main_table.order_id = sales_order.entity_id",
            "status"
        )->addFieldToFilter('status', [
            'nin' => $this->notAllowedReauthorizeStatus
        ])->addFieldToFilter(
            'pre_order', 0
        )->addFieldToFilter('re_authorization_status', [
            'or' => [
                0 => ['null' => true],
                1 => 1,
            ]
        ]);

        $checkSql = $collection->getConnection()->getCheckSql(
            new \Zend_Db_Expr('main_table.re_authorization_date IS NOT NULL'),
            'main_table.re_authorization_date',
            'main_table.order_date'
        );

        $collection->getSelect()->where(
            "$checkSql <= ?", date(
            'Y-m-d H:i:s',
            strtotime($toDate->format('Y-m-d H:i:s')) - $timezoneOffset
        ));

        if (!$collection->getSize()) {
            return false;
        }

        return $collection;
    }

    /**
     * Re-auth again for order after 7days re-auth fail
     *
     * @return \Bluecom\Paygent\Model\ResourceModel\Reauthorize\Collection|bool
     */
    public function getAllOrderReAuthorizeFail()
    {
        $day = $this->scopeConfig->getValue('paygent_config/authorisation/afterdays_again');
        $toDate = $this->timezone->date()->setTimestamp(time() - $day*86400);
        $timezoneOffset = $toDate->getTimezone()->getOffset($toDate);

        /* @var $collection \Bluecom\Paygent\Model\ResourceModel\Reauthorize\Collection */
        $collection = $this->reauthorize->getCollection();
        $collection->join(
            $collection->getTable('sales_order'),
            "main_table.order_id = sales_order.entity_id",
            "status"
        )->addFieldToFilter('status', [
            'nin' => $this->notAllowedReauthorizeStatus
        ])->addFieldToFilter('re_authorization_date', [
            'date' => true,
            'to' => date(
                'Y-m-d H:i:s',
                strtotime($toDate->format('Y-m-d H:i:s')) - $timezoneOffset
            )
        ])->addFieldToFilter(
            're_authorization_status', 0
        )->addFieldToFilter(
            'authorized_number', ['gt' => 1]
        )->setOrder(
            're_authorization_date', \Magento\Framework\Data\Collection::SORT_ORDER_ASC
        );

        if (!empty($this->skipOrders)) {
            $collection->addFieldToFilter('order_id', ['nin' => $this->skipOrders]);
        }

        if (!$collection->getSize()) {
            return false;
        }

        return $collection;
    }

    /**
     * Make re-authorisation for order
     *
     * @param $data
     * @return $this
     */
    public function reAuthorize($data)
    {
        $this->skipOrders[] = $data->getOrderId();
        /* @var $order \Magento\Sales\Model\Order */
        try {
            $order = $this->orderRepository->get($data->getOrderId());
        } catch (\Exception $e) {
            $this->paygentLogger->info($e->getMessage());
            $this->paygentLogger->info($e->getTraceAsString());
        }
        if (!$this->canReAuthorize($order)) {
            $this->paygentLogger->info(__(
                "Order #%1 cannot re authorize. Order status \"%2\" is not allowed.",
                $order->getIncrementId(),
                $order->getStatus()
            ));
            return $this;
        }
        $this->paygentLogger->info(__('Re-authorisation payment order: %1', $order->getIncrementId()));
        $payment = $order->getPayment();

        $unclosedAuthorizeTransactions = $this->getUnclosedAuthorizeTransactionsByPayment($payment->getEntityId());

        /** @var \Magento\Framework\DB\Transaction $dbTransaction */
        $dbTransaction = $this->dbTransactionFactory->create();

        list($status, $result, $paymentObject) = $this->paygentManagement->authorize($order);

        if ($status) {
            $order->setPaymentAgent($payment->getAdditionalInformation(self::PAYMENT_AGENT_CODE));

            $order->setIsNotified(false);
            $order->addStatusHistoryComment(__('Re-authorized successfully.'), false);
            //save reference trading id
            $order->setRefTradingId($order->getIncrementId());

            // change authorization status and reset loop counter
            $data->setReAuthorizationStatus(1);
            $data->setAuthorizedNumber(0);

            //pass payment_review
            $currentState = $order->getState();
            $currentStatus = $order->getStatus();
            if ($currentState == \Magento\Sales\Model\Order::STATE_PAYMENT_REVIEW) {
                $order->setState(\Magento\Sales\Model\Order::STATE_PROCESSING);
                $order->setStatus($currentStatus);
            }

            if ($currentState == \Magento\Sales\Model\Order::STATE_PENDING_PAYMENT) {
                $order->setState(\Magento\Sales\Model\Order::STATE_PROCESSING);
                $order->setStatus(OrderStatus::STATUS_ORDER_NOT_SHIPPED);
            }

            $order->setPaymentStatus(PaymentStatus::PAYMENT_AUTHORIZED);

            //void old transactions
            foreach ($unclosedAuthorizeTransactions as $transaction) {
                try {
                    $this->paygentManagement->voidTransaction($transaction);
                    $dbTransaction->addObject($transaction);
                } catch (\Exception $e) {
                    $order->addStatusHistoryComment($e->getMessage());
                }
            }

            $dbTransaction->addObject($order);

            try {
                $dbTransaction->save();
            } catch (\Exception $e) {
                $this->paygentLogger->addError(__(
                    'Reauthorize order #%1 error: %2',
                    $order->getIncrementId(),
                    $e->getMessage()
                ));
                $this->paygentLogger->critical($e);
            }
        } else {
            $errorCode   = $paymentObject->getResponseCode();
            $errorDetail = $paymentObject->getResponseDetail() ?: 'Others';
            $errorMessage = $this->paygent->getErrorMessageByErrorCode($paymentObject->getResponseDetail());

            $message = sprintf(
                'Re-authorized process has an error. error code is %s, error detail is %s.',
                $errorCode,
                $errorMessage
            );

            $this->paygentLogger->error(__('Reauthorize order #%1 error: %2', $order->getIncrementId(), $message));

            $order->setPaymentErrorCode($errorDetail);
            $order->setPaymentStatus(PaymentStatus::PAYMENT_AUTHORIZED_FAILED);
            $order->setState(Order::STATE_PENDING_PAYMENT);
            $order->addStatusHistoryComment(__($message), OrderStatus::STATUS_ORDER_PENDING_CC);

            $dbTransaction->addObject($order);

            try {
                $dbTransaction->save();
            } catch (\Exception $e) {
                $this->paygentLogger->addError(__(
                    'Reauthorize order #%1 error: %2',
                    $order->getIncrementId(),
                    $e->getMessage()
                ));
                $this->paygentLogger->critical($e);
            }

            //set status re-auth to 0
            $data->setReAuthorizationStatus(0);

            $this->sendAuthorizeFailureMail($order, $errorMessage);
        }

        //Save data after re-auth
        $noAuthorize = $data->getAuthorizedNumber() + 1;
        $data->setAuthorizedNumber($noAuthorize);
        $data->setReAuthorizationDate(time());

        try {
            $data->save();
        } catch (\Exception $e) {
            $this->paygentLogger->critical($e);
        }

        return $this;
    }

    /**
     * @param $paymentId
     * @return \Magento\Sales\Api\Data\TransactionInterface[]
     */
    public function getUnclosedAuthorizeTransactionsByPayment($paymentId)
    {
        $searchCriteria = $this->searchCriteriaBuilder
            ->addFilter('payment_id', $paymentId, 'eq')
            ->addFilter('txn_type', Transaction::TYPE_AUTH, 'eq')
            ->addFilter('is_closed', 1, 'neq')
            ->create();

        $searchResults = $this->transactionRepository->getList($searchCriteria);

        return $searchResults->getItems();
    }

    /**
     * check order can re authorize
     *
     * @param \Magento\Sales\Model\Order $order
     * @return bool
     */
    public function canReAuthorize(\Magento\Sales\Model\Order $order)
    {
        if (!in_array($order->getStatus(), $this->notAllowedReauthorizeStatus)) {
            return true;
        }

        return false;
    }

    /**
     * @param Order $order
     * @param $errorMessage
     * @return $this
     */
    public function sendAuthorizeFailureMail(Order $order, $errorMessage)
    {
        //controlled by Email Marketing Extension
        if (strtoupper($order->getRikiType()) == Type::TYPE_ORDER_SUBSCRIPTION
            || strtoupper($order->getRikiType()) == Type::TYPE_ORDER_DELAY_PAYMENT
        ) {
            $this->reauthorizeFailSubscriptionEmail->send([
                'receiver' => $order->getCustomerEmail(),
                'order' => $order
            ]);
        } else {
            $this->reauthorizeFailEmail->send([
                'receiver' => $order->getCustomerEmail(),
                'order' => $order
            ]);
        }

        $this->reauthorizeFailureBusinessEmail->addItem([
            'errorMessage' => $errorMessage,
            'order' => $order
        ]);

        return $this;
    }
}