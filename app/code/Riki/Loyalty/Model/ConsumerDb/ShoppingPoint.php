<?php

namespace Riki\Loyalty\Model\ConsumerDb;

use Magento\Customer\Model\Customer;
use Magento\Customer\Model\CustomerFactory;
use Magento\Developer\Model\Tools\Formatter;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Riki\Customer\Helper\ConsumerLog;

class ShoppingPoint
{
    const CLIENT_INFO_XC = 'XC';
    const CLIENT_INFO_DOMAIN = 'g.nestle.jp';

    const CODE_SUCCESS = 'MID00000';
    const CODE_NOT_FOUND = 'MID99003';
    const CODE_EXPIRATION = 'MID03010';

    const TYPE_POINT = '11000000';
    const TYPE_COIN = '12000000';

    const POINT_AMOUNT_ID = 'MAGENTO_SHOPPINGPOINT';

    const REQUEST_TYPE_ALLOCATION = 1;
    const REQUEST_TYPE_USE = 2;
    const REQUEST_TYPE_CANCEL = 3;
    const REQUEST_TYPE_EXPIRATION  = 4;

    const ISSUE_TYPE_PURCHASE = 0;
    const ISSUE_TYPE_REVIEW = 1;
    const ISSUE_TYPE_QUESTION = 2;
    const ISSUE_TYPE_ADJUSTMENT = 3;
    const ISSUE_TYPE_REGISTER = 4;
    const ISSUE_TYPE_FREE_GIFT = 5;
    const ISSUE_TYPE_DISCOUNT = 6;
    const ISSUE_TYPE_SITE_VISIT = 7;
    const ISSUE_TYPE_GAME = 8;
    const ISSUE_TYPE_CAMPAIGN = 9;
    const ISSUE_TYPE_CONTENT_USAGE = 10;
    const ISSUE_TYPE_POINT_EXCHANGE = 11;
    const ISSUE_TYPE_OTHER = 99;

    const ISSUE_STATUS_TEMP = 0;
    const ISSUE_STATUS_ADDING = 1;
    const ISSUE_STATUS_INVALID = 2;
    const ISSUE_STATUS_SUBTRACTION = 3;

    const DEFAULT_DATE_FROM = '1990/01/01';
    const DEFAULT_DATE_TO = '2030/12/31';
    const DEFAULT_TIME_LIMIT = 99;
    const RESPONSE_HEADER_INDEX = 3;

    /**
     * @var \Riki\Customer\Helper\ConsumerLog
     */
    protected $_apiLogger;

    /**
     * @var TimezoneInterface
     */
    protected $_dateTime;

    /**
     * @var Formatter
     */
    protected $_formatter;

    /**
     * @var \Riki\Loyalty\Helper\Api
     */
    protected $_apiHelper;

    /**
     * @var \Riki\Framework\Webapi\Soap\ClientFactory
     */
    protected $_soapClientFactory;

    /**
     * @var \Zend\Soap\Client
     */
    protected $_soapGetPoint;

    /**
     * @var \Zend\Soap\Client
     */
    protected $_soapSetPoint;

    /**
     * @var \Zend\Soap\Client
     */
    protected $_soapPointHistory;

    /**
     * @var \Zend\Soap\Client
     */
    protected $_soapPointExpired;

    /**
     * @var \Riki\Customer\Helper\ConsumerDb\Soap
     */
    protected $soapHelper;

    /**
     * Point type 11000000
     *
     * @var null
     */
    protected $point = null;
    /**
     * Point type 12000000
     *
     * @var null
     */
    protected $coint = null;
    /*
     *
     */
    protected $consumer_id = null;
    /**
     * @var \Magento\Framework\Registry
     */
    protected $_coreRegistry;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;

    /**
     * @var \Magento\Customer\Model\ResourceModel\CustomerFactory
     */
    protected $customerResourceFactory;

    /**
     * @var CustomerFactory
     */
    protected $customerFactory;

