<?php

namespace Riki\Loyalty\Model\ConsumerDb;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Zend\Soap\Client as SoapClient;

use Riki\Customer\Helper\ConsumerLog;

class CustomerSub
{
    const CLIENT_INFO_XC = 'XC';
    const CLIENT_INFO_DOMAIN = 'g.nestle.jp';

    const CODE_SUCCESS = 'MID00000';
    const RESPONSE_HEADER_INDEX = 3;

    const USE_POINT_TYPE = 840;
    const USE_POINT_AMOUNT = 841;

    /**
     * @var \Riki\Customer\Helper\ConsumerLog
     */
    protected $_apiLogger;

    /**
     * @var TimezoneInterface
     */
    protected $_dateTime;

    /**
     * @var \Riki\Loyalty\Helper\Api
     */
    protected $_apiHelper;

    /**
     * @var \Zend\Soap\Client
     */
    protected $_soapSetCustomerSub;

    /**
     * @var \Zend\Soap\Client
     */
    protected $_soapGetCustomerSub;

    /**
     * @var \Riki\Customer\Helper\ConsumerDb\Soap
     */
    protected $soapHelper;

    /**
     * @var \Riki\Framework\Webapi\Soap\ClientFactory
     */
    protected $soapClientFactory;

    /**
     * CustomerSub constructor.
     * @param ConsumerLog $apiLogger
     * @param TimezoneInterface $dateTime
     * @param \Riki\Loyalty\Helper\Api $apiHelper
     * @param \Riki\Customer\Helper\ConsumerDb\Soap $soapHelper
     * @param \Riki\Framework\Webapi\Soap\ClientFactory $soapClientFactory
     */
    public function __construct(
        ConsumerLog $apiLogger,
        TimezoneInterface $dateTime,
        \Riki\Loyalty\Helper\Api $apiHelper,
        \Riki\Customer\Helper\ConsumerDb\Soap $soapHelper,
        \Riki\Framework\Webapi\Soap\ClientFactory $soapClientFactory
    ) {
        $this->_apiLogger = $apiLogger;
        $this->_dateTime = $dateTime;
        $this->_apiHelper = $apiHelper;
        $this->soapHelper = $soapHelper;
        $this->soapClientFactory = $soapClientFactory;
    }

    /**
     * Init soap client
     *
     * @return array
     */
    protected function _initClient()
    {
        $soapConfig = $this->soapHelper->getCommonRequestParams();

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

        $setSubWsdl = $this->_apiHelper->soapBaseUrl('/SetCustomerSubService?wsdl');
        $setSubEP = $this->_apiHelper->soapBaseUrl('/SetCustomerSubService.SetCustomerSubServiceHttpSoap12Endpoint/');
        $this->_soapSetCustomerSub = $this->soapClientFactory->create($setSubWsdl, $soapConfig);
        $this->_soapSetCustomerSub->setLocation($setSubEP);

        $getSubWsdl = $this->_apiHelper->soapBaseUrl('/GetCustomerSubService?wsdl');
        $getSubEP = $this->_apiHelper->soapBaseUrl('/GetCustomerSubService.GetCustomerSubServiceHttpSoap12Endpoint/');
        $this->_soapGetCustomerSub = $this->soapClientFactory->create($getSubWsdl, $soapConfig);
        $this->_soapGetCustomerSub->setLocation($getSubEP);

        $dateTime =  $this->_dateTime->date()->format('Y/m/d H:i:s');
        $params = [];
        $params[] = new \SoapVar($clientInfo, XSD_STRING, null, null, 'clientInfo' );
        $params[] = new \SoapVar($clientInfoDomain, XSD_STRING, null, null, 'clientInfo' );
        $params[] = new \SoapVar($dateTime, XSD_STRING, null, null, 'clientInfo' );
        return $params;
    }

