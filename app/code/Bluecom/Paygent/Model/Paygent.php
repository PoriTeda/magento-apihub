<?php

namespace Bluecom\Paygent\Model;

use Bluecom\Paygent\Exception\PaygentCaptureException;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Config\Source\Order\Status;
use Magento\Framework\DataObject;
use Magento\Payment\Model\InfoInterface;
use Riki\Loyalty\Model\RewardManagement;

class Paygent extends \Magento\Payment\Model\Method\AbstractMethod
{
    const CODE = 'paygent';
    const DEFAULT_PAYMENT_AGENT = 'NICOS';
    const PAYMENT_AGENT_CODE = 'acq_name';
    const CODE_NEW = 'new_paygent';

    const TELEGRAM_KIND_AUTHORIZE = '020';
    const TELEGRAM_KIND_VOID = '021';

    const SKIP_CALL_CAPTURE_PAYGENT = 'skip_call_capture_paygent';

    protected $_code = self::CODE;
    protected $_paymentType = '02';
    protected $_isGateway = true;
    protected $_isInitializeNeeded = true;
    protected $_canAuthorize = false;
    protected $_canCapture = true;
    protected $_canCapturePartial = false;
    protected $_canRefund = true;
    protected $_canRefundInvoicePartial = true;
    protected $_canCancel = true;
    protected $_canVoid = true;
    protected $_canUseInternal = true;
    protected $_canUseCheckout = true;
    protected $_canFetchTransactionInfo = true;
    protected $_canReviewPayment = true;
    protected $_canUseForMultishipping = false;
    protected $_canSaveCc = false;
    protected $_infoBlockType = 'Bluecom\Paygent\Block\Info';

    /**
     * @var \Riki\Framework\Helper\Cache\FunctionCache
     */
    protected $functionCache;

    /**
     * @var \Magento\Framework\UrlInterface
     */
    protected $urlInterface;
    /**
     * @var \Magento\Customer\Model\Session
     */
    protected $customerSession;
    /**
     * @var \Magento\Eav\Model\Config
     */
    protected $eavConfig;
    /**
     * @var \Magento\Checkout\Model\Session
     */
    protected $checkoutSession;
    /**
     * @var \Magento\Customer\Model\Customer
     */
    protected $customerModel;
    /**
     * @var \Bluecom\Paygent\Logger\Logger
     */
    protected $paygentLogger;
    /**
     * @var Processor\Cclink
     */
    protected $cclink;
    /**
     * @var Processor\Cc
     */
    protected $cc;
    /**
     * @var \Magento\Framework\Message\ManagerInterface
     */
    protected $messageManager;
    /**
     * @var \Magento\Sales\Model\ResourceModel\Order\CollectionFactory
     */
    protected $orderCollection;
    /**
     * @var \Bluecom\Paygent\Helper\Data
     */
    protected $paygentHelper;
    /**
     * @var \Magento\Framework\Stdlib\DateTime\TimezoneInterface
     */
    protected $timezone;

    /**
     * @var \Bluecom\Paygent\Model\Error
     */
    protected $_errorHandling;
    /**
     * @var RewardManagement
     */
    protected $rewardManagement;
    /**
     * @var \Magento\Framework\Stdlib\DateTime\DateTime
     */
    protected $dateTime;
    /**
     * @var \Bluecom\Paygent\Model\HistoryUsed
     */
    protected $historyUsed;
    /**
     * @var \Bluecom\Paygent\Model\PaygentHistory
     */
    protected $paygentHistory;
    /**
     * @var \Bluecom\Paygent\Model\PaygentOption
     */
    protected $paygentOption;
    /**
     * @var \Magento\Customer\Api\CustomerRepositoryInterface
     */
    protected $customerRepository;

    /**
     * @var \Magento\Framework\Api\SearchCriteriaBuilder
     */
    protected $searchCriteriaBuilder;

    /**
     * @var \Magento\Sales\Api\TransactionRepositoryInterface
     */
    protected $transactionRepository;
    /**
     * @var \Bluecom\Paygent\Observer\AuthorizeAfterAssignationSuccess
     */
    protected $authorizeAfterAssignation;
    /**
     * @var \Riki\SpotOrderApi\Helper\CheckRequestApi
     */
    protected $_checkRequestApi;

    /**
     * @var \Riki\DelayPayment\Helper\Data
     */
    protected $helperDelayPayment;

    /**
     * @var \Magento\Framework\App\ResourceConnection
     */
    protected $resourceConnection;

    /**
     * @var \Riki\SubscriptionMachine\Model\MonthlyFeeProfile\Validator
     */
    protected $validatorMonthlyFee;