    /**
     * ShoppingPoint constructor.
     * @param \Riki\Loyalty\Helper\ConsumerLog $apiLogger
     * @param TimezoneInterface $dateTime
     * @param \Riki\Loyalty\Helper\Api $apiHelper
     * @param \Riki\Framework\Webapi\Soap\ClientFactory $clientFactory
     * @param \Riki\Customer\Helper\ConsumerDb\Soap $soapHelper
     * @param \Magento\Customer\Model\ResourceModel\CustomerFactory $customerResourceFactory
     * @param CustomerFactory $customerFactory
     * @param \Magento\Framework\Registry $registry
     * @param \Psr\Log\LoggerInterface $logger
     */
    public function __construct(
        \Riki\Loyalty\Helper\ConsumerLog $apiLogger,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $dateTime,
        \Riki\Loyalty\Helper\Api $apiHelper,
        \Riki\Framework\Webapi\Soap\ClientFactory $clientFactory,
        \Riki\Customer\Helper\ConsumerDb\Soap $soapHelper,
        \Magento\Customer\Model\ResourceModel\CustomerFactory $customerResourceFactory,
        \Magento\Customer\Model\CustomerFactory $customerFactory,
        \Magento\Framework\Registry $registry,
        \Psr\Log\LoggerInterface $logger
    ) {
        $this->_apiLogger = $apiLogger;
        $this->_dateTime = $dateTime;
        $this->_apiHelper = $apiHelper;
        $this->_soapClientFactory = $clientFactory;
        $this->soapHelper = $soapHelper;
        $this->_coreRegistry = $registry;
        $this->logger = $logger;
        $this->customerResourceFactory = $customerResourceFactory;
        $this->customerFactory = $customerFactory;
    }
    
    /**
     * SOAP client info
     *
     * @return array
     */
    protected function _clientInfoParam()
    {
        /**
         * Get api client info
         */
        $clientInfo = $this->_apiHelper->getApiClientInfo();
        if(!$clientInfo) $clientInfo = self::CLIENT_INFO_XC;
        /**
         * Get api client info domain
         */
        $clientInfoDomain = $this->_apiHelper->getApiClientInfoDomain();
        if(!$clientInfoDomain) $clientInfoDomain = self::CLIENT_INFO_DOMAIN;

        $dateTime =  $this->_dateTime->date()->format('Y/m/d H:i:s');
        $params[] = new \SoapVar($clientInfo, XSD_STRING, null, null, 'clientInfo' );
        $params[] = new \SoapVar($clientInfoDomain, XSD_STRING, null, null, 'clientInfo' );
        $params[] = new \SoapVar($dateTime, XSD_STRING, null, null, 'clientInfo' );
        return $params;
    }

    /**
     * Init SOAP Client for getPoint
     *
     * @param string $wsdl
     * @param string $endpoint
     * @return \Zend\Soap\Client
     */
    protected function _initClient($wsdl, $endpoint)
    {
        $soapClient = $this->_soapClientFactory->create(
            $this->_apiHelper->soapBaseUrl($wsdl),
            $this->soapHelper->getCommonRequestParams()
        );

        $soapClient->setLocation($this->_apiHelper->soapBaseUrl($endpoint));
        return $soapClient;
    }

