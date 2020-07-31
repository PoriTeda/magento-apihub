<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Riki\Customer\Controller\Adminhtml\Index;

use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Framework\Exception\NoSuchEntityException;

class Edit extends \Magento\Customer\Controller\Adminhtml\Index
{
    /**
     * Customer edit action
     *
     * @return \Magento\Backend\Model\View\Result\Page|\Magento\Backend\Model\View\Result\Redirect
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */

    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $_logger;
    /**
     * @var \Riki\Customer\Model\AmbCustomerRepository $_ambCustomerRepository
     */
    protected $_ambCustomerRepository;

    /**
     * @var \Riki\Customer\Model\CustomerRepository $_rikiCustomerRepository
     */
    protected $_rikiCustomerRepository;

    /**
     * @var \Riki\Customer\Helper\Region $_rikiRegionHelper
     */
    protected $_rikiRegionHelper;

    /**
     * Edit constructor.
     * @param \Riki\Customer\Model\CustomerRepository $_rikiCustomerRepository
     * @param \Riki\Customer\Model\AmbCustomerRepository $_ambCustomerRepository
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
     * @param \Riki\Customer\Helper\Membership $membershipHelper
     * @param \Psr\Log\LoggerInterface $logger
     * @param \Riki\Customer\Helper\Region $_rikiRegionHelper
     */
    public function __construct(
        \Riki\Customer\Model\CustomerRepository $_rikiCustomerRepository,
        \Riki\Customer\Model\AmbCustomerRepository $_ambCustomerRepository,
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
        \Riki\Customer\Helper\Membership $membershipHelper,
        \Psr\Log\LoggerInterface $logger,
        \Riki\Customer\Helper\Region $_rikiRegionHelper
    )
    {
        $this->_rikiCustomerRepository = $_rikiCustomerRepository;
        $this->_ambCustomerRepository = $_ambCustomerRepository;
        $this->_customerMembershipHelper = $membershipHelper;
        $this->_logger = $logger;
        $this->_rikiRegionHelper = $_rikiRegionHelper;
        parent::__construct($context, $coreRegistry, $fileFactory, $customerFactory, $addressFactory, $formFactory, $subscriberFactory, $viewHelper, $random, $customerRepository, $extensibleDataObjectConverter, $addressMapper, $customerAccountManagement, $addressRepository, $customerDataFactory, $addressDataFactory, $customerMapper, $dataObjectProcessor, $dataObjectHelper, $objectFactory, $layoutFactory, $resultLayoutFactory, $resultPageFactory, $resultForwardFactory, $resultJsonFactory);
    }

