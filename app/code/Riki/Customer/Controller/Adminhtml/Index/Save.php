<?php
namespace Riki\Customer\Controller\Adminhtml\Index;

use Riki\Customer\Model\AmbCustomerRepository;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Customer\Controller\RegistryConstants;
use Magento\Framework\Exception\LocalizedException;

class Save extends \Magento\Customer\Controller\Adminhtml\Index\Save{


    const JA_JP = 'ja_JP';
    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface $_scopeConfig
     *
     */
    protected $_scopeConfig;

    /**
     * @var \Riki\Customer\Helper\Region $_regionHelper
     */
    protected $_regionHelper;
    /**
     * @var \Psr\Log\LoggerInterface $_logger
     */
    protected $_logger;
    /**
     * @var \Zend\Soap\Client $_soapClient
     */
    protected $_soapClient;
    /**
     * @var \Magento\Framework\Stdlib\DateTime\DateTime $_dateTime
     */
    protected $_dateTime;

    /**
     * @const string CONST_DEFAULT_COUNTRY
     */
    const CONST_DEFAULT_COUNTRY = 'JP';
    /**
     * @var \Riki\Customer\Model\AmbCustomerRepository $_ambCustomerRepository
     */
    protected $_ambCustomerRepository;
    /**
     * @var  \Riki\Customer\Model\CustomerRepository $_rikiCustomerRepository
     */
    protected $_rikiCustomerRepository;
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
     * @var \Magento\Directory\Helper\Data $_directoryHelper
     */

    protected $_directoryHelper;
    /**
     * @var \Riki\Customer\Model\ShoshaFactory $_modelShoshaFactory
     */
    protected $_modelShoshaFactory;

    /**
     * @var \Riki\Subscription\Model\ProductCart\ProductCart
     */
    protected $_productCartModel;

    /**
     * @var array MappingAttributeSubProfile
     */
    protected $_mappingAttributeSubProfile = array(

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
        'LENDING_STATUS_DUO' => 2570,
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
        'GARDIAN_APPROVAL' => 710,
        'AMB_SALE' => 920,
        'NescafeStandFlg'=>1850
    );

    protected $_mappingMageMembership = array(
        'Off Line Members' => 1,
        'On Line Members' => 2,
        'Ambassador Members' => 3,
        'Invoice Members' => 4,
        'CNC Members' => 5,
        'CIS Members'=> 6,
        'EC Site' => 1,
        'CNC Site' => 3,
        'CIS Site' => 4
    );

    /**
     * Save constructor.
     * @param AmbCustomerRepository $ambCustomerRepository
     * @param \Riki\Customer\Model\CustomerRepository $rikiCustomerRepository
     * @param \Psr\Log\LoggerInterface $logger
     * @param \Zend\Soap\Client $soapClient
     * @param \Magento\Framework\Stdlib\DateTime\DateTime $dateTime
     * @param \Riki\Customer\Helper\Region $regionHelper
     * @param \Riki\Customer\Model\ShoshaFactory $modelShoshaFactory
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Directory\Helper\Data $directoryHelper
     * @param \Magento\Backend\App\Action\Context $context
     * @param \Magento\Framework\Registry $coreRegistry
     * @param \Magento\Framework\App\Response\Http\FileFactory $fileFactory
     * @param \Magento\Customer\Model\CustomerFactory $customerFactory
     * @param \Magento\Customer\Model\AddressFactory $addressFactory
     * @param \Magento\Customer\Model\Metadata\FormFactory $formFactory
     * @param \Magento\Newsletter\Model\SubscriberFactory $subscriberFactory
     * @param \Magento\Customer\Helper\View $viewHelper
     * @param \Magento\Framework\Math\Random $random
     * @param \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository
     * @param \Magento\Framework\Api\ExtensibleDataObjectConverter $extensibleDataObjectConverter
     * @param \Magento\Customer\Model\Address\Mapper $addressMapper
     * @param \Magento\Customer\Api\AccountManagementInterface $customerAccountManagement
     * @param \Magento\Customer\Api\AddressRepositoryInterface $addressRepository
     * @param \Magento\Customer\Api\Data\CustomerInterfaceFactory $customerDataFactory
     * @param \Magento\Customer\Api\Data\AddressInterfaceFactory $addressDataFactory
     * @param \Magento\Customer\Model\Customer\Mapper $customerMapper
     * @param \Magento\Framework\Reflection\DataObjectProcessor $dataObjectProcessor
     * @param \Magento\Framework\Api\DataObjectHelper $dataObjectHelper
     * @param \Magento\Framework\DataObjectFactory $objectFactory
     * @param \Magento\Framework\View\LayoutFactory $layoutFactory
     * @param \Magento\Framework\View\Result\LayoutFactory $resultLayoutFactory
     * @param \Magento\Framework\View\Result\PageFactory $resultPageFactory
     * @param \Magento\Backend\Model\View\Result\ForwardFactory $resultForwardFactory
     * @param \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory
     */
    public function __construct(
        \Riki\Customer\Model\AmbCustomerRepository $ambCustomerRepository,
        \Riki\Customer\Model\CustomerRepository $rikiCustomerRepository,
        \Psr\Log\LoggerInterface $logger,
        \Zend\Soap\Client $soapClient,
        \Magento\Framework\Stdlib\DateTime\DateTime $dateTime,
        \Riki\Customer\Helper\Region $regionHelper,
        \Riki\Customer\Model\ShoshaFactory $modelShoshaFactory,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Directory\Helper\Data $directoryHelper,
        \Magento\Backend\App\Action\Context $context,
        \Magento\Framework\Registry $coreRegistry,
        \Magento\Framework\App\Response\Http\FileFactory $fileFactory,
        \Magento\Customer\Model\CustomerFactory $customerFactory,
        \Magento\Customer\Model\AddressFactory $addressFactory,
        \Magento\Customer\Model\Metadata\FormFactory $formFactory,
        \Magento\Newsletter\Model\SubscriberFactory $subscriberFactory,
        \Magento\Customer\Helper\View $viewHelper,
        \Magento\Framework\Math\Random $random,
        \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository,
        \Magento\Framework\Api\ExtensibleDataObjectConverter $extensibleDataObjectConverter,
        \Magento\Customer\Model\Address\Mapper $addressMapper,
        \Magento\Customer\Api\AccountManagementInterface $customerAccountManagement,
        \Magento\Customer\Api\AddressRepositoryInterface $addressRepository,
        \Magento\Customer\Api\Data\CustomerInterfaceFactory $customerDataFactory,
        \Magento\Customer\Api\Data\AddressInterfaceFactory $addressDataFactory,
        \Magento\Customer\Model\Customer\Mapper $customerMapper,
        \Magento\Framework\Reflection\DataObjectProcessor $dataObjectProcessor,
        \Magento\Framework\Api\DataObjectHelper $dataObjectHelper,
        \Magento\Framework\DataObjectFactory $objectFactory,
        \Magento\Framework\View\LayoutFactory $layoutFactory,
        \Magento\Framework\View\Result\LayoutFactory $resultLayoutFactory,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory,
        \Magento\Backend\Model\View\Result\ForwardFactory $resultForwardFactory,
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory,
        \Riki\Subscription\Model\ProductCart\ProductCart $productCartModel
    )
    {
        $this->_ambCustomerRepository = $ambCustomerRepository;
        $this->_rikiCustomerRepository = $rikiCustomerRepository;
        $this->_customerFactory = $customerFactory;
        $this->_logger = $logger;
        $this->_dateTime = $dateTime;
        $this->_soapClient = $soapClient;
        $this->_regionHelper = $regionHelper;
        $this->_scopeConfig = $scopeConfig;
        $this->_directoryHelper = $directoryHelper;
        $this->_modelShoshaFactory = $modelShoshaFactory;
        $this->_productCartModel = $productCartModel;
        parent::__construct($context, $coreRegistry, $fileFactory, $customerFactory, $addressFactory, $formFactory, $subscriberFactory, $viewHelper, $random, $customerRepository, $extensibleDataObjectConverter, $addressMapper, $customerAccountManagement, $addressRepository, $customerDataFactory, $addressDataFactory, $customerMapper, $dataObjectProcessor, $dataObjectHelper, $objectFactory, $layoutFactory, $resultLayoutFactory, $resultPageFactory, $resultForwardFactory, $resultJsonFactory);
    }

