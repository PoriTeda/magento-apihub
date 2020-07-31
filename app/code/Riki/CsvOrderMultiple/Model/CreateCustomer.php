<?php

namespace Riki\CsvOrderMultiple\Model;

class CreateCustomer extends \Riki\Customer\Controller\Adminhtml\Index\Save
{

    protected $errorMessage;
    /**
     * @var \Riki\MachineApi\Model\ApiCustomerRepository
     */
    protected $apiCustomerRepository;

    /**
     * CreateCustomer constructor.
     * @param \Riki\Customer\Model\AmbCustomerRepository $ambCustomerRepository
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
     * @param \Riki\Subscription\Model\ProductCart\ProductCart $productCartModel
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
        \Riki\Subscription\Model\ProductCart\ProductCart $productCartModel,
        \Riki\MachineApi\Model\ApiCustomerRepository $apiCustomerRepository
    )
    {
        parent::__construct(
            $ambCustomerRepository,
            $rikiCustomerRepository,
            $logger,
            $soapClient,
            $dateTime,
            $regionHelper,
            $modelShoshaFactory,
            $scopeConfig,
            $directoryHelper,
            $context,
            $coreRegistry,
            $fileFactory,
            $customerFactory,
            $addressFactory,
            $formFactory,
            $subscriberFactory,
            $viewHelper,
            $random,
            $customerRepository,
            $extensibleDataObjectConverter,
            $addressMapper,
            $customerAccountManagement,
            $addressRepository,
            $customerDataFactory,
            $addressDataFactory,
            $customerMapper,
            $dataObjectProcessor,
            $dataObjectHelper,
            $objectFactory,
            $layoutFactory,
            $resultLayoutFactory,
            $resultPageFactory,
            $resultForwardFactory,
            $resultJsonFactory,
            $productCartModel
        );

        $this->apiCustomerRepository = $apiCustomerRepository;
    }


    /**
     * @param $originalRequestData
     * @return \Magento\Framework\Phrase|string
     */
    public function createNewCustomer($originalRequestData)
    {
        $editFlag = 1;
        $customerCode = null;
        $customerId = null;

        if ($originalRequestData) {
            /**
             * get customer address
             */
            $customerAddress = isset($originalRequestData['address']) ? $originalRequestData['address'] : null;

            /**
             * get customer info
             */
            $customers = isset($originalRequestData['customer']) ? $originalRequestData['customer'] : null;
            list($customerData, $customerAddressData) = $this->prepareAllInfoCustomer($customers, $customerAddress);

            /**
             * Call api
             */
            $response = $this->_rikiCustomerRepository->setCustomerAPI($customerData, $customerAddressData, $editFlag, $customerCode);

            /**
             * Response data
             */
            if (property_exists($response, 'return')) {
                $codeReturn = $response->return;
                if (isset($codeReturn[0]) && \Riki\Customer\Model\SsoConfig::SSO_RESPONSE_SUCCESS_CODE == $codeReturn[0]) {
                    $consumerDbId = $codeReturn[3];

                    /**
                     * Set sub  customer
                     */
                    $aSubCustomerData = $this->prepareSubCustomerData($customers,$editFlag);
                    if(isset($aSubCustomerData)){
                        $this->_rikiCustomerRepository->setCustomerSubAPI($codeReturn[3], $aSubCustomerData);
                    }

                    /**
                     * Create customer with api
                     */
                    try {
                         $customer =  $this->apiCustomerRepository->createNewCustomerWithConsumerDb($consumerDbId);
                         if($customer && !is_string($customer))
                         {
                             return $customer;
                         } else{
                             return  __("The has error when create customer on system with consumer id : $consumerDbId");
                         }
                    } catch (\Exception $e) {
                        return __("Get info customer error with consumerDdId : $consumerDbId");
                    }
                } else {
                    return __("Create customer error") . (isset($codeReturn[1]) ? $codeReturn[1] : "");
                }
            } else {
                return __("Call API error with message");
            }
        }
    }


}