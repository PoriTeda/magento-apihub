<?php

namespace Riki\Customer\Block\Address;

use Magento\Customer\Api\AddressRepositoryInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Model\Address\Mapper;

class Book extends \Magento\Customer\Block\Address\Book
{
    protected $_addresses;

    protected $_addressCollectionFactory;

    /**
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param CustomerRepositoryInterface $customerRepository
     * @param AddressRepositoryInterface $addressRepository
     * @param \Magento\Customer\Helper\Session\CurrentCustomer $currentCustomer
     * @param \Magento\Customer\Model\Address\Config $addressConfig
     * @param Mapper $addressMapper
     * @param \Magento\Customer\Model\ResourceModel\Address\CollectionFactory $addressCollectionFactory
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        CustomerRepositoryInterface $customerRepository,
        AddressRepositoryInterface $addressRepository,
        \Magento\Customer\Helper\Session\CurrentCustomer $currentCustomer,
        \Magento\Customer\Model\Address\Config $addressConfig,
        Mapper $addressMapper,
        \Magento\Customer\Model\ResourceModel\Address\CollectionFactory $addressCollectionFactory,
        array $data = []
    ){
        $this->_addressCollectionFactory = $addressCollectionFactory;

        parent::__construct(
            $context,
            $customerRepository,
            $addressRepository,
            $currentCustomer,
            $addressConfig,
            $addressMapper,
            $data
        );
    }

    /**
     * @return $this
     */
    protected function _prepareLayout()
    {
        parent::_prepareLayout();
        if ($this->getAddresses()) {
            $pager = $this->getLayout()->createBlock(
                'Magento\Theme\Block\Html\Pager',
                'customer.address.pager'
            )
                ->setTemplate('Riki_Customer::address/html/pager.phtml')
                ->setAvailableLimit(array(4 => 4, 10 => 10, 20 => 20))
                ->setCollection($this->getAddresses());
            $this->setChild('pager', $pager);
            $this->getAddresses()->load();
        }
        return $this;
    }

    /**
     * @return \Magento\Customer\Api\Data\AddressInterface[]|bool
     */
    public function getAddresses()
    {

        if (!($customerId = $this->currentCustomer->getCustomerId())) {
            return false;
        }
        if (!$this->_addresses) {
            $this->_addresses = $this->_addressCollectionFactory->create()->addFieldToSelect(
                '*'
            )->addFieldToFilter(
                'parent_id',
                $customerId
            )->setOrder(
                'created_at',
                'asc'
            );
        }
        return $this->_addresses;

    }

    /**
     * @return string
     */
    public function getPagerHtml()
    {
        return $this->getChildHtml('pager');
    }

    /**
     * @return string
     */
    public function getBackUrl()
    {
        if ($this->getRefererUrl()) {

            if(
                strpos($this->getRefererUrl(), 'shipment/history') === false &&
                strpos($this->getRefererUrl(), 'customer/address/edit') === false
            )
                return $this->getRefererUrl();
        }


        return $this->getUrl('customer/account/', ['_secure' => true]);
    }
}
