<?php
namespace Riki\GiftOrder\Observer;

use Magento\Framework\Event\ObserverInterface;

class CheckGiftOrder implements ObserverInterface
{
    /**
     * @var \Magento\Sales\Model\Order\Address
     */
    protected $_salesOrderAddressModel;
    /**
     * @var \Magento\Customer\Model\Customer
     */
    protected $_customerModel;
    /**
     * @var \Riki\GiftOrder\Helper\Data
     */
    protected $_messageHelper;
    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;

    public function __construct(
        \Magento\Sales\Model\Order\Address $addressModel,
        \Magento\Customer\Model\Customer $customerModel,
        \Riki\GiftOrder\Helper\Data $messageHelper,
        \Magento\Framework\Message\ManagerInterface $managerInterface,
        \Psr\Log\LoggerInterface $logger
    ) {
        $this->_messageHelper = $messageHelper;
        $this->_customerModel = $customerModel;
        $this->_salesOrderAddressModel = $addressModel;
        $this->logger = $logger;
    }

    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        if ($this->_messageHelper->getConfig(\Riki\GiftOrder\Helper\Data::CONFIG_GIFT_ORDER_ENABLE) != 1) {
            return false;
        }

        $order = $observer->getEvent()->getOrder();
        if(!$order->getId()){
            return false;
        }

        // Handle in observer CheckGiftOrderMultiCheckout
        if ($order->getData('is_multiple_shipping') == 1){
            return false;
        }

        $billingAddressId = $order->getBillingAddressId();
        $shippingAddressId = $order->getShippingAddressId();
        $customerId = $order->getCustomerId();

        $isGiftOrder = $this->checkGiftOrder($shippingAddressId, $billingAddressId, $customerId);

        if ($isGiftOrder) {
            $messageId = $this->_messageHelper->getConfig(\Riki\GiftOrder\Helper\Data::CONFIG_GIFT_ORDER_GIFT_OPTION);

            $order->setGiftMessageId($messageId);
            $order->setIsGiftOrder(1);

            try {
                $order->save();
            } catch (\Exception $e) {
                $this->logger->critical($e);
            }
        } elseif ($order->getIsGiftOrder() != null) {
            $order->setIsGiftOrder(null);
            $order->setGiftMessageId(null);
            try {
                $order->save();
            } catch (\Exception $e) {
                $this->logger->critical($e);
            }
        }

        return $this;
    }

    public function getDetailFromAddressId($addressId, $orderItemAddressId = null)
    {
        $result = [];
        if ($orderItemAddressId != null) {
            $addressModel = $this->_salesOrderAddressModel->load($orderItemAddressId);
            if (!$addressModel->getId()) {
                $addressModel = $this->_salesOrderAddressModel->load($addressId);
            }
        } else {
            $addressModel = $this->_salesOrderAddressModel->load($addressId);
        }

        if($addressModel) {
            $result['region_id'] = $addressModel->getData('region_id');
            $result['city'] = $addressModel->getData('city');
            $result['street'] = $addressModel->getData('street');
            $result['riki_type_address'] = $addressModel->getData('riki_type_address');
            $result['first_name'] = $addressModel->getData('firstname');
            $result['last_name'] = $addressModel->getData('lastname');
            $result['first_name_kana'] = $addressModel->getData('firstnamekana');
            $result['last_name_kana'] = $addressModel->getData('lastnamekana');
        }
        return $result;
    }

    public function checkIsAmbassador($customerId)
    {
        $customer = $this->_customerModel->load($customerId);
        if ($customer) {
            $customerMemberShip = $customer->getMembership();

            if (strpos($customerMemberShip, '3') !== false ) {
                return true;
            }
        }
        return false;
    }


    public function checkGiftOrder($shippingAddressId, $billingAddressId, $customerId, $orderItemShippingAddressId = null)
    {
        $shippingAddressDetail = $this->getDetailFromAddressId($shippingAddressId, $orderItemShippingAddressId);

        if($shippingAddressDetail['riki_type_address'] == \Riki\Customer\Model\Address\AddressType::HOME){
            return false;
        } elseif ($shippingAddressDetail['riki_type_address'] == \Riki\Customer\Model\Address\AddressType::OFFICE) {
            $customerIsAmbassador = $this->checkIsAmbassador($customerId);
            if ($customerIsAmbassador){
                return false;
            }
        }
        return true;
    }
}