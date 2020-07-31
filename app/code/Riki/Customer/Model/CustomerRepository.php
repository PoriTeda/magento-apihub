<?php
namespace Riki\Customer\Model;

use Magento\TestFramework\Event\Magento;
use Magento\Framework\Exception\InputException;
use \Riki\SubscriptionMachine\Model\MachineConditionRule;

/**
 * Class CustomerRepository
 * @package Riki\Customer\Model
 */
class CustomerRepository
{
    const CONST_DEFAULT_COUNTRY = 'JP';
    const CUSTOMER_KEY = 'CUSTOMER_KEY';
    const CUSTOMER_VALUE = 'CUSTOMER_VALUE';
    const CUSTOMER_SUB_KEY = 'CUSTOMER_SUB_KEY';
    const CUSTOMER_SUB_VALUE = 'CUSTOMER_SUB_VALUE';
    const AMBASSADOR_INFO_KEY = 'AMBASSADOR_INFO_KEY';
    const AMBASSADOR_INFO_VALUE = 'AMBASSADOR_INFO_VALUE';
    const MACHINE_KEY = 'MACHINE_KEY';
    const MACHINE_VALUE = 'MACHINE_VALUE';
    const SUB_PROFILE_ID_MACHINE_TYPE_NBA = '915';
    const SUB_PROFILE_ID_MACHINE_TYPE_NDG = '916';
    const SUB_PROFILE_ID_MACHINE_TYPE_SPT = '917';
    const SUB_PROFILE_ID_MACHINE_TYPE_BLC = '918';
    const SUB_PROFILE_ID_MACHINE_TYPE_NESPRESSO = '919';
    const SUB_PROFILE_ID_MACHINE_TYPE_DUO = '2570';

    /**
     * @var array
     */
    protected $_ambMapValue = [
        0 => "",
        1 => "11",
        9 => "99"
    ];


    /**
     * @var array $_arraySubId
     */
    protected $_arraySubId = [
        'AMB_TYPE' => 770,
        'BUSINESS_CODE'=> 720,
        'USE_POINT_TYPE'=>840,
        'USE_POINT_AMOUNT'=>841,
        'EDIT_MESSAGE' => 750,
        'NHS_INTRODUCER_ID' =>852,
        'AMB_REFERENCE_CUS_CODE'=> 810,
        'PETSHOP_APPLICATION_DATE' => 830,
        'PETSHOP_AUTHORIZED_DATE' => 831,
        'PET_BREED' => 832,
        'PET_SEX' => 833,
        'PET_BIRTH_DT' => 834,
        'PETSHOP_CODE' =>835,
        'PET_NAME' => 836,
        'PA_CUSTOMER_TYPE'=> 880,
        'LENDING_STATUS_NBA'=>915,
        'LENDING_STATUS_NDG'=>916,
        'LENDING_STATUS_SPT'=>917,
        'LENDING_STATUS_ICS'=>918,
        'LENDING_STATUS_NSP'=>919,
        'NWC_CAT_STATUS'=> 960,
        'NWC_CUSTOMER_STATUS'=> 890,
        'SUBSCRIPTION_STATUS'=> 1131,
        'CNC_Status' => 1133,
        'CIS_Status'=> 1134,
        'CHOCOLLATORY_FLG' => 980,
        'KITKAT_CLUB_FLG' => 990,
        'MILANO_STATUS' => 1135,
        'ALLEGRIA_STATUS' =>1136,
        'SATELLITE_FLG' =>970,
        'AMB_FRIENDS' => 1200,
        'SATELLITE_AMB' =>1201,
        'WELLNESSCLUB_AMB'=>1202,
        'GARDIAN_APPROVAL'=>710,
        'SUBSCRIPTION_CUMU_DELIVERY'=>1132,
        'HANPUKAI_STATUS'=>1137,
        'AMB_SALE' => 920,
        'WAMB_Status'=>1360,
        'NescafeStandFlg'=>1850,
        'AMB_DUO_SKU' => 2571,
        'LENDING_STATUS_DUO' => 2570
    ];

    /**
     * @var array $_arrayCusMappingId
     */
    protected $_arrayCusMappingId = [
        "KEY_LAST_NAME" => 'lastname',
        "KEY_FIRST_NAME" => 'firstname',
        "KEY_LAST_NAME_KANA" => 'lastnamekana',
        "KEY_FIRST_NAME_KANA"=> 'firstnamekana',
        "KEY_SEX" => 'gender',
        "KEY_BIRTH_DATE" => 'dob',
        "KEY_LOGIN_EMAIL" => 'email',
        "KEY_EMAIL" => 'email',
        "KEY_EMAIL2" => 'email_2',
        "CUSTOMER_TYPE" => 'offline_customer'
    ];

    /**
     * @var array $_arrayAmbMappingId
     */
    protected $_arrayAmbMappingId = [
        "COM_NAME" => "amb_com_name",
        "COM_DIVISION_NAME" => "amb_com_division_name",
        "COM_PH_NUM" => "amb_ph_num",

    ];

    /**
     * @var array
     */
    protected $_arraySubEckey = [
        "LENDING_STATUS_NBA" => "status_machine_NBA",
        "LENDING_STATUS_NDG" => "status_machine_NDG",
        "LENDING_STATUS_SPT" => "status_machine_SPT",
        "LENDING_STATUS_ICS" => "status_machine_BLC",
        "LENDING_STATUS_NSP" => "status_machine_Nespresso"
    ];

    /**
     * @var \Riki\Customer\Model\AmbCustomerRepository $_ambCustomerRepository
     */
    protected $_ambCustomerRepository;

    /**
     * @var array MappingAttributeSubProfile
     */
    protected $_mappingAttributeSubProfile = array(
        'CNC_Status' => 1133,
        'CIS_Status'=> 1134,
        'BUSINESS_CODE'=> 720
    );

    /**
     * @var \Riki\Customer\Helper\Membership ,
     */
    protected $_customerMembershipHelper;

    /**
     * @var Magento system config
     *
     */
    protected $_scopeConfig;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface $storeManager
     */
    protected $storeManager;

    /**
     * @var \Magento\Customer\Api\Data\CustomerInterface
     */
    protected $_customerInterface;

    /**
     * @var \Magento\Customer\Api\Data\AddressInterfaceFactory|\Magento\Customer\Model\AddressFactory $_customerAddressFactory
     */
    protected $_customerAddressFactory;

    /**
     * @var \Riki\Customer\Logger\ConsumerLog\Logger
     */
    protected $consumerLog;
    /**
     * Directory data
     *
     * @var \Magento\Directory\Helper\Data
     */
    protected $directoryData;

    /**
     * @var \Magento\Customer\Model\AddressFactory
     */
    protected $_addressModelFactory;
    /**
     * @var \Magento\Directory\Helper\Data $_regionHelper
     */
    protected $_regionHelper;

    /**
     * @var \Riki\Customer\Helper\Region $_rikiRegionHelper
     */
    protected $_rikiRegionHelper;

    /**
     * @var \Magento\Customer\Api\AddressRepositoryInterface $_customerAddressRepository
     */
    protected $_customerAddressRepository;

    /**
     * @var \Riki\Customer\Helper\Api
     */
    protected $_apiCustomerHelper;

    /**
     * @var array $_arrayCusId
     */
    protected $_arrayCusId = [
        "KEY_LAST_NAME",
        "KEY_FIRST_NAME",
        "KEY_LAST_NAME_KANA",
        "KEY_FIRST_NAME_KANA",
        "KEY_ADDRESS_LAST_NAME",
        "KEY_ADDRESS_LAST_NAME_KANA",
        "KEY_ADDRESS_FIRST_NAME",
        "KEY_ADDRESS_FIRST_NAME_KANA",
        "KEY_SEX",
        "KEY_BIRTH_DATE",
        "KEY_BIRTH_FLG",
        "KEY_MARITAL_STAT_CODE",
        "KEY_CLIENT_MAIL_TYPE",
        "KEY_LOGIN_EMAIL",
        "KEY_EMAIL",
        "KEY_CLIENT_MAIL_TYPE2",
        "KEY_EMAIL2",
        "KEY_JOB_TITLE",
        "KEY_PASSWORD",
        "KEY_HANDLE_NAME",
        "KEY_CELL_NUMBER",
        "KEY_ASST_PH_NUM",
        "KEY_POST_NAME",
        "KEY_COMPANY_NAME",
        "KEY_WORK_PH_NUM",
        "KEY_CAUTION",
        "KEY_EPS_FLG",
        "CUSTOMER_TYPE",
        "EMP_FLG",
        'KEY_POSTAL_CODE',
        'KEY_PREFECTURE_CODE',
        'KEY_ADDRESS1',
        'KEY_ADDRESS2',
        'KEY_ADDRESS3',
        'KEY_ADDRESS4',
        'KEY_PHONE_NUMBER',
    ];

    /**
     * @var array $_mappingZoneJapan
     */
    protected $_mappingZoneJapan = array(
        'HKD' => 1,
        'AMR' => 2,
        'IWT' => 3,
        'MYG' => 4,
        'AKT' => 5,
        'YGT' => 6,
        'FSM' => 7,
        'IBR' => 8,
        'TOC' => 9,
        'GUM' => 10,
        'STM' => 11,
        'CHB' => 12,
        'TKY' => 13,
        'KNG' => 14,
        'NGT' => 15,
        'TYM' => 16,
        'IKW' => 17,
        'FKI' => 18,
        'YNS' => 19,
        'NGN' => 20,
        'GFU' => 21,
        'SZK' => 22,
        'AIC' => 23,
        'MIE' => 24,
        'SHG' => 25,
        'KYT' => 26,
        'OSK' => 27,
        'HYG' => 28,
        'NRA' => 29,
        'WKY' => 30,
        'TTR' => 31,
        'SMN' => 32,
        'OKY' => 33,
        'HRS' => 34,
        'YGC' => 35,
        'TKS' => 36,
        'KGW' => 37,
        'EHM' => 38,
        'KCH' => 39,
        'FKO' => 40,
        'SAG' => 41,
        'NGS' => 42,
        'KMM' => 43,
        'OTA' => 44,
        'MYZ' => 45,
        'KGS' => 46,
        'OKN' => 47,
    );