    /**
     * @return \Magento\Backend\Model\View\Result\Redirect|\Magento\Framework\Controller\Result\Redirect
     */
    public function execute()
    {
        $returnToEdit = false;
        $originalRequestData = $this->getRequest()->getPostValue();
        $customerId = isset($originalRequestData['customer']['entity_id'])
            ? $originalRequestData['customer']['entity_id']
            : null;
        $editFlag = 1;
        $customerCode = null;
        $successMessageAmb = __("Created ambassador customer: ");
        $successMessageSetCustomerSub= __("Customer sub-profile set success.");
        $successMessageCustomer = __("Created customer on consumerDB: ");
        $errorMessageAmb = __("Create ambassador customer error: ");
        $errorMessageCustomer = __("Create customer error: ");
        if($customerId){
            $editFlag =2;
            $successMessageAmb = __("Updated ambassador customer: ");
            $successMessageCustomer = __("Updated customer on consumer DB: ");
            $errorMessageAmb = __("Update ambassador customer error: ");
            $errorMessageCustomer = __("Update customer error: ");
        }
        $customerAttribute = array();
        $aSubCustomerData = array();
        if ($originalRequestData) {
            //get customer address
            $customerAddress = isset($originalRequestData['address']) ? $originalRequestData['address']:null;
            //get customer info;
            $customers = isset($originalRequestData['customer']) ? $originalRequestData['customer'] : null;
            //check duplicate;
                try {
                    if($editFlag == 1){
                        if($this->checkDuplicate($customers,$this->getHomeAddress($originalRequestData))){
                            $this->_getSession()->setCustomerData($originalRequestData);
                            $resultRedirect = $this->resultRedirectFactory->create();
                            $resultRedirect->setPath(
                                'customer/*/new',
                                ['_current' => true]
                            );
                            return $resultRedirect;
                        }
                    }
                    if($customerId){
                        $customerOrigin = $this->_customerFactory->create()->load($customers['entity_id']);
                        $customerCode = $customerOrigin['consumer_db_id'];
                    }

                    
                    list($customerData,$customerAddressData,$ambassadorInfo,$machineInfo) = $this->prepareAllInfoCustomer($customers,$customerAddress);

                    // prevent call center to Edit Password, Home and Company Address
                    if( $editFlag == 2){
                        if(isset($customerData['KEY_PASSWORD'])){
                            unset($customerData['KEY_PASSWORD']);
                        }
                        //$customerAddressData = [];

                        /**
                         * check delete company address
                         */
                        $resultCheckCompanyAddress = $this->checkDeleteAddress($originalRequestData,'company');
                        if(!empty($resultCheckCompanyAddress)) {
                            return $resultCheckCompanyAddress;
                        }

                        /**
                         * check delete home address
                         */
                        $resultCheckHomeAddress = $this->checkDeleteAddress($originalRequestData,'home');
                        if(!empty($resultCheckHomeAddress)) {
                            return $resultCheckHomeAddress;
                        }

                    }

                    $response = $this->_rikiCustomerRepository->setCustomerAPI($customerData, $customerAddressData, $editFlag, $customerCode);

                    if(property_exists($response,'return')){
                        $codeReturn = $response->return;
                        if(isset($codeReturn[0]) && \Riki\Customer\Model\SsoConfig::SSO_RESPONSE_SUCCESS_CODE == $codeReturn[0]){
                            //set ambassador customer
                            if(isset($codeReturn[3]) && $customers['amb_type']!=0){

                                $consumerDbId = $codeReturn[3];
                                $flagSetAmb =1;
                                $isAmb = $this->checkAmbassadorState($consumerDbId);
                                if($isAmb){
                                    $flagSetAmb =2;
                                }
                                $ambSetResponse = $this->_ambCustomerRepository->setAmbCustomerAPI($ambassadorInfo,$flagSetAmb,$codeReturn[3]);
                                if($ambSetResponse){
                                    $this->messageManager->addSuccess($successMessageAmb.$consumerDbId);
                                }else{
                                    $this->messageManager->addError($errorMessageAmb);
                                }
                            }
                            //set sub customer
                            $aSubCustomerData = $this->prepareSubCustomerData($customers,$editFlag);
                            if(isset($aSubCustomerData)){
                                $responseSubCustomer = $this->_rikiCustomerRepository->setCustomerSubAPI($codeReturn[3], $aSubCustomerData);
                                if($responseSubCustomer){
                                    list($customerAttribute['membership'],$customerAttribute['multiple_website'],$customerAttribute['group_id'],$aCustomerResponseData) = $this->getMemberShipFromSubCustomer($codeReturn[3],$customers,$aSubCustomerData);
                                }
                                $this->messageManager->addSuccess($successMessageSetCustomerSub.$codeReturn[1]);
                            }
                            // set machine customer
                            if(count($machineInfo) >0){
                                $setMachineResponse = $this->_rikiCustomerRepository->setMachine($machineInfo,$codeReturn[3]);
                                if($setMachineResponse){
                                    $this->messageManager->addSuccess($setMachineResponse);
                                }
                            }
                            //update shosha customer information
                            $customerAttribute = $this->setShoshaInfoCustomer($originalRequestData,$customerAttribute);

                            $this->messageManager->addSuccess($successMessageCustomer.$codeReturn[1]);
                            if(!$customerCode && (isset($codeReturn[3]) && ($codeReturn[3] !=""))){
                                $customerCode = $codeReturn[3];
                            }
                            if(isset($originalRequestData['customer']['email']) && $originalRequestData['customer']['email'] != null){
                                return $this->createCustomerEC($originalRequestData,$customerId,$customerCode,$customerAttribute,null);
                            }else{
                                return $this->createCustomerEC($originalRequestData,$customerId,$customerCode,$customerAttribute,$aCustomerResponseData);
                            }
                        }
                        else{
                            $this->_addSessionErrorMessages($errorMessageCustomer.(isset($codeReturn[1]) ? $codeReturn[1] : ""));
                            $this->_getSession()->setCustomerData($originalRequestData);
                            $returnToEdit = true;
                        }
                    }else{
                        $this->_addSessionErrorMessages(__("Call API error with message: ").(isset($codeReturn[1]) ? $codeReturn[1] : ""));
                        $this->_getSession()->setCustomerData($originalRequestData);
                        $returnToEdit = true;
                    }

                } catch (\Magento\Framework\Validator\Exception $exception) {
                    $messages = $exception->getMessages();
                    if (empty($messages)) {
                        $messages = $exception->getMessage();
                    }
                    $this->_addSessionErrorMessages($messages);
                    $this->_getSession()->setCustomerData($originalRequestData);
                    $returnToEdit = true;
                } catch (LocalizedException $exception) {
                    $this->_addSessionErrorMessages($exception->getMessage());
                    $this->_getSession()->setCustomerData($originalRequestData);
                    $returnToEdit = true;
                } catch (\Exception $exception) {
                    $this->messageManager->addException($exception, __('Something went wrong while saving the customer.'));
                    $this->_getSession()->setCustomerData($originalRequestData);
                    $returnToEdit = true;
                }

            }

        $resultRedirect = $this->resultRedirectFactory->create();
        if ($returnToEdit) {
            if ($customerId) {
                $resultRedirect->setPath(
                    'customer/*/edit',
                    ['id' => $customerId, '_current' => true]
                );
            } else {
                $resultRedirect->setPath(
                    'customer/*/new',
                    ['_current' => true]
                );
            }
        } else {
            $resultRedirect->setPath('customer/index');
        }
        return $resultRedirect;
    }

