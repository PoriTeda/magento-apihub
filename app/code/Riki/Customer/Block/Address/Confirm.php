<?php

namespace Riki\Customer\Block\Address;

/**
 * Customer address confirm block.
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class Confirm extends \Riki\Customer\Block\Address\Edit
{
    /**
     * @var \Magento\Directory\Model\RegionFactory
     */
    protected $_regionFactory;

    /**
     * Confirm constructor.
     *
     * @param \Magento\Framework\View\Element\Template\Context                 $context
     * @param \Magento\Directory\Helper\Data                                   $directoryHelper
     * @param \Magento\Framework\Json\EncoderInterface                         $jsonEncoder
     * @param \Magento\Framework\App\Cache\Type\Config                         $configCacheType
     * @param \Magento\Directory\Model\ResourceModel\Region\CollectionFactory  $regionCollectionFactory
     * @param \Magento\Directory\Model\ResourceModel\Country\CollectionFactory $countryCollectionFactory
     * @param \Magento\Customer\Model\Session                                  $customerSession
     * @param \Magento\Customer\Api\AddressRepositoryInterface                 $addressRepository
     * @param \Magento\Customer\Api\Data\AddressInterfaceFactory               $addressDataFactory
     * @param \Magento\Customer\Helper\Session\CurrentCustomer                 $currentCustomer
     * @param \Magento\Framework\Api\DataObjectHelper                          $dataObjectHelper
     * @param \Magento\Directory\Model\RegionFactory                           $regionFactory
     * @param \Riki\Customer\Helper\Data                                       $dataHelper
     * @param array                                                            $data
     */
    public function __construct(
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
        \Magento\Directory\Model\RegionFactory $regionFactory,
        \Riki\Customer\Helper\Data $dataHelper,
        array $data = []
    ) {
        $this->_regionFactory = $regionFactory;

        parent::__construct($context, $directoryHelper, $jsonEncoder, $configCacheType, $regionCollectionFactory, $countryCollectionFactory, $customerSession, $addressRepository, $addressDataFactory, $currentCustomer, $dataObjectHelper, $dataHelper, $data);
    }

    public function getRegionName($code)
    {
        return $this->_regionFactory->create()->load($code)->getName();
    }

    /**
     * {@inheritdoc}
     */
    protected function _prepareLayout()
    {
        $postedData = $this->_customerSession->getAddressFormData();

        parent::_prepareLayout();

        $this->_customerSession->setAddressFormData($postedData);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getTitle()
    {
        if ($title = $this->getData('title')) {
            return $title;
        }
        if ($this->getAddress()->getId()) {
            $title = __('Edit Address Confirmation');
        } else {
            $title = __('New Address Confirmation');
        }

        return $title;
    }

    /**
     * {@inheritdoc}
     */
    public function getBackUrl()
    {
        if ($this->getData('back_url')) {
            return $this->getData('back_url');
        }

        if ($this->getAddress()->getId()) {
            return $this->getUrl('customer/address/edit/id/'.$this->getAddress()->getId());
        } else {
            return $this->getUrl('customer/address/new');
        }
    }

    /**
     * Is the address the default billing address?
     *
     * @return bool
     */
    public function isDefaultBilling()
    {
        $data = $this->_customerSession->getAddressFormData();

        return !isset($data['default_billing']) ? 0 : $data['default_billing'];
    }

    /**
     * Is the address the default shipping address?
     *
     * @return bool
     */
    public function isDefaultShipping()
    {
        $data = $this->_customerSession->getAddressFormData();

        return !isset($data['default_shipping']) ? 0 : $data['default_shipping'];
    }

    public function getKatakanaLastName()
    {
        $data = $this->_customerSession->getAddressFormData();

        return $data['lastnamekana'];
    }

    public function getKatakanaFirstName()
    {
        $data = $this->_customerSession->getAddressFormData();

        return $data['firstnamekana'];
    }

    public function getNickNameAddress()
    {
        $data = $this->_customerSession->getAddressFormData();

        return $data['riki_nickname'];
    }

    public function getRikiTypeAddress()
    {
        $data = $this->_customerSession->getAddressFormData();

        return $data['riki_type_address'];
    }
    
}