    /**
     * @var \Riki\Customer\Helper\ConsumerDb\Soap
     */
    protected $soapHelper;

    /**
     * @var \Riki\Framework\Webapi\Soap\ClientFactory
     */
    protected $soapClientFactory;

    /**
     * CustomerRepository constructor.
     * @param ConsumerLogFileFactory $consumerLogFactory
     * @param \Magento\Framework\Stdlib\DateTime\DateTime $dateTime
     * @param \Psr\Log\LoggerInterface $logger
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Customer\Api\Data\CustomerInterfaceFactory $customerDataFactory
     * @param \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository
     * @param \Riki\Customer\Helper\Membership $membershipHelper
     * @param \Magento\Customer\Api\AddressRepositoryInterface $customerAddressRepository
     * @param \Magento\Customer\Api\Data\AddressInterfaceFactory $customerAddressFactory
     * @param \Magento\Directory\Helper\Data $regionHelper
     * @param AmbCustomerRepository $_ambCustomerRepository
     * @param \Riki\Customer\Helper\Region $rikiRegionHelper
     * @param \Riki\Customer\Helper\Api $apiCustomerHelper
     * @param \Magento\Customer\Model\AddressFactory $addressModelFactory
     * @param \Magento\Directory\Helper\Data $directoryData
     * @param \Riki\Customer\Logger\ConsumerLog\Logger $consumerLog
     * @param \Riki\Customer\Helper\ConsumerDb\Soap $soapHelper
     * @param \Riki\Framework\Webapi\Soap\ClientFactory $soapClientFactory
     */
    public function __construct(
        \Riki\Customer\Model\ConsumerLogFileFactory $consumerLogFactory,
        \Magento\Framework\Stdlib\DateTime\DateTime $dateTime,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Customer\Api\Data\CustomerInterfaceFactory $customerDataFactory,
        \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository,
        \Riki\Customer\Helper\Membership $membershipHelper,
        \Magento\Customer\Api\AddressRepositoryInterface $customerAddressRepository,
        \Magento\Customer\Api\Data\AddressInterfaceFactory $customerAddressFactory,
        \Magento\Directory\Helper\Data $regionHelper,
        \Riki\Customer\Model\AmbCustomerRepository $_ambCustomerRepository,
        \Riki\Customer\Helper\Region $rikiRegionHelper,
        \Riki\Customer\Helper\Api $apiCustomerHelper,
        \Magento\Customer\Model\AddressFactory $addressModelFactory,
        \Magento\Directory\Helper\Data $directoryData,
        \Riki\Customer\Logger\ConsumerLog\Logger $consumerLog,
        \Riki\Customer\Helper\ConsumerDb\Soap $soapHelper,
        \Riki\Framework\Webapi\Soap\ClientFactory $soapClientFactory
    ) {
        $this->_consumerLogFactory = $consumerLogFactory;
        $this->_dateTime = $dateTime;
        $this->_logger = $logger;
        $this->_scopeConfig = $scopeConfig;
        $this->storeManager = $storeManager;
        $this->_customerInterface = $customerDataFactory;
        $this->customerRepository = $customerRepository;
        $this->_customerMembershipHelper = $membershipHelper;
        $this->_customerAddressFactory = $customerAddressFactory;
        $this->_customerAddressRepository = $customerAddressRepository;
        $this->_regionHelper = $regionHelper;
        $this->_ambCustomerRepository = $_ambCustomerRepository;
        $this->_rikiRegionHelper = $rikiRegionHelper;
        $this->_apiCustomerHelper = $apiCustomerHelper;
        $this->_addressModelFactory = $addressModelFactory;
        $this->directoryData = $directoryData;
        $this->consumerLog = $consumerLog;
        $this->soapHelper = $soapHelper;
        $this->soapClientFactory = $soapClientFactory;
    }

    /**
     * @param $wsdl
     * @param $soapConfig
     * @return \Zend\Soap\Client
     */
    private function initSoapClient($wsdl, $soapConfig)
    {
        return $this->soapClientFactory->create($wsdl, $soapConfig);
    }

    /**
     * SetCustomerAPI
     *
     * @param $customerInfo
     *
     * @param $addressInfo
     *
     * @param $requestType = 1 | 1: new, 2: edit, 3: delete
     * @param $customerCode = '' | create new is default
     *
     * @return bool
     */
    public function setCustomerAPI(
        $customerInfo = [],
        $addressInfo = [],
        $requestType = 1,
        $customerCode = ''
    ) {
        //set Log for call API
        $logModel = $this->_consumerLogFactory->create();
        $now = $this->_dateTime->date('Y/m/d H:m:i');
        $logModel->setName('setCustomer');
        $logModel->setDescription('Set Customer API');
        $logModel->setDate($now);

        $soapConfig = $this->soapHelper->getCommonRequestParams();

        $wsdl = $this->_apiCustomerHelper->getConsumerApiUrl('/SetCustomerService?wsdl');
        $endPoint = $this->_apiCustomerHelper->getConsumerApiUrl('/SetCustomerService.SetCustomerServiceHttpSoap12Endpoint/');
        $param1 = $this->_scopeConfig->getValue('consumer_db_api_url/customer/param1', \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE);
        $param2 = $this->_scopeConfig->getValue('consumer_db_api_url/customer/param2', \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE);

        $soapClient = $this->initSoapClient($wsdl, $soapConfig);
        $soapClient->setLocation($endPoint);

        $params = [];
        $params[] = new \SoapVar($param1, XSD_STRING, null, null, 'clientInfo' );
        $params[] = new \SoapVar($param2, XSD_STRING, null, null, 'clientInfo' );
        $params[] = new \SoapVar($now, XSD_STRING, null, null, 'clientInfo' );

        $params[] = new \SoapVar($requestType, XSD_STRING, null, null, 'requestType' );
        $params[] = new \SoapVar($customerCode, XSD_STRING, null, null, 'customerCode' );

        if (sizeof($customerInfo)) {
            $setParameterKey = [];
            foreach ($customerInfo as $key => $data) {
                $setParameterKey[] = new \SoapVar($key, XSD_STRING, null, null, 'array');
            }
            $params[] = new \SoapVar($setParameterKey, SOAP_ENC_ARRAY, null, null, 'setParameter');
            $setParameterValue = [];
            foreach ($customerInfo as $key => $data) {
                $setParameterValue[] = new \SoapVar($data, XSD_STRING, null, null, 'array');
            }
            $params[] = new \SoapVar($setParameterValue, SOAP_ENC_ARRAY, null, null, 'setParameter');
        } else {
            // missing this empty node, the API will throw an error
            $params[] = new \SoapVar([], SOAP_ENC_ARRAY, null, null, 'setParameter');
        }

        if (sizeof($addressInfo)) {
            $setParameterKey = [];
            foreach ($addressInfo as $key => $data) {
                $setParameterKey[] = new \SoapVar($key, XSD_STRING, null, null, 'array');
            }
            $params[] = new \SoapVar($setParameterKey, SOAP_ENC_ARRAY, null, null, 'setParameterAddress');
            $setParameterValue = [];
            foreach ($addressInfo as $key => $data) {
                $setParameterValue[] = new \SoapVar($data, XSD_STRING, null, null, 'array');
            }
            $params[] = new \SoapVar($setParameterValue, SOAP_ENC_ARRAY, null, null, 'setParameterAddress');
        }
        $logModel->setRequest(\Zend_Json::encode(array(
            'customerCode' => $customerCode,
            'requestType' => $requestType,
            'setParameter' => ['customer'=>$customerInfo,'address'=>$addressInfo],
            JSON_UNESCAPED_UNICODE))
        );
        try{
            $response = $soapClient->setCustomer( new \SoapVar($params, SOAP_ENC_OBJECT) );
            $logModel->setResponseData(\Zend_Json::encode($response));
            $logModel->save();
            return $response;
        } catch (\Exception $e) {
            $logModel->save();
            $this->_logger->error((string)$e->getMessage());
            return false;
        }

        return false;
    }

    protected function _getSubProfileId($key){
        if(isset($this->_mappingAttributeSubProfile[$key])){
            return $this->_mappingAttributeSubProfile[$key];
        }
        return false;
    }

