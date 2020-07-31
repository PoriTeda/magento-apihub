<?php
namespace Riki\Sales\Observer;

use Magento\Framework\Event\ObserverInterface;

class AdminQuoteServiceSubmitBefore implements ObserverInterface
{
    protected $_quoteSession;

    protected $_authSession;

    protected $_salesAdminHelper;

    protected $_quoteAddresses;

    protected $_customerAddressRepositoryInterface;

    protected $_quoteAddressFactory;

    public function __construct(
        \Magento\Customer\Model\AddressFactory $addressFactory,
        \Magento\Quote\Model\Quote\AddressFactory $quoteAddressFactory,
        \Magento\Customer\Api\AddressRepositoryInterface $addressRepositoryInterface,
        \Magento\Backend\Model\Session\Quote $quoteSession,
        \Magento\Backend\Model\Auth\Session $authSession,
        \Riki\Sales\Helper\Admin $salesAdminHelper
    ){
        $this->_authSession = $authSession;
        $this->_salesAdminHelper = $salesAdminHelper;
        $this->_quoteSession = $quoteSession;
        $this->_customerAddressRepositoryInterface = $addressRepositoryInterface;
        $this->_quoteAddressFactory = $quoteAddressFactory;
    }

    /**
     *
     * @param \Magento\Framework\Event\Observer $observer
     * @return void
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        /** @var \Magento\Quote\Model\Quote $quote */
        $quote = $observer->getQuote();

        /** @var \Magento\Sales\Model\Order $order */
        $order = $observer->getOrder();

        if($this->_quoteSession->getFreeSurcharge()){
            $order->setIsFreePaymentChargeByAdmin(1);
        }

        if($this->_quoteSession->getFreeShippingFlag()){
            $order->setIsFreeShippingByAdmin(1);
        }

        ////
        if($this->_authSession->getUser()){
            $order->setCreatedBy($this->_authSession->getUser()->getUserName());
        }

        if($this->_salesAdminHelper->isMultipleShippingAddressCart())
            $order->setIsMultipleShipping(1);

        // add order item address
        foreach($quote->getAllItems() as $item){

            if($this->_salesAdminHelper->isMultipleShippingAddressCart()){

                $addressId = $item->getAddressId()? $item->getAddressId() : $this->_quoteSession->getQuote()->getCustomer()->getDefaultShipping();

                if($addressId && $quoteAddress = $this->getQuoteAddressFromCustomerAddress($addressId)){
                    try {
                        $quoteAddress->addItem($item)->save();
                    }catch (\Exception $e) {
                        continue;
                    }
                }
            }
        }
    }

    /**
     * @param $customerAddressId
     * @return \Magento\Quote\Model\Quote\Address
     */
    public function getQuoteAddressFromCustomerAddress($customerAddressId){
        if(!isset($this->_quoteAddresses[$customerAddressId])){
            try{
                $customerAddressObject = $this->_customerAddressRepositoryInterface->getById($customerAddressId);
                if($customerAddressObject instanceof \Magento\Customer\Api\Data\AddressInterface && $customerAddressObject->getId()){
                    $quoteAddress = $this->_quoteAddressFactory->create();
                    $quoteAddress->importCustomerAddressData($customerAddressObject);
                    $quoteAddress->setQuoteId($this->_quoteSession->getQuoteId());
                    $quoteAddress->setAddressType(\Riki\Sales\Helper\Address::ADDRESS_MULTI_SHIPPING_TYPE);
                    $quoteAddress->setEmail($this->_quoteSession->getQuote()->getCustomerEmail());
                    $quoteAddress->save();
                    $this->_quoteAddresses[$customerAddressId] = $quoteAddress;
                }else{
                    $this->_quoteAddresses[$customerAddressId] =  false;
                }
            }catch (\Exception $e){
                $this->_quoteAddresses[$customerAddressId] =  false;
            }

        }

        return $this->_quoteAddresses[$customerAddressId];
    }
}