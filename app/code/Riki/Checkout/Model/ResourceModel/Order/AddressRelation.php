<?php

namespace Riki\Checkout\Model\ResourceModel\Order;

class AddressRelation implements \Magento\Framework\Model\ResourceModel\Db\VersionControl\RelationInterface
{
    /**
     * @var \Riki\Checkout\Model\AddressItemRelationship
     */
    protected $addressItemRelationShip;

    /**
     * @var \Riki\Checkout\Model\Order\Address\ItemFactory
     */
    protected $orderAddressItemFactory;

    /**
     * AddressRelation constructor.
     *
     * @param \Riki\Checkout\Model\AddressItemRelationship $addressItemRelationship
     * @param \Riki\Checkout\Model\Order\Address\ItemFactory $orderAddressItemFactory
     */
    public function __construct(
        \Riki\Checkout\Model\AddressItemRelationship $addressItemRelationship,
        \Riki\Checkout\Model\Order\Address\ItemFactory $orderAddressItemFactory
    ) {
        $this->addressItemRelationShip = $addressItemRelationship;
        $this->orderAddressItemFactory = $orderAddressItemFactory;
    }

    /**
     * Save relations for Order
     *
     * @param \Magento\Framework\Model\AbstractModel $object
     * @return void
     * @throws \Exception
     */
    public function processRelation(\Magento\Framework\Model\AbstractModel $object)
    {
        if ($object->getNeedToSaveMultipleShippingAddresses()) {
            $this->addressItemRelationShip->saveOrderAddressItemRelationByQuoteId(
                $object->getQuoteId(),
                $object
            );
        }

        $object->unsNeedToSaveMultipleShippingAddresses();
    }
}