    protected function _getSubProfileId($key){
        if(isset($this->_mappingAttributeSubProfile[$key])){
            return $this->_mappingAttributeSubProfile[$key];
        }
        return false;
    }

    protected function _getMemberShipId($key){
        if(isset($this->_mappingMageMembership[$key])){
            return $this->_mappingMageMembership[$key];
        }
        return false;
    }

    /** Mapping gender for set Customer
     * @param $value
     * @return int
     */
    public function mappingGender($value){
        switch ($value){
            case 3 : return 0;
            case 1 : return 1;
            case 2 : return 2;
        }
    }
    /**
     * prepareAllInfoCustomer
     *
     * @param $customers
     * @param $customerAddress
     * @return array
     */
    protected function prepareAllInfoCustomer($customers,$customerAddress){

        $homeAddress = $this->getHomeAddress($customerAddress);
        $companyAddress = $this->getCompanyAddress($customerAddress);

        $regionDatas  = $this->_directoryHelper->getRegionData();

        $mapKeyCodeId = array();
        foreach($regionDatas[self::CONST_DEFAULT_COUNTRY] as $regionId => $regionData){
            if($regionId > 0){
                $mapKeyCodeId[$regionId] = $regionData['code'];
            }
        }
        $regionNameJpHome = $regionNameJpCompany = "";
        if(isset($homeAddress['region_id']) && $homeAddress['region_id'] > 0){
            if(isset($mapKeyCodeId[$homeAddress['region_id']])){
                $regionCode = $mapKeyCodeId[$homeAddress['region_id']];
                $homeAddress['prefecture_code'] = $this->_mappingZoneJapan[$regionCode];
            }

            $regionNameJpHome = $this->_regionHelper->getJapanRegion($homeAddress['region_id']);
        }
        if(isset($customers['COM_ADDRESS1']) && $customers['COM_ADDRESS1'] > 0){
            if(isset($mapKeyCodeId[$customers['COM_ADDRESS1']])){
                $regionCode = $mapKeyCodeId[$customers['COM_ADDRESS1']];
                $companyAddress['prefecture_code'] = $this->_mappingZoneJapan[$regionCode];
            }

            $regionNameJpCompany = $this->_regionHelper->getJapanRegion($customers['COM_ADDRESS1']);
        }

        $emailType1  = isset($customers['email_1_type']) ?$customers['email_1_type'] :"" ;
        $emailType2 = isset($customers['email_2_type']) ?$customers['email_2_type'] :"" ;
        $maritalStatCode = isset($customers['KEY_MARITAL_STAT_CODE']) ?$customers['KEY_MARITAL_STAT_CODE'] :"";
        $jobTitle = isset($customers['KEY_JOB_TITLE']) ?$customers['KEY_JOB_TITLE'] :"";
        $espFlag = isset($customers['KEY_EPS_FLG']) ?$customers['KEY_EPS_FLG'] :"";
        $keySex = $this->mappingGender($customers['gender']);
        $homeTelephone = isset($homeAddress['telephone']) ?$homeAddress['telephone'] :"";
        $companyTelephone = isset($companyAddress['telephone']) ?$companyAddress['telephone'] :"";

        $dob = date('Y/m/d',strtotime(isset($customers['dob']) ?$customers['dob'] :"")) ;
        $keyCaution = isset($customers['KEY_CAUTION']) ?$customers['KEY_CAUTION'] :"";
        $keyCompanyName =  isset($customers['customer_company_name']) ?$customers['customer_company_name'] :"";
        $keyPostName = isset($customers['KEY_POST_NAME']) ?$customers['KEY_POST_NAME'] :"";
        $keyWorkPhNum =  isset($customers['key_work_ph_num']) ?$customers['key_work_ph_num'] :"";
        $keyAsstPhNum = isset($customers['KEY_ASST_PH_NUM']) ?$customers['KEY_ASST_PH_NUM'] :"";

        if(isset($customers['shosha_business_code']) && $customers['shosha_business_code'] != ''){
            $customers['b2b_flag'] = 1;
        }
        else{
            $customers['b2b_flag'] = 0;
        }

        $customerData =   [
            "KEY_LAST_NAME" =>  isset($customers['lastname'])? $customers['lastname'] : "",
            "KEY_FIRST_NAME" =>  isset($customers['firstname']) ?$customers['firstname'] :"",
            "KEY_LAST_NAME_KANA" =>  isset($customers['lastnamekana']) ?$customers['lastnamekana'] :"",
            "KEY_FIRST_NAME_KANA" => isset($customers['firstnamekana']) ?$customers['firstnamekana'] :"" ,
            "KEY_SEX" =>   $keySex,
            "KEY_BIRTH_DATE" => $dob,
            "KEY_BIRTH_FLG" => 0,
            "KEY_MARITAL_STAT_CODE" => (int) $maritalStatCode,
            "KEY_EMAIL" =>  isset($customers['email']) ?$customers['email'] :"",
            "KEY_CLIENT_MAIL_TYPE" => (int) $emailType1 ,
            "KEY_JOB_TITLE" =>   $jobTitle,
            "KEY_EPS_FLG" =>   $espFlag,
            "KEY_EMAIL2" => isset($customers['email_2']) ?$customers['email_2'] :"",
            "KEY_CLIENT_MAIL_TYPE2" => (int) $emailType2,
            "KEY_ID_DISC_NUMBER" =>  "",
            "KEY_CELL_NUMBER" =>   $homeTelephone,
            "KEY_ASST_PH_NUM" => $keyAsstPhNum,
            "KEY_POST_NAME" => $keyPostName,
            "KEY_COMPANY_NAME" => $keyCompanyName,
            "KEY_WORK_PH_NUM" =>$keyWorkPhNum,
            "KEY_CAUTION" => $keyCaution,
            "KEY_OFFICE_USE_FLG" => isset($customers['b2b_flag'])?$customers['b2b_flag']:0
        ];
        if(isset($customers['KEY_PASSWORD']) && $customers['KEY_PASSWORD'] !=""){
            $customerData['KEY_PASSWORD'] = $customers['KEY_PASSWORD'];
        }

        if(isset($customers['offline_customer'])){
            if($customers['offline_customer'] == 0){
                $customerData['CUSTOMER_TYPE'] = 1;
            }
            else
            if($customers['offline_customer'] == 1){
                $customerData['CUSTOMER_TYPE'] = 2;
            }
        }

        $customerAddressData = [
            'KEY_ADDRESS_LAST_NAME' => isset($homeAddress['lastname'])?$homeAddress['lastname']:'',
            'KEY_ADDRESS_FIRST_NAME' => isset($homeAddress['firstname'])?$homeAddress['firstname']:'',
            'KEY_ADDRESS_LAST_NAME_KANA' => isset($homeAddress['lastnamekana'])?$homeAddress['lastnamekana']:'',
            'KEY_ADDRESS_FIRST_NAME_KANA' => isset($homeAddress['firstnamekana'])?$homeAddress['firstnamekana']:'',
            'KEY_POSTAL_CODE' => str_replace("-",'',isset($homeAddress['postcode']) ? $homeAddress['postcode'] :""),
            'KEY_PREFECTURE_CODE' => isset($homeAddress['prefecture_code'])?$homeAddress['prefecture_code']:'',
            'KEY_ADDRESS1' => $regionNameJpHome,
            'KEY_ADDRESS2' => isset($homeAddress['street'][0])?$homeAddress['street'][0]:'',
            'KEY_ADDRESS3' => isset($homeAddress['KEY_ADDRESS3'])?$homeAddress['KEY_ADDRESS3']:'',
            'KEY_ADDRESS4' => isset($homeAddress['KEY_ADDRESS4'])?$homeAddress['KEY_ADDRESS4']:'',
            'KEY_PHONE_NUMBER' => $homeTelephone,
            'KEY_FAX_NUMBER' => isset($homeAddress['fax'])?$homeAddress['fax']:'',
        ];
        $ambAppDate =  ($customers['AMB_APPLICATION_DATE'] !="") ? date('Y/m/d',strtotime($customers['AMB_APPLICATION_DATE'])) :"" ;

        $ambStopDate = ($customers['AMB_STOP_DATE'] !="") ? date('Y/m/d',strtotime($customers['AMB_STOP_DATE'])) :"" ;

        $ambassadorInfo = [
            "AMB_APPLICATION_DATE" =>  $ambAppDate,
            "AMB_STOP_DATE" =>  $ambStopDate,
            "AMB_STOP_REASON" =>  isset($customers['AMB_STOP_REASON']) ?$customers['AMB_STOP_REASON'] :"",
            "NJL_CHARGE_COMPANY" =>  isset($customers['NJL_CHARGE_COMPANY']) ?$customers['NJL_CHARGE_COMPANY'] :"",
            "NJL_CHARGE" =>  isset($customers['NJL_CHARGE']) ?$customers['NJL_CHARGE'] :"",
            "COM_NAME" =>  isset($customers['amb_com_name']) ?$customers['amb_com_name'] :"",
            "COM_DIVISION_NAME" => isset($customers['amb_com_division_name']) ?$customers['amb_com_division_name'] :"",
            "CHARGE_PERSON" =>  isset($customers['CHARGE_PERSON']) ?$customers['CHARGE_PERSON'] :"",
            "COM_PH_NUM" =>  isset($customers['amb_ph_num']) ?$customers['amb_ph_num'] :"",
            "COM_POSTAL_CODE" =>   str_replace("-",'',isset($customers['COM_POSTAL_CODE']) ?$customers['COM_POSTAL_CODE'] :""),
            "COM_ADDRESS1" =>  isset($regionNameJpCompany)?$regionNameJpCompany :"",
            "COM_ADDRESS2" =>  isset($customers['COM_ADDRESS2']) ?$customers['COM_ADDRESS2'] :"",
            "COM_ADDRESS3" =>  isset($customers['COM_ADDRESS3']) ?$customers['COM_ADDRESS3'] :"",
            "COM_ADDRESS4" =>  isset($customers['COM_ADDRESS4']) ?$customers['COM_ADDRESS4'] :"",
            "EMPLOYEES" => isset($customers['EMPLOYEES']) ?$customers['EMPLOYEES'] :"",
            "INTRODUCER_E_MAIL" =>   isset($customers['INTRODUCER_E_MAIL']) ?$customers['INTRODUCER_E_MAIL'] :"",
            "DAY_CONTACT_TEL" =>  isset($customers['DAY_CONTACT_TEL']) ?$customers['DAY_CONTACT_TEL'] :""
        ];
        
        $machineInfo = array();
        $listMachineDelete = array();
        $listMachineAdd = array();
        if(isset($customers['MD0000'])){
            $machineInfo['MD0000'] = $customers['MD0000'];
        }
        if(isset($customers['PM0000'])){
            $machineInfo['PM0000'] = $customers['PM0000'];
        }
        if(isset($customers['SPM0000'])){
            $machineInfo['SPM0000'] = $customers['SPM0000'];
        }
        if(isset($customers['NM0000'])){
            $machineInfo['NM0000'] = $customers['NM0000'];
        }
        if(isset($customers['ST0000'])){
            $machineInfo['ST0000'] = $customers['ST0000'];
        } 
        if(isset($customers['OT0000'])){
            $machineInfo['OT0000'] = $customers['OT0000'];
        }
        $getMachineStatus = $this->_session->getCustomerMachineResponse();
        if(is_array($getMachineStatus)){
            foreach ($machineInfo as $machineName => $value){
                if($value == "false"){
                    foreach ( $getMachineStatus as $k => $v) {
                        if ($machineName == $v['MACHINE_NO']) {
                            $listMachineDelete[$v['REGISTRATION_NO']] = $v['MACHINE_NO'];
                            unset($getMachineStatus[$k]);
                        }
                    }
                }else{
                    $flag = true;
                    foreach ( $getMachineStatus as $k => $v) {
                        if ($machineName == $v['MACHINE_NO']) {
                           $flag = false;
                        }
                    }
                    if($flag){$listMachineAdd[$machineName] = "true";}
                }
            }
        }
        $machineInfoReturn = ['delete'=>$listMachineDelete, 'add'=>$listMachineAdd ];
        return array($customerData,$customerAddressData,$ambassadorInfo,$machineInfoReturn);
    }

