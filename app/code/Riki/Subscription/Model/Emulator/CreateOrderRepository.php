<?php

namespace Riki\Subscription\Model\Emulator;

class CreateOrderRepository
    extends \Riki\Subscription\Model\Order\CreateOrderRepository
{
    public function __construct(
        \Magento\Customer\Api\AddressRepositoryInterface $customerAddressRepositoryInterface,
        \Magento\Quote\Api\Data\AddressInterface $quoteAddressInterface,
        \Riki\Checkout\Api\Data\AddressItemRelationshipInterface $addressItemRelationshipProcessor,
        \Magento\Quote\Model\Quote\Address\TotalFactory $addressTotalFactory,
        \Riki\ShippingProvider\Api\Data\CalculateShippingFeeBasedOnAddressItemProcessorInterface $shippingFee,
        \Riki\Subscription\Model\Emulator\AddressItemRelationship $emulatorAddressItemRelationShipProcessor,
        \Riki\Subscription\Model\Emulator\Address $emulatorCartAddress
    )
    {
        parent::__construct($customerAddressRepositoryInterface, $quoteAddressInterface, $addressItemRelationshipProcessor, $addressTotalFactory, $shippingFee);
        $this->addressItemRelationshipProcessor = $emulatorAddressItemRelationShipProcessor;
        $this->quoteAddressInterface = $emulatorCartAddress;
    }
}