    public function setCustomerSubAPI($customerCode, $customerSubInfo = [])
    {
        //set Log for call API
        $logModel = $this->_consumerLogFactory->create();
        $now = $this->_dateTime->date('Y/m/d H:m:i');
        $logModel->setName('setCustomerSub');
        $logModel->setDescription('Set Customer Sub Profile API');
        $logModel->setDate($now);

        $soapConfig = $this->soapHelper->getCommonRequestParams();

        $wsdl = $this->_apiCustomerHelper->getConsumerApiUrl('/SetCustomerSubService?wsdl');
        $endPoint = $this->_apiCustomerHelper->getConsumerApiUrl('/SetCustomerSubService.SetCustomerSubServiceHttpSoap12Endpoint/');
        $param1 = $this->_scopeConfig->getValue('consumer_db_api_url/customer_sub/param1', \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE);
        $param2 = $this->_scopeConfig->getValue('consumer_db_api_url/customer_sub/param2', \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE);

        $soapClient = $this->initSoapClient($wsdl, $soapConfig);
        $soapClient->setLocation($endPoint);

        $params = [];
        $params[] = new \SoapVar($param1, XSD_STRING, null, null, 'clientInfo');
        $params[] = new \SoapVar($param2, XSD_STRING, null, null, 'clientInfo');
        $params[] = new \SoapVar($now, XSD_STRING, null, null, 'clientInfo');

        $params[] = new \SoapVar($customerCode, XSD_STRING, null, null, 'customerCode');

        $setParameterKey = [];
        $setParameterKey[] = new \SoapVar('KEY_CUSTOMER_CODE', XSD_STRING, null, null, 'array');
        $setParameterKey[] = new \SoapVar('KEY_SUBPROFILE_ID', XSD_STRING, null, null, 'array');
        $setParameterKey[] = new \SoapVar('KEY_VALUE_NAME', XSD_STRING, null, null, 'array');

        $params[] = new \SoapVar($setParameterKey, SOAP_ENC_ARRAY, null, null, 'setParameter');

        if (sizeof($customerSubInfo)) {
            foreach ($customerSubInfo as $key => $data) {
                $setParameterValue = [];
                $setParameterValue[] = new \SoapVar($customerCode, XSD_STRING, null, null, 'array');
                $setParameterValue[] = new \SoapVar($key, XSD_STRING, null, null, 'array');
                $setParameterValue[] = new \SoapVar($data, XSD_STRING, null, null, 'array');
                $params[] = new \SoapVar($setParameterValue, SOAP_ENC_ARRAY, null, null, 'setParameter');
            }
        } else {
            // missing this empty node, the API will throw an error
            $params[] = new \SoapVar([], SOAP_ENC_ARRAY, null, null, 'setParameter');
        }

        $logModel->setRequest(\Zend_Json::encode(array(
            'customerCode' => $customerCode,
            'setParameter' => $customerSubInfo,
            JSON_UNESCAPED_UNICODE))
        );

        try {
            $response = $soapClient->setCustomerSub(new \SoapVar($params, SOAP_ENC_OBJECT));
        } catch (\Exception $e) {
            $this->_logger->critical($e);
            return false;
        }

        $isError = true;
        if (property_exists($response, 'return')) {
            $codeReturn = $response->return;
            if (isset($codeReturn[0]) && \Riki\Customer\Model\SsoConfig::SSO_RESPONSE_SUCCESS_CODE == $codeReturn[0]) {
                if (isset($codeReturn[3])) {
                    $logModel->setResponseData(\Zend_Json::encode($codeReturn, JSON_UNESCAPED_UNICODE));
                    $logModel->setStatus(1);
                    $isError = false;
                }
            } else {
                $logModel->setResponseData(\Zend_Json::encode($codeReturn, JSON_UNESCAPED_UNICODE));
                $logModel->setStatus(0);
            }
        } else {
            $logModel->setResponseData(\Zend_Json::encode($response, JSON_UNESCAPED_UNICODE));
            $logModel->setStatus(0);
        }

        try {
            $logModel->save();
        } catch (\Exception $e) {
            $this->_logger->critical($e);
        }

        if ($isError) {
            return false;
        } else {
            return $response;
        }
    }