    /**
     * Get point in ConsumerDB
     *
     * @param string $customerCode
     * @param string $pointType
     * @return array
     */
    public function getPoint($customerCode, $pointType = self::TYPE_POINT,$isSimulator = false)
    {
        $registryKey = "customer_point_balance_{$customerCode}";

        // Return for point
        if (!is_null($this->point) && $pointType == self::TYPE_POINT && $this->consumer_id == $customerCode) {
            return $this->_coreRegistry->registry($registryKey);
        }
        // Return for coin
        if(!is_null($this->coint) && $pointType == self::TYPE_COIN && $this->consumer_id == $customerCode){
            return $this->coint;
        }
        $customer = $this->customerFactory->create()->getCollection()
                        ->addFieldToFilter('consumer_db_id', ['eq' => $customerCode])
                        ->getFirstItem();
        $needToSaveCustomer = false;
        if ($customer->getId()) {
            $needToSaveCustomer = true;
            $customerData = $customer->getDataModel();
        }

        $logMsg = __("Get shopping point of customer: %1", $customerCode);
        $apiRequest = $apiResponse = '';
        $responseCode = 0;

        try {
            $soapClient = $this->_initClient(
                '/GetPointService?wsdl', '/GetPointService.GetPointServiceHttpSoap12Endpoint/'
            );
            $status = \Riki\Customer\Model\ConsumerLog::STATUS_SUCCESS;
            $params = $this->_clientInfoParam();
            $params[] = new \SoapVar($customerCode, XSD_STRING, null, null, 'customerCode' );
            if (in_array($pointType, [self::TYPE_POINT, self::TYPE_COIN])) {
                $params[] = new \SoapVar($pointType, XSD_STRING, null, null, 'pointType' );
            }
            $response = $soapClient->getPoint(new \SoapVar($params, SOAP_ENC_OBJECT));
            $apiRequest = $soapClient->getLastRequest();
            $apiResponse = $soapClient->getLastResponse();
            if (!property_exists($response, 'return')) {
                throw new LocalizedException(__('SOAP Error, missing return'));
            }
            $msgAndCode = $this->_apiHelper->responseCode($response);
            $responseCode = $msgAndCode['code'];
            if ($responseCode !== self::CODE_SUCCESS) {
                throw new LocalizedException(__($msgAndCode['msg']));
            }
            $balance = [];
            foreach ($response->return[3]->array as $index => $field) {
                if ($field == 'REST_POINT' && $customerData->getId() && $pointType == self::TYPE_POINT) {
                    $customerData->setCustomAttribute('reward_point', $response->return[4]->array[$index]);
                }
                $balance[$field] = $response->return[4]->array[$index];
            }
            if ($needToSaveCustomer && $pointType == self::TYPE_POINT) {
                $customer->updateData($customerData);
                $customerResource = $this->customerResourceFactory->create();
                $customerResource->saveAttribute($customer, 'reward_point');
            }
            /**
             * Fields: CUSTOMER_CODE, POINT_TYPE, REST_POINT, TEMPORARY_POINT, GAIN_TOTAL_POINT,
             * USED_POINT_TOTAL, EXPIRED_TOTAL_POINT, POINT_UNIT
             */
            $result = ['error' => false, 'return' => $balance];
        } catch (\Exception $e) {
            $result = ['error' => true, 'msg' => $e->getMessage(), 'responseCode' => $responseCode];
            $status = \Riki\Customer\Model\ConsumerLog::STATUS_ERROR;
            $this->_apiLogger->saveAPILog('getPoint', $logMsg, $status, $apiRequest, $apiResponse,$isSimulator,false,true);
        }


        $this->consumer_id = $customerCode;
        // Set type result
        if ($pointType == self::TYPE_COIN) {
            $this->coint = $result;
        } else {
            // Unset and set point balance on one process
            $this->_coreRegistry->unregister($registryKey);
            $this->_coreRegistry->register($registryKey,$result);
            $this->point = $result;
        }

        $this->_apiLogger->saveAPILog('getPoint', $logMsg, $status, $apiRequest, $apiResponse,$isSimulator);
        return $result;
    }

    /**
     * Set point in ConsumerDB
     *
     * @param integer $requestType
     * @param string $customerCode
     * @param array $arrData
     * @return array
     */
    public function setPoint($requestType, $customerCode, $arrData)
    {
        $apiRequest = $apiResponse = '';
        $logMsg = __("Set shopping point for customer: %1", $customerCode);
        $customer = $this->customerFactory->create()->getCollection()
            ->addFieldToFilter('consumer_db_id', ['eq' => $customerCode])
            ->getFirstItem();
        $needToSaveCustomer = false;
        if ($customer->getId()) {
            $needToSaveCustomer = true;
            $customerData = $customer->getDataModel();
        }
        try {
            $soapClient = $this->_initClient(
                '/SetShoppingPointService?wsdl', '/SetShoppingPointService.SetShoppingPointServiceHttpSoap12Endpoint/'
            );
            $status = \Riki\Customer\Model\ConsumerLog::STATUS_SUCCESS;
            $params = $this->_clientInfoParam();
            $params[] = new \SoapVar($requestType, XSD_STRING, null, null, 'requestType');
            $params[] = new \SoapVar($customerCode, XSD_STRING, null, null, 'customerCode');
            foreach ($arrData as $key => $value) {
                $params[] = new \SoapVar($value, XSD_STRING, null, null, $key);
            }
            $response = $soapClient->setShoppingPoint(new \SoapVar($params, SOAP_ENC_OBJECT));
            $apiRequest = $soapClient->getLastRequest();
            $apiResponse = $soapClient->getLastResponse();
            if (!property_exists($response, 'return')) {
                throw new LocalizedException(__('SOAP Error, missing return'));
            }
            $msgAndCode = $this->_apiHelper->responseCode($response);
            $responseCode = $msgAndCode['code'];
            if ($responseCode !== self::CODE_SUCCESS) {
                throw new LocalizedException(__($msgAndCode['msg']));
            }
            //point balance after set successful
            $customer->setData('reward_point', $response->return[3]);
            if ($needToSaveCustomer) {
                $customer->updateData($customerData);
                $customerResource = $this->customerResourceFactory->create();
                $customerResource->saveAttribute($customer, 'reward_point');
            }
            $result = ['error' => false, 'return' => $response->return[3]];
        } catch (LocalizedException $e) {
            $result = [
                'error' => true,
                'msg' => $e->getMessage(),
                'request' => $apiRequest,
                'response' => $apiResponse
            ];
            $status = \Riki\Customer\Model\ConsumerLog::STATUS_ERROR;
        } catch (\Exception $e) {
            $this->logger->critical($e);
            $result = [
                'error' => true,
                'msg' => __('Call API error'),
                'request' => $apiRequest,
                'response' => $apiResponse
            ];
            $status = \Riki\Customer\Model\ConsumerLog::STATUS_ERROR;
        }

        $this->_apiLogger->saveAPILog(
            'setShoppingPoint',
            $logMsg,
            $status,
            $apiRequest,
            $apiResponse,
            false,
            $status == \Riki\Customer\Model\ConsumerLog::STATUS_ERROR
        );

        return $result;
    }