    /**
     * Paygent constructor.
     * @param \Magento\Framework\Model\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\Api\ExtensionAttributesFactory $extensionFactory
     * @param \Magento\Framework\Api\AttributeValueFactory $customAttributeFactory
     * @param \Magento\Payment\Helper\Data $paymentData
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Payment\Model\Method\Logger $logger
     * @param \Magento\Framework\UrlInterface $urlInterface
     * @param \Magento\Customer\Model\Session $customerSession
     * @param \Magento\Eav\Model\Config $eavConfig
     * @param \Magento\Checkout\Model\Session\Proxy $checkoutSession
     * @param \Magento\Customer\Model\Customer $customerModel
     * @param \Magento\Framework\Message\ManagerInterface $messageManager
     * @param \Bluecom\Paygent\Logger\Logger $paygentLogger
     * @param Processor\Cclink $cclink
     * @param Processor\Cc $cc
     * @param \Magento\Sales\Model\ResourceModel\Order\Collection $orderCollection
     * @param \Bluecom\Paygent\Helper\Data $paygentHelper
     * @param \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezone
     * @param Error $errorHandling
     * @param RewardManagement $rewardManagement
     * @param \Magento\Framework\Stdlib\DateTime\DateTime $dateTime
     * @param HistoryUsed $historyUsed
     * @param PaygentHistory $paygentHistory
     * @param PaygentOption $paygentOption
     * @param \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository
     * @param \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder
     * @param \Magento\Sales\Api\TransactionRepositoryInterface $transactionRepository
     * @param \Riki\Framework\Helper\Cache\FunctionCache $functionCache
     * @param \Bluecom\Paygent\Observer\AuthorizeAfterAssignationSuccess $authorizeAfterAssignation
     * @param \Riki\SpotOrderApi\Helper\CheckRequestApi $checkRequestApi
     * @param \Riki\DelayPayment\Helper\Data $helperDelayPayment
     * @param \Magento\Framework\Model\ResourceModel\AbstractResource|null $resource
     * @param \Magento\Framework\Data\Collection\AbstractDb|null $resourceCollection
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Api\ExtensionAttributesFactory $extensionFactory,
        \Magento\Framework\Api\AttributeValueFactory $customAttributeFactory,
        \Magento\Payment\Helper\Data $paymentData,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Payment\Model\Method\Logger $logger,
        \Magento\Framework\UrlInterface $urlInterface,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Eav\Model\Config $eavConfig,
        \Magento\Checkout\Model\Session\Proxy $checkoutSession,
        \Magento\Customer\Model\Customer $customerModel,
        \Magento\Framework\Message\ManagerInterface $messageManager,
        \Bluecom\Paygent\Logger\Logger $paygentLogger,
        \Bluecom\Paygent\Model\Processor\Cclink $cclink,
        \Bluecom\Paygent\Model\Processor\Cc $cc,
        \Magento\Sales\Model\ResourceModel\Order\Collection $orderCollection,
        \Bluecom\Paygent\Helper\Data $paygentHelper,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezone,
        \Bluecom\Paygent\Model\Error $errorHandling,
        \Riki\Loyalty\Model\RewardManagement $rewardManagement,
        \Magento\Framework\Stdlib\DateTime\DateTime $dateTime,
        \Bluecom\Paygent\Model\HistoryUsed $historyUsed,
        \Bluecom\Paygent\Model\PaygentHistory $paygentHistory,
        \Bluecom\Paygent\Model\PaygentOption $paygentOption,
        \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository,
        \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder,
        \Magento\Sales\Api\TransactionRepositoryInterface $transactionRepository,
        \Riki\Framework\Helper\Cache\FunctionCache $functionCache,
        \Bluecom\Paygent\Observer\AuthorizeAfterAssignationSuccess $authorizeAfterAssignation,
        \Riki\SpotOrderApi\Helper\CheckRequestApi $checkRequestApi,
        \Riki\DelayPayment\Helper\Data $helperDelayPayment,
        \Magento\Framework\App\ResourceConnection $resourceConnection,
        \Riki\SubscriptionMachine\Model\MonthlyFeeProfile\Validator $validatorMonthlyFee,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = []
    )
    {
        parent::__construct(
            $context,
            $registry,
            $extensionFactory,
            $customAttributeFactory,
            $paymentData,
            $scopeConfig,
            $logger,
            $resource,
            $resourceCollection,
            $data
        );
        $this->urlInterface = $urlInterface;
        $this->customerSession = $customerSession;
        $this->eavConfig = $eavConfig;
        $this->checkoutSession = $checkoutSession;
        $this->customerModel = $customerModel;
        $this->messageManager = $messageManager;
        $this->paygentLogger = $paygentLogger;
        $this->cclink = $cclink;
        $this->cc = $cc;
        $this->orderCollection = $orderCollection;
        $this->paygentHelper = $paygentHelper;
        $this->timezone = $timezone;
        $this->paygentLogger->setTimezone(new \DateTimeZone($timezone->getConfigTimezone()));
        $this->_errorHandling = $errorHandling;
        $this->rewardManagement = $rewardManagement;
        $this->dateTime = $dateTime;
        $this->historyUsed = $historyUsed;
        $this->paygentHistory = $paygentHistory;
        $this->paygentOption = $paygentOption;
        $this->customerRepository = $customerRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->transactionRepository = $transactionRepository;
        $this->functionCache = $functionCache;
        $this->authorizeAfterAssignation = $authorizeAfterAssignation;
        $this->_checkRequestApi = $checkRequestApi;
        $this->helperDelayPayment = $helperDelayPayment;
        $this->resourceConnection = $resourceConnection;
        $this->validatorMonthlyFee = $validatorMonthlyFee;
    }

    /**
     * @return Processor\Cc
     */
    public function getProcessorCc()
    {
        return $this->cc;
    }

