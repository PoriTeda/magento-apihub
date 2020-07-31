<?php

namespace Riki\Checkout\Controller\Address;

use Magento\Customer\Api\AddressRepositoryInterface;
use Magento\Customer\Api\Data\AddressInterfaceFactory;
use Magento\Customer\Api\Data\RegionInterfaceFactory;
use Magento\Customer\Model\Metadata\FormFactory;
use Magento\Customer\Model\Session;
use Magento\Directory\Helper\Data as HelperData;
use Magento\Directory\Model\RegionFactory;
use Magento\Framework\Api\DataObjectHelper;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\ForwardFactory;
use Magento\Framework\Data\Form\FormKey\Validator as FormKeyValidator;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Reflection\DataObjectProcessor;
use Magento\Framework\View\Result\PageFactory;

class Save extends \Magento\Customer\Controller\Address\FormPost
{
    /**
     * @var \Magento\Framework\Controller\Result\JsonFactory
     */
    protected $_resultJson;

    protected $_checkoutConfigProvider;

    public function __construct(
        Context $context,
        Session $customerSession,
        FormKeyValidator $formKeyValidator,
        FormFactory $formFactory,
        AddressRepositoryInterface $addressRepository,
        AddressInterfaceFactory $addressDataFactory,
        RegionInterfaceFactory $regionDataFactory,
        DataObjectProcessor $dataProcessor,
        DataObjectHelper $dataObjectHelper,
        ForwardFactory $resultForwardFactory,
        PageFactory $resultPageFactory,
        RegionFactory $regionFactory,
        HelperData $helperData,
        \Riki\Checkout\Model\DefaultConfigProvider $defaultConfigProvider,
        \Magento\Framework\Controller\Result\JsonFactory $jsonFactory
    ){
        $this->_resultJson = $jsonFactory;
        $this->_checkoutConfigProvider = $defaultConfigProvider;

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
            $resultPageFactory,
            $regionFactory,
            $helperData
        );
    }

    /**
     * Extract address from request
     *
     * @return \Magento\Customer\Api\Data\AddressInterface
     */
    protected function _extractAddress()
    {
        $existingAddressData = $this->getExistingAddressData();

        /** @var \Magento\Customer\Model\Metadata\Form $addressForm */
        $addressForm = $this->_formFactory->create('customer_address', 'customer_address_edit', $existingAddressData);
        $addressData = $addressForm->extractData($this->getRequest());
        $addressData['city'] = "None";
        $customAttributesData = $this->getRequest()->getParam('custom_attributes', []);

        if(is_array($customAttributesData)){
            foreach ($customAttributesData as $attribute =>  $value){
                $addressData[$attribute] = $value;
            }
        }

        $attributeValues = $addressForm->compactData($addressData);

        $this->updateRegionData($attributeValues);

        $addressDataObject = $this->addressDataFactory->create();
        $this->dataObjectHelper->populateWithArray(
            $addressDataObject,
            array_merge($existingAddressData, $attributeValues),
            '\Magento\Customer\Api\Data\AddressInterface'
        );
        $addressDataObject->setCustomerId($this->_getSession()->getCustomerId())
            ->setIsDefaultBilling($this->getRequest()->getParam('default_billing', false))
            ->setIsDefaultShipping($this->getRequest()->getParam('default_shipping', false));

        return $addressDataObject;
    }


    /**
     * Retrieve existing address data
     *
     * @return array
     * @throws \Exception
     */
    protected function getExistingAddressData()
    {
        $existingAddressData = [];
        if ($addressId = $this->getRequest()->getParam('customer_address_id')) {
            $existingAddress = $this->_addressRepository->getById($addressId);
            if ($existingAddress->getCustomerId() != $this->_getSession()->getCustomerId()) {
                throw new LocalizedException(__('Data is invalid.'));
            }
            $existingAddressData = $this->_dataProcessor->buildOutputDataArray(
                $existingAddress,
                '\Magento\Customer\Api\Data\AddressInterface'
            );
            $existingAddressData['region_code'] = $existingAddress->getRegion()->getRegionCode();
            $existingAddressData['region'] = $existingAddress->getRegion()->getRegion();
        }
        return $existingAddressData;
    }

    public function execute()
    {
        $result = [
            'error' =>  0,
            'message'   =>  ''
        ];

        try {
            $address = $this->_extractAddress();
            $dataSave = $this->_addressRepository->save($address);;
            $result['customerData'] = $this->_checkoutConfigProvider->getConfigCustomerData($address->getCustomerId());
            $result['addressId'] = $dataSave->getId();
            $result['message'] = __('You saved the address.');

        } catch (InputException $e) {
            $result['error'] = 1;

            $messages = [];
            $messages[] = $e->getMessage();

            foreach ($e->getErrors() as $error) {
                $messages[] = $error->getMessage();
            }

            $result['message'] = implode('; ', $messages);

        } catch (\Exception $e) {
            $result['error'] = 1;
            $result['message'] = __('We can\'t save the address.');
        }

        return $this->_resultJson->create()->setData($result);
    }
}
