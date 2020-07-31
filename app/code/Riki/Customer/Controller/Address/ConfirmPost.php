<?php

namespace Riki\Customer\Controller\Address;

class ConfirmPost extends \Magento\Customer\Controller\Address
{
    /**
     * @var \Riki\Customer\Helper\ValidateAddress
     */
    protected $validateAddress;

    /**
     * ConfirmPost constructor.
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Magento\Customer\Model\Session $customerSession
     * @param \Magento\Framework\Data\Form\FormKey\Validator $formKeyValidator
     * @param \Magento\Customer\Model\Metadata\FormFactory $formFactory
     * @param \Magento\Customer\Api\AddressRepositoryInterface $addressRepository
     * @param \Magento\Customer\Api\Data\AddressInterfaceFactory $addressDataFactory
     * @param \Magento\Customer\Api\Data\RegionInterfaceFactory $regionDataFactory
     * @param \Magento\Framework\Reflection\DataObjectProcessor $dataProcessor
     * @param \Magento\Framework\Api\DataObjectHelper $dataObjectHelper
     * @param \Magento\Framework\Controller\Result\ForwardFactory $resultForwardFactory
     * @param \Riki\Customer\Helper\ValidateAddress $validateAddress
     * @param \Magento\Framework\View\Result\PageFactory $resultPageFactory
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Framework\Data\Form\FormKey\Validator $formKeyValidator,
        \Magento\Customer\Model\Metadata\FormFactory $formFactory,
        \Magento\Customer\Api\AddressRepositoryInterface $addressRepository,
        \Magento\Customer\Api\Data\AddressInterfaceFactory $addressDataFactory,
        \Magento\Customer\Api\Data\RegionInterfaceFactory $regionDataFactory,
        \Magento\Framework\Reflection\DataObjectProcessor $dataProcessor,
        \Magento\Framework\Api\DataObjectHelper $dataObjectHelper,
        \Magento\Framework\Controller\Result\ForwardFactory $resultForwardFactory,
        \Riki\Customer\Helper\ValidateAddress $validateAddress,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory
    ) {
        $this->validateAddress = $validateAddress;
        parent::__construct(
            $context,
            $customerSession,
            $formKeyValidator,
            $formFactory,
            $addressRepository,
            $addressDataFactory,
            $regionDataFactory,
            $dataProcessor,
            $dataObjectHelper,
            $resultForwardFactory,
            $resultPageFactory
        );
    }

    /**
     * Process address form save
     */
    public function execute()
    {
        $redirectUrl = null;
        if (!$this->_formKeyValidator->validate($this->getRequest())) {
            return $this->resultRedirectFactory->create()->setPath('*/*/edit');
        }

        if (!$this->getRequest()->isPost()) {
            $this->_customerSession->setAddressFormData($this->getRequest()->getPostValue());

            return $this->resultRedirectFactory->create()->setUrl(
                $this->_redirect->error($this->_buildUrl('*/*/edit'))
            );
        }

        /***
         * Validate address data
         */
        $address = $this->extractAddress();
        $inputException = $this->validateAddress->validate($address);
        if ($inputException->wasErrorAdded()) {
            if (!empty($inputException->getErrors())) {
                foreach ($inputException->getErrors() as $error) {
                    $this->messageManager->addError($error->getMessage());
                }
            } else {
                $this->messageManager->addError($inputException->getMessage());
            }

            if ($addressId = $this->getRequest()->getParam('id')) {
                return $this->resultRedirectFactory->create()->setUrl(
                    $this->_redirect->error($this->_buildUrl('*/*/edit', ['id' => $addressId]))
                );
            } else {
                return $this->resultRedirectFactory->create()->setUrl(
                    $this->_redirect->error($this->_buildUrl('*/*/new'))
                );
            }
        } else {
            $this->_customerSession->setAddressFormData($this->getRequest()->getPostValue());
        }

        $resultPage = $this->resultPageFactory->create();
        $navigationBlock = $resultPage->getLayout()->getBlock('customer_account_navigation');
        if ($navigationBlock) {
            $navigationBlock->setActive('customer/address');
        }

        return $resultPage;
    }

    /**
     * Extract address from request
     *
     * @return \Magento\Customer\Api\Data\AddressInterface
     */
    private function extractAddress()
    {
        $addressForm = $this->getRequest()->getPostValue();

        /**
         * @var \Magento\Customer\Api\Data\AddressInterface $addressDataObject
         */
        $addressDataObject = $this->addressDataFactory->create();

        if (isset($addressForm['country_id'])) {
            $addressDataObject->setCountryId($addressForm['country_id']);
        }

        if (isset($addressForm['postcode'])) {
            $addressDataObject->setPostCode($addressForm['postcode']);
        }

        if (isset($addressForm['region_id'])) {
            $addressDataObject->setRegionId($addressForm['region_id']);
        }

        return $addressDataObject;
    }
}
