<?php

namespace Riki\Customer\Model;

class AmbCustomerRepository
{
    /**
     * @var \Riki\Customer\Model\ConsumerLogFileFactory $consumerLogFactory
     */
    protected $_consumerLogFactory;

    /**
     * @var Magento system config
     *
     */
    protected $_scopeConfig;

    /**
     * @var \Riki\Customer\Helper\Api
     */
    protected $_apiCustomerHelper;

    /**
     * @var \Riki\Customer\Helper\ConsumerDb\Soap
     */
    protected $soapHelper;

    /**
     * @var \Riki\Framework\Webapi\Soap\ClientFactory
     */
    protected $soapClientFactory;

    /**
     * AmbCustomerRepository constructor.
     * @param ConsumerLogFileFactory $consumerLogFactory
     * @param \Magento\Framework\Stdlib\DateTime\DateTime $dateTime
     * @param \Psr\Log\LoggerInterface $logger
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Riki\Customer\Helper\Api $apiCustomerHelper
     * @param \Riki\Customer\Helper\ConsumerDb\Soap $soapHelper
     * @param \Riki\Framework\Webapi\Soap\ClientFactory $soapClientFactory
     */
    public function __construct(
        \Riki\Customer\Model\ConsumerLogFileFactory $consumerLogFactory,
        \Magento\Framework\Stdlib\DateTime\DateTime $dateTime,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Riki\Customer\Helper\Api $apiCustomerHelper,
        \Riki\Customer\Helper\ConsumerDb\Soap $soapHelper,
        \Riki\Framework\Webapi\Soap\ClientFactory $soapClientFactory
    ) {
        $this->_consumerLogFactory = $consumerLogFactory;
        $this->_dateTime = $dateTime;
        $this->_logger = $logger;
        $this->_scopeConfig = $scopeConfig;
        $this->_apiCustomerHelper = $apiCustomerHelper;
        $this->soapHelper = $soapHelper;
        $this->soapClientFactory = $soapClientFactory;
    }

    /**
     * @param $wsdl
     * @param $config
     * @return \Zend\Soap\Client
     */
    private function initSoapClient($wsdl, $config)
    {
        return $this->soapClientFactory->create($wsdl, $config);
    }