    /**
     * GetHomeAddress
     *
     * @param $originalRequestData
     * @return $customerAddress
     */
    public function getHomeAddress($originalRequestData){

        $customerAddress = null;

        $customerAddressList = isset($originalRequestData['address']) ? $originalRequestData['address']:false;
        if(!$customerAddressList){
            $customerAddressList = $originalRequestData;
        }
        if($customerAddressList && count($customerAddressList) >0) {
            foreach ($customerAddressList as $value){
                if(isset($value['riki_type_address']) && $value['riki_type_address'] == \Riki\Customer\Model\Address\AddressType::HOME){
                    $customerAddress = $value;
                }
            }
        }

        return $customerAddress;
    }
    /**
     * GetCompanyAddress
     *
     * @param $originalRequestData
     * @return $customerAddress
     */
    public function getCompanyAddress($originalRequestData){

        $customerAddress = null;

        $customerAddressList = isset($originalRequestData['address']) ? $originalRequestData['address']:false;
        if(!$customerAddressList){
            $customerAddressList = $originalRequestData;
        }
        if($customerAddressList && count($customerAddressList) >0) {
            foreach ($customerAddressList as $value){
                if(isset($value['riki_type_address']) && $value['riki_type_address'] == \Riki\Customer\Model\Address\AddressType::OFFICE){
                    $customerAddress = $value;
                }
            }
        }

        return $customerAddress;
    }
    /**
     * PrepareSubCustomerData
     *
     * @param $customers
     * @param $editFlag
     * @return array
     */
    public function prepareSubCustomerData($customers,$editFlag){

        //Ambassador info
        $ambType = isset($customers['amb_type']) ?$customers['amb_type'] :"";
        $AmbType = "";
        switch ($ambType){
            case 0: $AmbType = "";
                break;
            case 1: $AmbType = 11;
                break;
            case 9: $AmbType = 99;
        }

        $AmbReferenceCusCode = isset($customers['AMB_REFERENCE_CUS_CODE']) ?$customers['AMB_REFERENCE_CUS_CODE'] :"";
        $UsePointType =  isset($customers['USE_POINT_TYPE']) ?$customers['USE_POINT_TYPE'] :"";
        $UsePointAmount =  isset($customers['USE_POINT_AMOUNT']) ?$customers['USE_POINT_AMOUNT'] :"";
        $EditMessage =  isset($customers['EDIT_MESSAGE']) ?$customers['EDIT_MESSAGE'] :"";
        $NhsIntroducerId =  isset($customers['NHS_INTRODUCER_ID']) ?$customers['NHS_INTRODUCER_ID'] :"";
        $PetshopCode =  isset($customers['PETSHOP_CODE']) ?$customers['PETSHOP_CODE'] :"";
        $PetshopApplicationDate = (isset($customers['PETSHOP_APPLICATION_DATE']) && $customers['PETSHOP_APPLICATION_DATE']!="") ? date('Y/m/d',strtotime($customers['PETSHOP_APPLICATION_DATE'])) :"" ;
        $PetshopAuthorizedDate = (isset($customers['PETSHOP_AUTHORIZED_DATE']) && $customers['PETSHOP_AUTHORIZED_DATE']!="") ?date('Y/m/d',strtotime($customers['PETSHOP_AUTHORIZED_DATE'])) :"";
        $PetName = isset($customers['PET_NAME']) ?$customers['PET_NAME'] :"";
        $PetBreed = isset($customers['PET_BREED']) ?$customers['PET_BREED'] :"";
        $PetSex = isset($customers['PET_SEX']) ?$customers['PET_SEX'] :"";
        $PetBirthDt = (isset($customers['PET_BIRTH_DT'])&& $customers['PET_BIRTH_DT']!="")  ? date('Y/m/d',strtotime($customers['PET_BIRTH_DT'])) :"";
        $ambSale = isset($customers['amb_sale']) ?$customers['amb_sale'] : null;


        $lendingStatusNBA = isset($customers['LENDING_STATUS_NBA']) ?$customers['LENDING_STATUS_NBA'] :"";
        $lendingStatusNDG = isset($customers['LENDING_STATUS_NDG']) ?$customers['LENDING_STATUS_NDG'] :"";
        $lendingStatusSPT = isset($customers['LENDING_STATUS_SPT']) ?$customers['LENDING_STATUS_SPT'] :"";
        $lendingStatusICS = isset($customers['LENDING_STATUS_ICS']) ?$customers['LENDING_STATUS_ICS'] :"";
        $lendingStatusNSP = isset($customers['LENDING_STATUS_NSP']) ?$customers['LENDING_STATUS_NSP'] :"";
        $lendingStatusDUO = isset($customers['LENDING_STATUS_DUO']) ?$customers['LENDING_STATUS_DUO'] :"";


        $chocolatoryFLG =  ($customers['CHOCOLLATORY_FLG'] == 'true') ? '1' :'0';
        $kittkatClubFLG = ($customers['KITKAT_CLUB_FLG']== 'true') ? '1' :'0';
        $milanoStatus = ($customers['MILANO_STATUS']== 'true') ? '1' :'0';
        $allegriaStatus  = ($customers['ALLEGRIA_STATUS']== 'true') ? '1' :'0';
        $satelliteStatus= ($customers['SATELLITE_FLG']== 'true') ? '1' :'0';
        $guardianApproval = ($customers['GARDIAN_APPROVAL'] == 'true') ? '1' :'0';
        $ambFriends = ($customers['AMB_FRIENDS'] == 'true') ? '1' :'0';
        $satelliteAmb = ($customers['SATELLITE_AMB'] == 'true') ? '1' :'0';
        $wellnessClubAmb = ($customers['WELLNESSCLUB_AMB'] == 'true') ? '1' :'0';
        $nescafeStandFlg = ($customers['NescafeStandFlg'] == 'true') ? '1' :'0';

        $aSubCustomerData = [
            $this->_getSubProfileId("AMB_TYPE") => $AmbType,
            $this->_getSubProfileId("AMB_REFERENCE_CUS_CODE") => $AmbReferenceCusCode,
            $this->_getSubProfileId("USE_POINT_TYPE") => $UsePointType,
            $this->_getSubProfileId("USE_POINT_AMOUNT") => $UsePointAmount,
            $this->_getSubProfileId("EDIT_MESSAGE") => $EditMessage,
            $this->_getSubProfileId("NHS_INTRODUCER_ID") => $NhsIntroducerId,
            $this->_getSubProfileId("PETSHOP_CODE") => $PetshopCode,
            $this->_getSubProfileId("PETSHOP_APPLICATION_DATE") => $PetshopApplicationDate,
            $this->_getSubProfileId("PETSHOP_AUTHORIZED_DATE") => $PetshopAuthorizedDate,
            $this->_getSubProfileId("PET_NAME") => $PetName,
            $this->_getSubProfileId("PET_BREED") => $PetBreed,
            $this->_getSubProfileId("PET_SEX") => $PetSex,
            $this->_getSubProfileId("PET_BIRTH_DT") => $PetBirthDt,
            $this->_getSubProfileId("CHOCOLLATORY_FLG") => $chocolatoryFLG,
            $this->_getSubProfileId("KITKAT_CLUB_FLG") => $kittkatClubFLG,
            $this->_getSubProfileId("MILANO_STATUS") => $milanoStatus,
            $this->_getSubProfileId("ALLEGRIA_STATUS") => $allegriaStatus,
            $this->_getSubProfileId("SATELLITE_FLG") => $satelliteStatus,
            $this->_getSubProfileId("GARDIAN_APPROVAL") => $guardianApproval,
            $this->_getSubProfileId("AMB_FRIENDS") => $ambFriends,
            $this->_getSubProfileId("SATELLITE_AMB") => $satelliteAmb,
            $this->_getSubProfileId("WELLNESSCLUB_AMB") => $wellnessClubAmb,
            $this->_getSubProfileId("LENDING_STATUS_NBA") => $lendingStatusNBA,
            $this->_getSubProfileId("LENDING_STATUS_NDG") => $lendingStatusNDG,
            $this->_getSubProfileId("LENDING_STATUS_SPT") => $lendingStatusSPT,
            $this->_getSubProfileId("LENDING_STATUS_ICS") => $lendingStatusICS,
            $this->_getSubProfileId("LENDING_STATUS_NSP") => $lendingStatusNSP,
            $this->_getSubProfileId("LENDING_STATUS_DUO") => $lendingStatusDUO,
            $this->_getSubProfileId("AMB_SALE") => $ambSale,
            $this->_getSubProfileId("NescafeStandFlg") => $nescafeStandFlg
        ];


        // Invoice customer, CIS member, CNC member

        if(isset($customers['shosha_business_code']) && $customers['shosha_business_code'] != ''){
            $customers['b2b_flag'] = 1;
        }
        else{
            $customers['b2b_flag'] = 0;
        }

        $b2bFlag = $customers['b2b_flag'];
        $shoshaBusinessCode = $customers['shosha_business_code'];

        $aSubCustomerData[$this->_getSubProfileId('BUSINESS_CODE')] = $shoshaBusinessCode;

        if($b2bFlag && (strpos($shoshaBusinessCode,'032') === 0 || strpos($shoshaBusinessCode,'031') === 0)){
            if(strpos($shoshaBusinessCode,'031') === 0){
                $aSubCustomerData[$this->_getSubProfileId('CIS_Status') ] = 1;
                $aSubCustomerData[$this->_getSubProfileId('CNC_Status') ] = 0;
            }
            else
            if(strpos($shoshaBusinessCode,'032') === 0){
                $aSubCustomerData[$this->_getSubProfileId('CNC_Status') ] = 1;
                $aSubCustomerData[$this->_getSubProfileId('CIS_Status') ] = 0;
            }
        }
        else{
            $aSubCustomerData[$this->_getSubProfileId('CIS_Status') ] = 0;
            $aSubCustomerData[$this->_getSubProfileId('CNC_Status') ] = 0;
        }

        return $aSubCustomerData;
    }

