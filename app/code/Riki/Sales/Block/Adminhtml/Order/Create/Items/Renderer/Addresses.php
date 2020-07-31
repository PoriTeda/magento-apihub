<?php

namespace Riki\Sales\Block\Adminhtml\Order\Create\Items\Renderer;

class Addresses extends \Magento\Sales\Block\Adminhtml\Order\Create\AbstractCreate
{
    /**
     * Filter builder
     *
     * @var \Magento\Framework\Api\FilterBuilder
     */
    protected $filterBuilder;

    /**
     * Search criteria builder
     *
     * @var \Magento\Framework\Api\SearchCriteriaBuilder
     */
    protected $searchCriteriaBuilder;

    /**
     * Address service
     *
     * @var \Magento\Customer\Api\AddressRepositoryInterface
     */
    protected $addressService;

    /**
     * Address helper
     *
     * @var \Magento\Customer\Helper\Address
     */
    protected $_addressHelper;

    /**
     * @var \Magento\Customer\Model\Address\Mapper
     */
    protected $addressMapper;

    protected $addresses;

    protected $adminHelper;

    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Backend\Model\Session\Quote $sessionQuote,
        \Magento\Sales\Model\AdminOrder\Create $orderCreate,
        \Magento\Framework\Pricing\PriceCurrencyInterface $priceCurrency,
        \Magento\Framework\Api\FilterBuilder $filterBuilder,
        \Magento\Framework\Api\SearchCriteriaBuilder $criteriaBuilder,
        \Magento\Customer\Api\AddressRepositoryInterface $addressService,
        \Magento\Customer\Helper\Address $addressHelper,
        \Magento\Customer\Model\Address\Mapper $addressMapper,
        \Riki\Sales\Helper\Admin $adminHelper,
        array $data = []
    ){
        parent::__construct(
            $context,
            $sessionQuote,
            $orderCreate,
            $priceCurrency,
            $data
        );

        $this->searchCriteriaBuilder = $criteriaBuilder;
        $this->filterBuilder = $filterBuilder;
        $this->addressService = $addressService;
        $this->_addressHelper = $addressHelper;
        $this->addressMapper = $addressMapper;
        $this->adminHelper = $adminHelper;
    }

    /**
     * Define block template
     *
     * @return void
     */
    protected function _construct()
    {
        $this->setTemplate('Riki_Sales::order/create/items/addresses.phtml');
        parent::_construct();
    }

    /**
     * Retrieve current customer address DATA collection.
     *
     * @return \Magento\Customer\Api\Data\AddressInterface[]
     */
    public function getAddressCollection()
    {
        if(is_null($this->addresses)){
            if ($this->getCustomerId()) {
                $this->addresses = $this->adminHelper->getAddressListByCustomerId($this->getCustomerId());
            }else{
                $this->addresses = [];
            }
        }

        return $this->addresses;

    }

    /**
     * Represent customer address in 'online' format.
     *
     * @param \Magento\Customer\Api\Data\AddressInterface $address
     * @return string
     */
    public function getAddressAsString(\Magento\Customer\Api\Data\AddressInterface $address)
    {
        $formatTypeRenderer = $this->_addressHelper->getFormatTypeRenderer('oneline');
        $result = '';
        if ($formatTypeRenderer) {
            $result = $formatTypeRenderer->renderArray($this->addressMapper->toFlatArray($address));
        }

        return $this->escapeHtml($result);
    }

    /**
     * @return mixed
     */
    public function getAddressId(){
        return $this->getItem()->getAddressId();
    }

    /**
     * @return \Magento\Framework\DataObject
     */
    public function getDefaultShippingAddress(){
        return $this->adminHelper->getAddressHelper()->getDefaultShippingAddress($this->getQuote()->getCustomer());
    }
}
