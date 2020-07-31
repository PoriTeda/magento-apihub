<?php

namespace Riki\Sales\Block\Adminhtml\Order\View\Items\Renderer;

class Addresses extends \Magento\Sales\Block\Adminhtml\Items\AbstractItems
{

    protected $_addresses;

    protected $_salesAdminHelper;

    protected $_addressHelper;

    protected $_addressMapper;

    protected $_salesAddressHelper;

    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\CatalogInventory\Api\StockRegistryInterface $stockRegistry,
        \Magento\CatalogInventory\Api\StockConfigurationInterface $stockConfiguration,
        \Magento\Framework\Registry $registry,
        \Riki\Sales\Helper\Admin $adminHelper,
        \Riki\Sales\Helper\Address $salesAddressHelper,
        \Magento\Customer\Helper\Address $addressHelper,
        \Magento\Customer\Model\Address\Mapper $addressMapper,
        array $data = []
    ){
        parent::__construct(
            $context,
            $stockRegistry,
            $stockConfiguration,
            $registry,
            $data
        );

        $this->_salesAdminHelper = $adminHelper;
        $this->_addressHelper = $addressHelper;
        $this->_addressMapper = $addressMapper;
        $this->_salesAddressHelper = $salesAddressHelper;
    }

    /**
     * Define block template
     *
     * @return void
     */
    protected function _construct()
    {
        $this->setTemplate('Riki_Sales::order/view/items/renderer/addresses.phtml');
        parent::_construct();
    }

    /**
     * Retrieve current customer address DATA collection.
     *
     * @return \Magento\Customer\Api\Data\AddressInterface[]
     */
    public function getAddressCollection()
    {
        if(is_null($this->_addresses)){
            if ($this->getOrder()->getCustomerId()) {
                $this->_addresses = $this->_salesAdminHelper->getAddressListByCustomerId($this->getOrder()->getCustomerId());
            }else{
                $this->_addresses = [];
            }
        }

        return $this->_addresses;

    }

    /**
     * Represent customer address in 'online' format.
     *
     * @param \Magento\Customer\Api\Data\AddressInterface $address
     * @return string
     */
    public function getAddressAsString(\Magento\Customer\Api\Data\AddressInterface $address)
    {
        return $this->escapeHtml($this->_salesAddressHelper->getAddressAsOneLineString($address));
    }

    /**
     * @return mixed
     */
    public function getAddressId(){

        if($addressId = $this->getItem()->getAddressId())
            return $addressId;

        return $this->_salesAddressHelper->getCustomerAddressIdByOrderItemId($this->getItem()->getId());
    }

    /**
     * @return bool
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function allowToEditShippingAddress(){
        return $this->_salesAdminHelper->allowToChangeShippingAddressOfOrder($this->getOrder());
    }
}