    /**
     * GetMemberShipFromSubCustomer
     *
     * @param $customers
     * @param $aSubCustomerData
     * @return array
     */
    protected function getMemberShipFromSubCustomer($customerCode,$customers,$aSubCustomerData){

        $mageMembership = [];


        $sites = [$this->_getMemberShipId('EC Site')];

        if('' != $aSubCustomerData[$this->_getSubProfileId('BUSINESS_CODE')]){
            $mageMembership[] = $this->_getMemberShipId('Invoice Members');
        }

        if(isset($aSubCustomerData[$this->_getSubProfileId('CNC_Status')]) && 1 == $aSubCustomerData[$this->_getSubProfileId('CNC_Status')]){
            $mageMembership[] = $this->_getMemberShipId('CNC Members');
            $sites[] = $this->_getMemberShipId('CNC Site');
        }

        if(isset($aSubCustomerData[$this->_getSubProfileId('CIS_Status')]) && 1 == $aSubCustomerData[$this->_getSubProfileId('CIS_Status')]){
            $mageMembership[] = $this->_getMemberShipId('CIS Members');
            $sites[] = $this->_getMemberShipId('CIS Site');
        }

        if(isset($customers['offline_customer'])){
            if($customers['offline_customer'] == 0){
                $mageMembership[] = $this->_getMemberShipId('On Line Members');
            }
            else
            if($customers['offline_customer'] == 1){
                $mageMembership[] = $this->_getMemberShipId('Off Line Members');
            }
        }

        if(isset($aSubCustomerData[$this->_getSubProfileId("AMB_TYPE")]) && $aSubCustomerData[$this->_getSubProfileId("AMB_TYPE")] > 0){
            $mageMembership[] = $this->_getMemberShipId('Ambassador Members');
        }

        //unset all membership
        $aCustomerResponseData = $this->_rikiCustomerRepository->prepareAllInfoCustomer($customerCode);

        $customers['membership'] = [];
        if(isset($aCustomerResponseData['customer_api']['membership']) && '' != $aCustomerResponseData['customer_api']['membership']){
            $customers['membership'] = explode(",",$aCustomerResponseData['customer_api']['membership']);
        }
        
        $customers['multiple_website'] = [$this->_getMemberShipId('EC Site')];
        if(isset($aCustomerResponseData['customer_api']['multiple_website']) && '' != $aCustomerResponseData['customer_api']['multiple_website']){
            $customers['multiple_website'] = explode(",",$aCustomerResponseData['customer_api']['multiple_website']);
        }

        $sGroups = 1;
        if(isset($aCustomerResponseData['customer_api']['group_id']) && '' != $aCustomerResponseData['customer_api']['group_id']){
            $sGroups = $aCustomerResponseData['customer_api']['group_id'];
        }

        $mageMembership = array_unique(array_merge($customers['membership'],$mageMembership));

        $sites = array_unique(array_merge($customers['multiple_website'],$sites));

        $sMageMembership = '';
        $sSites = '';

        if(count($mageMembership)){
            $sMageMembership = implode(",",$mageMembership);
        }

        if(count($sites)){
            $sSites = implode(",",$sites);
        }

        return array($sMageMembership,$sSites,$sGroups,$aCustomerResponseData);
    }
    /**
     * Create customer on magento side after create success customer from consumerDB
     */
    protected function createCustomerEC($originalRequestData,$customerId,$consumerDbId,$customerAttribute,$aCustomerResponseData){
        $returnToEdit = false;
        if ($originalRequestData) {
            try {
                // optional fields might be set in request for future processing by observers in other modules
                $customerData = $this->_extractCustomerData();
                if($customerData['email'] =="" || !isset($customerData['email'])){
                    $customerData['email'] = isset($aCustomerResponseData['customer_api']['email'])?$aCustomerResponseData['customer_api']['email']:"";
                }
                if(count($customerAttribute)){
                    foreach($customerAttribute as $keyAttribute => $valueAttribute){
                        $customerData[$keyAttribute] = $valueAttribute;
                    }
                }

                $addressesData = $this->_extractCustomerAddressData($customerData);
                $request = $this->getRequest();
                $isExistingCustomer = (bool)$customerId;
                $customer = $this->customerDataFactory->create();
                if ($isExistingCustomer) {
                    $savedCustomerData = $this->_customerRepository->getById($customerId);
                    $customerData = array_merge(
                        $this->customerMapper->toFlatArray($savedCustomerData),
                        $customerData
                    );
                    $customerData['id'] = $customerId;
                }

                $this->dataObjectHelper->populateWithArray(
                    $customer,
                    $customerData,
                    '\Magento\Customer\Api\Data\CustomerInterface'
                );
                $addresses = [];
                foreach ($addressesData as $addressData) {
                    $region = isset($addressData['region']) ? $addressData['region'] : null;
                    $regionId = isset($addressData['region_id']) ? $addressData['region_id'] : null;
                    $addressData['region'] = [
                        'region' => $region,
                        'region_id' => $regionId,
                    ];
                    $addressDataObject = $this->addressDataFactory->create();
                    $this->dataObjectHelper->populateWithArray(
                        $addressDataObject,
                        $addressData,
                        '\Magento\Customer\Api\Data\AddressInterface'
                    );
                    $addresses[] = $addressDataObject;
                }

                $this->_eventManager->dispatch(
                    'adminhtml_customer_prepare_save',
                    ['customer' => $customer, 'request' => $request]
                );
                $customer->setAddresses($addresses);
                if(isset($consumerDbId) && $consumerDbId !=""){
                    $customer->setCustomAttribute('consumer_db_id',$consumerDbId);
                }
                // Save customer
                if ($isExistingCustomer) {
                    $this->_customerRepository->save($customer);
                } else {
                    $customer = $this->customerAccountManagement->createAccount($customer);
                    $customerId = $customer->getId();
                }

                $isSubscribed = null;
                if ($this->_authorization->isAllowed(null)) {
                    $isSubscribed = $this->getRequest()->getPost('subscription');
                }
                if ($isSubscribed !== null) {
                    if ($isSubscribed !== 'false') {
                        $this->_subscriberFactory->create()->subscribeCustomerById($customerId);
                    } else {
                        $this->_subscriberFactory->create()->unsubscribeCustomerById($customerId);
                    }
                }

                // After save
                $this->_eventManager->dispatch(
                    'adminhtml_customer_save_after',
                    ['customer' => $customer, 'request' => $request]
                );
                $this->_getSession()->unsCustomerData();
                // Done Saving customer, finish save action
                $this->_coreRegistry->register(RegistryConstants::CURRENT_CUSTOMER_ID, $customerId);
                $this->messageManager->addSuccess(__('You saved the customer.'));
                $returnToEdit = (bool)$this->getRequest()->getParam('back', false);
            } catch (\Magento\Framework\Validator\Exception $exception) {
                $messages = $exception->getMessages();
                if (empty($messages)) {
                    $messages = $exception->getMessage();
                }
                $this->_addSessionErrorMessages($messages);
                $this->_getSession()->setCustomerData($originalRequestData);
                $returnToEdit = true;
            } catch (\Magento\Framework\Exception\LocalizedException $exception) {
                $this->_addSessionErrorMessages($exception->getMessage());
                $this->_getSession()->setCustomerData($originalRequestData);
                $returnToEdit = true;
            } catch (\Exception $exception) {
                $this->messageManager->addException($exception, __('Something went wrong while saving the customer.'));
                $this->_getSession()->setCustomerData($originalRequestData);
                $returnToEdit = true;
            }
        }
        $resultRedirect = $this->resultRedirectFactory->create();
        if ($returnToEdit) {
            if ($customerId) {
                $resultRedirect->setPath(
                    'customer/*/edit',
                    ['id' => $customerId, '_current' => true]
                );
            } else {
                $resultRedirect->setPath(
                    'customer/*/new',
                    ['_current' => true]
                );
            }
        } else {
            $resultRedirect->setPath('customer/index');
        }
        return $resultRedirect;
    }