    /**
     * Get point history from consumerDB API
     *
     * @param string $customerCode
     * @param string $pointType
     * @param string $dateFrom
     * @param string $dateTo
     * @return array
     */
    public function getPointHistory(
        $customerCode,
        $pointType = self::TYPE_POINT,
        $dateFrom = self::DEFAULT_DATE_FROM,
        $dateTo = self::DEFAULT_DATE_TO
    )
    {
        $apiRequest = $apiResponse = '';
        try {
            $soapClient = $this->_initClient(
                '/GetPointHistoryService?wsdl', '/GetPointHistoryService.GetPointHistoryServiceHttpSoap12Endpoint/'
            );
            $status = \Riki\Customer\Model\ConsumerLog::STATUS_SUCCESS;
            $params = $this->_clientInfoParam();
            $params[] = new \SoapVar($customerCode, XSD_STRING, null, null, 'customerCode');
            $params[] = new \SoapVar($pointType, XSD_STRING, null, null, 'pointType');
            $params[] = new \SoapVar('', XSD_STRING, null, null, 'pointAmountId');
            $params[] = new \SoapVar($dateFrom, XSD_STRING, null, null, 'dateFrom');
            $params[] = new \SoapVar($dateTo, XSD_STRING, null, null, 'dateTo');
            $response = $soapClient->getPointHistory(new \SoapVar($params, SOAP_ENC_OBJECT));
            $apiRequest = $soapClient->getLastRequest();
            $apiResponse = $soapClient->getLastResponse();
            if (!property_exists($response, 'return')) {
                throw new LocalizedException(__('SOAP Error, missing return'));
            }
            $msgAndCode = $this->_apiHelper->responseCode($response);
            $responseCode = $msgAndCode['code'];
            if ($responseCode !== self::CODE_SUCCESS) {
                throw new LocalizedException(__($msgAndCode['msg']));
            }
            $result = [
                'error' => false,
                'history' => $this->_apiHelper->buildArray($response, self::RESPONSE_HEADER_INDEX)
            ];
        } catch (\Exception $e) {
            $result = ['error' => true, 'msg' => $e->getMessage()];
            $status = \Riki\Customer\Model\ConsumerLog::STATUS_ERROR;
        }
        $logMsg = __("Get point history for customer: %1", $customerCode);
        $this->_apiLogger->saveAPILog('getPointHistory', $logMsg, $status, $apiRequest, $apiResponse);
        return $result;
    }