    /**
     * @return \Bluecom\Paygent\Logger\Logger
     */
    public function getLogger()
    {
        return $this->paygentLogger;
    }

    /**
     * @return HistoryUsed
     */
    public function getHistoryModel()
    {
        return $this->historyUsed;
    }

    /**
     * Get option paygent checkout
     *
     * @return $this
     */
    protected function getOptionPaygent()
    {
        $customerId = $this->customerSession->getCustomerId();
        $currentOption = $this->paygentOption->loadByAttribute('customer_id', $customerId);

        if (!$currentOption->getId()) {
            //Save paygent option
            $data = [
                'customer_id' => $customerId,
                'option_checkout' => 0
            ];
            try {
                $currentOption->setData($data)->save();
            } catch (\Exception $e) {
                $this->logger->critical($e);
            }
        }

        return $currentOption;

    }

    /**
     * Init checkout
     *
     * @param string $paymentAction PaymentAction
     * @param object $stateObject StateOrder
     *
     * @return $this
     *
     * @throws \Exception
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function initialize($paymentAction, $stateObject)
    {
        /* @var \Magento\Sales\Model\Order $order */
        $order = $this->getInfoInstance()->getOrder();
        $amount = floor($order->getGrandTotal());
        if ($this->rewardManagement->isSpecialCase($order->getQuoteId(), $order->getStoreId())) {
            $amount = RewardManagement::VALIDATE_CARD_AMOUNT;
        }
        $payment = $order->getPayment();

        //default for credit card paygent set to PENDING PAYMENT
        $state = \Magento\Sales\Model\Order::STATE_PENDING_PAYMENT;

        $isFromAdmin = $order->getRemoteIp();
        $subscriptionTradingId = $this->_registry->registry('trading_id');

        /**
         * order use ivr paygent from back-end
         * Doesn`t check request for call create spot order api
         *
         */
        if(!$this->_checkRequestApi->checkCallApi())
        {
            if (!$isFromAdmin && !$subscriptionTradingId) {
                //set states and status for new order with paygent used ivr from back end
                $stateObject->setState($state);
                $stateObject->setStatus(\Magento\Sales\Model\Order::STATE_PENDING_PAYMENT);
                $stateObject->setIsNotified(false);
                $order->setUseIvr(1);
                return $this;
            }
        }

        //get paygent option
        $currentOption = $this->getOptionPaygent();
        $paygentOption = $currentOption->getOptionCheckout();

        $lastTradingId = null;
        //if customer choose Paygent Option is pay without redirect
        if (!$paygentOption && !$subscriptionTradingId) {
            //get last trading id
            $customerId = $order->getCustomerId();
            if ($customerId && !$order->getCustomerIsGuest()) {
                //get last trading id of customer
                $profileId = null;
                if ($order->getIsOosOrder() && $order->getProfileId()) {
                    $profileId = $order->getProfileId();
                }
                $lastTradingId = $this->canReAuthorization($customerId, $profileId);
            }
        }
        if (!$lastTradingId && $subscriptionTradingId) {
            $lastTradingId = $subscriptionTradingId;
            // when total order = 0 , set amount = 1 for subscription order
            if ($amount == 0) {
                $amount = 1;
                $order->setBaseGrandTotal(1);
                $order->setGrandTotal(1);
            }
        }

        /**
         * if $lastTradingId is null ,need show error message
         */
        $isGillette = $this->_registry->registry('is_gillette_order');
        $params = [];
        if ($isGillette) {
            $params = [
                'return_url' => $this->_scopeConfig->getValue(
                    'gillette/general/url_redirect'
                )
            ];
        }
        if($this->_checkRequestApi->checkCallApi() && $lastTradingId==null and !$isGillette)
        {
            throw new \Magento\Framework\Exception\LocalizedException(__('The Trading ID is not valid'));
        }

        // have trading id , ready for authorize without redirect
        if ($lastTradingId) {

            $authorizationData = [
                'payment' => $payment,
                'amount' => $amount,
                'lastTradingId' => $lastTradingId
            ];

            $this->authorizeAfterAssignation->setAuthorizeData($order->getIncrementId(), $authorizationData);

            $this->_eventManager->dispatch('paygent_init_authorization_data_after', [
                'order' => $order,
                'authorization_data'   =>  $authorizationData
            ]);

            if($stateObject->getStatus() != \Riki\Sales\Model\ResourceModel\Order\OrderStatus::STATUS_ORDER_IN_PROCESSING
            && !$this->validatorMonthlyFee->isMonthlyFeeProfile($order->getProfileId())
            ) {
                $stateObject->setState(\Magento\Sales\Model\Order::STATE_PROCESSING);
                $stateObject->setStatus(\Riki\Sales\Model\ResourceModel\Order\OrderStatus::STATUS_ORDER_NOT_SHIPPED);
            }
            $stateObject->setIsNotified(false);

            return $this;
        } else {
            //init redirect link of paygent
            for ($i = 0; $i < 10; $i++) {
                $res = $this->initRedirectLink($order->getIncrementId(), $amount, $params);
                if ($res['result'] == 0) {
                    //set payment info
                    $payment->setPaygentLimitDate($res['limit_date']);
                    $payment->setPaygentUrl($res['url']);

                    //set states and status for new order with paygent
                    $stateObject->setState($state);
                    $stateObject->setStatus(\Magento\Sales\Model\Order::STATE_PENDING_PAYMENT);
                    $stateObject->setIsNotified(false);
                    //send new order transactional email for Paygent only when Paygent sent back with success.
                    $order->setCanSendNewEmailFlag(false);

                    //redirect url received from paygent
                    $currentOption->setData('link_redirect', $res['url']);
                    try {
                        $currentOption->save();
                    } catch (\Exception $e) {
                        $this->logger->critical($e);
                    }

                    return $this;
                } else {
                    $errorCode = $res['response_code'];
                    $errorDetail = $res['response_detail'];
                    if ($errorCode == 'P010') {
                        //P010 fetch new increment id when transaction is exists
                        $quote = $this->checkoutSession->getQuote();
                        $quote->reserveOrderId();
                        $order->setIncrementId($quote->getReservedOrderId());
                        continue 1;
                    } else {
                        $message = sprintf(
                            'Authorization process has an error. error code is %s, error detail is %s.',
                            $errorCode,
                            $errorDetail
                        );
                        throw new \Magento\Framework\Exception\LocalizedException(__($message));
                    }
                }
            }
        }