    /**
     * @param $consummerDbId
     * @return bool
     */
    public function checkAmbassadorState($consummerDbId){
       $response =  $this->_ambCustomerRepository->getAmbassadorInfo($consummerDbId);
        if(property_exists($response,'return')){
            $codeReturn = $response->return;
            if(isset($response->return[0]->array[0]) && \Riki\Customer\Model\SsoConfig::SSO_RESPONSE_SUCCESS_CODE == $response->return[0]->array[0]){
                return true;
            }
            else{
                return false;
            }
        }
        return false;
    }

    /**
     * setShoshaInfoCustomer
     *
     * @param $originalRequestData
     * @return mixed
     */
    public function setShoshaInfoCustomer($originalRequestData,$customerAttribute){

        $shoshaBusinessCode = isset($originalRequestData['customer']['shosha_business_code'])?$originalRequestData['customer']['shosha_business_code']:'';
        $isInvoiceCustomer = ('' != $shoshaBusinessCode)?true:false;

        if($isInvoiceCustomer){
            $aShoshaCollections = $this->_modelShoshaFactory->create()->getCollection()->addFieldToFilter('shosha_business_code',$shoshaBusinessCode);
            $aShoshaItem = null;
            foreach ($aShoshaCollections as $aShoshaCollectionItem) {
                $aShoshaItem = $aShoshaCollectionItem;
            }
            if($aShoshaItem){
                $customerAttribute['b2b_flag'] = true;
                $customerAttribute['shosha_code'] = $aShoshaItem->getShoshaCode();
            }
        }
        else{
                $customerAttribute['b2b_flag'] = false;
        }
        return $customerAttribute;
    }

