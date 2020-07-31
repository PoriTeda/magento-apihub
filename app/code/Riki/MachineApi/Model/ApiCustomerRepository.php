<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Riki\MachineApi\Model;

use Magento\Customer\Api\CustomerMetadataInterface;
use Magento\Customer\Model\Data\CustomerSecure;
use Magento\Framework\Api\DataObjectHelper;
use Magento\Framework\Api\ImageProcessorInterface;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Api\SortOrder;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\NoSuchEntityException;

/**
 * Customer repository.
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class ApiCustomerRepository implements \Riki\MachineApi\Api\ApiCustomerRepositoryInterface
{
    /**
     * @var \Magento\Customer\Model\CustomerFactory
     */
    protected $customerFactory;

    /**
     * @var \Magento\Customer\Model\Data\CustomerSecureFactory
     */
    protected $customerSecureFactory;

    /**
     * @var \Magento\Customer\Model\CustomerRegistry
     */
    protected $customerRegistry;

    /**
     * @var \Magento\Customer\Model\ResourceModel\AddressRepository
     */
    protected $addressRepository;

    /**
     * @var \Magento\Customer\Model\ResourceModel\Customer
     */
    protected $customerResourceModel;

    /**
     * @var \Magento\Customer\Api\CustomerMetadataInterface
     */
    protected $customerMetadata;

    /**
     * @var \Magento\Customer\Api\Data\CustomerSearchResultsInterfaceFactory
     */
    protected $searchResultsFactory;

    /**
     * @var \Magento\Framework\Event\ManagerInterface
     */
    protected $eventManager;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var \Magento\Framework\Api\ExtensibleDataObjectConverter
     */
    protected $extensibleDataObjectConverter;

    /**
     * @var DataObjectHelper
     */
    protected $dataObjectHelper;

    /**
     * @var ImageProcessorInterface
     */
    protected $imageProcessor;

    /**
     * @var \Magento\Framework\Api\ExtensionAttribute\JoinProcessorInterface
     */
    protected $extensionAttributesJoinProcessor;

    /**
     * @var \Magento\Customer\Model\ResourceModel\Customer\CollectionFactory
     */
    protected $_customerCollectionFactory;

    /**
     * @var \Riki\Quote\Model\QuoteManagement
     */
    protected $_quoteManagement;
    /**
     * @var \Magento\Framework\Webapi\Rest\Request
     */
    protected $_request;

    /**
     * @var \Magento\Customer\Api\CustomerRepositoryInterface $_customerRepository
     */
    protected $_customerRepository;

    /**
     * @var  \Riki\Customer\Model\CustomerRepository $customerRepository
     */
    protected $_rikiCustomerRepository;

    /**
     * @var \Magento\Directory\Helper\Data
     */
    protected $_directoryHelperData ;

    /**
     * @const string CONST_DEFAULT_COUNTRY
     */
    const CONST_DEFAULT_COUNTRY = 'JP';

    /**
     * @var \Magento\Directory\Helper\Data $_regionHelper
     */
    protected $_regionHelper;

    /**
     * @var array MappingAttributeSubProfile
     */
    protected $_mappingAttributeSubProfile = [
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
        'LENDING_STATUS_NBA'=>915,
        'LENDING_STATUS_NDG'=>916,
        'LENDING_STATUS_SPT'=>917,
        'LENDING_STATUS_ICS'=>918,
        'LENDING_STATUS_NSP'=>919,
        'CNC_Status' => 1133,
        'CIS_Status'=> 1134
    ];
    /**
     * @var array $_mappingZoneJapan
     */
    protected $_mappingZoneJapan = [
        'HKD' => 1,'AMR' => 2,'IWT' => 3, 'MYG' => 4, 'AKT' => 5,'YGT' => 6, 'FSM' => 7, 'IBR' => 8,'TOC' => 9, 'GUM' => 10,'STM' => 11, 'CHB' => 12,'TKY' => 13, 'KNG' => 14,'NGT' => 15, 'TYM' => 16,'IKW' => 17, 'FKI' => 18, 'YNS' => 19, 'NGN' => 20, 'GFU' => 21,'SZK' => 22,'AIC' => 23,'MIE' => 24,'SHG' => 25, 'KYT' => 26, 'OSK' => 27,'HYG' => 28,'NRA' => 29, 'WKY' => 30, 'TTR' => 31, 'SMN' => 32, 'OKY' => 33,'HRS' => 34,'YGC' => 35, 'TKS' => 36,'KGW' => 37, 'EHM' => 38, 'KCH' => 39,'FKO' => 40,'SAG' => 41, 'NGS' => 42, 'KMM' => 43, 'OTA' => 44, 'MYZ' => 45,'KGS' => 46,'OKN' => 47];

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig ,
     */
    protected $_scopeConfig;

    /**
     * @var \Magento\Framework\Stdlib\DateTime\TimezoneInterface
     */
    protected $_dateTime;

    /**
     * @var \Riki\MachineApi\Api\Data\ApiAddressInterfaceFactory $_apiAddressFactory
     */
    protected $_apiAddressFactory;

    /**
     * @var \Riki\MachineApi\Api\Data\ApiCustomerInterfaceFactory $apiCustomerFactory
     */
    protected $_apiCustomerFactory;

    /**
     * @var \Riki\Customer\Helper\Membership ,
     */
    protected $_customerMembershipHelper;

    /**
     * @var \Riki\Customer\Helper\Api
     */
    protected $_apiCustomerHelper;

    /**
     * @var \Riki\Customer\Helper\ConsumerDb\Soap
     */
    protected $soapHelper;

    /**
     * @var \Magento\Framework\Api\SearchCriteriaBuilder $_searchCriteriaBuilder
     */
    protected $_searchCriteriaBuilder;

    /**
     * @var \Riki\Framework\Webapi\Soap\ClientFactory
     */
    protected $soapClientFactory;

    /**
     * ApiCustomerRepository constructor.
     * @param \Magento\Customer\Model\CustomerFactory $customerFactory
     * @param \Magento\Customer\Model\Data\CustomerSecureFactory $customerSecureFactory
     * @param \Magento\Customer\Model\CustomerRegistry $customerRegistry
     * @param \Magento\Customer\Model\ResourceModel\AddressRepository $addressRepository
     * @param \Magento\Customer\Model\ResourceModel\Customer $customerResourceModel
     * @param CustomerMetadataInterface $customerMetadata
     * @param \Magento\Customer\Api\Data\CustomerSearchResultsInterfaceFactory $searchResultsFactory
     * @param \Magento\Framework\Event\ManagerInterface $eventManager
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Framework\Api\ExtensibleDataObjectConverter $extensibleDataObjectConverter
     * @param \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository
     * @param \Magento\Customer\Model\ResourceModel\Customer\CollectionFactory $customerCollectionFactory
     * @param \Magento\Framework\Stdlib\DateTime\TimezoneInterface $dateTime
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Riki\Quote\Model\QuoteManagement $quoteManagement
     * @param \Riki\Customer\Model\CustomerRepository $rikiCustomerRepository
     * @param \Magento\Directory\Helper\Data $regionHelper
     * @param \Riki\MachineApi\Api\Data\ApiAddressInterfaceFactory $apiAddressFactory
     * @param \Riki\MachineApi\Api\Data\ApiCustomerInterfaceFactory $apiCustomerFactory
     * @param \Riki\Customer\Helper\Membership $customerMembershipHelper
     * @param \Magento\Directory\Helper\Data $directoryHelperData
     * @param DataObjectHelper $dataObjectHelper
     * @param ImageProcessorInterface $imageProcessor
     * @param \Magento\Framework\Api\ExtensionAttribute\JoinProcessorInterface $extensionAttributesJoinProcessor
     * @param \Magento\Framework\Webapi\Rest\Request $request
     * @param \Riki\Customer\Helper\Api $apiCustomerHelper
     * @param \Riki\Customer\Helper\ConsumerDb\Soap $soapHelper
     * @param \Magento\Framework\Api\SearchCriteriaBuilder $_searchCriteriaBuilder
     * @param \Riki\Framework\Webapi\Soap\ClientFactory $soapClientFactory
     */
    public function __construct(
        \Magento\Customer\Model\CustomerFactory $customerFactory,
        \Magento\Customer\Model\Data\CustomerSecureFactory $customerSecureFactory,
        \Magento\Customer\Model\CustomerRegistry $customerRegistry,
        \Magento\Customer\Model\ResourceModel\AddressRepository $addressRepository,
        \Magento\Customer\Model\ResourceModel\Customer $customerResourceModel,
        \Magento\Customer\Api\CustomerMetadataInterface $customerMetadata,
        \Magento\Customer\Api\Data\CustomerSearchResultsInterfaceFactory $searchResultsFactory,
        \Magento\Framework\Event\ManagerInterface $eventManager,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\Api\ExtensibleDataObjectConverter $extensibleDataObjectConverter,
        \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository,
        \Magento\Customer\Model\ResourceModel\Customer\CollectionFactory $customerCollectionFactory,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $dateTime,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Riki\Quote\Model\QuoteManagement $quoteManagement,
        \Riki\Customer\Model\CustomerRepository $rikiCustomerRepository,
        \Magento\Directory\Helper\Data $regionHelper,
        \Riki\MachineApi\Api\Data\ApiAddressInterfaceFactory $apiAddressFactory,
        \Riki\MachineApi\Api\Data\ApiCustomerInterfaceFactory $apiCustomerFactory,
        \Riki\Customer\Helper\Membership $customerMembershipHelper,
        \Magento\Directory\Helper\Data $directoryHelperData,
        DataObjectHelper $dataObjectHelper,
        ImageProcessorInterface $imageProcessor,
        \Magento\Framework\Api\ExtensionAttribute\JoinProcessorInterface $extensionAttributesJoinProcessor,
        \Magento\Framework\Webapi\Rest\Request $request,
        \Riki\Customer\Helper\Api $apiCustomerHelper,
        \Riki\Customer\Helper\ConsumerDb\Soap $soapHelper,
        \Magento\Framework\Api\SearchCriteriaBuilder $_searchCriteriaBuilder,
        \Riki\Framework\Webapi\Soap\ClientFactory $soapClientFactory
    ) {
        $this->customerFactory = $customerFactory;
        $this->customerSecureFactory = $customerSecureFactory;
        $this->customerRegistry = $customerRegistry;
        $this->addressRepository = $addressRepository;
        $this->customerResourceModel = $customerResourceModel;
        $this->customerMetadata = $customerMetadata;
        $this->searchResultsFactory = $searchResultsFactory;
        $this->eventManager = $eventManager;
        $this->storeManager = $storeManager;
        $this->extensibleDataObjectConverter = $extensibleDataObjectConverter;
        $this->dataObjectHelper = $dataObjectHelper;
        $this->imageProcessor = $imageProcessor;
        $this->extensionAttributesJoinProcessor = $extensionAttributesJoinProcessor;
        $this->_customerCollectionFactory = $customerCollectionFactory;
        $this->_quoteManagement = $quoteManagement;
        $this->_request = $request;
        $this->_customerRepository = $customerRepository;
        $this->_rikiCustomerRepository = $rikiCustomerRepository;
        $this->_directoryHelperData = $directoryHelperData;
        $this->_regionHelper = $regionHelper;
        $this->_dateTime = $dateTime;
        $this->_scopeConfig = $scopeConfig;
        $this->_apiAddressFactory = $apiAddressFactory;
        $this->_apiCustomerFactory = $apiCustomerFactory;
        $this->_customerMembershipHelper = $customerMembershipHelper;
        $this->_apiCustomerHelper = $apiCustomerHelper;
        $this->soapHelper = $soapHelper;
        $this->_searchCriteriaBuilder = $_searchCriteriaBuilder;
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
     * @param $value
     * @return bool|false|int|string
     */
    protected function _getSubProfileName($value)
    {
        $name = array_search((int)$value, $this->_mappingAttributeSubProfile);
        if ($name) {
            return $name;
        }
        return false;
    }

    /**
     * Get Customer sub Data
     *
     * @return array|bool
     *
     * @throws \Exception
     */
    public function getCustomerSubData($customerConsumerDbId)
    {
        $dateTime = $this->_dateTime->date()->format('Y/m/d H:i:s');

        $soapConfig = $this->soapHelper->getCommonRequestParams();

        $wsdl = $this->_apiCustomerHelper->getConsumerApiUrl('/GetCustomerSubService?wsdl');
        $endPoint = $this->_apiCustomerHelper->getConsumerApiUrl('/GetCustomerSubService.GetCustomerSubServiceHttpSoap12Endpoint/');
        $param3 = $this->_scopeConfig->getValue('consumer_db_api_url/customer_sub/param3', \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE);
        $param4 = $this->_scopeConfig->getValue('consumer_db_api_url/customer_sub/param4', \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE);

        $soapClient = $this->initSoapClient($wsdl, $soapConfig);
        $soapClient->setLocation($endPoint);

        $params = [];
        $params[] = new \SoapVar($param3, XSD_STRING, null, null, 'clientInfo');
        $params[] = new \SoapVar($param4, XSD_STRING, null, null, 'clientInfo');
        $params[] = new \SoapVar($dateTime, XSD_STRING, null, null, 'clientInfo');
        $params[] = new \SoapVar($customerConsumerDbId, XSD_STRING, null, null, 'customerCode');

        foreach ($this->_mappingAttributeSubProfile as $keyAttribute => $valueAttribute) {
            $params[] = new \SoapVar($valueAttribute, XSD_STRING, null, null, 'getParameter');
        }
        try {
            $response = $soapClient->getCustomerSub(new \SoapVar($params, SOAP_ENC_OBJECT));
        } catch (\Exception $exception) {
            throw new \Magento\Framework\Exception\LocalizedException(__("We got problem while contacting consumer db,detail:".$exception->getMessage()));
        }
        if (property_exists($response, 'return')) {
            $codeReturn = $response->return[0]->array;
            $customersubs = [];
            $customersubsReturn = [];
            if (isset($codeReturn[0]) && \Riki\Customer\Model\SsoConfig::SSO_RESPONSE_SUCCESS_CODE == $codeReturn[0]) {
                $i = 4;
                $customersubsKey = $response->return[3]->array;
                while (true) {
                    if (!isset($response->return[$i])) {
                        break;
                    }
                    $customersub = [];
                    $customersubsValue = $response->return[($i)]->array;
                    foreach ($customersubsKey as $key => $customersubKey) {
                        $customersub[$customersubKey] = $customersubsValue[$key];
                    }
                    $customersubs[] = $customersub;
                    $i ++;
                }
                foreach ($customersubs as $customersub) {
                    if ($this->_getSubProfileName($customersub['SUBPROFILE_ID'])) {
                        $customersubsReturn[$this->_getSubProfileName($customersub['SUBPROFILE_ID'])] = $customersub['VALUE_NAME'];
                    }
                }
                return $customersubsReturn;
            } else {
                return [];
            }
        } else {
            throw new \Magento\Framework\Exception\LocalizedException(__("We got problem while contacting consumer db"));
        }
    }

    public function getCustomerData($customerConsumerDbId)
    {
        $dateTime = $this->_dateTime->date()->format('Y/m/d H:i:s');
        $soapConfig = $this->soapHelper->getCommonRequestParams();

        $wsdl = $this->_apiCustomerHelper->getConsumerApiUrl('/GetCustomerService?wsdl');
        $endPoint = $this->_apiCustomerHelper->getConsumerApiUrl('/GetCustomerService.GetCustomerServiceHttpSoap12Endpoint/');
        $param3 = $this->_scopeConfig->getValue('consumer_db_api_url/customer/param3', \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE);
        $param4 = $this->_scopeConfig->getValue('consumer_db_api_url/customer/param4', \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE);

        $soapClient = $this->initSoapClient($wsdl, $soapConfig);
        $soapClient->setLocation($endPoint);

        $params = [];
        $params[] = new \SoapVar($param3, XSD_STRING, null, null, 'clientInfo');
        $params[] = new \SoapVar($param4, XSD_STRING, null, null, 'clientInfo');
        $params[] = new \SoapVar($dateTime, XSD_STRING, null, null, 'clientInfo');
        $params[] = new \SoapVar($customerConsumerDbId, XSD_STRING, null, null, 'customerCode');
        $params[] = new \SoapVar('KEY_LAST_NAME', XSD_STRING, null, null, 'getParameter');
        $params[] = new \SoapVar('KEY_FIRST_NAME', XSD_STRING, null, null, 'getParameter');
        $params[] = new \SoapVar('KEY_LAST_NAME_KANA', XSD_STRING, null, null, 'getParameter');
        $params[] = new \SoapVar('KEY_FIRST_NAME_KANA', XSD_STRING, null, null, 'getParameter');
        $params[] = new \SoapVar('KEY_SEX', XSD_STRING, null, null, 'getParameter');
        $params[] = new \SoapVar('KEY_BIRTH_DATE', XSD_STRING, null, null, 'getParameter');
        $params[] = new \SoapVar('KEY_BIRTH_FLG', XSD_STRING, null, null, 'getParameter');
        $params[] = new \SoapVar('KEY_MARITAL_STAT_CODE', XSD_STRING, null, null, 'getParameter');
        $params[] = new \SoapVar('KEY_LOGIN_EMAIL', XSD_STRING, null, null, 'getParameter');
        $params[] = new \SoapVar('KEY_CLIENT_MAIL_TYPE', XSD_STRING, null, null, 'getParameter');
        $params[] = new \SoapVar('KEY_JOB_TITLE', XSD_STRING, null, null, 'getParameter');
        $params[] = new \SoapVar('KEY_PASSWORD', XSD_STRING, null, null, 'getParameter');
        $params[] = new \SoapVar('KEY_HANDLE_NAME', XSD_STRING, null, null, 'getParameter');

        $params[] = new \SoapVar('KEY_POSTAL_CODE', XSD_STRING, null, null, 'getParameter');
        $params[] = new \SoapVar('KEY_PREFECTURE_CODE', XSD_STRING, null, null, 'getParameter');
        $params[] = new \SoapVar('KEY_ADDRESS1', XSD_STRING, null, null, 'getParameter');
        $params[] = new \SoapVar('KEY_ADDRESS2', XSD_STRING, null, null, 'getParameter');
        $params[] = new \SoapVar('KEY_ADDRESS3', XSD_STRING, null, null, 'getParameter');
        $params[] = new \SoapVar('KEY_PHONE_NUMBER', XSD_STRING, null, null, 'getParameter');
        $params[] = new \SoapVar('KEY_COMPANY_NAME', XSD_STRING, null, null, 'getParameter');

        $params[] = new \SoapVar('CUSTOMER_TYPE', XSD_STRING, null, null, 'getParameter');
        $params[] = new \SoapVar('EMP_FLG', XSD_STRING, null, null, 'getParameter');

        /* new value */
        $params[] = new \SoapVar('KEY_EMAIL2', XSD_STRING, null, null, 'getParameter');
        $params[] = new \SoapVar('KEY_CLIENT_MAIL_TYPE2', XSD_STRING, null, null, 'getParameter');
        $params[] = new \SoapVar('KEY_FAX_NUMBER', XSD_STRING, null, null, 'getParameter');

        try {
            $response = $soapClient->getCustomer(new \SoapVar($params, SOAP_ENC_OBJECT));
        } catch (\Exception $exception) {
            throw new \Magento\Framework\Exception\LocalizedException(__("We got error while contact to consumer db,detail:" . $exception->getMessage()));
        }
        if (property_exists($response, 'return')) {
            $codeReturn = $response->return[0]->array;
            $customer = [];
            if (isset($codeReturn[0]) && \Riki\Customer\Model\SsoConfig::SSO_RESPONSE_SUCCESS_CODE == $codeReturn[0]) {
                $customersKey = $response->return[3]->array;
                $customersValue = $response->return[4]->array;
                foreach ($customersKey as $key => $customerKey) {
                    $customer[$customerKey] = $customersValue[$key];
                }

                /* we try to get customer sub data  */
                try {
                    //init action
                    $aCustomerSubData = $this->getCustomerSubData($customerConsumerDbId);
                } catch (\Magento\Framework\Exception\LocalizedException $exception) {
                    throw $exception;
                }

                /* now we're trying to get calculating customer group based on customer sub data */
                $this->_customerMembershipHelper->initMappingFieldCustomer($customer, $aCustomerSubData);

                $sites = $this->_customerMembershipHelper->getCustomerWebsite();
                $group = $this->_customerMembershipHelper->getCustomerGroup();
                $memberships = $this->_customerMembershipHelper->getCustomerMemberShip();

                $customerConsumerDb['sites'] = $sites;
                $customerConsumerDb['group'] = $group;
                $customerConsumerDb['memberships'] = $memberships;


                /* if every thing seem be ok , go to create customer API data */
                /** @var \Riki\MachineApi\Api\Data\ApiCustomerInterface $customerDataModel */
                $customerDataModel = $this->_apiCustomerFactory->create();
                /** @var \Riki\MachineApi\Api\Data\ApiAddressInterface $customerAddressDataModel */
                $customerAddressDataModel = $this->_apiAddressFactory->create();
                $customerDataModel->setLastname($customer["LAST_NAME"]);
                $customerDataModel->setFirstname($customer["FIRST_NAME"]);
                $customerDataModel->setCustomAttribute("firstnamekana", $customer["FIRST_NAME_KANA"]);
                $customerDataModel->setCustomAttribute("lastnamekana", $customer["LAST_NAME_KANA"]);
                $customerDataModel->setCustomAttribute("multiple_website", $sites);
                $customerDataModel->setWebsiteId(1);
                $customerDataModel->setCustomAttribute("consumer_db_id", $customerConsumerDbId);
                $customerDataModel->setGender($customer["SEX"]);
                $customerDataModel->setDob($customer["BIRTH_DATE"]);
                $customerDataModel->setBirthFlg($customer["BIRTH_FLG"]);
                $customerDataModel->setMaritalStatCode($customer["MARITAL_STAT_CODE"]);
                $customerDataModel->setEmail($customer["LOGIN_EMAIL"]);
                $customerDataModel->setEmail1Type($customer["CLIENT_MAIL_TYPE"]);
                $customerDataModel->setConsumerPassword($customer["PASSWORD"]);
                $customerDataModel->setCompanyName($customer["COMPANY_NAME"]);
                $customerDataModel->setEmail2($customer["EMAIL2"]);
                $customerDataModel->setEmail2Type($customer["CLIENT_MAIL_TYPE2"]);

                /* website and group information */
                $customerDataModel->setGroupId($group);
                $customerDataModel->setCustomAttribute("memberships", $memberships);

                $customerAddressDataModel->setAddressFirstName($customer["FIRST_NAME"]);
                $customerAddressDataModel->setAddressLastName($customer["LAST_NAME"]);
                $customerAddressDataModel->setAddressFirstNameKana($customer["FIRST_NAME_KANA"]);
                $customerAddressDataModel->setAddressLastNameKana($customer["LAST_NAME_KANA"]);

                if (isset($customer["POSTAL_CODE"]) && $customer["POSTAL_CODE"] != '') {
                    $postalCode = substr_replace($customer["POSTAL_CODE"], '-', 3, 0);
                    $customerAddressDataModel->setPostalCode($postalCode);
                }
                $customerAddressDataModel->setPrefectureCode($customer["PREFECTURE_CODE"]);
                $customerAddressDataModel->setPhoneNumber($customer["PHONE_NUMBER"]);
                $customerAddressDataModel->setFaxNumber($customer["FAX_NUMBER"]);
                $customerAddressDataModel->setAddress1($customer["ADDRESS1"]);
                $customerAddressDataModel->setAddress2($customer["ADDRESS2"]);
                $customerAddressDataModel->setCity(__("None"));
                $customerAddressDataModel->setCompany($customer["COMPANY_NAME"]);
                $customerAddressDataModel->setCountryId(self::CONST_DEFAULT_COUNTRY);

                $regionDatas = $this->_regionHelper->getRegionData();

                $mapKeyCodeId = [];
                foreach ($regionDatas[self::CONST_DEFAULT_COUNTRY] as $regionId => $regionData) {
                    if ($regionId > 0) {
                        $mapKeyCodeId[$regionId] = $regionData['code'];
                    }
                }

                $regionCode = array_search($customer["PREFECTURE_CODE"], $this->_mappingZoneJapan);
                $regionId = array_search($regionCode, $mapKeyCodeId);

                if (!$regionId) {
                    $regionId = 0;
                }
                $customerAddressDataModel->setRegionId($regionId);


                $customerDataModel->setAddresses([$customerAddressDataModel]);
                return $customerDataModel;
            } else {
                throw new \Magento\Framework\Exception\LocalizedException(__("We got error while contacting to consumer db,detail:" . $response->return[1]->array));
            }
        } else {
            throw new \Magento\Framework\Exception\LocalizedException(__("Could not get response from consumer db"));
        }
    }


    /**
     * {@inheritdoc}
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    public function save(
        $cartId,
        $consumerDbId
    ) {
        //set param for machine api
        $this->_request->setParam('call_machine_api', 'call_machine_api');

        $dataCustomer = $this->createNewCustomerWithConsumerDb($consumerDbId);

        if (!\Zend_Validate::is($dataCustomer, "NotEmpty")) {
            throw new  \Zend\Code\Exception\RuntimeException(__("Got error while process customer data"));
        }
        $customerId = $dataCustomer->getId();
        $storeId    = $dataCustomer->getStoreId();
        /* save quote to customer before process the next step */
        $this->_quoteManagement->assignCustomer($cartId, $customerId, $storeId);
        //add payment information
        $paymentInfomation = $this->processPaymentInformation($cartId, $customerId, $storeId);
        return $paymentInfomation;
    }

    /**
     * @param string $email
     * @return bool
     */
    public function checkConsumerDbCustomer($consumerDbId)
    {
        $dataCustomer =  $this->_customerCollectionFactory
            ->create()
            ->addFieldToFilter('consumer_db_id', trim($consumerDbId))
            ->setPageSize(1)
            ->setCurPage(1)
            ->getSize();
        if ($dataCustomer>0) {
            return true;
        } else {
            return false;
        }
    }
    /**
     * @param string $consumerDbId
     * @return bool
     */
    public function getCustomerByConsumerDbId($consumerDbId)
    {
        $filter = $this->_searchCriteriaBuilder
            ->addFilter('consumer_db_id', $consumerDbId, 'eq')
            ->create();
        try {
            $customers = $this->_customerRepository->getList($filter);
            foreach ($customers->getItems() as $customer) {
                return $customer;
            }
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * @param string $email
     * @return bool
     */
    public function getCustomerByEmail($email)
    {
        $filter = $this->_searchCriteriaBuilder
            ->addFilter('email', $email, 'eq')
            ->create();
        try {
            $customers = $this->_customerRepository->getList($filter);
            foreach ($customers->getItems() as $customer) {
                return $customer;
            }
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * @param string $email
     * @return bool
     */
    public function checkEmailCustomer($email)
    {
        $dataCustomer =  $this->_customerCollectionFactory
                    ->create()
                    ->addFieldToFilter('email', trim($email))
                    ->setPageSize(1)
                    ->setCurPage(1)
                    ->getSize();
        if ($dataCustomer>0) {
            return true;
        } else {
            return false;
        }
    }

    public function updateCustomer(
        \Riki\MachineApi\Api\Data\ApiCustomerInterface $customer
    ) {
        $email = $customer->getEmail();
        $dataCustomer = $this->_customerRepository->get(trim($email));
        ;
        if ($dataCustomer) {
            $prevCustomerData = $dataCustomer;
            $customerData = $this->extensibleDataObjectConverter->toNestedArray(
                $customer,
                [],
                '\Magento\Customer\Api\Data\CustomerInterface'
            );

            $customerModel = $this->customerFactory->create(['data' => $customerData]);
            $storeId = $customerModel->getStoreId();
            if ($storeId === null) {
                $customerModel->setStoreId($this->storeManager->getStore()->getId());
            }
            $customerModel->setId($prevCustomerData->getId());

            // Need to use attribute set or future updates can cause data loss
            if (!$customerModel->getAttributeSetId()) {
                $customerModel->setAttributeSetId(
                    \Magento\Customer\Api\CustomerMetadataInterface::ATTRIBUTE_SET_ID_CUSTOMER
                );
            }

            $customerSecure = $this->customerRegistry->retrieveSecureData($prevCustomerData->getId());
            $customerModel->setRpToken($customerSecure->getRpToken());
            $customerModel->setRpTokenCreatedAt($customerSecure->getRpTokenCreatedAt());
            $customerModel->setPasswordHash($customerSecure->getPasswordHash());

            // If customer email was changed, reset RpToken info
            if ($prevCustomerData
                && $prevCustomerData->getEmail() !== $customerModel->getEmail()
            ) {
                $customerModel->setRpToken(null);
                $customerModel->setRpTokenCreatedAt(null);
            }
            $customerModel->save();

            if ($customer->getAddresses() !== null) {
                if ($customerModel->getId()) {
                    $existingAddresses = $this->getById($customerModel->getId())->getAddresses();
                    $getIdFunc = function ($address) {
                        return $address->getId();
                    };
                    $existingAddressIds = array_map($getIdFunc, $existingAddresses);
                } else {
                    $existingAddressIds = [];
                }

                $savedAddressIds = [];
                $index = 1;
                foreach ($customer->getAddresses() as $address) {
                    /* convert to magento customer address object */
                    if ($address instanceof \Riki\MachineApi\Model\Data\ApiAddress) {
                        $address = $address->convertToCustomerAddressObject();
                    }
                    $address->setCustomerId($customerModel->getId())
                        ->setRegion($address->getRegion());
                    if ($index++ == 1) {
                        $address->setIsDefaultBilling(true);
                        $address->setIsDefaultShipping(true);
                    }

                    $this->addressRepository->save($address);
                    if ($address->getId()) {
                        $savedAddressIds[] = $address->getId();
                    }
                }
            }
            $savedCustomer = $this->get($customer->getEmail(), $customer->getWebsiteId());
            $this->eventManager->dispatch(
                'customer_save_after_data_object',
                ['customer_data_object' => $savedCustomer, 'orig_customer_data_object' => $customer]
            );
            return $savedCustomer;
        } else {
            throw new \Magento\Framework\Exception\NoSuchEntityException(__("Customer with email {$email} does not exists"));
        }
    }

    public function getMessageSuccess()
    {
        return [["message"=>"Add Customer information successful."]];
    }

    public function getMessageError()
    {
        return [["message"=>"Could not add customer information."]];
    }

    /**
     * @param $customer
     * @param $websiteID
     */
    public function processPaymentInformation($cartId, $customerId, $storeId)
    {
        $paymentInfomation =  $this->_quoteManagement->assignCustomer($cartId, $customerId, $storeId);
        if ($paymentInfomation) {
            return $this->getMessageSuccess();
        } else {
            return $this->getMessageError();
        }
    }

    protected function _getSubProfileId($key)
    {
        if (isset($this->_mappingAttributeSubProfile[$key])) {
            return $this->_mappingAttributeSubProfile[$key];
        }
        return false;
    }

    /**
     * Validate customer attribute values.
     *
     * @param \Riki\MachineApi\Api\Data\ApiCustomerInterface $customer
     * @throws InputException
     * @return void
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    protected function validate(\Riki\MachineApi\Api\Data\ApiCustomerInterface $customer)
    {
        $exception = new InputException();
        if (!\Zend_Validate::is(trim($customer->getFirstname()), 'NotEmpty')) {
            $exception->addError(__(InputException::REQUIRED_FIELD, ['fieldName' => 'firstname']));
        }

        if (!\Zend_Validate::is(trim($customer->getLastname()), 'NotEmpty')) {
            $exception->addError(__(InputException::REQUIRED_FIELD, ['fieldName' => 'lastname']));
        }

        $isEmailAddress = \Zend_Validate::is(
            $customer->getEmail(),
            'EmailAddress'
        );

        if (!$isEmailAddress) {
            $exception->addError(
                __(
                    InputException::INVALID_FIELD_VALUE,
                    ['fieldName' => 'email', 'value' => $customer->getEmail()]
                )
            );
        }
        $groupID = $customer->getGroupId();
        if ($groupID=='') {
            $exception->addError(__(InputException::REQUIRED_FIELD, ['fieldName' => 'group_id']));
        }

        $websiteID = $customer->getWebsiteId();
        if ($websiteID=='') {
            $exception->addError(__(InputException::REQUIRED_FIELD, ['fieldName' => 'website_id']));
        }

        $dob = $this->getAttributeMetadata('dob');
        if ($dob !== null && $dob->isRequired() && '' == trim($customer->getDob())) {
            $exception->addError(__(InputException::REQUIRED_FIELD, ['fieldName' => 'dob']));
        }

        $taxvat = $this->getAttributeMetadata('taxvat');
        if ($taxvat !== null && $taxvat->isRequired() && '' == trim($customer->getTaxvat())) {
            $exception->addError(__(InputException::REQUIRED_FIELD, ['fieldName' => 'taxvat']));
        }

        $gender = $this->getAttributeMetadata('gender');
        if ($gender !== null && $gender->isRequired() && '' == trim($customer->getGender())) {
            $exception->addError(__(InputException::REQUIRED_FIELD, ['fieldName' => 'gender']));
        }

        $consumerDb = $customer->getCustomAttribute('consumer_db_id')->getValue();
        if (trim($consumerDb) =='') {
            $exception->addError(__(InputException::REQUIRED_FIELD, ['fieldName' => 'consumer_db_id']));
        }

        $firstnamekana = $customer->getCustomAttribute('firstnamekana')->getValue();
        if (trim($firstnamekana) =='') {
            $exception->addError(__(InputException::REQUIRED_FIELD, ['fieldName' => 'firstnamekana']));
        }

        $lastnamekana = $customer->getCustomAttribute('lastnamekana')->getValue();
        if (trim($lastnamekana) =='') {
            $exception->addError(__(InputException::REQUIRED_FIELD, ['fieldName' => 'lastnamekana']));
        }

        if ($exception->wasErrorAdded()) {
            throw $exception;
        }
    }

    /**
     * Get attribute metadata.
     *
     * @param string $attributeCode
     * @return \Magento\Customer\Api\Data\AttributeMetadataInterface|null
     */
    protected function getAttributeMetadata($attributeCode)
    {
        try {
            return $this->customerMetadata->getAttributeMetadata($attributeCode);
        } catch (NoSuchEntityException $e) {
            return null;
        }
    }

    /**
     * Helper function that adds a FilterGroup to the collection.
     *
     * @param \Magento\Framework\Api\Search\FilterGroup $filterGroup
     * @param \Magento\Customer\Model\ResourceModel\Customer\Collection $collection
     * @return void
     * @throws \Magento\Framework\Exception\InputException
     */
    protected function addFilterGroupToCollection(
        \Magento\Framework\Api\Search\FilterGroup $filterGroup,
        \Magento\Customer\Model\ResourceModel\Customer\Collection $collection
    ) {
        $fields = [];
        $conditions = [];
        foreach ($filterGroup->getFilters() as $filter) {
            $condition = $filter->getConditionType() ? $filter->getConditionType() : 'eq';
            $fields[] = ['attribute' => $filter->getField(), $condition => $filter->getValue()];
        }
        if ($fields) {
            $collection->addFieldToFilter($fields, $conditions);
        }
    }

    /**
     * valdiate data
     * @param $arrDataValidate
     * @throws NoSuchEntityException
     */
    public function dataValidate($arrDataValidate)
    {
        $data = $this->_request->getRequestData();
        foreach ($arrDataValidate as $attribute) {
            if (isset($data['customer']) && isset($data['customer'][$attribute])) {
                if ($data['customer'][$attribute] ==null) {
                    throw InputException::requiredField($attribute);
                }
            } else {
                throw InputException::requiredField($attribute);
            }
        }
    }

    public function createCustomter(\Riki\MachineApi\Api\Data\ApiCustomerInterface $customer, $passwordHash = null)
    {
        $this->validate($customer);
        /* update to consumer db first */
        $prevCustomerData = null;
        if ($customer->getId()) {
            $prevCustomerData = $this->getById($customer->getId());
        }
        $customer = $this->imageProcessor->save(
            $customer,
            CustomerMetadataInterface::ENTITY_TYPE_CUSTOMER,
            $prevCustomerData
        );

        $origAddresses = $customer->getAddresses();
        $customer->setAddresses([]);
        $customerData = $this->extensibleDataObjectConverter->toNestedArray(
            $customer,
            [],
            '\Magento\Customer\Api\Data\CustomerInterface'
        );

        $customer->setAddresses($origAddresses);
        $customerModel = $this->customerFactory->create(['data' => $customerData]);
        $storeId = $customerModel->getStoreId();
        if ($storeId === null) {
            $customerModel->setStoreId($this->storeManager->getStore()->getId());
        }
        $customerModel->setId($customer->getId());

        // Need to use attribute set or future updates can cause data loss
        if (!$customerModel->getAttributeSetId()) {
            $customerModel->setAttributeSetId(
                \Magento\Customer\Api\CustomerMetadataInterface::ATTRIBUTE_SET_ID_CUSTOMER
            );
        }
        // Populate model with secure data
        if ($customer->getId()) {
            $customerSecure = $this->customerRegistry->retrieveSecureData($customer->getId());
            $customerModel->setRpToken($customerSecure->getRpToken());
            $customerModel->setRpTokenCreatedAt($customerSecure->getRpTokenCreatedAt());
            $customerModel->setPasswordHash($customerSecure->getPasswordHash());
        } else {
            if ($passwordHash) {
                $customerModel->setPasswordHash($passwordHash);
            }
        }

        // If customer email was changed, reset RpToken info
        if ($prevCustomerData
            && $prevCustomerData->getEmail() !== $customerModel->getEmail()
        ) {
            $customerModel->setRpToken(null);
            $customerModel->setRpTokenCreatedAt(null);
        }
        $customerModel->save();
        $this->customerRegistry->push($customerModel);
        $customerId = $customerModel->getId();

        if ($customer->getAddresses() !== null) {
            if ($customer->getId()) {
                $existingAddresses = $this->getById($customer->getId())->getAddresses();
                $getIdFunc = function ($address) {
                    return $address->getId();
                };
                $existingAddressIds = array_map($getIdFunc, $existingAddresses);
            } else {
                $existingAddressIds = [];
            }

            $savedAddressIds = [];
            $index = 1;
            foreach ($customer->getAddresses() as $address) {
                /* convert to magento customer address object */
                if ($address instanceof \Riki\MachineApi\Model\Data\ApiAddress) {
                    $address = $address->convertToCustomerAddressObject();
                }
                $address->setCustomerId($customerId)
                    ->setRegion($address->getRegion());
                if ($index++ == 1) {
                    $address->setIsDefaultBilling(true);
                    $address->setIsDefaultShipping(true);
                }
                $this->addressRepository->save($address);
                if ($address->getId()) {
                    $savedAddressIds[] = $address->getId();
                }
            }
        }
        /* update customer to consumer db */

        $savedCustomer = $this->get($customer->getEmail(), $customer->getWebsiteId());
        $this->eventManager->dispatch(
            'customer_save_after_data_object',
            ['customer_data_object' => $savedCustomer, 'orig_customer_data_object' => $customer]
        );


        return $savedCustomer;
    }

    /**
     * {@inheritdoc}
     */
    public function get($email, $websiteId = null)
    {
        $customerModel = $this->customerRegistry->retrieveByEmail($email, $websiteId);
        return $customerModel->getDataModel();
    }

    /**
     * {@inheritdoc}
     */
    public function getById($customerId)
    {
        $customerModel = $this->customerRegistry->retrieve($customerId);
        return $customerModel->getDataModel();
    }

    /**
     * @param $consumerDbId
     * @return mixed|\Riki\MachineApi\Api\Data\ApiCustomerInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Zend_Validate_Exception
     */
    public function createNewCustomerWithConsumerDb($consumerDbId)
    {
        if (!\Zend_Validate::is($consumerDbId, "NotEmpty")) {
            throw new \Magento\Framework\Exception\LocalizedException(__("Consumer db id is a required field"));
        }

        /* try to get customer information from consumer db */
        try {
            /** @var \Riki\MachineApi\Api\Data\ApiCustomerInterface $dataCustomer */
            $dataCustomer = $this->getCustomerData($consumerDbId);
            $kssResponseData =  $this->_rikiCustomerRepository->prepareAllInfoCustomer($consumerDbId);
        } catch (\Exception $e) {
            throw new \Magento\Framework\Exception\LocalizedException(__($e->getMessage()));
        }
        /* base on recived data customer we will determine create or update customer based on these data */
        $checkEmailCustomer = $this->checkEmailCustomer($kssResponseData['customer_api']['email']);
        $checkConsumerDbIdCustomer = $this->checkConsumerDbCustomer($consumerDbId);

        /** @var 1 : create | 2 update $magentoActionFlag */
        $magentoActionFlag = null;

        /*if both consumerDbId and email are empty, customer is new on magento */
        if (!$checkEmailCustomer && !$checkConsumerDbIdCustomer) {
            $magentoActionFlag = 1;
        } else {
            $magentoActionFlag = 2;
        }

        if (!\Zend_Validate::is($magentoActionFlag, "NotEmpty")) {
            throw new  \Zend\Code\Exception\RuntimeException(__("Could not determine Magento action"));
        }
        switch ($magentoActionFlag) {
            case 1:
                //create new customer
                $dataCustomer = $this->_rikiCustomerRepository->createUpdateEcCustomer($kssResponseData, $consumerDbId, null, null);
                /*get customer with address*/
                if ($dataCustomer) {
                    $dataCustomer = $this->_customerRepository->getById($dataCustomer->getId());
                }
                break;
            case 2:
                //update customer information
                $customerModel = $this->getCustomerByConsumerDbId($consumerDbId);

                /*check if the customer has email to be exist*/
                if (!$customerModel && isset($kssResponseData['customer_api']['email'])) {
                    $customerModel = $this->getCustomerByEmail($kssResponseData['customer_api']['email']);
                }

                $dataCustomer = $this->_rikiCustomerRepository->createUpdateEcCustomer($kssResponseData, $consumerDbId, null, $customerModel);
                break;
        }
        return $dataCustomer;
    }
}
