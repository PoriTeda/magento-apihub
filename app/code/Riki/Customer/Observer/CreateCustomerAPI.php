<?php

namespace Riki\Customer\Observer;

class CreateCustomerAPI implements \Magento\Framework\Event\ObserverInterface
{
    /**
     * @var CONST_DEFAULT_COUNTRY
     */
    const CONST_DEFAULT_COUNTRY = 'JP';

    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $_logger;

    /**
     * @var \Magento\Framework\Registry $_coreRegistry
     */
    protected $_coreRegistry;

    /**
     * @var \Magento\Directory\Helper\Data $_directoryHelper
     */
    protected $_directoryHelper;

    /**
     * @var \Riki\Customer\Helper\Api
     */
    protected $_apiCustomerHelper;

    /**
     * @var $_request
     */
    protected $_request;

    /**
     * @var $_consumerLog
     */
    protected $_consumerLog;

    /**
     * @var $regionFactory
     */
    protected $_regionFactory;

    /**
     * @var \Magento\Framework\Locale\ResolverInterface
     */
    protected $_localeResolver;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $_scopeConfig;

    /**
     * @var MappingZoneJapan
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
     * @var \Magento\Framework\Stdlib\DateTime\DateTime
     */
    protected $_dateTime;

    /**
     * @var \Riki\Customer\Helper\Region
     */
    protected $_regionHelper;

    /**
     * @var \Riki\Customer\Helper\ConsumerDb\Soap
     */
    protected $soapHelper;

    /**
     * @var \Riki\Framework\Webapi\Soap\ClientFactory
     */
    protected $soapClientFactory;

    /**
     * CreateCustomerAPI constructor.
     * @param \Psr\Log\LoggerInterface $logger
     * @param \Magento\Framework\Stdlib\DateTime\DateTime $dateTime
     * @param \Magento\Framework\App\RequestInterface $request
     * @param \Riki\Customer\Model\ConsumerLogFile $consumerLog
     * @param \Riki\Customer\Helper\Region $regionHelper
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Framework\Registry $coreRegistry
     * @param \Magento\Directory\Helper\Data $_directoryHelper
     * @param \Riki\Customer\Helper\Api $apiCustomerHelper
     * @param \Riki\Customer\Helper\ConsumerDb\Soap $soapHelper
     * @param \Riki\Framework\Webapi\Soap\ClientFactory $soapClientFactory
     */
    public function __construct(
        \Psr\Log\LoggerInterface $logger,
        \Magento\Framework\Stdlib\DateTime\DateTime $dateTime,
        \Magento\Framework\App\RequestInterface $request,
        \Riki\Customer\Model\ConsumerLogFile $consumerLog,
        \Riki\Customer\Helper\Region $regionHelper,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Framework\Registry $coreRegistry,
        \Magento\Directory\Helper\Data $_directoryHelper,
        \Riki\Customer\Helper\Api $apiCustomerHelper,
        \Riki\Customer\Helper\ConsumerDb\Soap $soapHelper,
        \Riki\Framework\Webapi\Soap\ClientFactory $soapClientFactory
    ) {
        $this->_logger = $logger;
        $this->_dateTime = $dateTime;
        $this->_request = $request;
        $this->_consumerLog = $consumerLog;
        $this->_regionHelper = $regionHelper;
        $this->_scopeConfig = $scopeConfig;
        $this->_coreRegistry = $coreRegistry;
        $this->_directoryHelper = $_directoryHelper;
        $this->_apiCustomerHelper = $apiCustomerHelper;
        $this->soapHelper = $soapHelper;
        $this->soapClientFactory = $soapClientFactory;
    }

    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        if(!($this->_request->getPost('order_account_fake') && $this->_request->getPost('order_account_fake') == 1)){
            return $this;
        }
        $this->_request->setParam('order_account_fake',null);

        //set Log for call API
        $this->_consumerLog;

        $now = $this->_dateTime->date('Y/m/d H:m:i');
        $this->_consumerLog->setName('setCustomer');
        $this->_consumerLog->setDescription('Create Customer API');
        $this->_consumerLog->setDate($now);

        $flagCreateOrder = $this->_coreRegistry->registry('riki_create_customer_order');
        if(null == $flagCreateOrder){
            $this->_coreRegistry->register('riki_create_customer_order',true);
        }
        else{
            return $this;
        }

        $order = $observer->getEvent()->getOrder();
        $orderData = $order->getData();