    /**
     * Call API Model to check if customer duplicate
     * @param $customerInfo
     * @param $addressInfo
     * @return bool
     * @throws \Exception
     */
    public function checkDuplicate($customerInfo,$addressInfo){
        //return false;
        try{
            $fieldsToValidate = [];
            $fieldsToValidate[] = isset($customerInfo['lastname']) ? $customerInfo['lastname'] :"";
            $fieldsToValidate[] = isset($customerInfo['firstname']) ? $customerInfo['firstname'] :"";
            $fieldsToValidate[] = isset($customerInfo['lastnamekana']) ? $customerInfo['lastnamekana'] :"";
            $fieldsToValidate[] = isset($customerInfo['firstnamekana']) ? $customerInfo['firstnamekana'] :"";
            $fieldsToValidate[] = str_replace("-","",isset($addressInfo['postcode']) ? $addressInfo['postcode'] :"");
            $fieldsToValidate[] = isset($addressInfo['telephone']) ? $addressInfo['telephone'] :"";
            foreach ($fieldsToValidate as $k=>$v){
                if($v == ""){
                    $this->messageManager->addError(__("Missing required field %1 to check duplicate",$k));
                    return false;
                }
            }
            $response  =  $this->_rikiCustomerRepository->checkDuplicate($fieldsToValidate);
            if (property_exists($response,'return')) {
                $codeReturn = $response->return;
                if (isset($codeReturn[0]) && \Riki\Customer\Model\SsoConfig::SSO_RESPONSE_SUCCESS_CODE == $codeReturn[0]) {
                    $flag = false;
                } else {
                    $flag = true;
                    $this->messageManager->addError(__("Customer already exist! Message from KSS: %1",$codeReturn[1]));
                }
                return $flag;
            }

        }catch (\Exception $e){
            throw $e;
        }
    }