    /**
     * SetAmbCustomerAPI
     *
     * @param array $ambCustomerInfo
     *
     * @param $requestType = 1 | 1: new, 2: edit, 3: delete
     *
     * @return bool
     */
    public function setAmbCustomerAPI(
        $ambCustomerInfo = [],
        $requestType = 1,
        $customerCode = ''
    ) {
        //set Log for call API
        $logModel = $this->_consumerLogFactory->create();
        $now = $this->_dateTime->date('Y/m/d H:m:i');
        $logModel->setName('setAmbassadorInfo');
        $logModel->setDescription('Set AmbassadorInfo API');
        $logModel->setDate($now);

        $now = $this->_dateTime->date('Y/m/d H:m:i');

        $soapConfig = $this->soapHelper->getCommonRequestParams();

        $wsdl = $this->_apiCustomerHelper->getConsumerApiUrl('/SetAmbassadorInfoService?wsdl');
        $endPoint = $this->_apiCustomerHelper->getConsumerApiUrl('/SetAmbassadorInfoService.SetAmbassadorInfoServiceHttpSoap12Endpoint/');
        $param1 = $this->_scopeConfig->getValue('consumer_db_api_url/ambassador/param1', \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE);
        $param2 = $this->_scopeConfig->getValue('consumer_db_api_url/ambassador/param2', \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE);

        $soapClient = $this->initSoapClient($wsdl, $soapConfig);
        $soapClient->setLocation($endPoint);

        $params = [];
        $params[] = new \SoapVar($param1, XSD_STRING, null, null, 'clientInfo' );
        $params[] = new \SoapVar($param2, XSD_STRING, null, null, 'clientInfo' );
        $params[] = new \SoapVar($now, XSD_STRING, null, null, 'clientInfo' );

        $params[] = new \SoapVar($requestType, XSD_STRING, null, null, 'requestType' );
        $params[] = new \SoapVar($customerCode, XSD_STRING, null, null, 'customerCode' );

        if (sizeof($ambCustomerInfo)) {
            $setParameterKey = [];
            foreach ($ambCustomerInfo as $key => $data) {
                $setParameterKey[] = new \SoapVar($key, XSD_STRING, null, null, 'array');
            }
            $params[] = new \SoapVar($setParameterKey, SOAP_ENC_ARRAY, null, null, 'setParameter');
            $setParameterValue = [];
            foreach ($ambCustomerInfo as $key => $data) {
                $setParameterValue[] = new \SoapVar($data, XSD_STRING, null, null, 'array');
            }
            $params[] = new \SoapVar($setParameterValue, SOAP_ENC_ARRAY, null, null, 'setParameter');
        } else {
            // missing this empty node, the API will throw an error
            $params[] = new \SoapVar([], SOAP_ENC_ARRAY, null, null, 'setParameter');
        }
        $logModel->setRequest(\Zend_Json::encode(array(
            'customerCode' => $customerCode,
            'setParameter' => $ambCustomerInfo,
            JSON_UNESCAPED_UNICODE)));
        try{
            $response = $soapClient->setAmbassadorInfo( new \SoapVar($params, SOAP_ENC_OBJECT) );
            if(property_exists($response,'return')){
                $codeReturn = $response->return;
                if(isset($codeReturn[0]) && \Riki\Customer\Model\SsoConfig::SSO_RESPONSE_SUCCESS_CODE == $codeReturn[0]){
                    if(isset($codeReturn[3])){
                        $logModel->setResponseData(\Zend_Json::encode($codeReturn,JSON_UNESCAPED_UNICODE));
                        $logModel->setStatus(1);
                        $logModel->save();
                        return $codeReturn;
                    }
                }
                else{
                    $logModel->setResponseData(\Zend_Json::encode($codeReturn,JSON_UNESCAPED_UNICODE));
                    $logModel->setStatus(0);
                    $logModel->save();
                    return false;
                }
            }
            else{
                $logModel->setResponseData(\Zend_Json::encode($response,JSON_UNESCAPED_UNICODE));
                $logModel->setStatus(0);
                $logModel->save();
            }
            $logModel->save();
            return false;
        } catch (\Exception $e) {
            $logModel->setResponseData($e->getMessage());
            $logModel->setStatus(0);
            $logModel->save();
            return false;
        }
    }

    /**
     * @param string $customerCode
     * @return bool
     */
    public function getAmbassadorInfo($customerCode = '')
    {
        //set Log for call API
        $logModel = $this->_consumerLogFactory->create();
        $now = $this->_dateTime->date('Y/m/d H:m:i');
        $logModel->setName('getAmbassadorInfo');
        $logModel->setDescription('get Ambassador Info API');
        $logModel->setDate($now);

        $soapConfig = $this->soapHelper->getCommonRequestParams();

        $wsdl = $this->_apiCustomerHelper->getConsumerApiUrl('/GetAmbassadorInfoService?wsdl');
        $endPoint = $this->_apiCustomerHelper->getConsumerApiUrl('/GetAmbassadorInfoService.GetAmbassadorInfoServiceHttpSoap12Endpoint/');
        $param1 = $this->_scopeConfig->getValue('consumer_db_api_url/ambassador/param1', \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE);
        $param2 = $this->_scopeConfig->getValue('consumer_db_api_url/ambassador/param2', \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE);

        $soapClient = $this->initSoapClient($wsdl, $soapConfig);
        $soapClient->setLocation($endPoint);

        $params = [];
        $params[] = new \SoapVar($param1, XSD_STRING, null, null, 'clientInfo' );
        $params[] = new \SoapVar($param2, XSD_STRING, null, null, 'clientInfo' );
        $params[] = new \SoapVar($now, XSD_STRING, null, null, 'clientInfo' );
        $params[] = new \SoapVar($customerCode, XSD_STRING, null, null, 'customerCode' );

        $logModel->setRequest(\Zend_Json::encode(array(
            'description'=> 'getAmbassador info',
            'customerCode' => $customerCode,
            'params' => $params)));
        try{
            $response = $soapClient->getAmbassadorInfo( new \SoapVar($params, SOAP_ENC_OBJECT) );
            if(property_exists($response,'return')){
                $codeReturn = $response->return;
                if(isset($response->return[0]->array[0]) && \Riki\Customer\Model\SsoConfig::SSO_RESPONSE_SUCCESS_CODE == $response->return[0]->array[0]){
                    if(isset($codeReturn[3])){
                        $logModel->setResponseData(\Zend_Json::encode($codeReturn,JSON_UNESCAPED_UNICODE));
                        $logModel->setStatus(1);
                        $logModel->save();
                        return $response;
                    }
                }
                else{
                    $logModel->setResponseData(\Zend_Json::encode($codeReturn,JSON_UNESCAPED_UNICODE));
                    $logModel->setStatus(0);
                    $logModel->save();
                    return $response;
                }
            }
        } catch (\Exception $e) {
            $logModel->setResponseData($e->getMessage());
            $logModel->setStatus(0);
            $logModel->save();
        }
        return false;
    }

