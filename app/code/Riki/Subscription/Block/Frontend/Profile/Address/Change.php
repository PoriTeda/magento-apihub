<?php

namespace Riki\Subscription\Block\Frontend\Profile\Address;

use Magento\Framework\Exception\NoSuchEntityException;
use Riki\Subscription\Model\Profile\WebApi\ProfileRepository;

class Change extends \Magento\Customer\Block\Address\Edit
{

    protected $_helperProfileData;

    /* @var \Magento\Framework\Registry */
    protected $_registry;

    /* @var \Magento\Framework\Data\Form\FormKey */
    protected $_formKey;

    public function __construct(
        \Magento\Framework\Data\Form\FormKey $formKey,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Directory\Helper\Data $directoryHelper,
        \Magento\Framework\Json\EncoderInterface $jsonEncoder,
        \Magento\Framework\App\Cache\Type\Config $configCacheType,
        \Magento\Directory\Model\ResourceModel\Region\CollectionFactory $regionCollectionFactory,
        \Magento\Directory\Model\ResourceModel\Country\CollectionFactory $countryCollectionFactory,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Customer\Api\AddressRepositoryInterface $addressRepository,
        \Magento\Customer\Api\Data\AddressInterfaceFactory $addressDataFactory,
        \Magento\Customer\Helper\Session\CurrentCustomer $currentCustomer,
        \Magento\Framework\Api\DataObjectHelper $dataObjectHelper,
        \Riki\Subscription\Helper\Profile\Data $helperProfileData,
        array $data = []
    ) {
        $this->_formKey = $formKey;
        $this->_helperProfileData = $helperProfileData;
        $this->_registry = $registry;
        parent::__construct(
            $context,
            $directoryHelper,
            $jsonEncoder,
            $configCacheType,
            $regionCollectionFactory,
            $countryCollectionFactory,
            $customerSession,
            $addressRepository,
            $addressDataFactory,
            $currentCustomer,
            $dataObjectHelper,
            $data
        );

    }

    protected function _prepareLayout()
    {
        parent::_prepareLayout();

        // Init address object
        if ($this->getRequest()->getParam('id')) {
            $addressId = $this->getAddressId($this->getRequest()->getParam('id'));
        } else {
            $addressId = $this->_registry->registry(ProfileRepository::EDIT_REGISTRATION_SHIPPING_ADDRESS_ID);
        }
        if ($addressId) {
            try {
                $this->_address = $this->_addressRepository->getById($addressId);
                if ($this->_address->getCustomerId() != $this->_customerSession->getCustomerId()) {
                    $this->_address = null;
                }
            } catch (NoSuchEntityException $e) {
                $this->_address = null;
            }
        }

        if ($this->_address === null || !$this->_address->getId()) {
            $this->_address = $this->addressDataFactory->create();
            $customer = $this->getCustomer();
            $this->_address->setPrefix($customer->getPrefix());
            $this->_address->setFirstname($customer->getFirstname());
            $this->_address->setMiddlename($customer->getMiddlename());
            $this->_address->setLastname($customer->getLastname());
            $this->_address->setSuffix($customer->getSuffix());
        }

        $this->pageConfig->getTitle()->set($this->getTitle());

        if ($postedData = $this->_customerSession->getAddressFormData(true)) {
            if (!empty($postedData['region_id']) || !empty($postedData['region'])) {
                $postedData['region'] = [
                    'region_id' => $postedData['region_id'],
                    'region' => $postedData['region'],
                ];
            }
            $this->dataObjectHelper->populateWithArray(
                $this->_address,
                $postedData,
                '\Magento\Customer\Api\Data\AddressInterface'
            );
        }

        return $this;
    }

    public function getAddressId($profileId)
    {
        $arrProductCart = $this->_helperProfileData->getArrProductCart((int)$profileId);
        if (count($arrProductCart) > 0) {
            foreach ($arrProductCart as $productId => $value) {
                return $value['profile']['shipping_address_id'];
            }
        }
        return '';
    }

    public function getSaveUrl()
    {
        $this->setData("riki_do_not_use_after", true);
        return $this->_urlBuilder->getUrl(
            'subscriptions/profile/saveAddress',
            ['_secure' => true, 'id' => $this->getAddress()->getId()]
        );
    }
    public function getConfirmUrl(){
        $this->setData("riki_do_not_use_after", true);
        return $this->_urlBuilder->getUrl(
            'subscriptions/profile/saveAddress',
            ['_secure' => true, 'id' => $this->getAddress()->getId()]
        );
    }

    /**
     * Get Form Key
     *
     * @return string
     */
    public function showFormKey()
    {
        return $this->_formKey->getFormKey();
    }
}