    /**
     * Rewrite to fix magento hard code disable_auto_group_change
     *
     * @return array
     */
    protected function _extractCustomerData()
    {
        $customerData = [];
        if ($this->getRequest()->getPost('customer')) {
            $serviceAttributes = [
                CustomerInterface::DEFAULT_BILLING,
                CustomerInterface::DEFAULT_SHIPPING,
                'confirmation',
                'sendemail_store_id',
            ];

            $customerData = $this->_extractData(
                'adminhtml_customer',
                \Magento\Customer\Api\CustomerMetadataInterface::ENTITY_TYPE_CUSTOMER,
                $serviceAttributes,
                'customer'
            );
        }

        $attributesBoolean = array(
            "MD0000",
            "PM0000",
            "SPM0000",
            "NM0000",
            "ST0000",
            "OT0000",
            "disable_auto_group_change"
        );
        foreach ($attributesBoolean as $attributeCode){
            if (isset($customerData[$attributeCode])) {
                $customerData[$attributeCode] = (int) filter_var(
                    $customerData[$attributeCode],
                    FILTER_VALIDATE_BOOLEAN
                );
            }
        }
        return $customerData;
    }

    /**
     * Customer access rights checking
     *
     * @return bool
     */
    protected function _isAllowed()
    {
        $originalRequestData = $this->getRequest()->getPostValue();

        if(isset($originalRequestData['customer']['entity_id'])){
            return $this->_authorization->isAllowed('Riki_Customer::edit');
        }

        return $this->_authorization->isAllowed('Riki_Customer::new');
    }

    /**
     * Check delete company address exit on subscription profile
     *
     * @param $addressType
     * @param $originalRequestData
     * @return null|string
     */
    public function checkDeleteAddress($originalRequestData,$addressType)
    {
        if($addressType=='company') {
            $addressData = $this->getCompanyAddress($originalRequestData);
        }else if($addressType=='home') {
            $addressData = $this->getHomeAddress($originalRequestData);
        }

        $customerId  = $originalRequestData['customer']['entity_id'];
        $addressId   = $this->getAddressFromDatabase($customerId,$addressType);
        /**
         * check delete current company address and create new company address
         */
        if(!empty($addressId) &&  !isset($addressData['entity_id'])){

            $result = $this->_productCartModel->validateAddress($addressId);
            if($result == false){
                $address      = $this->addressRepository->getById($addressId);
                $rikiNickName = $address->getCustomAttribute('riki_nickname')->getValue();
                $this->messageManager->addError(__('We can\'t delete the address exist in subscription profile. ').$rikiNickName);
                $this->_getSession()->setCustomerData($originalRequestData);
                $resultRedirect = $this->resultRedirectFactory->create();
                $resultRedirect->setPath(
                    'customer/*/edit',
                    ['id' => $customerId, '_current' => true]
                );
                return $resultRedirect;
            }
        }
        return null;
    }

    /**
     * Get company address
     *
     * @param $customerId
     * @param $addressType
     * @return null|string
     */
    public function getAddressFromDatabase($customerId,$addressType)
    {
        $connection = $this->_customerFactory->create()->getCollection()->getConnection();
        $select = $connection->select()
            ->from(['c'=> 'customer_address_entity'])
            ->joinLeft(
                [
                    'v'=> 'customer_address_entity_varchar'
                ],
                "c.entity_id = v.entity_id"
            )
            ->where("c.parent_id = $customerId AND v.value ='$addressType' ");

        $companyAddress = $connection->fetchOne($select);
        if(!empty($companyAddress) && isset($companyAddress)){
            return $companyAddress;
        }
        return null;
    }
}