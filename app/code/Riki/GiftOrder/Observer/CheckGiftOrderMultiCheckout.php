<?php
namespace Riki\GiftOrder\Observer;

use Magento\Framework\Event\ObserverInterface;
use Riki\Checkout\Model\ResourceModel\Order\Address\Item\CollectionFactory as AddressItemCollectionFactory;
use Magento\Framework\Logger\Monolog;

class CheckGiftOrderMultiCheckout implements ObserverInterface
{
    protected $_salesOrderAddressModel;
    protected $_customerModel;
    protected $_messageHelper;

    /* Riki\Checkout\Model\ResourceModel\Order\Address\Item\Collection */
    protected $_addressItemCollectionFactory;

    /* Magento\Framework\Logger\Monolog */
    protected $_monoLog;

    /**
     * @var \Magento\Framework\Message\ManagerInterface
     */
    protected $_managerInterface;

    public function __construct(
        Monolog $monolog,
        AddressItemCollectionFactory $addressItemCollectionFactory,
        \Magento\Sales\Model\Order\Address $addressModel,
        \Magento\Customer\Model\Customer $customerModel,
        \Riki\GiftOrder\Helper\Data $messageHelper,
        \Magento\Framework\Message\ManagerInterface $managerInterface
    )
    {
        $this->_monoLog = $monolog;
        $this->_addressItemCollectionFactory = $addressItemCollectionFactory;
        $this->_messageHelper = $managerInterface;
        $this->_messageHelper = $messageHelper;
        $this->_customerModel = $customerModel;
        $this->_salesOrderAddressModel = $addressModel;
    }

    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        if ($this->_messageHelper->getConfig(\Riki\GiftOrder\Helper\Data::CONFIG_GIFT_ORDER_ENABLE) != 1) {
            return false;
        }

        $order = $observer->getEvent()->getOrder();
        if(!$order) {
            // multiple address checkout
            $multiOrder = $observer->getEvent()->getOrders();
            // not implement for multiple address checkout
            if($multiOrder) {
                return false;
            }
        }

        if(!$order->getId()){
            return false;
        }


        $billingAddressId = $order->getBillingAddressId();
        $shippingAddressId = $order->getShippingAddressId();
        $customerId = $order->getCustomerId();

        // For multi checkout
        try {
            $orderItems = $order->getItems();
            $isGiftOrder = false;
            if ($orderItems) {
                foreach ($orderItems as $_orderItem) {
                    $collectionAddress = $this->_addressItemCollectionFactory->create()->addFieldToFilter('order_item_id', $_orderItem->getId())->load();
                    if ($collectionAddress->getSize()) {
                        $orderItemShippingAddressId = $collectionAddress->getFirstItem()->getOrderAddressId();
                        $isGiftOrder = $this->checkGiftOrder($shippingAddressId, $billingAddressId, $customerId, $orderItemShippingAddressId);
                        if ($isGiftOrder == true) {
                            break;
                        }
                    }
                }
            }
        } catch (\Exception $e) {
            $this->_monoLog->critical($e);
        }

        if ($isGiftOrder) {
            $messageId = $this->_messageHelper->getConfig(\Riki\GiftOrder\Helper\Data::CONFIG_GIFT_ORDER_GIFT_OPTION);

            $order->setGiftMessageId($messageId);
            $order->setIsGiftOrder(1);
            try {
                $order->save();
            } catch (\Exception $e) {
                $message = __('Something went wrong while save Gift Order.')
                    . $e->getMessage()
                    . '<pre>' . $e->getTraceAsString() . '</pre>';
                $this->_managerInterface->addException($e, $message);
            }
        } elseif ($order->getIsGiftOrder() != null) {
            $order->setIsGiftOrder(null);
            $order->setGiftMessageId(null);
            try {
                $order->save();
            } catch (\Exception $e) {
                $message = __('Something went wrong while remove Gift Order flag.')
                    . $e->getMessage()
                    . '<pre>' . $e->getTraceAsString() . '</pre>';
                $this->_managerInterface->addException($e, $message);
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

        if ($addressModel) {
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

        if ($shippingAddressDetail['riki_type_address'] == \Riki\Customer\Model\Address\AddressType::HOME) {
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