        return $this;
    }

    /**
     * Init link redirect to Paygent for first Order with Paygent
     *
     * @param string $tradingId TradingId
     * @param int $amount Amount
     * @param array $params
     *
     * @return array
     */
    public function initRedirectLink($tradingId, $amount, $params = [])
    {
        $obj = new DataObject();
        $obj->setTradingId($tradingId);
        $obj->setPaymentType($this->_paymentType);
        $obj->setId($amount);
        $obj->setSeqMerchantId($this->getConfigData('merchant_id'));
        $obj->setMerchantName(mb_convert_kana($this->getConfigData('merchant_name'), 'ASKV', 'utf-8'));
        $obj->setPaymentDetail($this->getConfigData('payment_detail'));
        $obj->setPaymentDetailKana(mb_convert_kana($this->getConfigData('payment_detail_kana'), 'rnkh', 'utf-8'));
        $obj->setPaymentTermDay($this->getConfigData('payment_term_day'));
        if (isset($params['return_url'])) {
            $returnUrl = $params['return_url'];
        } else {
            $returnUrl = $this->urlInterface->getUrl(
                'checkout/onepage/success',
                ['_query' => ['_' => time() . mt_rand(0, 99999)]]
            );
        }
        $obj->setReturnUrl($returnUrl);
        $obj->setIsbtob('1');
        if (isset($params['inform_url'])) {
            $informUrl = $params['inform_url'];
        } else {
            $informUrl = $this->urlInterface->getUrl('paygent/paygent/response');
        }
        $obj->setInformUrl($this->_scopeConfig->getValue('payment/paygent/use_http_inform')
            ? str_replace('https', 'http', $informUrl)
            : $informUrl
        );
        $obj->setPaymentClass($this->getConfigData('paymentclass'));
        $obj->setUseCardConfNumber($this->getConfigData('use_cvv'));
        $obj->setThreedsecureRyaku($this->getConfigData('use_3dsecure'));

        //generate Hash string
        $obj->setHc($this->paygentHelper->generateHash($obj));
        //send request to paygent and get link type redirect
        $result = $this->paygentHelper->executeCallPaygent($obj);

        return $result;

    }

    /**
     * Get trading data of customer
     *
     * @param $customerId
     *
     * @return mixed
     */
    private function getPaygentCustomerData($customerId)
    {
        $customer = $this->customerRepository->getById($customerId);

        if ( $customer->getCustomAttribute('paygent_transaction_id') ) {
            return $customer->getCustomAttribute('paygent_transaction_id')->getValue();
        }
        return false;
    }

    /**
     * Check customer has used paygent method and return last trading id
     *
     * @param $customerId
     * @param $profileId
     *
     * @return bool
     */
    public function canReAuthorization($customerId, $profileId = null)
    {
        $collection = $this->paygentHistory->getCollection()
            ->addFieldToFilter('customer_id', ['eq' => $customerId])
            //->addFieldToFilter('order_number', ['neq' => ''])
            ->addFieldToFilter('type', ['eq' => 'authorize'])
            ->setOrder('id', 'desc')
            ->setPageSize(1);

        // In case of Oos Order, filter by profileId
        if ($profileId) {
            $collection->addFieldToFilter('profile_id', ['eq' => $profileId]);
        }

        if (!$collection->getSize()) {
            return $this->getPaygentCustomerData($customerId);
        }
        return $collection->getFirstItem()->getTradingId();
    }

    /**
     * Authorize Order with Paygent without redirect to paygent
     *
     * @param InfoInterface $payment InfoInterface
     * @param int $amount Amount
     * @param string $lastTradingId LastTradingId
     *
     * @return bool
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function authorizeWithoutRedirect(InfoInterface $payment, $amount, $lastTradingId)
    {
        $this->checkoutSession->setCentinel('');
        $order = $payment->getOrder();

        $processor = $this->cc->init();
        $processor->setParam("telegram_kind", "020");
        $processor->setParam("payment_amount", $amount);
        $processor->setParam("trading_id", preg_replace('/\-/', '_', $order->getRealOrderId()));
        $processor->setParam('ref_trading_id', $lastTradingId);
        // pay one time , 10 => one time , 61 => split count
        //currently in RIKI we have no to use split pay :D
        $processor->setParam("payment_class", '10');

        $processor->setParam("3dsecure_ryaku", 1);

        $result = $processor->process();
        $_result = $processor->getResult();
        $paymentObject = $processor->getPaymentObject();
        $status = null;

        if ($result == "1") {
            $status = $paymentObject->getResultStatus();
        } else {
            throw new \Magento\Framework\Exception\LocalizedException(__(sprintf('Network Error, %s', $result)));
        }

        if ($status === "0") {
            $payment->setCcTransId($_result["payment_id"]);
            $payment->setTransactionId($_result["payment_id"]);
            $payment->setIsTransactionClosed(false);
            $payment->registerAuthorizationNotification($amount);
            $payment->accept();

            $order->setIsNotified(false);
            $order->addStatusHistoryComment(__('System automatically call to Paygent API and make authorized'), false);
            //save reference trading id
            $order->setRefTradingId($lastTradingId);
            $order->setPaymentStatus(\Riki\ArReconciliation\Model\ResourceModel\Status\PaymentStatus::PAYMENT_AUTHORIZED);

            if ($this->validatorMonthlyFee->isMonthlyFeeProfile($order->getProfileId())) {
                $order->setState(\Magento\Sales\Model\Order::STATE_PROCESSING);
                $order->setStatus(\Riki\Sales\Model\ResourceModel\Order\OrderStatus::STATUS_ORDER_NOT_SHIPPED);
            }

            $paymentAgentCode = self::PAYMENT_AGENT_CODE;
            $paymentAgentValue = isset($_result[$paymentAgentCode]) ? $_result[$paymentAgentCode] : self::DEFAULT_PAYMENT_AGENT;

            $quote = $this->checkoutSession->getQuote();
            if ($this->helperDelayPayment->checkOrderDelayPayment($order, $quote)) {
                $paymentAgentValue = $this->helperDelayPayment->convertPaymentAgentDelayPayment($paymentAgentValue);
            }

            if (!$payment->getAdditionalInformation($paymentAgentCode)) {
                $payment->setAdditionalInformation($paymentAgentCode, $paymentAgentValue);
            }
            $order->setPaymentAgent($payment->getAdditionalInformation($paymentAgentCode));

            $profileId = $order->getSubscriptionProfileId() ? $order->getSubscriptionProfileId() : $order->getProfileId();

            //Save paygent history used
            $paygentHistory = [
                'customer_id' => $order->getCustomerId(),
                'order_number' => $order->getIncrementId(),
                'profile_id' => $profileId,
                'trading_id' => $order->getIncrementId(),
                'used_date' => $this->timezone->formatDateTime($this->dateTime->gmtDate(), 2),
                'type' => 'authorize',
                'payment_agent' => $paymentAgentValue
            ];
            //save history used
            $this->historyUsed->savePaygentHistory($paygentHistory);
            return true;

        } else {
            $this->paygentLogger->info(\Zend_Json::encode($_result));

            //send email notify when refund fail
            $configs = $this->paygentHelper->getConfigSendMailFail();

            $errorCode = $paymentObject->getResponseCode();
            $errorDetail = $paymentObject->getResponseDetail();

            //get error code from table error handling
            $paymentCodeErrorDefault = $errorMessageHandling = $errorDetail;
            if ($errorDetail != '') {
                $errorMessageHandling = $this->paygentHelper->getPaymentErrorCodeHandling($errorDetail);
                if ($errorMessageHandling === null) {
                    //default payment error code
                    $errorMessageHandling = $this->paygentHelper->getPaymentErrorCodeHandling('Others');
                    $paymentCodeErrorDefault = 'Others';
                }
            }
            $vars = $this->paygentHelper->setVariablesEmail($order, $errorMessageHandling);

            //save payment error code
            $order->setPaymentErrorCode($paymentCodeErrorDefault);
            $order->setPaymentErrorMessage($errorMessageHandling);
            $order->setPaymentStatus(\Riki\ArReconciliation\Model\ResourceModel\Status\PaymentStatus::PAYMENT_AUTHORIZED_FAILED);
            if($this->_scopeConfig->getValue('paygent_config/paygent_fail/enable'))
            {
                if (isset($configs['recipients'])) {
                    try {
                        foreach ($configs['recipients'] as $email) {
                            $this->paygentHelper->sendMailNotify($email, $configs['senderInfo'], $configs['templateEmail'], $vars);
                        }
                    } catch (\Exception $e) {
                        $this->_logger->critical($e);
                    }
                }
            }
            $message = sprintf(
                'Authorization process has an error. error code is %s, error detail is %s.',
                $errorCode,
                $errorDetail
            );
            $order->addStatusHistoryComment(__($message), false);
            return false;
        }
    }

    /**
     * Set centinel html
     *
     * @param string $html Html
     *
     * @return bool
     */
    protected function setCentinelHtml($html)
    {
        $this->checkoutSession->setCentinel($html);
        return true;
    }

    /**
     * Get centinel html
     *git
     * @return mixed
     */
    public function getCentinelHtml()
    {
        return $this->checkoutSession->getCentinel();
    }

    /**
     * Set IsInitialize Needed
     *
     * @param boolean $isInitializeNeeded IsInitialize
     *
     * @return Paygent
     */
    public function setIsInitializeNeeded($isInitializeNeeded)
    {
        $this->_isInitializeNeeded = $isInitializeNeeded;
        return $this;
    }


    /**
     * Fetch Transaction Info
     *
     * @param InfoInterface $payment InfoInterface
     * @param string $transactionId TransactionId
     *
     * @return array
     */
    public function fetchTransactionInfo(InfoInterface $payment, $transactionId)
    {
        $data = parent::fetchTransactionInfo($payment, $transactionId);
        $processor = $this->cclink->init();

        //telegram 094 for get Association info
        $processor->setParam('telegram_kind', '094');
        $processor->setParam('payment_id', $transactionId);

        $this->paygentLogger->info('Get Paygent information for transaction id '.$transactionId);

        if ($processor->process()) {

            $this->paygentLogger->info('Get Paygent information for transaction id '.$transactionId.': processing');

            $paymentObject = $processor->getPaymentObject();

            if ($paymentObject->getResultStatus() == 0) {
                $result = $processor->getResult();
                $this->paygentLogger->info('Get Paygent information for transaction id '.$transactionId.': '.\Zend_Json::encode($result));

                unset($result['code']);
                unset($result['detail']);
                unset($result['result']);

                $data = array_merge($data, $result);

                foreach (array('acq_id', 'acq_name') as $code) {
                    if (isset($result[$code]) && !$payment->getAdditionalInformation($code)) {

                        if ($code == 'acq_name') {
                            $order = $payment->getOrder();

                            if ($this->helperDelayPayment->checkOrderDelayPayment($order)) {
                                $result[$code] = $this->helperDelayPayment->convertPaymentAgentDelayPayment($result[$code]);
                            }
                            $order->setPaymentAgent($result[$code])->save();
                            $data['acq_name'] = $result[$code];
                        }
                        $payment->setAdditionalInformation($code, mb_convert_encoding($result[$code], 'UTF-8'));
                    }
                }
            }
        }

        return $data;
    }

    /**
     * Void the transaction when order is canceled
     *
     * @param InfoInterface $payment InfoInterface
     *
     * @return Paygent|bool
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function cancel(InfoInterface $payment)
    {
        return $this->void($payment);
    }

    /**
     * Void the transaction when order is canceled
     *
     * @param InfoInterface $payment InfoInterface
     *
     * @return $this|bool
     */
    public function void(InfoInterface $payment)
    {
        $order = $payment->getOrder();

        $orderIncrement = str_replace('-','',$order->getIncrementId());
        // Check reorder edit
        if (!$payment->getCcTransId()) {
            return false;
        }

        $processor = $this->cclink->init();

        //telegram 021 for cancel authorize
        $processor->setParam('telegram_kind', '021');
        $processor->setParam('payment_id', $payment->getCcTransId());
        $processor->setParam('trading_id', $orderIncrement);

        $result = $processor->process();
        $processor->getResult();
        $paymentObject = $processor->getPaymentObject();

        $status = null;

        if ($result == '1') {
            $status = $paymentObject->getResultStatus();
        } else {
            $this->messageManager->addError(__('Network Error.'));
            throw new \Magento\Framework\Exception\LocalizedException(__('Network Error.'));
        }

        if ($status == '0') {
            $payment->setIsTransactionClosed(true);
        } else {
            $errorCode = $paymentObject->getResponseCode();
            $errorDetail = $paymentObject->getResponseDetail();

            $message = sprintf(
                'Order void failure. error code is %s, error detail is %s',
                $errorCode,
                mb_convert_encoding($errorDetail, 'utf-8', 'sjis')
            );
            $this->messageManager->addError(__($message));
            $order->addStatusHistoryComment(__($message), false);
        }

        return true;
    }

    /**
     * Capture payment method
     *
     * @param InfoInterface $payment InfoInterface
     * @param float $amount Amount
     *
     * @return $this|bool
     * @throws Exception
     */
    public function capture(InfoInterface $payment, $amount)
    {
        /** @var Order $order */
        $order = $payment->getOrder();

        if ($order->getData(self::SKIP_CALL_CAPTURE_PAYGENT)) {
            $payment->setIsTransactionClosed(true);
            return $this;
        }

        $processor = $this->cclink->init();
        if (strpos($this->_scopeConfig->getValue('web/secure/base_url'), 'preprod') !== false) {
            // Reload config merchant_id in processor
            $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
            $connection = $this->resourceConnection->getConnection();
            $table = $connection->getTableName('core_config_data');
            $path = "payment/paygent/merchant_id";
            $sql = "SELECT * FROM $table WHERE path = '$path' LIMIT 1";
            $data = $connection->fetchRow($sql);
            if (!empty($data) && isset($data['value']) && !empty($data['value'])) {
                $merchantId = $data['value'];
                $processor->setParam("merchant_id", $merchantId);
            }
        }
        $processor->setParam('telegram_kind', '022');
        $processor->setParam('payment_id', $payment->getCcTransId());
        $processor->setParam('trading_id', $order->getIncrementId());

        $this->paygentLogger->info('Start capture Paygent');
        $result        = $processor->process();
        $this->paygentLogger->info('End capture Paygent');
        $_result       = $processor->getResult();
        $paymentObject = $processor->getPaymentObject();

        // write log tracking capture
        $this->paygentLogger->info('Capture #'. $order->getIncrementId());
        $this->paygentLogger->info('Result : '. $paymentObject->getResultStatus());

        $status = null;

        if ($result == '1') {
            $status = $paymentObject->getResultStatus();
        } else {
            $this->messageManager->addError(__('Network Error.'));
            throw new \Magento\Framework\Exception\LocalizedException(__('Network Error.'));
        }

        if ($status == '0') {
            //set closed transaction after captured success
            $payment->setIsTransactionClosed(true);

            $paymentAgentCode = self::PAYMENT_AGENT_CODE;
            $paymentAgentValue = isset($_result[$paymentAgentCode]) ? $_result[$paymentAgentCode] : self::DEFAULT_PAYMENT_AGENT;

            if ($this->helperDelayPayment->checkOrderDelayPayment($order)) {
                $paymentAgentValue = $this->helperDelayPayment->convertPaymentAgentDelayPayment($paymentAgentValue);
            }

            //Save paygent history used
            $paygentHistory = [
                'customer_id' => $order->getCustomerId(),
                'order_number' => $order->getIncrementId(),
                'profile_id' => $order->getSubscriptionProfileId(),
                'trading_id' => $order->getIncrementId(),
                'used_date' => $this->timezone->formatDateTime($this->dateTime->gmtDate(), 2),
                'type' => 'capture',
                'payment_agent' => $paymentAgentValue
            ];
            //save history used
            $this->historyUsed->savePaygentHistory($paygentHistory);

            // write log tracking capture success
            $this->paygentLogger->info('Capture Success # : '. $order->getIncrementId());

            return $this;
        } else {

            // write log tracking capture
            $this->paygentLogger->info('Capture Failed # : '. $order->getIncrementId());

            $errorCode = $paymentObject->getResponseCode();
            $errorDetail = $paymentObject->getResponseDetail();

            $message = sprintf(
                'Order capture failure. error code is %s, error detail is %s',
                $errorCode,
                mb_convert_encoding($errorDetail, 'utf-8', 'sjis')
            );
            $order->setPaymentStatus(\Riki\ArReconciliation\Model\ResourceModel\Status\PaymentStatus::PAYMENT_CAPTURE_FAILED);
            $order->setState(\Magento\Sales\Model\Order::STATE_PROCESSING);
            $order->setStatus(\Riki\Sales\Model\ResourceModel\Order\OrderStatus::STATUS_ORDER_CAPTURE_FAILED);
            $order->addStatusHistoryComment(__($message), false);

            $this->messageManager->addError(__($message));

            $this->paygentHelper->sendCaptureFailedMail($order, $errorDetail);

            // write log
            $this->paygentLogger->info($message);

            $this->_eventManager->dispatch('paygent_capture_failed_after', [
                'order' =>  $order,
                'error_message' =>  $errorDetail
            ]);

            throw new PaygentCaptureException(__($message));
        }
        return $this;
    }

    /**
     * Refund
     *
     * @param InfoInterface $payment InfoInterface
     * @param float $amount Amount
     *
     * @return $this|bool
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function refund(InfoInterface $payment, $amount)
    {
        parent::refund($payment, $amount);

        $order = $payment->getOrder();

        $processor = $this->cclink->init();

        $isFullRefund = true;
        $amountRefunded = $order->getBaseTotalRefunded();
        $amountPaid = $order->getBaseTotalPaid();
        if ($amountRefunded < $amountPaid) {
            $isFullRefund = false;
        }

        if ($isFullRefund) {
            //telegram 023 for full refund
            $processor->setParam('telegram_kind', '023');
        } else {
            //telegram 029 for full refund invoice partial
            $processor->setParam('telegram_kind', '029');
            $processor->setParam('reduction_flag', '1');
            $processor->setParam('payment_amount', $amount);
        }

        $processor->setParam('payment_id', $payment->getCcTransId());
        $processor->setParam('trading_id', $order->getIncrementId());

        $result = $processor->process();
        $_result = $processor->getResult();
        $paymentObject = $processor->getPaymentObject();
        $status = null;

        //write log response
        $this->paygentLogger->info(\Zend_Json::encode($_result));

        if ($result == '1') {
            $status = $paymentObject->getResultStatus();
        } else {
            $this->messageManager->addError(__('Network Error.'));
            throw new \Magento\Framework\Exception\LocalizedException(__('Network Error.'));
        }

        if ($status == '0') {
            $payment->setCcTransId($_result['payment_id']);
            $payment->setTransactionId($_result['payment_id']);
            $payment->setIsTransactionClosed(true);

            $paymentAgentCode = self::PAYMENT_AGENT_CODE;
            $paymentAgentValue = isset($_result[$paymentAgentCode]) ? $_result[$paymentAgentCode] : self::DEFAULT_PAYMENT_AGENT;

            /** change payment agent for delay payment */
            if ($this->helperDelayPayment->checkOrderDelayPayment($order)) {
                $paymentAgentValue = $this->helperDelayPayment->convertPaymentAgentDelayPayment($paymentAgentValue);
            }

            //Save paygent history used
            $paygentHistory = [
                'customer_id' => $order->getCustomerId(),
                'order_number' => $order->getIncrementId(),
                'profile_id' => $order->getSubscriptionProfileId(),
                'trading_id' => $order->getIncrementId(),
                'used_date' => $this->timezone->formatDateTime($this->dateTime->gmtDate(), 2),
                'type' => 'refund',
                'payment_agent' => $paymentAgentValue
            ];
            //save history used
            $this->historyUsed->savePaygentHistory($paygentHistory);
            return $this;
        } else {
            $errorCode = $paymentObject->getResponseCode();
            $errorDetail = $paymentObject->getResponseDetail();

            if($this->_scopeConfig->getValue('paygent_config/refund/email_enable')) {
                //send email notify when refund fail
                $recipients = array_filter(explode(";", $this->_scopeConfig->getValue('paygent_config/refund/receiver')), 'trim');

                $senderInfo = $this->_scopeConfig->getValue('paygent_config/refund/identity');
                $templateEmail = $this->_scopeConfig->getValue('paygent_config/refund/email_template');

                $vars = [
                    'orderIncrement' => $order->getIncrementId(),
                    'description' => 'Refund failure',
                    'paygent_rejection_code' => $errorCode
                ];
                $variables = new \Magento\Framework\DataObject($vars);
                $this->_eventManager->dispatch('refund_reject_adjust_data_mail_notify', ['vars' => $variables]);
                foreach ($recipients as $email) {
                    $this->paygentHelper->sendMailNotify($email, $senderInfo, $templateEmail, $variables->getData());
                }
            }

            $message = sprintf(
                'Order refund failure. error code is %s, error detail is %s',
                $errorCode,
                mb_convert_encoding($errorDetail, 'utf-8', 'sjis')
            );

            throw new \Bluecom\Paygent\Exception\PaygentRefundException(__($message));
        }
    }

    /**
     * Void a transaction by order
     *
     * @param Order $order
     * @param $transactionId
     *
     * @return bool
     *
     * @throws \Exception
     */
    public function voidByTransactionId(\Magento\Sales\Model\Order $order, $transactionId)
    {
        $processor = $this->cclink->init();
        $payment = $order->getPayment();

        //telegram 021 for cancel authorize
        $processor->setParam('telegram_kind', '021');
        $processor->setParam('payment_id', $transactionId);
        $processor->setParam('trading_id', $order->getIncrementId());

        $result = $processor->process();
        $processor->getResult();
        $paymentObject = $processor->getPaymentObject();

        $status = null;

        if ($result == '1') {
            $status = $paymentObject->getResultStatus();
        }
        if ($status == '0') {
            //close old transaction
            $paymentId = $payment->getId();

            $searchCriteria = $this->searchCriteriaBuilder
                ->addFilter('payment_id', $paymentId, 'eq')
                ->addFilter('txn_type', 'authorization', 'eq')
                ->addFilter('txn_id', $transactionId, 'eq')
                ->create();
            $searchResults = $this->transactionRepository->getList($searchCriteria);
            if ($searchResults->getTotalCount()) {
                foreach ($searchResults->getItems() as $trans) {
                    $trans->setIsClosed(1);
                    $trans->save();
                }
            }

            $order->setIsNotified(false);
            $order->addStatusHistoryComment(__('Cancel unnecessary authorization successfully.'), false);
            return true;
        } else {
            $errorCode = $paymentObject->getResponseCode();
            $errorDetail = $paymentObject->getResponseDetail();
            $message = sprintf(
                'Order %s,transaction %s void failure after authorized. Error code is %s, error detail is %s',
                $order->getIncrementId(),
                $transactionId,
                $errorCode,
                mb_convert_encoding($errorDetail, 'utf-8', 'sjis')
            );
            $order->setIsNotified(false);
            $order->addStatusHistoryComment($message, false);
            $this->paygentLogger->info($message);
        }
        try {
            $order->save();
        } catch (\Exception $e) {
            $this->paygentLogger->critical($e);
            throw $e;
        }

        return false;
    }

    /**
     * Get error message
     *
     * @param $errorCode
     * @param string $type
     *
     * @return string
     */
    public function getErrorMessageByErrorCode($errorCode, $type = 'backend_message')
    {
        $cacheKey = [$errorCode, $type];
        if ($this->functionCache->has($cacheKey)) {
            return $this->functionCache->load($cacheKey);
        }

        $result = $errorCode;
        $collection = $this->_errorHandling->getCollection()
            ->addFieldToFilter('error_code', ['in' => [trim($errorCode, " \t\n\r\0\x0B\""), 'Others']])
            ->setPageSize(2);

        foreach ($collection->getItems() as $error) {
            if ($error->getData('error_code') != 'Others') {
                $result = $error->getData($type);
                break;
            }

            $result = $error->getData($type);
        }

        $this->functionCache->store($result, $cacheKey);

        return $result;
    }
}