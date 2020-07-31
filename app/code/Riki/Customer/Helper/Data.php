<?php
namespace Riki\Customer\Helper;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Store\Model\ScopeInterface;
use Riki\Customer\Model\Address\AddressType;
use Riki\SubscriptionMembership\Model\Customer\Attribute\Source\Membership as CustomerMembership;
use Riki\Customer\Helper\Membership as MembershipHelper;
/**
 * Custom Module Riki helper
 */
class Data extends \Magento\Framework\App\Helper\AbstractHelper
{

    const XML_PATH_AMBASSADOR = 'riki_customer_group/customergroup/grouplist';


    const XML_PATH_CEDYNA_CUSTOMER_HOLD_HOST = 'cedyna_customer_hold/sftp/host';

    const XML_PATH_CEDYNA_CUSTOMER_HOLD_PORT = 'cedyna_customer_hold/sftp/port';

    const XML_PATH_CEDYNA_CUSTOMER_HOLD_USERNAME = 'cedyna_customer_hold/sftp/username';

    const XML_PATH_CEDYNA_CUSTOMER_HOLD_PASSWORD = 'cedyna_customer_hold/sftp/password';


    const XML_PATH_CEDYNA_CUSTOMER_HOLD_FILEPATH = 'cedyna_customer_hold/file_setting/file_path';

    const XML_PATH_CEDYNA_CUSTOMER_HOLD_FILENAME = 'cedyna_customer_hold/file_setting/file_import';

    const XML_PATH_CEDYNA_CUSTOMER_HOLD_EMAIL_ALERT = 'cedyna_customer_hold/email_setting/email_alert';

    const XML_PATH_CEDYNA_CUSTOMER_HOLD_EMAIL_TEMPLATE = 'cedyna_customer_hold/email_setting/email_template';

    const XML_PATH_CEDYNA_CUSTOMER_HOLD_EMAIL_TEMPLATE_ERROR = 'cedyna_customer_hold/email_setting/email_template_error';


    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $_scopeConfig;

    /**
     * Store manager
     *
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var \Magento\Customer\Model\CustomerFactory
     */
    protected $_customerFactory;

    protected $_logger;

    /**
     * @var \Magento\Framework\Api\SearchCriteriaBuilder
     */
    protected $_searchBuilder;

    /**
     * @var \Magento\Customer\Model\ResourceModel\CustomerRepository
     */
    protected $_customerRepository;
    /**
     * @var EncryptorInterface
     */
    protected $_encryptor;

    protected $shipmentExporterHelper;