    /**
     * @param array $cusCode
     * @param string $customerCode
     * @return mixed
     */
    public function getCustomer(
        $cusCode = [],
        $customerCode = ''
    ){
        //set Log for call API
        $logModel = $this->_consumerLogFactory->create();
        $now = $this->_dateTime->date('Y/m/d H:m:i');
        $logModel->setName('getCustomer');
        $logModel->setDescription('get Customer Info API');
        $logModel->setDate($now);

        $soapConfig = $this->soapHelper->getCommonRequestParams();

        $wsdl = $this->_apiCustomerHelper->getConsumerApiUrl('/GetCustomerService?wsdl');
        $endPoint = $this->_apiCustomerHelper->getConsumerApiUrl('/GetCustomerService.GetCustomerServiceHttpSoap12Endpoint/');
        $param3 = $this->_scopeConfig->getValue('consumer_db_api_url/customer/param3', \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE);
        $param4 = $this->_scopeConfig->getValue('consumer_db_api_url/customer/param4', \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE);


        $soapClient = $this->initSoapClient($wsdl, $soapConfig);
        $soapClient->setLocation($endPoint);

        $params = [];
        $params[] = new \SoapVar($param3, XSD_STRING, null, null, 'clientInfo' );
        $params[] = new \SoapVar($param4, XSD_STRING, null, null, 'clientInfo' );
        $params[] = new \SoapVar($now, XSD_STRING, null, null, 'clientInfo' );
        $params[] = new \SoapVar($customerCode, XSD_STRING, null, null, 'customerCode' );


        if (sizeof($cusCode)) {
            foreach ($cusCode as $key) {
                $params[] = new \SoapVar($key, XSD_STRING, null, null, 'getParameter');
            }
        } else {
            // missing this empty node, the API will throw an error
            $params[] = new \SoapVar([], SOAP_ENC_ARRAY, null, null, 'getParameter');
        }

        $logModel->setRequest(\Zend_Json::encode(array(
            'description'=> 'get Customer Info API',
            'customerCode' => $customerCode,
            'params' => $params,
            JSON_UNESCAPED_UNICODE)));
        try{
            $response = $soapClient->getCustomer( new \SoapVar($params, SOAP_ENC_OBJECT) );
            if(property_exists($response,'return')){
                $codeReturn = $response->return;
                if(isset($codeReturn[0]->array[0]) && \Riki\Customer\Model\SsoConfig::SSO_RESPONSE_SUCCESS_CODE == $codeReturn[0]->array[0]){
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
     * @param array $cusCode
     * @param array $cusSubCode
     * @param string $customerCode
     * @return mixed
     */
    public function getAllCustomerInfo(
        $cusCode = [],
        $cusSubCode = [],
        $customerCode = ''
    ){
        //set Log for call API
        $logModel = $this->_consumerLogFactory->create();
        $now = $this->_dateTime->date('Y/m/d H:m:i');
        $logModel->setName('getAllCustomer');
        $logModel->setDescription('get All Customer Info API');
        $logModel->setDate($now);

        $soapConfig = $this->soapHelper->getCommonRequestParams();

        $wsdl = $this->_apiCustomerHelper->getConsumerApiUrl('/GetAllInfoCustomerService?wsdl');
        $endPoint = $this->_apiCustomerHelper->getConsumerApiUrl('/GetAllInfoCustomerService.GetAllInfoCustomerServiceHttpSoap12Endpoint/');
        $param3 = $this->_scopeConfig->getValue('consumer_db_api_url/customer/param3', \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE);
        $param4 = $this->_scopeConfig->getValue('consumer_db_api_url/customer/param4', \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE);


        $soapClient = $this->initSoapClient($wsdl, $soapConfig);
        $soapClient->setLocation($endPoint);

        $params = [];
        $params[] = new \SoapVar($param3, XSD_STRING, null, null, 'clientInfo' );
        $params[] = new \SoapVar($param4, XSD_STRING, null, null, 'clientInfo' );
        $params[] = new \SoapVar($now, XSD_STRING, null, null, 'clientInfo' );
        $params[] = new \SoapVar($customerCode, XSD_STRING, null, null, 'customerCode' );


        if (sizeof($cusCode)) {
            foreach ($cusCode as $key) {
                $params[] = new \SoapVar($key, XSD_STRING, null, null, 'getCustomerParameter');
            }
        } else {
            // missing this empty node, the API will throw an error
            $params[] = new \SoapVar([], SOAP_ENC_ARRAY, null, null, 'getCustomerParameter');
        }
        if (sizeof($cusSubCode)) {
            foreach ($cusSubCode as $key) {
                $params[] = new \SoapVar($key, XSD_STRING, null, null, 'getCustomerSubParameter');
            }
        } else {
            // missing this empty node, the API will throw an error
            $params[] = new \SoapVar([], SOAP_ENC_ARRAY, null, null, 'getCustomerSubParameter');
        }

        $logModel->setRequest(\Zend_Json::encode(array(
            'description'=> 'get All Customer Info API',
            'customerCode' => $customerCode,
            'params' => $params,
            JSON_UNESCAPED_UNICODE)));
        try{
            $response = $soapClient->getAllInfoCustomer( new \SoapVar($params, SOAP_ENC_OBJECT) );
            if(property_exists($response,'return')){
                $codeReturn = $response->return;
                if(isset($codeReturn[0]->array[0]) && \Riki\Customer\Model\SsoConfig::SSO_RESPONSE_SUCCESS_CODE == $codeReturn[0]->array[0]){
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
     * PrepareAllInfoCustomer
     *
     * @param $consumerDbId
     * @throws \Exception
     * @return array
     */
    public function prepareAllInfoCustomer($consumerDbId)
    {
        $cusResponse = $this->getAllCustomerInfo($this->_arrayCusId, $this->_arraySubId, $consumerDbId);
        $customersAPI = [];
        if ($cusResponse) {
            if (property_exists($cusResponse, 'return')) {
                $codeReturn = $cusResponse->return[0]->array;
                if (isset($codeReturn[0]) && \Riki\Customer\Model\SsoConfig::SSO_RESPONSE_SUCCESS_CODE == $codeReturn[0]) {
                    $machineKeyArray = $machineValueArray = $subInfo = $ambCustomer = $consumerDbResponse = $resultMachine = $resultToUpdateMachine = [];
                    $customerIndexArrayKey = $customerIndexArrayValue = $ambassadorIndexArrayKey = $ambassadorIndexArrayValue = 0;
                    foreach ($cusResponse->return as $indexObject => $valueObject) {
                        if ($valueObject->array[0] == self::CUSTOMER_KEY) {
                            $customerIndexArrayKey = $indexObject;
                        }
                        if ($valueObject->array[0] == self::CUSTOMER_VALUE) {
                            $customerIndexArrayValue = $indexObject;
                        }
                        if ($valueObject->array[0] == self::AMBASSADOR_INFO_KEY) {
                            $ambassadorIndexArrayKey = $indexObject;
                        }
                        if ($valueObject->array[0] == self::AMBASSADOR_INFO_VALUE) {
                            $ambassadorIndexArrayValue = $indexObject;
                        }
                        //Sub info
                        if ($valueObject->array[0] == self::CUSTOMER_SUB_VALUE) {
                            if ($this->getSubKey($valueObject->array[1]) == "AMB_TYPE") {
                                $subInfo['amb_type'] = $this->getAmbValue($valueObject->array[3]);
                            } else {
                                $customerSubKey = $this->getSubKey($valueObject->array[1]);
                                $customerSubValue = $valueObject->array[3];
                                list($customerSubKey, $customerSubValue) = $this->convertKeyValueField($customerSubKey, $customerSubValue);

                                $subInfo[$customerSubKey] = $customerSubValue;
                            }
                        }

                        //Filter machine info
                        if ($valueObject->array[0] == self::MACHINE_KEY) {
                            $machineKeyArray = $valueObject->array;
                        }
                        if ($valueObject->array[0] == self::MACHINE_VALUE) {
                            $machineValueArray[] = $valueObject->array;
                        }
                    }

                    $consumerDbResponse['customer_sub_api'] = $subInfo;

                    //Parse machine info
                    foreach ($machineKeyArray as $key => $nameOfMachineValue) {
                        if ($nameOfMachineValue == self::MACHINE_KEY) {
                            continue;
                        }
                        foreach ($machineValueArray as $k => $v) {
                            if ($v[$key] == self::MACHINE_VALUE) {
                                continue;
                            }
                            $resultMachine[$v[2]] = 1;
                            $resultToUpdateMachine[$k][$nameOfMachineValue] = $v[$key];
                        }
                    }
                    $consumerDbResponse['customer_machine_api'] = [$resultMachine, $resultToUpdateMachine];

                    // AMB
                    $ambCustomersKey = $cusResponse->return[$ambassadorIndexArrayKey]->array;;
                    $ambCustomersValue = $cusResponse->return[$ambassadorIndexArrayValue]->array;

                    foreach ($ambCustomersKey as $key => $ambCustomerKey) {
                        if ($ambCustomerKey == self::AMBASSADOR_INFO_KEY) {
                            continue;
                        }
                        if ($this->getAmbKey($ambCustomerKey)) {
                            $ambCustomer[$this->getAmbKey($ambCustomerKey)] = $ambCustomersValue[$key];
                        } else {
                            $ambCustomer[$ambCustomerKey] = $ambCustomersValue[$key];
                        }
                    }
                    $consumerDbResponse['amb_api'] = $ambCustomer;
                    if (isset($consumerDbResponse['amb_api']['COM_POSTAL_CODE'])) {
                        $consumerDbResponse['amb_api']['COM_POSTAL_CODE'] = substr_replace($consumerDbResponse['amb_api']['COM_POSTAL_CODE'], '-', 3, 0);
                    }
                    //Customer
                    $customersKey = isset($cusResponse->return[$customerIndexArrayKey]->array) ? $cusResponse->return[$customerIndexArrayKey]->array : [];
                    $customersValue = isset($cusResponse->return[$customerIndexArrayValue]->array) ? $cusResponse->return[$customerIndexArrayValue]->array : [];
                    foreach ($customersKey as $key => $customerKey) {
                        if ($customerKey == "SEX") {
                            if (isset($customersValue[$key])) {
                                $customersValue[$key] = $this->mappingGender($customersValue[$key]);
                            }
                        }
                        $customerKey = 'KEY_' . $customerKey;
                        $customerValue = $customersValue[$key];
                        list($customerKey, $customerValue) = $this->convertKeyValueField($customerKey, $customerValue);
                        $customersAPI[$customerKey] = $customerValue;
                    }
                    $consumerDbResponse['customer_api'] = $customersAPI;
                    $customersAPI = $this->updateCustomerAttribute($consumerDbResponse);
                    $consumerDbResponse['customer_api'] = $customersAPI['customer_api'];
                    // if email2 null, then set email_type_2 to null too
                    if (array_key_exists('email_2', $consumerDbResponse['customer_api'])
                        && $consumerDbResponse['customer_api']['email_2'] == null) {
                        $consumerDbResponse['customer_api']['KEY_CLIENT_MAIL_TYPE2'] = null;
                    }

                    /**
                     * Call Consumer DB - getMidInfo
                     */
                    $responseMidInfo = $this->getMidInfo($consumerDbId);
                    $consumerDbResponse['customer_getMidInfo'] = $responseMidInfo;
                    if ($responseMidInfo && isset($responseMidInfo[3]) && isset($responseMidInfo[4])) {
                        $item3 = (array)$responseMidInfo[3]->array;
                        $item4 = (array)$responseMidInfo[4]->array;
                        if (!empty($item3) && !empty($item4)) {
                            $item3 = array_flip($item3);
                            $indexLineId = isset($item3['USER_ID']) ? $item3['USER_ID'] : '';
                            if (isset($item4[$indexLineId])) {
                                $consumerDbResponse['customer_api']['line_id'] = $item4[$indexLineId];
                            }
                        }
                    }

                    return $consumerDbResponse;
                }
            }
            throw new \Magento\Framework\Exception\NoSuchEntityException(__('There no customer associated with id: %1', $consumerDbId));
        }
    }

    /**
     * @param array $data
     * @param int $consumerDbId
     * @param time $lastUpdateDate
     * @param \Magento\Customer\Model\Data\Customer $customer
     * @return mixed
     * @throws \Exception
     */
    public function createUpdateEcCustomer($data, $consumerDbId, $lastUpdateDate = null, $customer = null)
    {
        if ($data && count($data['customer_api']) > 0) {
            $newConsumerDataHash = sha1(json_encode($data));

            if ($customer && $customer->getId()) {
                $customerConsumerDataHash = $customer->getCustomAttribute('consumer_data_hash') ?: null;

                if ($customerConsumerDataHash && $customerConsumerDataHash->getValue() == $newConsumerDataHash) {
                    return $customer;
                }
            }

            $isNewCustomer = false;
            if (!$lastUpdateDate) {
                $lastUpdateDate = $this->_dateTime->date('Y/m/d H:m:i');
            }

            $websiteId  = $this->storeManager->getWebsite()->getWebsiteId();
            $addressDataModel = array();
            if(!$customer){
                $customer = $this->_customerInterface->create();
                $isNewCustomer = true;
            }

            if($address = $customer->getAddresses()){
                foreach ($address as $addressItem){
                    $addressType =  $addressItem->getCustomAttribute('riki_type_address');
                    if( $addressType instanceof \Magento\Framework\Api\AttributeValue){
                        if($addressType->getValue() ==\Riki\Customer\Model\Address\AddressType::HOME || $addressType->getValue() ==\Riki\Customer\Model\Address\AddressType::OFFICE){
                            $addressDataModel[] = $addressItem;
                        }
                    }
                }
            }

            $customer->setWebsiteId($websiteId);
            $customer->setStoreId($this->storeManager->getStore()->getId());
            //set data from consumerDB
            $customer->setEmail($data['customer_api']['email']);
            $customer->setFirstname($data['customer_api']['firstname']);
            $customer->setLastname($data['customer_api']['lastname']);
            $customer->setCustomAttribute('firstnamekana',$data['customer_api']['firstnamekana']);
            $customer->setCustomAttribute('lastnamekana',$data['customer_api']['lastnamekana']);
            $customer->setDob(date('Y-m-d',strtotime($data['customer_api']['dob'])));
            $customer->setGender($data['customer_api']['gender']);
            $customer->setCustomAttribute('consumer_db_id',$consumerDbId);
            $customer->setCustomAttribute('consumer_db_last_update_date',$lastUpdateDate);
            $customer->setCustomAttribute('consumer_data_hash', $newConsumerDataHash);

            if(isset($data['customer_api']['line_id'])){
                $customer->setCustomAttribute('line_id', $data['customer_api']['line_id']);
            }

            // For machine rental status (Subinfo)
            if(isset($data['customer_sub_api']['LENDING_STATUS_NBA'])){
                $customer->setCustomAttribute('LENDING_STATUS_NBA', $data['customer_sub_api']['LENDING_STATUS_NBA']);
            }
            if(isset($data['customer_sub_api']['LENDING_STATUS_NDG'])){
                $customer->setCustomAttribute('LENDING_STATUS_NDG', $data['customer_sub_api']['LENDING_STATUS_NDG']);
            }
            if(isset($data['customer_sub_api']['LENDING_STATUS_SPT'])){
                $customer->setCustomAttribute('LENDING_STATUS_SPT', $data['customer_sub_api']['LENDING_STATUS_SPT']);
            }
            if(isset($data['customer_sub_api']['LENDING_STATUS_ICS'])){
                $customer->setCustomAttribute('LENDING_STATUS_ICS', $data['customer_sub_api']['LENDING_STATUS_ICS']);
            }
            if(isset($data['customer_sub_api']['AMB_SALE'])){
                $customer->setCustomAttribute('amb_sale', $data['customer_sub_api']['AMB_SALE']);
            }
            if(isset($data['customer_sub_api']['LENDING_STATUS_NSP'])){
                $customer->setCustomAttribute('LENDING_STATUS_NSP', $data['customer_sub_api']['LENDING_STATUS_NSP']);
            }
            if(isset($data['customer_sub_api']['LENDING_STATUS_DUO'])){
                $customer->setCustomAttribute('LENDING_STATUS_DUO', $data['customer_sub_api']['LENDING_STATUS_DUO']);
            }
            if(isset($data['customer_api']['KEY_CLIENT_MAIL_TYPE'])){
                $customer->setCustomAttribute('email_1_type', $data['customer_api']['KEY_CLIENT_MAIL_TYPE']);
            }
            if(isset($data['customer_api']['KEY_CLIENT_MAIL_TYPE2'])){
                $customer->setCustomAttribute('email_2_type', $data['customer_api']['KEY_CLIENT_MAIL_TYPE2']);
            }
            // END machine rental status
            //set membership + assign website
            if(isset($data['customer_api']['multiple_website'])){
                $customer->setCustomAttribute('multiple_website', $data['customer_api']['multiple_website']);
            }
            if(isset($data['customer_api']['group_id'])){
                $customer->setGroupId($data['customer_api']['group_id']);
            }
            if(isset($data['customer_api']['membership'])){
                $customer->setCustomAttribute('membership', $data['customer_api']['membership']);
            }

            if(isset($data['customer_api']['offline_customer'])){
                $customer->setCustomAttribute('offline_customer', $data['customer_api']['offline_customer']);
            }
            if(isset($data['customer_api']['KEY_COMPANY_NAME'])){
                $customer->setCustomAttribute('customer_company_name', $data['customer_api']['KEY_COMPANY_NAME']);
            }
            if(isset($data['customer_api']['KEY_WORK_PH_NUM'])){
                $customer->setCustomAttribute('key_work_ph_num', $data['customer_api']['KEY_WORK_PH_NUM']);
            }


            //update ambassador info
            $this->updateCustomerAmbassador($customer,$data);

            if( $this->_customerMembershipHelper->checkExistKeyAndValue('BUSINESS_CODE',1,false,true)){
                $businessCode = $this->_customerMembershipHelper->getSubProfileValue('BUSINESS_CODE');
                $customer->setCustomAttribute('b2b_flag', 1);
                $customer->setCustomAttribute('shosha_business_code', $businessCode);
            }
            else{
                $customer->setCustomAttribute('b2b_flag', 0);
                $customer->setCustomAttribute('shosha_business_code', '');
            }

            if($isNewCustomer){
                $this->_logger->info("NED-4912 : Creating new customer data for consumer ID - ".$consumerDbId);
                $customerReturn = $this->customerRepository->save($customer);
                $this->createUpdateCustomerAddress($data, $addressDataModel, $customerReturn->getId());
            }else{
                $customerReturn = $this->customerRepository->save($customer);
                $this->createUpdateCustomerAddress($data, $addressDataModel, $customer->getId());
            }

            return $customerReturn;
        } else {
            return false;
        }

    }

    /**
     *UpdateCustomerAmbassador
     *
     * @param $customer
     * @param $data
     */
    public function updateCustomerAmbassador($customer,$data){

        if ($this->_customerMembershipHelper->checkExistKeyAndValue('amb_type', 1)) {

            if(isset($data['amb_api']['amb_com_name'])){
                $customer->setCustomAttribute('amb_com_name', $data['amb_api']['amb_com_name']);
            }
            if(isset($data['amb_api']['amb_com_division_name'])){
                $customer->setCustomAttribute('amb_com_division_name', $data['amb_api']['amb_com_division_name']);
            }

            if(isset($data['amb_api']['CHARGE_PERSON'])){
                $customer->setCustomAttribute('amb_charge_person', $data['amb_api']['CHARGE_PERSON']);
            }

            if(isset($data['amb_api']['amb_ph_num'])){
                $customer->setCustomAttribute('amb_ph_num', $data['amb_api']['amb_ph_num']);
            }

            $customer->setCustomAttribute('amb_type', 1);


            if ($this->_customerMembershipHelper->getSubProfileValue('status_machine_NBA')) {
                $customer->setCustomAttribute('status_machine_NBA', $this->_customerMembershipHelper->getSubProfileValue('status_machine_NBA'));
            }

            if ($this->_customerMembershipHelper->getSubProfileValue('status_machine_NDG')) {
                $customer->setCustomAttribute('status_machine_NDG', $this->_customerMembershipHelper->getSubProfileValue('status_machine_NDG'));
            }

            if ($this->_customerMembershipHelper->getSubProfileValue('status_machine_SPT')) {
                $customer->setCustomAttribute('status_machine_SPT', $this->_customerMembershipHelper->getSubProfileValue('status_machine_SPT'));
            }

            if ($this->_customerMembershipHelper->getSubProfileValue('status_machine_BLC')) {
                $customer->setCustomAttribute('status_machine_BLC', $this->_customerMembershipHelper->getSubProfileValue('status_machine_BLC'));
            }

            if ($this->_customerMembershipHelper->getSubProfileValue('status_machine_Nespresso')) {
                $customer->setCustomAttribute('status_machine_Nespresso', $this->_customerMembershipHelper->getSubProfileValue('status_machine_Nespresso'));
            }
        }else {

            /**
             * save data amb_type if Browse customers from ConsumerDB
             * set data default Not ambassador
             */
            if(isset($data['customer_sub_api']) && isset($data['customer_sub_api']['amb_type']) ){
                $customer->setCustomAttribute('amb_type', $data['customer_sub_api']['amb_type']);
            }else{
                $customer->setCustomAttribute('amb_type', 0);
            }
        }

        if(isset($data['amb_api']) && isset($data['amb_api']['COM_POSTAL_CODE']) ){
            $customer->setCustomAttribute('COM_POSTAL_CODE', $data['amb_api']['COM_POSTAL_CODE']);
        }else{
            $customer->setCustomAttribute('COM_POSTAL_CODE','');
        }

        if(isset($data['amb_api']) && isset($data['amb_api']['COM_ADDRESS1']) ){
            $customer->setCustomAttribute('COM_ADDRESS1', $data['amb_api']['COM_ADDRESS1']);
        }else{
            $customer->setCustomAttribute('COM_ADDRESS1','');
        }

        if(isset($data['amb_api']) && isset($data['amb_api']['COM_ADDRESS2']) ){
            $customer->setCustomAttribute('COM_ADDRESS2', $data['amb_api']['COM_ADDRESS2']);
        }else{
            $customer->setCustomAttribute('COM_ADDRESS2','');
        }

        if(isset($data['amb_api']) && isset($data['amb_api']['COM_ADDRESS3']) ){
            $customer->setCustomAttribute('COM_ADDRESS3', $data['amb_api']['COM_ADDRESS3']);
        }else{
            $customer->setCustomAttribute('COM_ADDRESS3','');
        }

    }

    /**
     * Handle Address Update And Create
     * @param array $data
     * @param array $addressDataModel
     * @param string $customerId
     * @throws \Exception
     */
    public function createUpdateCustomerAddress($data, $addressDataModel, $customerId)
    {
        $isAmb = (isset($data['customer_sub_api']['amb_type']) && ($data['customer_sub_api']['amb_type'] == 1));
        $hasDataAmb = (count($data['amb_api']) >0);
        $hasHome = $hasCompany = false;
        $homeItem = $companyItem = null;
        if(is_array($addressDataModel) && count($addressDataModel)>0){

            foreach ($addressDataModel as $addressItems){
                if($addressItems->getCustomAttribute('riki_type_address')->getValue() ==\Riki\Customer\Model\Address\AddressType::HOME ){
                    $hasHome = true;
                    $homeItem = $addressItems;
                }
                if($addressItems->getCustomAttribute('riki_type_address')->getValue() ==\Riki\Customer\Model\Address\AddressType::OFFICE ){
                    $hasCompany = true;
                    $companyItem = $addressItems;
                }
            }
        }
        if($hasCompany){
            if($isAmb && $hasDataAmb){
                $this->saveCustomerAddress($data,$companyItem,$customerId,\Riki\Customer\Model\Address\AddressType::OFFICE);
            }
        }else if(!$hasCompany && $isAmb && $hasDataAmb){
            $this->saveCustomerAddress($data,null,$customerId,\Riki\Customer\Model\Address\AddressType::OFFICE);
        }
        if($hasHome){
            $this->saveCustomerAddress($data,$homeItem,$customerId,\Riki\Customer\Model\Address\AddressType::HOME);
        }else if(!$hasHome){
            $this->saveCustomerAddress($data,null,$customerId,\Riki\Customer\Model\Address\AddressType::HOME);
        }
    }
    /**
     *  Save customer address
     * @param array $data
     * @param array of Magento\Customer\Model\Address $address
     * @param string $customerId
     * @param string $addressType
     * @throws \Exception
     */
    public function saveCustomerAddress($data,  $address = null, $customerId ="", $addressType ="" )
    {
        if(!$address){
            $address = $this->_customerAddressFactory->create();
        }
        $rikiNickName = __('本人');
        $isBilling = false;
        $regionDatas = $this->_regionHelper->getRegionData();

        $mapKeyCodeId = array();
        foreach ($regionDatas[self::CONST_DEFAULT_COUNTRY] as $regionId => $regionData) {
            if ($regionId > 0) {
                $mapKeyCodeId[$regionId] = $regionData['code'];
            }
        }

        $addressName = $postcode = $telephone = $sAddressFirstAmbName = $sAddressLastAmbName = '';
        $sAddressFirstName = isset($data['customer_api']['KEY_ADDRESS_FIRST_NAME'])?$data['customer_api']['KEY_ADDRESS_FIRST_NAME']:'';
        $sAddressLastName = isset($data['customer_api']['KEY_ADDRESS_LAST_NAME'])?$data['customer_api']['KEY_ADDRESS_LAST_NAME']:'';

        //// Separate
        if($addressType == \Riki\Customer\Model\Address\AddressType::OFFICE &&  isset($data['amb_api']) && count($data['amb_api']) >0){
            $rikiNickName = __('会社');
            if(isset($data['amb_api']) && isset($data['amb_api']['COM_POSTAL_CODE']) && $data['amb_api']['COM_POSTAL_CODE'] != ''){
                    $postcode = $data['amb_api']['COM_POSTAL_CODE'];
                }
            if(isset($data['amb_api']['COM_ADDRESS1'])){
                $regionId = $this->_rikiRegionHelper->getRegionIdByName($data['amb_api']['COM_ADDRESS1']);
            }

            $addressAmbName = '';
            if(isset($data['amb_api']['COM_ADDRESS2']) && '' != $data['amb_api']['COM_ADDRESS2']){
                $addressAmbName = ' '.$data['amb_api']['COM_ADDRESS2'];
            }

            if(isset($data['amb_api']['COM_ADDRESS3']) && '' != $data['amb_api']['COM_ADDRESS3']){
                $addressAmbName .= ' '.$data['amb_api']['COM_ADDRESS3'];
            }

            if(isset($data['amb_api']['COM_ADDRESS4']) && '' != $data['amb_api']['COM_ADDRESS4']){
                $addressAmbName .= ' '.$data['amb_api']['COM_ADDRESS4'];
            }

            if('' != $addressAmbName){
                $addressAmbName = trim($addressAmbName);
                $addressName = $addressAmbName;
            }

            //ambassador
            $data['amb_api']['amb_com_name'] = isset($data['amb_api']['amb_com_name'])?$data['amb_api']['amb_com_name']:'';
            $data['amb_api']['amb_com_division_name'] = isset($data['amb_api']['amb_com_division_name'])?$data['amb_api']['amb_com_division_name']:'';
            if('' != $data['amb_api']['amb_com_name']){
                $sAddressLastAmbName = $data['amb_api']['amb_com_name'];
            }
            if('' != $data['amb_api']['amb_com_division_name']){
                $sAddressLastAmbName.= ' '.$data['amb_api']['amb_com_division_name'];
            }

            if(isset($data['amb_api']['CHARGE_PERSON']) && '' != $data['amb_api']['CHARGE_PERSON']){
                $sAddressFirstAmbName = '（ご担当：'. $data['amb_api']['CHARGE_PERSON'].'様）';
            }
            else{
                $sAddressFirstAmbName = '（ご担当：'.$data['customer_api']['lastname'].' '.$data['customer_api']['firstname'].'様）';
            }

            if('' != $sAddressFirstAmbName){
                $sAddressFirstName = $sAddressFirstAmbName;
            }

            if('' == $sAddressLastAmbName){
                $sAddressLastName = 'ー';
            }else{
                $sAddressLastName = $sAddressLastAmbName;
            }
            $telephone = isset($data['amb_api']['amb_ph_num']) ?$data['amb_api']['amb_ph_num'] :"";

        }else if($addressType == \Riki\Customer\Model\Address\AddressType::HOME){
            if(isset($data['customer_api']['KEY_POSTAL_CODE']) && $data['customer_api']['KEY_POSTAL_CODE'] != '') {
                $postcode = substr_replace($data['customer_api']['KEY_POSTAL_CODE'], '-', 3, 0);
            }
            $regionCode = array_search($data['customer_api']['KEY_PREFECTURE_CODE'], $this->_mappingZoneJapan);
            $regionId = array_search($regionCode, $mapKeyCodeId);

            if('' != $data['customer_api']['KEY_ADDRESS2']){
                $addressName .= ' '.$data['customer_api']['KEY_ADDRESS2'];
            }

            if('' != $data['customer_api']['KEY_ADDRESS3']){
                $addressName .= ' '.$data['customer_api']['KEY_ADDRESS3'];
            }

            if('' != $data['customer_api']['KEY_ADDRESS4']){
                $addressName .= ' '.$data['customer_api']['KEY_ADDRESS4'];
            }
            $isBilling = true;
            $telephone = isset($data['customer_api']['KEY_PHONE_NUMBER']) ?$data['customer_api']['KEY_PHONE_NUMBER'] :"";
        }

        $lastNameKana = isset($data['customer_api']['KEY_ADDRESS_LAST_NAME_KANA'])?$data['customer_api']['KEY_ADDRESS_LAST_NAME_KANA']:'';
        $firstNameKana = isset($data['customer_api']['KEY_ADDRESS_FIRST_NAME_KANA'])?$data['customer_api']['KEY_ADDRESS_FIRST_NAME_KANA']:'';

        $address->setCustomAttribute('riki_type_address',$addressType);
        $address->setCustomerId($customerId);
        if(!($address->getCustomAttribute('riki_nickname') && $address->getCustomAttribute('riki_nickname')->getValue() != '') ){
            $address->setCustomAttribute('riki_nickname',$rikiNickName);
        }
        $address->setFirstname($sAddressFirstName);
        $address->setLastname($sAddressLastName);

        $address->setCustomAttribute('firstnamekana',$firstNameKana);
        $address->setCustomAttribute('lastnamekana',$lastNameKana);
        $address->setCountryId(self::CONST_DEFAULT_COUNTRY);

        if (isset($regionId) && $regionId !=0) {

            $address->setRegionId($regionId);

            // Update Region
            $regionObject = $address->getRegion();
            if ($regionObject instanceof \Magento\Customer\Model\Data\Region) {
                $regionObject->setRegionId($regionId);
            }
        }

        if(!$address->getCity()){
            $address->setCity(__('None'));
        }

        $address->setCompany(isset($data['customer_api']['KEY_COMPANY_NAME'])?$data['customer_api']['KEY_COMPANY_NAME']:'');
        
        $address->setStreet([$addressName]);

        $address->setTelephone($telephone);
        
        $address->setPostcode($postcode);

        $address->setIsDefaultBilling($isBilling);
        
        try {
            $addressModel = $this->_addressModelFactory->create();
            $addressModel->updateData($address);
            $inputException = $this->_validate($addressModel);
            if ($inputException->wasErrorAdded()) {
                $dataLog = \Zend\Json\Encoder::encode($data);
                $this->consumerLog->error('Data return from KSS error!',['data'=>$dataLog]);
                unset($addressModel);
                return;
            }
            unset($addressModel);
            $this->_customerAddressRepository->save($address);
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /** Mapping gender for get Customer
     * @param $value
     * @return int
     */
    public function mappingGender($value){
        switch ($value){
            case 0: return 3;
            case 1: return 1;
            case 2: return 2;
        }
    }

    /**
     * PrepareInfoCustomer
     *
     * @param $consumerDbId
     * @param $consumerDbResponse
     * @return array
     * @throws \Exception
     */
    public function prepareInfoCustomer($consumerDbId,$consumerDbResponse){

        $cusResponse = $this->getCustomer($this->_arrayCusId,$consumerDbId);
        $customersAPI =[];
        if($cusResponse){
            if(property_exists($cusResponse,'return')){

                $codeReturn = $cusResponse->return[0]->array;
                if(isset($codeReturn[0]) && \Riki\Customer\Model\SsoConfig::SSO_RESPONSE_SUCCESS_CODE == $codeReturn[0]){

                    $customersKey = $cusResponse->return[3]->array;
                    $customersValue = $cusResponse->return[4]->array;

                    foreach($customersKey as $key => $customerKey){
                        if($customerKey == "SEX"){
                            if(isset($customersValue[$key])){
                                $customersValue[$key] = $this->mappingGender($customersValue[$key]);
                            }
                        }
                        $customerKey = 'KEY_'.$customerKey;
                        $customerValue = $customersValue[$key];

                        list($customerKey,$customerValue) =  $this->convertKeyValueField($customerKey,$customerValue);

                        $customersAPI[$customerKey] = $customerValue;
                    }
                    if (count($customersAPI)>0) {
                        $consumerDbResponse['customer_api'] = $customersAPI;

                        //update more membership,group,website
                        $consumerDbResponse = $this->updateCustomerAttribute($consumerDbResponse);
                        $customersAPI = $consumerDbResponse['customer_api'];

                        return $customersAPI;
                    }
                }else if(isset($codeReturn[0]) && 'MID99003' == $codeReturn){
                    throw new \Magento\Framework\Exception\NoSuchEntityException(__('There no customer associated with id: %1', $consumerDbId));
                }
            }
        }

        return [];
    }
    /**
     * Prepare Info SubCustomer
     *
     * @param $consumerDbId
     * @return array
     */
    public function prepareInfoSubCustomer($consumerDbId){

        $subResponse = $this->_ambCustomerRepository->getCustomerSub($this->_arraySubId,$consumerDbId);
        $subInfo =[];
        if($subResponse){
            if($subResponse->return[0]->array[0] == "MID00000"){

                if(isset($subResponse->return) && count($subResponse->return) > 0){
                    foreach ($subResponse->return as $k => $v){
                        if($k >=4){
                            if ($this->getSubKey($v->array[1]) == "AMB_TYPE") {
                                $subInfo['amb_type'] = $this->getAmbValue($v->array[3]);
                            }else{
                                $customerKey = $this->getSubKey($v->array[1]);
                                $customerValue = $v->array[3];
                                list($customerKey,$customerValue) =  $this->convertKeyValueField($customerKey,$customerValue);

                                $subInfo[$customerKey] = $customerValue;
                            }
                        }
                    }
                }

                if (count($subInfo)>0) {
                    return $subInfo;
                }
            }
        }

        return [];
    }

    /**
     * Prepare Info Ambassador Customer
     *
     * @param $consumerDbId
     * @return array
     */
    public function prepareInfoAmbassadorCustomer($consumerDbId){

        $ambInfo = $this->_ambCustomerRepository->getAmbassadorInfo($consumerDbId);
        if($ambInfo){
            if($ambInfo->return[0]->array[0] == "MID00000"){

                $ambCustomer = [];
                if(isset($ambInfo->return[3]->array) && count($ambInfo->return[3]->array) >0){

                    foreach ($ambInfo->return[3]->array as $key){
                        $ambCustomersKey = $ambInfo->return[3]->array;
                        $ambCustomersValue = $ambInfo->return[4]->array;

                        foreach($ambCustomersKey as $key => $customerKey){
                            if($this->getAmbKey($customerKey)){
                                $ambCustomer[$this->getAmbKey($customerKey)] = $ambCustomersValue[$key];
                            }else{
                                $ambCustomer[$customerKey] = $ambCustomersValue[$key];
                            }
                        }
                    }
                }

                if (count($ambCustomer)>0) {
                    return $ambCustomer;
                }
            }
        }

        return [];
    }
    /**
     * UpdateCustomerAttribute
     *
     * @param $consumerDbResponse
     * @return mixed
     */
    public function updateCustomerAttribute($consumerDbResponse){

        if(count($consumerDbResponse['customer_api'])){

            $this->_customerMembershipHelper->initMappingFieldCustomer($consumerDbResponse['customer_api'],isset($consumerDbResponse['customer_sub_api'])?$consumerDbResponse['customer_sub_api']:array());
            $sites = $this->_customerMembershipHelper->getCustomerWebsite();
            $group = $this->_customerMembershipHelper->getCustomerGroup();
            $memberships = $this->_customerMembershipHelper->getCustomerMemberShip();

            if(count($sites)){
                $consumerDbResponse['customer_api']['multiple_website'] = implode(",",$sites);
            }

            $consumerDbResponse['customer_api']['group_id'] = $group;

            if(count($memberships)) {
                $consumerDbResponse['customer_api']['membership'] = implode(",",$memberships);
            }
        }

        return $consumerDbResponse;
    }

    /**
     * ConvertKeyValueField
     *
     * @param $customerKey
     * @param $customerValue
     * @return array
     */
    public function convertKeyValueField($customerKey,$customerValue){

        if(in_array($customerKey,array('KEY_CUSTOMER_TYPE','KEY_EMP_FLG'))){
            $customerKey = str_replace('KEY_','',$customerKey);
        }

        if(isset($this->_arrayCusMappingId[$customerKey])){
            $customerKey = $this->_arrayCusMappingId[$customerKey];
        }
//
//        if(isset($this->_arraySubEckey[$customerKey])){
//            $customerKey = $this->_arraySubEckey[$customerKey];
//        }

        //mapping customer value
        if('offline_customer' == $customerKey){
            if(2 == $customerValue){
                $customerValue = 1;
            }
            else{
                $customerValue = 0;
            }
        }

        return array($customerKey,$customerValue);
    }

    /**
     * Get Sub key
     *
     * @param $keycodeRequest
     * @return int|string
     */
    public function getSubKey($keyCodeRequest){
        foreach ($this->_arraySubId as $keyName => $keyCode){
            if($keyCode == $keyCodeRequest){
                return $keyName;
            }
        }
        return "";
    }

    /**
     * Get subscription key code
     * @param $keyName
     * @return mixed
     */
    public function getSubscriptionKeyCode($keyName){
        if (array_key_exists($keyName, $this->_arraySubId)) {
           return $this->_arraySubId[$keyName];
        }
    }

    /** Get value of AM_TYPE map to Magento
     * @param $value
     * @return int|string
     */
    public function getAmbValue($value){
        foreach ($this->_ambMapValue as $k => $v){
            if ($value == $v){
                return $k;
            }
        }
        return "";
    }

    /**
     * @param $key
     * @return bool|mixed
     */
    public function getAmbKey($key){
        return isset($this->_arrayAmbMappingId[$key]) ? $this->_arrayAmbMappingId[$key] : false ;
    }

    /**
     * Call KSS API to set machine
     * @param array $dataMachine
     * @param string $customerCode
     * @param integer $type  1: Append / 2: Update / 3: Delete
     * @return bool
     */
    public function processToSetMachine($dataMachine = [], $customerCode ='', $type = 1)
    {
        //set Log for call API
        $logModel = $this->_consumerLogFactory->create();
        $now = $this->_dateTime->date('Y/m/d H:m:i');
        $logModel->setName('setCustomerMachine');
        $logModel->setDescription('Set Customer Machine API');
        $logModel->setDate($now);

        $soapConfig = $this->soapHelper->getCommonRequestParams();

        $wsdl = $this->_apiCustomerHelper->getConsumerApiUrl('/SetMachineService?wsdl');
        $endPoint = $this->_apiCustomerHelper->getConsumerApiUrl('/SetMachineService.SetMachineServiceHttpSoap12Endpoint/');
        $param1 = $this->_scopeConfig->getValue('consumer_db_api_url/customer_machine/param1', \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE);
        $param2 = $this->_scopeConfig->getValue('consumer_db_api_url/customer_machine/param2', \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE);

        $soapClient = $this->initSoapClient($wsdl, $soapConfig);
        $soapClient->setLocation($endPoint);

        $params = [];
        $params[] = new \SoapVar($param1, XSD_STRING, null, null, 'clientInfo' );
        $params[] = new \SoapVar($param2, XSD_STRING, null, null, 'clientInfo' );
        $params[] = new \SoapVar($now, XSD_STRING, null, null, 'clientInfo' );
        $params[] = new \SoapVar($type, XSD_STRING, null, null, 'requestType' );
        $params[] = new \SoapVar($customerCode, XSD_STRING, null, null, 'customerCode' );

        $setParameterKey = [];
        $setParameterKey[] = new \SoapVar('KEY_CUSTOMER_CODE', XSD_STRING, null, null, 'array');
        $setParameterKey[] = new \SoapVar('KEY_REGISTRATION_NO', XSD_STRING, null, null, 'array');
        $setParameterKey[] = new \SoapVar('KEY_MACHINE_NO', XSD_STRING, null, null, 'array');

        $params[] = new \SoapVar($setParameterKey, SOAP_ENC_ARRAY, null, null, 'setParameter');

        if (sizeof($dataMachine)) {
            if($type == 1){
                foreach ($dataMachine as $key => $data) {
                        $setParameterValue = [];
                        $setParameterValue[] = new \SoapVar($customerCode, XSD_STRING, null, null, 'array');
                        $setParameterValue[] = new \SoapVar('', XSD_STRING, null, null, 'array');
                        $setParameterValue[] = new \SoapVar($key, XSD_STRING, null, null, 'array');
                        $params[] = new \SoapVar($setParameterValue, SOAP_ENC_ARRAY, null, null, 'setParameter');
                }
            }
            if($type == 3){
                foreach ($dataMachine as $key => $data) {
                        $setParameterValue = [];
                        $setParameterValue[] = new \SoapVar($customerCode, XSD_STRING, null, null, 'array');
                        $setParameterValue[] = new \SoapVar($key, XSD_STRING, null, null, 'array');
                        $setParameterValue[] = new \SoapVar($data, XSD_STRING, null, null, 'array');
                        $params[] = new \SoapVar($setParameterValue, SOAP_ENC_ARRAY, null, null, 'setParameter');
                }
            }
        } else {
            // missing this empty node, the API will throw an error
            $params[] = new \SoapVar([], SOAP_ENC_ARRAY, null, null, 'setParameter');
        }

        $logModel->setRequest(\Zend_Json::encode(array(
            'customerCode' => $customerCode,
            'setParameter' => $dataMachine,
            JSON_UNESCAPED_UNICODE)));
        try{
            $response = $soapClient->setMachine( new \SoapVar($params, SOAP_ENC_OBJECT) );
            if(property_exists($response,'return')){
                $codeReturn = $response->return;
                if(isset($codeReturn[0]) && \Riki\Customer\Model\SsoConfig::SSO_RESPONSE_SUCCESS_CODE == $codeReturn[0]){
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
            $this->_logger->error((string)$e->getMessage());
            return false;
        }
        return false;
    }

    /**
     * @param $machineInfo //
     * @param string $customerId
     * @return array $response
     * @throws \Exception
     */
    public function setMachine($machineInfo,$customerId = ''){
        $response = array();
        try{
            $message ='';
            if(isset($machineInfo['add'])&& count($machineInfo['add']) >0){
                $response['add'] = $this->processToSetMachine($machineInfo['add'],$customerId,1);
                $message .= __('Add machine result: '). $response['add']->return[1] ."\n";
            }
            if(isset($machineInfo['update'])&& count($machineInfo['update']) >0){
                $response['update']= $this->processToSetMachine($machineInfo['delete'],$customerId,2);
                $message .=__('Update machine result: '). $response['update']->return[1] . "\n";
            }
            if(isset($machineInfo['delete']) && count($machineInfo['delete']) >0){
                $response['delete'] = $this->processToSetMachine($machineInfo['delete'],$customerId,3);
                $message .= __('Delete machine result: '). $response['delete']->return[1] ."\n";
            }
            return $message;
        }catch (\Exception $e){
            throw $e;
        }
    }
    /**
     * Get customer machine info from KSS
     * @param string $customerCode
     * @return bool
     */
    public function getMachine($customerCode = '')
    {
        //set Log for call API
        $logModel = $this->_consumerLogFactory->create();
        $now = $this->_dateTime->date('Y/m/d H:m:i');
        $logModel->setName('getCustomerMachine');
        $logModel->setDescription('get Customer Machine API');
        $logModel->setDate($now);

        $soapConfig = $this->soapHelper->getCommonRequestParams();

        $wsdl = $this->_apiCustomerHelper->getConsumerApiUrl('/GetMachineService?wsdl');
        $endPoint = $this->_apiCustomerHelper->getConsumerApiUrl('/GetMachineService.GetMachineServiceHttpSoap12Endpoint/');
        $param3 = $this->_scopeConfig->getValue('consumer_db_api_url/customer_machine/param3', \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE);
        $param4 = $this->_scopeConfig->getValue('consumer_db_api_url/customer_machine/param4', \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE);

        $soapClient = $this->initSoapClient($wsdl, $soapConfig);
        $soapClient->setLocation($endPoint);

        $params = [];
        $params[] = new \SoapVar($param3, XSD_STRING, null, null, 'clientInfo' );
        $params[] = new \SoapVar($param4, XSD_STRING, null, null, 'clientInfo' );
        $params[] = new \SoapVar($now, XSD_STRING, null, null, 'clientInfo' );
        $params[] = new \SoapVar($customerCode, XSD_STRING, null, null, 'customerCode' );


        $logModel->setRequest(\Zend_Json::encode(array(
            'description'=> 'get customer machine API',
            'customerCode' => $customerCode,
            'params' => $params,
            JSON_UNESCAPED_UNICODE)));
        try{
            $response = $soapClient->getMachine( new \SoapVar($params, SOAP_ENC_OBJECT) );
            if(property_exists($response,'return')){
                $codeReturn = $response->return;
                if(isset($codeReturn[0]->array[0]) && \Riki\Customer\Model\SsoConfig::SSO_RESPONSE_SUCCESS_CODE == $codeReturn[0]->array[0]){
                    $logModel->setResponseData(\Zend_Json::encode($codeReturn));
                    $logModel->setStatus(1);
                    $logModel->save();
                    return $response->return;
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

    /**
     * @param $customerInfo
     * @param int $checkLevel 1:準会員1次チェック/2:準会員2次チェック
     * @return bool
     */
    public function checkDuplicate($customerInfo,$checkLevel = 1){

        //set Log for call API
        $logModel = $this->_consumerLogFactory->create();
        $now = $this->_dateTime->date('Y/m/d H:m:i');
        $logModel->setName('checkRegularCustomer');
        $logModel->setDescription('Check duplicate customer before create customer');
        $logModel->setDate($now);

        $soapConfig = $this->soapHelper->getCommonRequestParams();

        $wsdl = $this->_apiCustomerHelper->getConsumerApiUrl('/CheckRegularCustomerService?wsdl');
        $endPoint = $this->_apiCustomerHelper->getConsumerApiUrl('/CheckRegularCustomerService.CheckRegularCustomerServiceHttpSoap12Endpoint/');
        $param1 = $this->_scopeConfig->getValue('consumer_db_api_url/customer_check_duplicate/param1',\Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE);
        $param2 = $this->_scopeConfig->getValue('consumer_db_api_url/customer_check_duplicate/param2',\Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE);

        $soapClient = $this->initSoapClient($wsdl, $soapConfig);
        $soapClient->setLocation($endPoint);

        $params = [];
        $params[] = new \SoapVar($param1, XSD_STRING, null, null, 'clientInfo' );
        $params[] = new \SoapVar($param2, XSD_STRING, null, null, 'clientInfo' );
        $params[] = new \SoapVar($now, XSD_STRING, null, null, 'clientInfo' );


        $setParameterKey = [];
        $setParameterKey[] = new \SoapVar('KEY_LAST_NAME', XSD_STRING, null, null, 'array');
        $setParameterKey[] = new \SoapVar('KEY_FIRST_NAME', XSD_STRING, null, null, 'array');
        $setParameterKey[] = new \SoapVar('KEY_LAST_NAME_KANA', XSD_STRING, null, null, 'array');
        $setParameterKey[] = new \SoapVar('KEY_FIRST_NAME_KANA', XSD_STRING, null, null, 'array');
        $setParameterKey[] = new \SoapVar('KEY_POSTAL_CODE', XSD_STRING, null, null, 'array');
        $setParameterKey[] = new \SoapVar('KEY_PHONE_NUMBER', XSD_STRING, null, null, 'array');

        $params[] = new \SoapVar($setParameterKey, SOAP_ENC_ARRAY, null, null, 'setParameter');

        if (sizeof($customerInfo)) {
            $setParameterValue = [];
            foreach ($customerInfo as $key => $data) {
                $setParameterValue[] = new \SoapVar($data, XSD_STRING, null, null, 'array');
            }
            $params[] = new \SoapVar($setParameterValue, SOAP_ENC_ARRAY, null, null, 'setParameter');
        } else {
            // missing this empty node, the API will throw an error
            $params[] = new \SoapVar([], SOAP_ENC_ARRAY, null, null, 'setParameter');
        }
        $params[] = new \SoapVar($checkLevel, XSD_STRING, null, null, 'checkLevel');

        $logModel->setRequest(\Zend_Json::encode(array(
            'description'=> 'Check duplicate customer before create customer',
            'params' => $customerInfo,
            JSON_UNESCAPED_UNICODE)));
        try{
            $response = $soapClient->checkRegularCustomer( new \SoapVar($params, SOAP_ENC_OBJECT) );
            if(property_exists($response,'return')){
                $codeReturn = $response->return;
                if(isset($codeReturn[0]) && \Riki\Customer\Model\SsoConfig::SSO_RESPONSE_SUCCESS_CODE == $codeReturn[0]){
                        $logModel->setResponseData(\Zend_Json::encode($codeReturn));
                        $logModel->setStatus(1);
                        $logModel->save();
                        return $response;
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

    /**
     * Prepare Machine Info for showing on customer Edit page
     *
     * @param string $customerConsumerDbId
     * @return array $result
     */
    public function prepareInfoMachineCustomer($customerConsumerDbId)
    {
        $response = $this->getMachine($customerConsumerDbId);
        $result = [];
        // Use for update/Delete Machine status
        $resultToUpdateMachine = [];
        if (is_array($response)) {
            foreach ($response[3]->array as $key => $nameOfMachineValue) {
                foreach ($response as $k => $v) {
                    if ($k < 4) {
                        continue;
                    }
                    $result[$v->array[1]] = 1;
                    $resultToUpdateMachine[$k][$nameOfMachineValue] = $v->array[$key];
                }
            }
        }

        return [$result, $resultToUpdateMachine];
    }

    /**
     * Validate Customer Addresses attribute values.
     *
     * @param CustomerAddressModel $customerAddressModel the model to validate
     * @return InputException
     *
     * @SuppressWarnings(PHPMD.NPathComplexity)
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function _validate(\Magento\Customer\Model\Address $customerAddressModel)
    {
        $exception = new InputException();
        if ($customerAddressModel->getShouldIgnoreValidation()) {
            return $exception;
        }

        if (!\Zend_Validate::is($customerAddressModel->getFirstname(), 'NotEmpty')) {
            $exception->addError(__(InputException::REQUIRED_FIELD, ['fieldName' => 'firstname']));
        }

        if (!\Zend_Validate::is($customerAddressModel->getLastname(), 'NotEmpty')) {
            $exception->addError(__(InputException::REQUIRED_FIELD, ['fieldName' => 'lastname']));
        }

        if (!\Zend_Validate::is($customerAddressModel->getStreetLine(1), 'NotEmpty')) {
            $exception->addError(__(InputException::REQUIRED_FIELD, ['fieldName' => 'street']));
        }

        if (!\Zend_Validate::is($customerAddressModel->getCity(), 'NotEmpty')) {
            $exception->addError(__(InputException::REQUIRED_FIELD, ['fieldName' => 'city']));
        }

        if (!\Zend_Validate::is($customerAddressModel->getTelephone(), 'NotEmpty')) {
            $exception->addError(__(InputException::REQUIRED_FIELD, ['fieldName' => 'telephone']));
        }

        $havingOptionalZip = $this->directoryData->getCountriesWithOptionalZip();
        if (!in_array($customerAddressModel->getCountryId(), $havingOptionalZip)
            && !\Zend_Validate::is($customerAddressModel->getPostcode(), 'NotEmpty')
        ) {
            $exception->addError(__(InputException::REQUIRED_FIELD, ['fieldName' => 'postcode']));
        }

        if (!\Zend_Validate::is($customerAddressModel->getCountryId(), 'NotEmpty')) {
            $exception->addError(__(InputException::REQUIRED_FIELD, ['fieldName' => 'countryId']));
        }

        if ($customerAddressModel->getCountryModel()->getRegionCollection()->getSize()
            && !\Zend_Validate::is($customerAddressModel->getRegionId(), 'NotEmpty')
            && $this->directoryData->isRegionRequired($customerAddressModel->getCountryId())
        ) {
            $exception->addError(__(InputException::REQUIRED_FIELD, ['fieldName' => 'regionId']));
        }

        return $exception;
    }

    /**
     * Consumer DB - getMidInfo
     *
     * @param string $customerCode
     * @return bool
     */
    public function getMidInfo($customerCode = null)
    {
        //set Log for call API
        $logModel = $this->_consumerLogFactory->create();
        $now = $this->_dateTime->date('Y/m/d H:m:i');
        $logModel->setName('getMidInfo');
        $logModel->setDescription('Consumer DB - getMidInfo API');
        $logModel->setDate($now);

        $soapConfig = $this->soapHelper->getCommonRequestParams();

        $wsdl = $this->_apiCustomerHelper->getConsumerMidInfoApiUrl('/GetMidInfoService?wsdl');
        $endPoint = $this->_apiCustomerHelper->getConsumerMidInfoApiUrl(
            '/GetMidInfoService.GetMidInfoServiceHttpSoap12Endpoint/'
        );

        $param3 = $this->_scopeConfig->getValue(
            'consumer_db_api_url/customer/param3', \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE
        );

        $param4 = $this->_scopeConfig->getValue(
            'consumer_db_api_url/customer/param4',
            \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE
        );

        $soapClient = $this->initSoapClient($wsdl, $soapConfig);
        $soapClient->setLocation($endPoint);

        $params = [];
        $params[] = new \SoapVar($param3, XSD_STRING, null, null, 'clientInfo');
        $params[] = new \SoapVar($param4, XSD_STRING, null, null, 'clientInfo');
        $params[] = new \SoapVar($now, XSD_STRING, null, null, 'clientInfo');
        $params[] = new \SoapVar(0, XSD_STRING, null, null, 'systemId');
        $params[] = new \SoapVar('', XSD_STRING, null, null, 'userId');
        $params[] = new \SoapVar($customerCode, XSD_STRING, null, null, 'customerCode');

        $logModel->setRequest(\Zend_Json::encode(array(
            'description' => 'Consumer DB - getMidInfo API',
            'customerCode' => $customerCode,
            'params' => $params,
            JSON_UNESCAPED_UNICODE)));
        try {
            $response = $soapClient->getMidInfo(new \SoapVar($params, SOAP_ENC_OBJECT));
            if (property_exists($response, 'return')) {
                $codeReturn = $response->return;
                $codeResponseSuccess = \Riki\Customer\Model\SsoConfig::SSO_RESPONSE_SUCCESS_CODE;
                if (isset($codeReturn[0]->array[0]) && $codeResponseSuccess == $codeReturn[0]->array[0]) {
                    if (isset($codeReturn[3]) && isset($codeReturn[4])) {
                        $logModel->setResponseData(\Zend_Json::encode($codeReturn, JSON_UNESCAPED_UNICODE));
                        $logModel->setStatus(1);
                        $logModel->save();
                        return $codeReturn;
                    }
                } else {
                    $logModel->setResponseData(\Zend_Json::encode($codeReturn, JSON_UNESCAPED_UNICODE));
                    $logModel->setStatus(0);
                    $logModel->save();
                    return $response->return;
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
     * Get sub profile id by machine type option array
     * @return array
     */
    public function getSubProfileIdByMachineTypeOptionArray()
    {
        return [
            MachineConditionRule::MACHINE_CODE_NBA => self::SUB_PROFILE_ID_MACHINE_TYPE_NBA,
            MachineConditionRule::MACHINE_CODE_NDG => self::SUB_PROFILE_ID_MACHINE_TYPE_NDG,
            MachineConditionRule::MACHINE_CODE_SPT => self::SUB_PROFILE_ID_MACHINE_TYPE_SPT,
            MachineConditionRule::MACHINE_CODE_BLC => self::SUB_PROFILE_ID_MACHINE_TYPE_BLC,
            MachineConditionRule::MACHINE_CODE_NESPRESSO => self::SUB_PROFILE_ID_MACHINE_TYPE_NESPRESSO,
            MachineConditionRule::MACHINE_CODE_DUO => self::SUB_PROFILE_ID_MACHINE_TYPE_DUO,
        ];
    }

}