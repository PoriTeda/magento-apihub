<?php

namespace Bluecom\Paygent\Helper;

use Bluecom\Paygent\Model\Email\ReauthorizeFailure as ReauthorizeFailureEmail;
use Magento\Sales\Model\Order;

class Data extends \Magento\Framework\App\Helper\AbstractHelper
{
    const CONFIG_MODULE_ACTIVE = 'paygent_config/generalconfig/active';
    const CONFIG_SEND_EMAIL_ACTIVE = 'paygent_config/generalconfig/active_send_email';
    const CONFIG_SENDER_EMAIL = 'paygent_config/generalconfig/identity';
    const CONFIG_TEMPLATE_EMAIL = 'paygent_config/generalconfig/template_pending_payment';
    const CONFIG_NEW_ORDER_STATES = 'paygent_config/generalconfig/statuslist';
    const CONFIG_CRON_EXPRESSION = 'paygent_config/generalconfig/cancellation_exp';
    const CONFIG_CANCEL_AFTER_X_HOURS = 'paygent_config/generalconfig/cancelhours';

    /**
     * @var \Magento\Framework\Mail\Template\TransportBuilder
     */
    protected $transportBuilder;

    /**
     * @var \Magento\Framework\Translate\Inline\StateInterface
     */
    protected $inlineTranslate;

    /**
     * Store manager.
     *
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var \Bluecom\Paygent\Logger\Logger
     */
    protected $paygentLogger;

    /**
     * @var \Magento\Framework\Stdlib\DateTime\TimezoneInterface
     */
    protected $timezone;

    /**
     * @var \Magento\Framework\Stdlib\DateTime\DateTime
     */
    protected $dateTime;

    /**
     * @var \Magento\Quote\Api\CartRepositoryInterface
     */
    protected $quoteRepository;

    /**
     * @var \Riki\Subscription\Helper\Hanpukai\Data
     */
    protected $_hanpukaiHelper;

    /**
     * @var \Magento\Framework\UrlInterface
     */
    protected $urlInterface;
    /**
     * @var \Magento\Framework\HTTP\ZendClientFactory
     */
    protected $clientFactory;

    /**
     * @var \Bluecom\Paygent\Model\Error
     */
    private $paygentError;

    /**
     * @var ReauthorizeFailureEmail
     */
    protected $reauthorizeFailureEmail;

    /**
     * @var \Bluecom\Paygent\Model\PaygentFactory
     */
    protected $paygentFactory;

