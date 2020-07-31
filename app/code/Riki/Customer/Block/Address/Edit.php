<?php

namespace Riki\Customer\Block\Address;

/**
 * Customer address edit block.
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class Edit extends \Magento\Customer\Block\Address\Edit
{
    /**
     * @var \Riki\Customer\Helper\Data
     */
    protected $_dataHelper;

    /**
     * Constructor.
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
     * @param \Riki\Customer\Helper\Data                                       $dataHelper
     * @param array                                                            $data
     *
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
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
        \Riki\Customer\Helper\Data $dataHelper,
        array $data = []
    ) {
        $this->_dataHelper = $dataHelper;

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
        $this->setData('riki_do_not_use_after', true);
    }

    /**
     * @return string
     */
    public function getConfirmUrl(){
        return $this->_urlBuilder->getUrl(
            'customer/address/confirmPost',
            ['_secure' => true, 'id' => $this->getAddress()->getId()]
        );
    }

    /**
     * @return bool
     */
    public function checkAmbassador()
    {
        $configGroupId = $this->_dataHelper->getAmbassador();
        $currentGroupId = $this->_customerSession->getData('customer_group_id');
        if ($configGroupId == $currentGroupId) {
            return 1;
        } else {
            return 0;
        }
    }
}