        $orderDataPost = $this->_request->getPost('order');
        if(isset($orderDataPost['account'])){
            $orderAccountPost = $orderDataPost['account'];
            $orderData['customer_firstname'] = isset($orderAccountPost['japan_firstname'])?$orderAccountPost['japan_firstname']:'';
            $orderData['customer_lastname'] = isset($orderAccountPost['japan_lastname'])?$orderAccountPost['japan_lastname']:'';
            $orderData['customer_firstnamekana'] = isset($orderAccountPost['firstnamekana'])?$orderAccountPost['firstnamekana']:'';
            $orderData['customer_lastnamekana'] = isset($orderAccountPost['lastnamekana'])?$orderAccountPost['lastnamekana']:'';
        }

        $billingAddressData = $order->getBillingAddress()->getData();
        $shippingAddressData = array();
        if(null == $order->getShippingAddress()){
            $shippingAddressData = $billingAddressData;
        }
        else{
            $shippingAddressData = $order->getShippingAddress()->getData();
        }

        $telephone = '';
        if(isset($shippingAddressData['telephone']) && $shippingAddressData['telephone'] != ''){
            $telephone = $shippingAddressData['telephone'];
        }
        else
            if(isset($billingAddressData['telephone']) && $billingAddressData['telephone'] != ''){
                $telephone = $billingAddressData['telephone'];
            }

        $companyName = '';
        if(isset($shippingAddressData['company']) && $shippingAddressData['company'] != ''){
            $companyName = $shippingAddressData['company'];
        }
        else
            if(isset($billingAddressData['company']) && $billingAddressData['company'] != ''){
                $companyName = $billingAddressData['company'];
            }

        $streets = array();
        if(isset($shippingAddressData['street'])){
            $streets = explode(PHP_EOL,$shippingAddressData['street']);
        }

        $shippingAddressData['postcode'] = str_replace("-",'',$shippingAddressData['postcode']);

        $regionDatas  = $this->_directoryHelper->getRegionData();

        $mapKeyCodeId = array();
        foreach($regionDatas[self::CONST_DEFAULT_COUNTRY] as $regionId => $regionData){
            if($regionId > 0){
                $mapKeyCodeId[$regionId] = $regionData['code'];
            }
        }


        $regionNameJp = '';
        if(isset($shippingAddressData['region_id']) && $shippingAddressData['region_id'] > 0){
            if(isset($mapKeyCodeId[$shippingAddressData['region_id']])){
                $regionCode = $mapKeyCodeId[$shippingAddressData['region_id']];
                $shippingAddressData['prefecture_code'] = $this->_mappingZoneJapan[$regionCode];
            }

            $regionNameJp = $this->_regionHelper->getJapanRegion($shippingAddressData['region_id']);
        }

        $customerInfo = array(
            'KEY_CUSTOMER_TYPE' => 2, // for none member
            'KEY_LAST_NAME' => isset($orderData['customer_lastname'])?$orderData['customer_lastname']:'',
            'KEY_FIRST_NAME' => isset($orderData['customer_firstname'])?$orderData['customer_firstname']:'',
            'KEY_FIRST_NAME_KANA' => isset($orderDataPost['account']['firstnamekana'])?$orderDataPost['account']['firstnamekana']:'',
            'KEY_LAST_NAME_KANA' => isset($orderDataPost['account']['lastnamekana'])?$orderDataPost['account']['lastnamekana']:'',
            'KEY_CELL_NUMBER' => $telephone,
            'KEY_COMPANY_NAME' => $companyName
        );


        $addressInfo = array(
            'KEY_ADDRESS_LAST_NAME' => isset($shippingAddressData['lastname'])?$shippingAddressData['lastname']:'',
            'KEY_ADDRESS_FIRST_NAME' => isset($shippingAddressData['firstname'])?$shippingAddressData['firstname']:'',
            'KEY_ADDRESS_LAST_NAME_KANA' => isset($shippingAddressData['lastnamekana'])?$shippingAddressData['lastnamekana']:'',
            'KEY_ADDRESS_FIRST_NAME_KANA' => isset($shippingAddressData['firstnamekana'])?$shippingAddressData['firstnamekana']:'',
            'KEY_POSTAL_CODE' => isset($shippingAddressData['postcode'])?$shippingAddressData['postcode']:'',
            'KEY_PREFECTURE_CODE' => isset($shippingAddressData['prefecture_code'])?$shippingAddressData['prefecture_code']:'',
            'KEY_ADDRESS1' => $regionNameJp,
            'KEY_ADDRESS2' => (isset($shippingAddressData['city'])?$shippingAddressData['city']:'').' '.(isset($streets[0])?$streets[0]:'').' '.(isset($streets[1])?$streets[1]:''),
            'KEY_ADDRESS3' => '',
            'KEY_PHONE_NUMBER' => isset($shippingAddressData['telephone'])?$shippingAddressData['telephone']:'',
            'KEY_FAX_NUMBER' => isset($shippingAddressData['fax'])?$shippingAddressData['fax']:'',
        );