    /**
     * @param string $customerCode
     * @return mixed
     */
    public function getCustomerSub($subCode = [], $customerCode = '')
    {
        //set Log for call API
        $logModel = $this->_consumerLogFactory->create();
        $now = $this->_dateTime->date('Y/m/d H:m:i');
        $logModel->setName('getCustomerSub');
        $logModel->setDescription('get Customer Sub Info API');
        $logModel->setDate($now);

        $soapConfig = $this->soapHelper->getCommonRequestParams();

        $wsdl = $this->_apiCustomerHelper->getConsumerApiUrl('/GetCustomerSubService?wsdl');
        $endPoint = $this->_apiCustomerHelper->getConsumerApiUrl('/GetCustomerSubService.GetCustomerSubServiceHttpSoap12Endpoint/');
        $param3 = $this->_scopeConfig->getValue('consumer_db_api_url/customer_sub/param3', \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE);
        $param4 = $this->_scopeConfig->getValue('consumer_db_api_url/customer_sub/param4', \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE);

        $soapClient = $this->initSoapClient($wsdl, $soapConfig);
        $soapClient->setLocation($endPoint);

        $params = [];
        $params[] = new \SoapVar($param3, XSD_STRING, null, null, 'clientInfo' );
        $params[] = new \SoapVar($param4, XSD_STRING, null, null, 'clientInfo' );
        $params[] = new \SoapVar($now, XSD_STRING, null, null, 'clientInfo' );
        $params[] = new \SoapVar($customerCode, XSD_STRING, null, null, 'customerCode' );


        if (sizeof($subCode)) {
            foreach ($subCode as $key) {
                $params[] = new \SoapVar($key, XSD_STRING, null, null, 'getParameter');
            }
        } else {
            // missing this empty node, the API will throw an error
            $params[] = new \SoapVar([], SOAP_ENC_ARRAY, null, null, 'getParameter');
        }

        $logModel->setRequest(\Zend_Json::encode(array(
            'description'=> 'get customer sub info API',
            'customerCode' => $customerCode,
            'params' => $params,
            JSON_UNESCAPED_UNICODE)));
        try{
            $response = $soapClient->getCustomerSub( new \SoapVar($params, SOAP_ENC_OBJECT) );
            if(property_exists($response,'return')){
                $codeReturn = $response->return;
                if(isset($codeReturn[0]->array[0]) && \Riki\Customer\Model\SsoConfig::SSO_RESPONSE_SUCCESS_CODE == $codeReturn[0]->array[0]){
                    if(isset($codeReturn[3])){
                        $logModel->setResponseData(\Zend_Json::encode($codeReturn));
                        $logModel->setStatus(1);
                        $logModel->save();
                        return $response;
                    }
                }
                else{
                    $logModel->setResponseData(\Zend_Json::encode($codeReturn));
                    $logModel->setStatus(0);
                    $logModel->save();
                    return $response;
                }
            }
        } catch (\Exception $e) {
            $logModel->setResponseData($e->getMessage());
            $logModel->setStatus(0);
            $logModel->save();
        }
        return false;
    }
}