    public function execute()
    {
        $customerId = $this->initCurrentCustomer();
        $cloneCustomerId = $this->getRequest()->getParam('copy_id');
        $isClone = (bool) $cloneCustomerId;
        if($isClone){
            $customerId = $cloneCustomerId;
        }
        $customerData = [];
        $customerData['account'] = [];
        $customerData['address'] = [];
        $customer = null;
        $isExistingCustomer = (bool)$customerId;
        if ($isExistingCustomer) {
            try {
                $customer = $this->_customerRepository->getById($customerId);
                $consumerDbIdAttribute = $customer->getCustomAttribute('consumer_db_id');
                if(isset($consumerDbIdAttribute)){
                    $consumerDbId = $consumerDbIdAttribute->getValue();
                    if($consumerDbId !=""){
                        $this->_customerFactory->create()->getResource()->setNeedHandleDuplicateEmailException(true);
                        $consumerDbResponse = $this->_rikiCustomerRepository->prepareAllInfoCustomer($consumerDbId);
                        $this->_rikiCustomerRepository->createUpdateEcCustomer($consumerDbResponse,$consumerDbId,null,$customer);
                        if ($consumerDbResponse && count($consumerDbResponse) > 0) {
                            if(isset($consumerDbResponse['customer_machine_api'][1])){
                                $this->_session->setCustomerMachineResponse($consumerDbResponse['customer_machine_api'][1]);
                            }
                            if(isset($consumerDbResponse['customer_machine_api'][0])){
                                $consumerDbResponse['customer_machine_api'] = $consumerDbResponse['customer_machine_api'][0];
                            }
                            if(isset($consumerDbResponse['amb_api']['COM_ADDRESS1'])){
                                $regionId = $this->_rikiRegionHelper->getRegionIdByName($consumerDbResponse['amb_api']['COM_ADDRESS1']);
                                $consumerDbResponse['amb_api']['COM_ADDRESS1'] = $regionId;
                            }
                            $this->_coreRegistry->register('consumer_customer_response',$consumerDbResponse);
                        }
                    }
                }
                $customerData['account'] = $this->customerMapper->toFlatArray($customer);
                $customerData['account'][\Magento\Customer\Api\Data\CustomerInterface::ID] = $customerId;
                try {
                    $addresses = $customer->getAddresses();
                    foreach ($addresses as $address) {
                        $customerData['address'][$address->getId()] = $this->addressMapper->toFlatArray($address);
                        $customerData['address'][$address->getId()]['id'] = $address->getId();
                    }
                } catch (\Magento\Framework\Exception\NoSuchEntityException $e) {
                    $this->messageManager->addException($e, __('Something went wrong while editing the customer.'));
                    $resultRedirect = $this->resultRedirectFactory->create();
                    $resultRedirect->setPath('customer/*/index');
                    return $resultRedirect;
                }
            } catch (\Exception $e) {
                $this->_logger->critical($e);
                $this->_addSessionErrorMessages($e->getMessage());
            }
        }
        $customerData['customer_id'] = $customerId;

        // set entered data if was error when we do save
        $data = $this->_getSession()->getCustomerData(true);
        // restore data from SESSION
        if ($data && (!isset(
                $data['customer_id']
                ) || isset(
                $data['customer_id']
                ) && $data['customer_id'] == $customerId)
        ) {
            $request = clone $this->getRequest();
            $request->setParams($data);

            if (isset($data['account']) && is_array($data['account'])) {
                $customerForm = $this->_formFactory->create(
                    'customer',
                    'adminhtml_customer',
                    $customerData['account'],
                    true
                );
                $formData = $customerForm->extractData($request, 'account');
                $customerData['account'] = $customerForm->restoreData($formData);
                $customer = $this->customerDataFactory->create();
                $this->dataObjectHelper->populateWithArray(
                    $customer,
                    $customerData['account'],
                    '\Magento\Customer\Api\Data\CustomerInterface'
                );
            }

            if (isset($data['address']) && is_array($data['address'])) {
                foreach (array_keys($data['address']) as $addressId) {
                    if ($addressId == '_template_') {
                        continue;
                    }

                    try {
                        $address = $this->addressRepository->getById($addressId);
                        if (empty($customerId) || $address->getCustomerId() != $customerId) {
                            //reinitialize address data object
                            $address = $this->addressDataFactory->create();
                        }
                    } catch (\Magento\Framework\Exception\NoSuchEntityException $e) {
                        $address = $this->addressDataFactory->create();
                        $address->setId($addressId);
                    }
                    if (!empty($customerId)) {
                        $address->setCustomerId($customerId);
                    }
                    $address->setIsDefaultBilling(
                        !empty($data['account'][\Magento\Customer\Api\Data\CustomerInterface::DEFAULT_BILLING]) &&
                        $data['account'][\Magento\Customer\Api\Data\CustomerInterface::DEFAULT_BILLING] == $addressId
                    );
                    $address->setIsDefaultShipping(
                        !empty($data['account'][\Magento\Customer\Api\Data\CustomerInterface::DEFAULT_SHIPPING]) &&
                        $data['account'][\Magento\Customer\Api\Data\CustomerInterface::DEFAULT_SHIPPING] == $addressId
                    );
                    $requestScope = sprintf('address/%s', $addressId);
                    $addressForm = $this->_formFactory->create(
                        'customer_address',
                        'adminhtml_customer_address',
                        $this->addressMapper->toFlatArray($address)
                    );
                    $formData = $addressForm->extractData($request, $requestScope);
                    $customerData['address'][$addressId] = $addressForm->restoreData($formData);
                    $customerData['address'][$addressId][\Magento\Customer\Api\Data\AddressInterface::ID] = $addressId;
                }
            }
        }

        $this->_getSession()->setCustomerData($customerData);
        if($isClone){
            $this->_coreRegistry->register('clone_customer_data',$customerData);
        }

        $resultPage = $this->resultPageFactory->create();
        $resultPage->setActiveMenu('Magento_Customer::customer_manage');
        $this->prepareDefaultCustomerTitle($resultPage);
        $resultPage->setActiveMenu('Magento_Customer::customer');
        if ($isExistingCustomer && !$isClone) {
            $resultPage->getConfig()->getTitle()->prepend($this->_viewHelper->getCustomerName($customer));
        } elseif($isClone){
            $resultPage->getConfig()->getTitle()->prepend(__('Copy Customer: ').$this->_viewHelper->getCustomerName($customer));
        } else {
            $resultPage->getConfig()->getTitle()->prepend(__('New Customer'));
        }
        return $resultPage;
    }

    /**
     * Customer access rights checking
     *
     * @return bool
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Riki_Customer::view');
    }

}