    /**
     * ConsumerDB: Set customer sub
     *
     * @param string $customerCode
     * @param array $updateData
     * @return array
     */
    public function setCustomerSub($customerCode, $updateData)
    {
        $apiRequest = $apiResponse = '';
        try {
            $status = \Riki\Customer\Model\ConsumerLog::STATUS_SUCCESS;
            $params = $this->_initClient();
            $params[] = new \SoapVar($customerCode, XSD_STRING, null, null, 'customerCode' );
            $keyVar = [
                new \SoapVar('KEY_CUSTOMER_CODE', XSD_STRING, null, null, 'array'),
                new \SoapVar('KEY_SUBPROFILE_ID', XSD_STRING, null, null, 'array'),
                new \SoapVar('KEY_VALUE_NAME', XSD_STRING, null, null, 'array')
            ];
            $params[] = new \SoapVar($keyVar, SOAP_ENC_ARRAY, null, null, 'setParameter' );
            foreach ($updateData as $keyID => $value) {
                $valueVar = [
                    new \SoapVar($customerCode, XSD_STRING, null, null, 'array'),
                    new \SoapVar($keyID, XSD_STRING, null, null, 'array'),
                    new \SoapVar($value, XSD_STRING, null, null, 'array')
                ];
                $params[] = new \SoapVar($valueVar, SOAP_ENC_ARRAY, null, null, 'setParameter' );
            }
            $response = $this->_soapSetCustomerSub->setCustomerSub(new \SoapVar($params, SOAP_ENC_OBJECT));
            $apiRequest = $this->_soapSetCustomerSub->getLastRequest();
            $apiResponse = $this->_soapSetCustomerSub->getLastResponse();
            if (!property_exists($response, 'return')) {
                throw new LocalizedException(__('SOAP Error, missing return'));
            }
            $responseCode = is_string($response->return[0]) ? $response->return[0] : $response->return[0]->array[0];
            if ($responseCode !== self::CODE_SUCCESS) {
                if (is_string($response->return[1])) {
                    $msg = $response->return[1];
                } else {
                    $msg = $response->return[1]->array[0];
                }
                throw new LocalizedException(__($msg));
            }
            $result = ['error' => false, 'value' => $response->return[2]];
        } catch (\Exception $e) {
            $result = ['error' => true, 'msg' => $e->getMessage()];
            $status = \Riki\Customer\Model\ConsumerLog::STATUS_ERROR;
        }
        $logMsg = __("SetCustomerSubService: %1", $customerCode);
        $this->_apiLogger->saveAPILog('setCustomerSub', $logMsg, $status, $apiRequest, $apiResponse);
        return $result;
    }

    /**
     * ConsumerDB: get customer sub value
     *
     * @param string $customerCode
     * @param integer|array $keyIDs
     * @return array
     */
    public function getCustomerSub($customerCode, $keyIDs)
    {
        $apiRequest = $apiResponse = '';
        try {
            $status = \Riki\Customer\Model\ConsumerLog::STATUS_SUCCESS;
            $params = $this->_initClient();
            $params[] = new \SoapVar($customerCode, XSD_STRING, null, null, 'customerCode' );
            if (!is_array($keyIDs)) {
                $keyIDs = [$keyIDs];
            }
            foreach ($keyIDs as $keyID) {
                $params[] = new \SoapVar($keyID, XSD_STRING, null, null, 'getParameter' );
            }
            $response = $this->_soapGetCustomerSub->getCustomerSub(new \SoapVar($params, SOAP_ENC_OBJECT));
            $apiRequest = $this->_soapGetCustomerSub->getLastRequest();
            $apiResponse = $this->_soapGetCustomerSub->getLastResponse();
            if (!property_exists($response, 'return')) {
                throw new LocalizedException(__('SOAP Error, missing return'));
            }
            $responseCode = is_string($response->return[0]) ? $response->return[0] : $response->return[0]->array[0];
            if ($responseCode !== self::CODE_SUCCESS) {
                if (is_string($response->return[1])) {
                    $msg = $response->return[1];
                } else {
                    $msg = $response->return[1]->array[0];
                }
                throw new LocalizedException(__($msg));
            }
            $result = ['error' => false, 'value' => $this->_apiHelper->customerSubValue($response, self::RESPONSE_HEADER_INDEX)];
        } catch (\Exception $e) {
            $result = ['error' => true, 'msg' => $e->getMessage()];
            $status = \Riki\Customer\Model\ConsumerLog::STATUS_ERROR;
        }
        $logMsg = __("GetCustomerSubService: %1", $customerCode);
        $this->_apiLogger->saveAPILog('getCustomerSub', $logMsg, $status, $apiRequest, $apiResponse);
        return $result;
    }
}