    /**
     * Data constructor.
     * @param \Magento\Framework\App\Helper\Context $context
     * @param \Magento\Framework\Mail\Template\TransportBuilder $transportBuilder
     * @param \Magento\Framework\Translate\Inline\StateInterface $inlineTranslate
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Bluecom\Paygent\Logger\Logger $paygentLogger
     * @param \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezone
     * @param \Magento\Framework\Stdlib\DateTime\DateTime $dateTime
     * @param \Magento\Quote\Api\CartRepositoryInterface $quoteRepository
     * @param \Riki\Subscription\Helper\Hanpukai\Data $helperHanpukai
     * @param \Magento\Framework\HTTP\ZendClientFactory $clientFactory
     * @param \Bluecom\Paygent\Model\Error $paygentError
     * @param ReauthorizeFailureEmail $reauthorizeFailureEmail
     */
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Framework\Mail\Template\TransportBuilder $transportBuilder,
        \Magento\Framework\Translate\Inline\StateInterface $inlineTranslate,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Bluecom\Paygent\Logger\Logger $paygentLogger,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezone,
        \Magento\Framework\Stdlib\DateTime\DateTime $dateTime,
        \Magento\Quote\Api\CartRepositoryInterface $quoteRepository,
        \Riki\Subscription\Helper\Hanpukai\Data $helperHanpukai,
        \Magento\Framework\HTTP\ZendClientFactory $clientFactory,
        \Bluecom\Paygent\Model\Error $paygentError,
        ReauthorizeFailureEmail $reauthorizeFailureEmail,
        \Bluecom\Paygent\Model\PaygentFactory $paygentFactory
    ) {
        $this->transportBuilder = $transportBuilder;
        $this->inlineTranslate = $inlineTranslate;
        $this->storeManager = $storeManager;
        $this->paygentLogger = $paygentLogger;
        $this->timezone = $timezone;
        $this->paygentLogger->setTimezone(new \DateTimeZone($timezone->getConfigTimezone()));
        $this->dateTime = $dateTime;
        $this->quoteRepository = $quoteRepository;
        $this->_hanpukaiHelper = $helperHanpukai;
        $this->urlInterface = $context->getUrlBuilder();
        $this->clientFactory = $clientFactory;
        $this->paygentError = $paygentError;
        $this->reauthorizeFailureEmail = $reauthorizeFailureEmail;
        $this->paygentFactory = $paygentFactory;
        parent::__construct($context);
    }

    /**
     * Get new states of order value config.
     *
     * @return array
     */
    public function getStatesConfig()
    {
        $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_STORE;
        $dataConfig = $this->scopeConfig->getValue(self::CONFIG_NEW_ORDER_STATES, $storeScope);
        $data = trim($dataConfig, ',');
        $states = explode(',', $data);

        return $states;
    }

    /**
     * Get Cron expression value config.
     *
     * @return mixed
     */
    public function getCronExpression()
    {
        $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_STORE;
        $cronExpression = $this->scopeConfig->getValue(self::CONFIG_CRON_EXPRESSION, $storeScope);

        return $cronExpression;
    }

    /**
     * Check whether or not the module output is enabled in Configuration.
     *
     * @return mixed
     */
    public function isEnable()
    {
        return $this->getConfigValue(
            self::CONFIG_MODULE_ACTIVE
        );
    }

    /**
     * Check whether or not send email after cancel order
     *
     * @return mixed
     */
    public function isEnableSendEmail() {
        return $this->getConfigValue(
            self::CONFIG_SEND_EMAIL_ACTIVE
        );
    }

    /**
     * Get senders which send warning email
     *
     * @return mixed
     */
    public function getSenderEmail()
    {
        return $this->getConfigValue(
            self::CONFIG_SENDER_EMAIL
        );
    }

    public function getTemplateEmail()
    {
        return $this->getConfigValue(
            self::CONFIG_TEMPLATE_EMAIL
        );
    }

    public function getConfigValue($path)
    {
        return $this->scopeConfig->getValue(
            $path,
            \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE
        );
    }

    /**
     * Get cancel hours value config.
     *
     * @return mixed
     */
    public function getCancelHours()
    {
        $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_STORE;
        $hours = $this->scopeConfig->getValue(self::CONFIG_CANCEL_AFTER_X_HOURS, $storeScope);

        return $hours;
    }

    /**
     * Send Email.
     *
     * @param string $email      Email
     * @param string $senderInfo Sender
     * @param string $template   Template
     * @param string $variables  Variables
     *
     * @return bool
     */
    public function sendMailNotify($email, $senderInfo, $template, $variables)
    {
        /**
         * Validate Email
         */
        if (!\Zend_Validate::is($email, 'EmailAddress')) {
            return false;
        }

        /* controlled by Email Marketing */
        /* Email: Error Capture Credit Card (Business user) */
        $this->inlineTranslate->suspend();
        $this->transportBuilder->setTemplateIdentifier($template)
            ->setTemplateOptions(
                [
                    'area' => \Magento\Framework\App\Area::AREA_FRONTEND,
                    'store' => $this->storeManager->getStore()->getId(),
                ]
            )
            ->setTemplateVars($variables)
            ->setFrom($senderInfo)
            ->addTo($email);

        $transport = $this->transportBuilder->getTransport();
        $transport->sendMessage();
        $this->inlineTranslate->resume();

        return true;
    }

    /**
     * Handle response received from paygent.
     *
     * @param string $body Body
     *
     * @return array
     *
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function parseResponseBodyPaygent($body)
    {
        if (strlen($body) == 0) {
            throw new \Magento\Framework\Exception\LocalizedException(__('Could not retrive response body. Please contact us.'));
        }

        $_results = preg_split("/(\r\n)/", $body);
        $returnValue = [];

        foreach ($_results as $data) {
            if (preg_match('/.+=/', $data)) {
                list($key, $value) = explode('=', $data, 2);
                $returnValue[$key] = trim($value);
            }
        }

        return $returnValue;
    }

    /**
     * Generate Hash string for paygent.
     *
     * @param object $obj Object
     *
     * @return string
     */
    public function generateHash($obj)
    {
        $str = $obj->getTradingId().
            //$obj->getEfTradingId().
            $obj->getPaymentType().
            $obj->getId().
            $obj->getInformUrl().
            $obj->getSeqMerchantId().
            $obj->getPaymentTermDay().
            $obj->getPaymentClass().
            $obj->getUseCardConfNumber().
            $obj->getCustomerId().
            $obj->getThreedsecureRyaku().
            $this->scopeConfig->getValue('payment/paygent/hash_key');

        $hashStr = hash('sha256', $str);

        // create random string
        $randStr = '';
        $randChar = ['a', 'b', 'c', 'd', 'e', 'f', 'A', 'B', 'C', 'D', 'E', 'F', '0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];
        for ($i = 0; ($i < 20 && rand(1, 10) != 10); ++$i) {
            $randStr .= $randChar[rand(0, count($randChar) - 1)];
        }

        return $hashStr.$randStr;
    }

    /**
     * Request call API Paygent and get link redirect for credit card link.
     *
     * @param array $data Data
     *
     * @return array
     *
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Zend_Http_Client_Exception
     */
    public function executeCallPaygent($data)
    {
        $returnValue = [];
        $client = $this->clientFactory->create();
        if ($this->scopeConfig->getValue('payment/paygent/sandbox_flag')) {
            $client->setUri($this->scopeConfig->getValue('payment/paygent/test_connect_url'));
        } else {
            $client->setUri($this->scopeConfig->getValue('payment/paygent/connect_url'));
        }

        foreach ((array) $data as $k => $v) {
            $client->setParameterPost($v);
        }
        //send http request to paygent
        $response = $client->request('POST');

        if ($response->isError() && !$response->getBody()) {
            $returnValue['result'] = '0';
            $returnValue['response_code'] = 'network_error';
            $returnValue['response_detail'] = __('Network error. Could not reach gateway server.');
        } else {
            $returnValue = $this->parseResponseBodyPaygent($response->getBody());
        }

        if ($this->scopeConfig->getValue('payment/paygent/debug')) {
            //write log to file when enable debug flag
            $this->paygentLogger->info(\Zend_Json::encode($response));
        }

        return $returnValue;
    }

    /**
     * Parse Response Body.
     *
     * @param object $body Body
     *
     * @return array
     *
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function parseResponseBodyIvr($body)
    {
        $_results = preg_split("/(\r\n)/", $body);

        $returnValue = [];

        foreach ($_results as $data) {
            $xml = simplexml_load_string($data);
            $returnValue['resultCode'] = (string) $xml->resultCode;
            if ($xml->resultCode == 0) {
                $returnValue['statusCode'] = (string) $xml->statusCode;
                switch ($returnValue['statusCode']) {
                    case 0:
                        $returnValue['message'] = __('No corresponding record ! No corresponding reception number.');
                        break;
                    case 1:
                        $returnValue['message'] = __('Uncompleted ! Designated ID has not been entered. The call was hung up in the middle.');
                        break;
                    case 2:
                        $returnValue['message'] = __('Completed (Unsettled) ! The call was hung up after the entry of designated ID and before settlement.');
                        break;
                    case 3:
                        $returnValue['message'] = __('Completed (Settled) ! The call was hung up after settlement.');
                        $cardAuth = (array) $xml->card_auth;
                        $returnValue['card_auth_result'] = (string) $cardAuth['result'];
                        $returnValue['payment_id'] = (string) $cardAuth['paymentId'];
                        $returnValue['trading_id'] = (string) $cardAuth['tradingId'];
                        $returnValue['acq_id'] = (string) $cardAuth['acqId'];
                        $returnValue['acq_name'] = (string) $cardAuth['acqName'];
                        break;
                    default;
                        $returnValue['message'] = __('No corresponding reception number.');
                        break;
                }
            } else {
                $returnValue['error'] = 1;
                $returnValue['message'] = __('Requested failure ! Could not retrive response code.');
            }
        }

        return $returnValue;
    }

    /**
     * Set variable for email authorize failure.
     *
     * @param $order
     * @param null $errorMessageHandling
     *
     * @return mixed
     */
    public function setVariablesEmail(\Magento\Sales\Model\Order $order, $errorMessageHandling = null)
    {
        $vars = [];
        //message error
        $vars['description'] = $errorMessageHandling;
        $vars['orderIncrementId'] = $order->getIncrementId();
        $vars['orderAmount'] = (int) $order->getGrandTotal();
        $vars['orderDate'] = $order->getCreatedAt();

        //billing address
        $biilingAddress = $order->getBillingAddress();
        $vars['customerName'] = $biilingAddress->getLastname().' '.$biilingAddress->getFirstname();
        $vars['postCode'] = $biilingAddress->getPostcode();
        $street = $biilingAddress->getStreet() ? current($biilingAddress->getStreet()) : '';
        $vars['street'] = $biilingAddress->getRegion().'  '.$street;
        $vars['phone'] = $biilingAddress->getTelephone();
        $vars['linkChangeCC'] = $this->urlInterface->getUrl('subscriptions/profile');
        //get order riki type
        if ($order->getSubscriptionProfileId()) {
            $vars['linkChangeCC'] = $this->urlInterface->getUrl('subscriptions/profile/edit', ['id' => $order->getSubscriptionProfileId()]);
        }
        //for email Error Capture Credit Card (Business user)
        $shipmentIds = [];
        $shipments = $order->getShipmentsCollection();
        if ($shipments) {
            if ($shipments->getSize()) {
                foreach ($shipments as $_ship) {
                    $shipmentIds[] = $_ship->getIncrementId();
                }
            }
        }
        $vars['year'] = $this->timezone->date()->format('Y');
        $vars['month'] = $this->timezone->date()->format('m');
        $vars['day'] = $this->timezone->date()->format('d');
        $vars['hour'] = $this->timezone->date()->format('H');
        $vars['order_increment_id'] = $order->getIncrementId();
        $vars['transaction_id'] = $order->getPayment()->getCcTransId();
        $vars['shipment_id'] = implode(",", $shipmentIds);
        return $vars;
    }

    /**
     * Get common config back-end for send mail.
     *
     * @return array
     */
    public function getConfigSendMailFail()
    {
        $configs = [];
        $configs['recipients'] = array_filter(explode(';', $this->scopeConfig->getValue('paygent_config/paygent_fail/receiver')), 'trim');
        $configs['senderInfo'] = $this->scopeConfig->getValue('paygent_config/paygent_fail/identity');
        $configs['templateEmail'] = $this->scopeConfig->getValue('paygent_config/paygent_fail/template');

        return $configs;
    }

    /**
     * Get Riki type from order
     *
     * @param $order
     *
     * @return bool|string
     */
    public function getRikiType($order)
    {
        if (!empty($order) && !empty($order->getData('riki_type'))) {
            return $order->getData('riki_type');
        }

        $subscriptionCourseType = \Riki\SubscriptionCourse\Model\Course\Type::TYPE_ORDER_SPOT;

        try {
            $quote = $this->quoteRepository->get($order->getQuoteId());

            if (!empty($quote->getData("riki_course_id"))) {
                $subscriptionCourseType = $this->_hanpukaiHelper->getSubscriptionCourseType($quote->getData("riki_course_id"));
            }
        } catch (\Exception $e) {
            $this->_logger->critical($e->getMessage());
        }

        return $subscriptionCourseType;
    }

    /**
     * @param \Magento\Sales\Model\Order $order
     * @param string $errorMessage
     * @return $this
     */
    public function sendCaptureFailedMail(\Magento\Sales\Model\Order $order, $errorMessage = '')
    {
        if ($this->scopeConfig->getValue('paygent_config/paygent_fail/enable')) {
            try {
                //send email notify when capture fail
                $configs = $this->getConfigSendMailFail();
                //get error code from table error handling
                $errorMessageHandling = false;
                if ($errorMessage != '') {
                    $errorMessageHandling = $this->getPaymentErrorCodeHandling($errorMessage);
                    if ($errorMessageHandling === null) {
                        //default payment error code
                        $errorMessageHandling = $this->getPaymentErrorCodeHandling('Others');
                    }
                }
                $vars = $this->setVariablesEmail($order, $errorMessageHandling);

                if (isset($configs['recipients'])) {
                    foreach ($configs['recipients'] as $email) {
                        $this->sendMailNotify($email, $configs['senderInfo'], $configs['templateEmail'], $vars);
                    }
                }
            } catch (\Exception $e) {
                $this->paygentLogger->info(__(
                    'Can not send the capture fail email for the order %1',
                    $order->getIncrementId()
                ));
                $this->paygentLogger->critical($e);
            }
        }

        return $this;
    }

    /**
     * Get message payment error handling
     *
     * @param $errorDetail
     *
     * @return mixed|null
     */
    public function getPaymentErrorCodeHandling($errorDetail)
    {
        $errorHandling = $this->paygentError->getCollection()
            ->addFieldToFilter('error_code', trim($errorDetail, " \t\n\r\0\x0B\""))
            ->setPageSize(1)
            ->setCurPage(1);
        $message = null;
        if ($errorHandling && $errorHandling->getSize() > 0) {
            $message = $errorHandling->getFirstItem()->getEmailMessage();
        }
        return $message;
    }

    /**
     * @param Order $order
     * @return string
     */
    public function getUrlChangeCardInfo(Order $order)
    {
        if ($profileId = $order->getSubscriptionProfileId()) {
            return $this->urlInterface->getUrl(
                'subscriptions/profile/payment_method_edit',
                [
                    'id' => $profileId,
                    '_nosid' => true,
                    '_scope' => $order->getStoreId()
                ]
            );
        }

        $ccLink = $this->reauthorizeFailureEmail->getAuthorizeLink([
            'trading_id' => $order->getIncrementId(),
            'amount' => $order->getGrandTotal(),
            'return_url' => $this->urlInterface->getUrl(null, [
                '_nosid' => true,
                '_scope' => $order->getStoreId()
            ]),
            'inform_url' => $this->urlInterface->getUrl('paygent/paygent/response/', [
                '_nosid' => true,
                '_scope' => $order->getStoreId()
            ])
        ]);

        return isset($ccLink['url']) ? $ccLink['url'] : '';
    }

    /**
     * @param $tradingId
     * @param $amount
     * @param array $params
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function generatePaygentRedirectUrl($tradingId, $amount, $params = [])
    {
        /** @var \Bluecom\Paygent\Model\Paygent $paymentModel */
        $paymentModel = $this->paygentFactory->create();
        $response = $paymentModel->initRedirectLink($tradingId, $amount, $params);
        if ($response['result'] != 0) {
            $errorCode = $response['response_code'];
            $errorDetail = $response['response_detail'];
            $message = sprintf(
                'Cannot generate Paygent redirect url for trading id %s. Error code is %s, error detail is %s.',
                $tradingId,
                $errorCode,
                $errorDetail
            );
            $this->paygentLogger->info($message);

            throw new \Magento\Framework\Exception\LocalizedException(__($message));
        }

        return [
            'paygent_url' => $response['url'],
            'paygent_limit_date' => $response['limit_date']
        ];
    }
}