<?php

namespace Riki\Subscription\Model\Emulator\Order;

class ToOrderAddress
    extends \Magento\Quote\Model\Quote\Address\ToOrderAddress
{
    public function __construct(
        \Magento\Sales\Model\Order\AddressRepository $orderAddressRepository,
        \Magento\Framework\DataObject\Copy $objectCopyService,
        \Magento\Framework\Api\DataObjectHelper $dataObjectHelper,
        \Riki\Subscription\Model\Emulator\Order\AddressRepository $emulatorOrderAddressRepository
    )
    {
        parent::__construct($orderAddressRepository, $objectCopyService, $dataObjectHelper);
        $this->orderAddressRepository = $emulatorOrderAddressRepository;
    }
}