    /**
     * Data constructor.
     * @param \Magento\Framework\App\Helper\Context $context
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Customer\Model\CustomerFactory $customerFactory
     * @param \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder
     * @param \Magento\Customer\Model\ResourceModel\CustomerRepository $customerRepository
     * @param EncryptorInterface $encryptor
     * @param \Riki\ShipmentExporter\Helper\Data $data
     */
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Customer\Model\CustomerFactory $customerFactory,
        \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder,
        \Magento\Customer\Model\ResourceModel\CustomerRepository $customerRepository,
        EncryptorInterface $encryptor,
        \Riki\ShipmentExporter\Helper\Data $data
    ) {
        $this->storeManager = $storeManager;
        $this->_customerFactory = $customerFactory;
        $this->_logger = $context->getLogger();
        $this->_searchBuilder = $searchCriteriaBuilder;
        $this->_customerRepository = $customerRepository;
        $this->_encryptor = $encryptor;
        $this->shipmentExporterHelper = $data;
        $this->customerRepository = $customerRepository;
        parent::__construct($context);
    }

    /**
     * Return store configuration value of your template field that which id you set for template
     *
     * @param string $path
     * @param int $storeId
     * @return mixed
     */
    protected function getConfigValue($path, $storeId)
    {
        return $this->scopeConfig->getValue(
            $path,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Get current store
     *
     * @return \Magento\Store\Api\Data\StoreInterface
     */
    public function getStore()
    {
        return $this->storeManager->getStore();
    }


    /**
     *  Get recipients which send warning email
     * @return mixed
     */
    public function getAmbassador()
    {
        return $this->getConfigValue(
            self::XML_PATH_AMBASSADOR,
            $this->getStore()->getStoreId()
        );
    }

    /**
     * Check the customer is amb sale user
     * @param  int|obj  $customerId integer or \Magento\Customer\Model\Customer
     * @return boolean             true | false
     */
    public function isAmbSalesCustomer($customerId) {

        $objCustomer = $customerId;
        if(!($customerId instanceof \Magento\Customer\Model\Customer)) {
            $objCustomer = $this->_customerFactory->create()->load($customerId);

            if(empty($objCustomer)) {
                $this->_logger->error(sprintf("Cannot load customer %s", $customerId));
                return false;
            }
        }


        $isAmbSales = $objCustomer->getData("amb_sale") == 1;

        $listMembership = $objCustomer->getData("membership");
        $arrMembershipId = [];
        if($listMembership) {
            $arrMembershipId = explode(",", $listMembership);
        }
        $isAmbMembership = in_array(CustomerMembership::AMB_MEMBERSHIP, $arrMembershipId);

        return $isAmbMembership || $isAmbSales;

    }

    /**
     * GetCedynaCustomerHoldSftpHost
     *
     * @return mixed
     */
    public function getCedynaCustomerHoldSftpHost(){
        return $this->getConfigValue(
            self::XML_PATH_CEDYNA_CUSTOMER_HOLD_HOST,
            $this->getStore()->getStoreId()
        );
    }

    /**
     * GetCedynaCustomerHoldSftpPort
     *
     * @return mixed
     */
    public function getCedynaCustomerHoldSftpPort(){
        return $this->getConfigValue(
            self::XML_PATH_CEDYNA_CUSTOMER_HOLD_PORT,
            $this->getStore()->getStoreId()
        );
    }

    /**
     * GetCedynaCustomerHoldSftpUser
     *
     * @return mixed
     */
    public function getCedynaCustomerHoldSftpUser(){
        return $this->getConfigValue(
            self::XML_PATH_CEDYNA_CUSTOMER_HOLD_USERNAME,
            $this->getStore()->getStoreId()
        );
    }

    /**
     * GetCedynaCustomerHoldSftpPass
     *
     * @return mixed
     */
    public function getCedynaCustomerHoldSftpPass(){
        $password = $this->getConfigValue(
            self::XML_PATH_CEDYNA_CUSTOMER_HOLD_PASSWORD,
            $this->getStore()->getStoreId()
        );
        return $this->_encryptor->decrypt($password);
    }

    /**
     * GetCedynaCustomerHoldSFTPFileName
     *
     * @return mixed
     */
    public function getCedynaCustomerHoldSFTPFileName(){
        return $this->getConfigValue(
            self::XML_PATH_CEDYNA_CUSTOMER_HOLD_FILENAME,
            $this->getStore()->getStoreId()
        );
    }

    /**
     * GetCedynaCustomerHoldSFTPFileName
     *
     * @return mixed
     */
    public function getCedynaCustomerHoldSFTPFilePath(){
        return $this->getConfigValue(
            self::XML_PATH_CEDYNA_CUSTOMER_HOLD_FILEPATH,
            $this->getStore()->getStoreId()
        );
    }

    /**
     * GetCedynaCustomerHoldEmailAlert
     *
     * @return mixed
     */
    public function getCedynaCustomerHoldEmailAlert(){
        return $this->getConfigValue(
            self::XML_PATH_CEDYNA_CUSTOMER_HOLD_EMAIL_ALERT,
            $this->getStore()->getStoreId()
        );
    }

    /**
     * GetCedynaCustomerHoldEmailTemplate
     *
     * @return mixed
     */
    public function getCedynaCustomerHoldEmailTemplate(){
        return $this->getConfigValue(
            self::XML_PATH_CEDYNA_CUSTOMER_HOLD_EMAIL_TEMPLATE,
            $this->getStore()->getStoreId()
        );
    }

    /**
     * GetCedynaCustomerHoldEmailTemplate
     *
     * @return mixed
     */
    public function getCedynaCustomerHoldEmailTemplateError(){
        return $this->getConfigValue(
            self::XML_PATH_CEDYNA_CUSTOMER_HOLD_EMAIL_TEMPLATE_ERROR,
            $this->getStore()->getStoreId()
        );
    }

    /**
     * GetSenderEmail
     *
     * @return mixed
     */
    public function getSenderEmail()
    {
        $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE;
        return $this->scopeConfig->getValue('trans_email/ident_support/email',$storeScope);
    }

    /**
     * GetSenderName
     *
     * @return mixed
     */
    public function getSenderName()
    {
        $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE;
        return $this->scopeConfig->getValue('trans_email/ident_support/name',$storeScope);
    }

    /**
     * Get list user and index by id in array
     *
     * @param array $customerIds
     * @return array
     */
    public function getCustomerByIds($customerIds)
    {
        $filter = $this->_searchBuilder->addFilter('entity_id', $customerIds, 'in');
        $customers = $this->_customerRepository->getList($filter->create());
        $result = [];
        if ($customers->getTotalCount()) {
            foreach ($customers->getItems() as $customer) {
                $result[$customer->getId()] = $customer;
            }
        }
        return $result;
    }

    /**
     * @param $data
     * @return int
     */
    public function getB2bFlagValue($data)
    {
        $wordsList = $this->shipmentExporterHelper->getPrefectureList();
        if(!$wordsList){
            return 0;
        }else
        {
            foreach($wordsList as $searchword)
            {
                $searchword = trim($searchword);
                if( array_filter($data, function($var) use ($searchword) { return preg_match("/$searchword/i", $var); }))
                {
                    return 1;
                }
            }
            return 0;
        }

    }

    /**
     * Get KSS edit account info url
     * Logic from Riki/Customer/Block/Account/Info
     *
     * @param $customer
     * @param $type
     *
     * @return mixed|null
     */
    public function getKssEditAccountUrl(\Magento\Customer\Model\Customer $customer, $type = 'home')
    {
        if (in_array(
            $this->storeManager->getStore()->getCode(),
            [
                MembershipHelper::MEMBERSHIP_CIS_CODE,
                MembershipHelper::MEMBERSHIP_CNC_CODE
            ]
        )) {
            return null;
        }

        $customerMembershipIds = explode(',', $customer->getMembership());

        if (empty(array_intersect(
            [CustomerMembership::CIS_MEMBERSHIP, CustomerMembership::CNC_MEMBERSHIP],
            $customerMembershipIds)
        ) || in_array(CustomerMembership::AMB_MEMBERSHIP, $customerMembershipIds)
        ) {
            if ($type == AddressType::OFFICE) {
                return $this->getConfigLinkEditCustomerUrl('kss_company_edit');
            } else {
                $customerAddress = $customer->getAddresses();

                /** @var \Magento\Customer\Model\Address $address */
                foreach ($customerAddress as $address){

                    $rikiAddressTypeAttr = $address->getCustomAttribute('riki_type_address');

                    if ($rikiAddressTypeAttr
                        && $rikiAddressTypeAttr->getValue() == AddressType::HOME
                        && $address->getCompany()
                    ) {
                        return $this->getConfigLinkEditCustomerUrl('kss_office_customer_edit');
                    }
                }

                return $this->getConfigLinkEditCustomerUrl('kss_customer_edit');
            }
        }

        return null;
    }

    /**
     * @param $urlPath
     * @return mixed
     */
    protected function getConfigLinkEditCustomerUrl($urlPath)
    {
        return $this->scopeConfig->getValue(
            'customerksslink/kss_link_edit_customer/' . $urlPath,
            ScopeInterface::SCOPE_WEBSITE
        );
    }
}
