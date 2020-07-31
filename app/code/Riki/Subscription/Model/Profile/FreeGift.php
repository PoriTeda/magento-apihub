<?php

namespace Riki\Subscription\Model\Profile;

use Magento\Customer\Api\AddressRepositoryInterface;
use Magento\Framework\DataObject;
use Psr\Log\LoggerInterface;
use Riki\Customer\Model\Address\AddressType;

class FreeGift extends DataObject
{
    /**
     * @var AddressRepositoryInterface
     */
    protected $_addressRepository;

    protected $_logger;

    protected $_dataObjFactory;
    /**
     * FreeGift constructor.
     * @param AddressRepositoryInterface $addressRepository
     * @param LoggerInterface $logger
     * @param DataObject\Factory $dataObjFactory
     * @param array $data
     */
    public function __construct(
        AddressRepositoryInterface $addressRepository,
        LoggerInterface $logger,
        DataObject\Factory $dataObjFactory,
        $data = []
    )
    {
        $this->_addressRepository = $addressRepository;
        $this->_logger = $logger;
        $this->_dataObjFactory = $dataObjFactory;
        parent::__construct($data);
    }

    /**
     * Add free gifts to profile cart items
     *
     * @param array $cartItems
     * @param array $giftItems
     * @return array
     */
    public function addFreeGiftsToCartProfile($cartItems, $giftItems)
    {
        $billingAddressId = $this->findBillingAddressId($cartItems);
        $shippingAddressId = $this->findShippingAddressId($cartItems);
        /** @var \Riki\Subscription\Model\Emulator\Order\Item $orderItem */
        foreach ($giftItems as $orderItem) {
            /** @var DataObject $profileCart */
            $profileCartItem = $this->_dataObjFactory->create();
            $profileCartItem->setData($orderItem->getData());
            $profileCartItem->setData('billing_address_id', $billingAddressId);
            $profileCartItem->setData('shipping_address_id', $shippingAddressId);
            $profileCartItem->setData('is_free_gift', true);
            $profileCartItem->setData('qty', $orderItem->getQtyOrdered());
            $profileCartItem->setData('name', $orderItem->getName());
            array_push($cartItems, $profileCartItem);
        }
        return $cartItems;
    }

    /**
     * @param $cartItems
     * @return int
     */
    public function getDefaultShippingAddressFromProfileItems($cartItems)
    {
        return $this->findShippingAddressId($cartItems);
    }

    /**
     * Get address for free gifts
     *
     * @param array $cartItems
     * @return int
     */
    private function findBillingAddressId($cartItems)
    {
        /** @var DataObject $item */
        foreach ($cartItems as $item) {
            try {
                $address = $this->_addressRepository->getById($item->getData('billing_address_id'));
                $addressType = $address->getCustomAttribute('riki_type_address');
                if ($addressType && $addressType->getValue() == AddressType::OFFICE) {
                    return $address->getId();
                }
            } catch (\Exception $e) {
                $this->_logger->critical($e);
                continue;
            }
        }
        return (int) reset($cartItems)['billing_address_id'];
    }

    /**
     * Get address for free gifts
     *
     * @param array $cartItems
     * @return int
     */
    private function findShippingAddressId($cartItems)
    {
        /** @var DataObject $item */
        foreach ($cartItems as $item) {
            try {
                $address = $this->_addressRepository->getById($item->getData('shipping_address_id'));
                $addressType = $address->getCustomAttribute('riki_type_address');
                if ($addressType && $addressType->getValue() == AddressType::OFFICE) {
                    return $address->getId();
                }
            } catch (\Exception $e) {
                $this->_logger->critical($e);
                continue;
            }
        }
        return (int) reset($cartItems)['shipping_address_id'];
    }
}