    /**
     * Get point scheduled expired
     *
     * @param string $customerCode
     * @param int $timeLimit
     * @return array
     */
    public function getScheduledExpiredPoint($customerCode, $timeLimit = self::DEFAULT_TIME_LIMIT)
    {
        $apiRequest = $apiResponse = '';
        try {
            $soapClient = $this->_initClient(
                '/GetScheduledExpiredPointService?wsdl',
                '/GetScheduledExpiredPointService.GetScheduledExpiredPointServiceHttpSoap12Endpoint/'
            );
            $status = \Riki\Customer\Model\ConsumerLog::STATUS_SUCCESS;
            $params = $this->_clientInfoParam();
            $params[] = new \SoapVar($customerCode, XSD_STRING, null, null, 'customerCode');
            $params[] = new \SoapVar($timeLimit, XSD_STRING, null, null, 'timeLimit');
            $response = $soapClient->getScheduledExpiredPoint(new \SoapVar($params, SOAP_ENC_OBJECT));
            $apiRequest = $soapClient->getLastRequest();
            $apiResponse = $soapClient->getLastResponse();
            if (!property_exists($response, 'return')) {
                throw new LocalizedException(__('SOAP Error, missing return'));
            }
            $msgAndCode = $this->_apiHelper->responseCode($response);
            $responseCode = $msgAndCode['code'];
            if ($responseCode !== self::CODE_SUCCESS) {
                throw new LocalizedException(__($msgAndCode['msg']));
            }
            $result = [
                'error' => false,
                'expired' => $this->_apiHelper->buildArray($response, self::RESPONSE_HEADER_INDEX)
            ];
        } catch (\Exception $e) {
            $result = ['error' => true, 'msg' => $e->getMessage()];
            $status = \Riki\Customer\Model\ConsumerLog::STATUS_ERROR;
        }
        $logMsg = __("Get point expired for customer: %1", $customerCode);
        $this->_apiLogger->saveAPILog('getScheduledExpiredPoint', $logMsg, $status, $apiRequest, $apiResponse);
        return $result;
    }
    /**
     * Set point in ConsumerDB
     *
     * @param integer $requestType
     * @param string $customerCode
     * @param array $arrData
     * @return array
     */
    public function checkPoint($requestType, $customerCode, $arrData)
    {
        try {
            $soapClient = $this->_initClient(
                '/SetShoppingPointService?wsdl', '/SetShoppingPointService.SetShoppingPointServiceHttpSoap12Endpoint/'
            );
            $params = $this->_clientInfoParam();
            $params[] = new \SoapVar($requestType, XSD_STRING, null, null, 'requestType');
            $params[] = new \SoapVar($customerCode, XSD_STRING, null, null, 'customerCode');
            foreach ($arrData as $key => $value) {
                $params[] = new \SoapVar($value, XSD_STRING, null, null, $key);
            }
            $response = $soapClient->setShoppingPoint(new \SoapVar($params, SOAP_ENC_OBJECT));
            $result = ['error' => false, 'data' => $response];
            return $result;
        } catch (\Exception $e) {
            $result = ['error' => true, 'msg' => $e->getMessage()];
            return $result;
        }
    }
    /**
     * Get point in ConsumerDB
     *
     * @param string $customerCode
     * @param string $pointType
     * @return array
     */
    public function getPointRealTime($customerCode, $pointType = self::TYPE_POINT,$isSimulator = false)
    {

        $logMsg = __("Get shopping point of customer: %1", $customerCode);
        $apiRequest = $apiResponse = '';
        $responseCode = 0;

        try {
            $soapClient = $this->_initClient(
                '/GetPointService?wsdl', '/GetPointService.GetPointServiceHttpSoap12Endpoint/'
            );
            $status = \Riki\Customer\Model\ConsumerLog::STATUS_SUCCESS;
            $params = $this->_clientInfoParam();
            $params[] = new \SoapVar($customerCode, XSD_STRING, null, null, 'customerCode' );
            if (in_array($pointType, [self::TYPE_POINT, self::TYPE_COIN])) {
                $params[] = new \SoapVar($pointType, XSD_STRING, null, null, 'pointType' );
            }
            $response = $soapClient->getPoint(new \SoapVar($params, SOAP_ENC_OBJECT));
            $apiRequest = $soapClient->getLastRequest();
            $apiResponse = $soapClient->getLastResponse();
            if (!property_exists($response, 'return')) {
                throw new LocalizedException(__('SOAP Error, missing return'));
            }
            $msgAndCode = $this->_apiHelper->responseCode($response);
            $responseCode = $msgAndCode['code'];
            if ($responseCode !== self::CODE_SUCCESS) {
                throw new LocalizedException(__($msgAndCode['msg']));
            }
            $balance = [];
            foreach ($response->return[3]->array as $index => $field) {
                $balance[$field] = $response->return[4]->array[$index];
            }
            /**
             * Fields: CUSTOMER_CODE, POINT_TYPE, REST_POINT, TEMPORARY_POINT, GAIN_TOTAL_POINT,
             * USED_POINT_TOTAL, EXPIRED_TOTAL_POINT, POINT_UNIT
             */
            $result = ['error' => false, 'return' => $balance];
        } catch (\Exception $e) {
            $result = ['error' => true, 'msg' => $e->getMessage(), 'responseCode' => $responseCode];
            $status = \Riki\Customer\Model\ConsumerLog::STATUS_ERROR;
            $this->_apiLogger->saveAPILog('getPoint', $logMsg, $status, $apiRequest, $apiResponse,$isSimulator,false,true);
        }

        $this->_apiLogger->saveAPILog('getPoint', $logMsg, $status, $apiRequest, $apiResponse,$isSimulator);
        return $result;
    }
}