        $this->_consumerLog->setRequest(\Zend_Json::encode(array('order_id' => $order->getIncrementId(),'customer' => $customerInfo,'address' => $addressInfo)));

        $customerCode = $this->setCustomerAPI($customerInfo,$addressInfo);
        if($customerCode !== false){
            //$this->_logger->debug(serialize(array('success',$customerCode)));
            $this->_consumerLog->setStatus(1);
        }
        else{
            $this->_consumerLog->setStatus(0);
        }
        $this->_consumerLog->save();
    }

    /**
     * SetCustomerAPI
     *
     * @param $customerInfo
     *
     * @param $addressInfo
     *
     * @param $requestType = 1
     * @param $customerCode = '' : create new is default
     *
     * @return bool
     */
    public function setCustomerAPI($customerInfo,$addressInfo, $requestType = 1, $customerCode = '')
    {
        $now = $this->_dateTime->date('Y/m/d H:m:i');

        $soapConfig = $this->soapHelper->getCommonRequestParams();

        $wsdl = $this->_apiCustomerHelper->getConsumerApiUrl('/SetCustomerService?wsdl');
        $endPoint = $this->_apiCustomerHelper->getConsumerApiUrl('/SetCustomerService.SetCustomerServiceHttpSoap12Endpoint/');
        $param1 = $this->_scopeConfig->getValue('consumer_db_api_url/customer/param1', \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE);
        $param2 = $this->_scopeConfig->getValue('consumer_db_api_url/customer/param2', \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE);


        $this->_soapclient = $this->soapClientFactory->create($wsdl, $soapConfig);
        $this->_soapclient->setLocation($endPoint);

        $params = array();
        $params[] = new \SoapVar($param1, XSD_STRING, null, null, 'clientInfo' );
        $params[] = new \SoapVar($param2, XSD_STRING, null, null, 'clientInfo' );
        $params[] = new \SoapVar($now, XSD_STRING, null, null, 'clientInfo' );

        $params[] = new \SoapVar($requestType, XSD_STRING, null, null, 'requestType' ); //create new account

        $params[] = new \SoapVar($customerCode, XSD_STRING, null, null, 'customerCode' );  //blank when create

        $params[] = new \SoapVar(array(
            new \SoapVar('KEY_CUSTOMER_TYPE', XSD_STRING, null, null, 'array' ),
            new \SoapVar('KEY_LAST_NAME', XSD_STRING, null, null, 'array' ),
            new \SoapVar('KEY_FIRST_NAME', XSD_STRING, null, null, 'array' ),
            new \SoapVar('KEY_LAST_NAME_KANA', XSD_STRING, null, null, 'array' ),
            new \SoapVar('KEY_FIRST_NAME_KANA', XSD_STRING, null, null, 'array' ),
            new \SoapVar('KEY_SEX', XSD_STRING, null, null, 'array' ),
            new \SoapVar('KEY_BIRTH_DATE', XSD_STRING, null, null, 'array' ),
            new \SoapVar('KEY_BIRTH_FLG', XSD_STRING, null, null, 'array' ),
            new \SoapVar('KEY_CELL_NUMBER', XSD_STRING, null, null, 'array' ),
            new \SoapVar('KEY_COMPANY_NAME', XSD_STRING, null, null, 'array' )
        ), SOAP_ENC_ARRAY, null, null, 'setParameter' );

        $params[] = new \SoapVar(array(
            new \SoapVar($customerInfo['KEY_CUSTOMER_TYPE'], XSD_STRING, null, null, 'array' ),
            new \SoapVar($customerInfo['KEY_LAST_NAME'], XSD_STRING, null, null, 'array' ),
            new \SoapVar($customerInfo['KEY_FIRST_NAME'], XSD_STRING, null, null, 'array' ),
            new \SoapVar($customerInfo['KEY_LAST_NAME_KANA'], XSD_STRING, null, null, 'array' ),
            new \SoapVar($customerInfo['KEY_FIRST_NAME_KANA'], XSD_STRING, null, null, 'array' ),
            new \SoapVar('0', XSD_STRING, null, null, 'array' ),
            new \SoapVar('', XSD_STRING, null, null, 'array' ),
            new \SoapVar('0', XSD_STRING, null, null, 'array' ),
            new \SoapVar($customerInfo['KEY_CELL_NUMBER'], XSD_STRING, null, null, 'array' ),
            new \SoapVar($customerInfo['KEY_COMPANY_NAME'], XSD_STRING, null, null, 'array' )
        ), SOAP_ENC_ARRAY, null, null, 'setParameter' );

        $params[] = new \SoapVar(array(
            new \SoapVar('KEY_ADDRESS_LAST_NAME', XSD_STRING, null, null, 'array' ),
            new \SoapVar('KEY_ADDRESS_FIRST_NAME', XSD_STRING, null, null, 'array' ),
            new \SoapVar('KEY_ADDRESS_LAST_NAME_KANA', XSD_STRING, null, null, 'array' ),
            new \SoapVar('KEY_ADDRESS_FIRST_NAME_KANA', XSD_STRING, null, null, 'array' ),
            new \SoapVar('KEY_POSTAL_CODE', XSD_STRING, null, null, 'array' ),
            new \SoapVar('KEY_PREFECTURE_CODE', XSD_STRING, null, null, 'array' ),
            new \SoapVar('KEY_ADDRESS1', XSD_STRING, null, null, 'array' ),
            new \SoapVar('KEY_ADDRESS2', XSD_STRING, null, null, 'array' ),
            new \SoapVar('KEY_ADDRESS3', XSD_STRING, null, null, 'array' ),
            new \SoapVar('KEY_PHONE_NUMBER', XSD_STRING, null, null, 'array' ),
            new \SoapVar('KEY_FAX_NUMBER', XSD_STRING, null, null, 'array' )
        ), SOAP_ENC_ARRAY, null, null, 'setParameterAddress' );
        $params[] = new \SoapVar(array(
            new \SoapVar($addressInfo['KEY_ADDRESS_LAST_NAME'], XSD_STRING, null, null, 'array' ),
            new \SoapVar($addressInfo['KEY_ADDRESS_FIRST_NAME'], XSD_STRING, null, null, 'array' ),
            new \SoapVar($addressInfo['KEY_ADDRESS_LAST_NAME_KANA'], XSD_STRING, null, null, 'array' ),
            new \SoapVar($addressInfo['KEY_ADDRESS_FIRST_NAME_KANA'], XSD_STRING, null, null, 'array' ),
            new \SoapVar($addressInfo['KEY_POSTAL_CODE'], XSD_STRING, null, null, 'array' ),
            new \SoapVar($addressInfo['KEY_PREFECTURE_CODE'], XSD_STRING, null, null, 'array' ),
            new \SoapVar($addressInfo['KEY_ADDRESS1'], XSD_STRING, null, null, 'array' ),
            new \SoapVar($addressInfo['KEY_ADDRESS2'], XSD_STRING, null, null, 'array' ),
            new \SoapVar($addressInfo['KEY_ADDRESS3'], XSD_STRING, null, null, 'array' ),
            new \SoapVar($addressInfo['KEY_PHONE_NUMBER'], XSD_STRING, null, null, 'array' ),
            new \SoapVar($addressInfo['KEY_FAX_NUMBER'], XSD_STRING, null, null, 'array' ),
        ), SOAP_ENC_ARRAY, null, null, 'setParameterAddress' );

        $response = array();
        try{
            $response = $this->_soapclient->setCustomer( new \SoapVar($params, SOAP_ENC_OBJECT) );
        } catch (\Exception $e) {
            $this->_logger->error((string)$e->getMessage());
            return false;
        }

        if(property_exists($response,'return')){
            $codeReturn = $response->return;
            if(isset($codeReturn[0]) && \Riki\Customer\Model\SsoConfig::SSO_RESPONSE_SUCCESS_CODE == $codeReturn[0]){
                if(isset($codeReturn[3])){
                    $customersCode = $codeReturn[3];
                    $this->_consumerLog->setResponseData(\Zend_Json::encode($codeReturn));
                    return $customersCode;
                }
            }
            else{
                $this->_consumerLog->setResponseData(\Zend_Json::encode($codeReturn));
                return false;
            }
        }
        else{
            $this->_consumerLog->setResponseData(\Zend_Json::encode($response));
            return false;

        }
        return false;
    }
}
