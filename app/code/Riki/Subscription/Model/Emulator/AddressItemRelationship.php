<?php
namespace Riki\Subscription\Model\Emulator;

class AddressItemRelationship
    extends \Riki\Checkout\Model\AddressItemRelationship
{
    public function __construct(
        \Magento\Quote\Model\Quote\Address\ItemFactory $cartAddressItemFactory,
        \Riki\Checkout\Model\Order\Address\ItemFactory $orderAddressItemFactory,
        \Magento\Quote\Model\ResourceModel\Quote\Address\CollectionFactory $quoteAddressCollectionFactory,
        \Magento\Sales\Model\Order\AddressFactory $orderAddressFactory,
        \Magento\Quote\Model\Quote\Address\ToOrderAddressFactory $toOrderAddressFactory,
        \Magento\Sales\Api\Data\OrderItemInterfaceFactory $orderItemInterfaceFactory,
        \Psr\Log\LoggerInterface $logger,
        \Riki\Subscription\Model\Emulator\Address\ItemFactory $emulatorAddressItemFactory,
        \Riki\Subscription\Model\Emulator\ResourceModel\Address\Item\CollectionFactory $emulatorAddressItemCollectionFactory
    )
    {
        parent::__construct(
            $cartAddressItemFactory,
            $orderAddressItemFactory,
            $quoteAddressCollectionFactory,
            $orderAddressFactory,
            $toOrderAddressFactory,
            $orderItemInterfaceFactory,
            $logger
        );
        $this->cartItemAddressFactory = $emulatorAddressItemFactory;
        $this->quoteAddressCollectionFactory = $emulatorAddressItemCollectionFactory;
    }
}