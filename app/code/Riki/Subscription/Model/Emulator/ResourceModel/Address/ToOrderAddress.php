<?php

namespace Riki\Subscription\Model\Emulator\ResourceModel\Address;

use Magento\Framework\DataObject\Copy;
use Magento\Sales\Model\Order\AddressRepository as OrderAddressRepository;
class ToOrderAddress
    extends \Magento\Quote\Model\Quote\Address\ToOrderAddress
{
    public function __construct(
        OrderAddressRepository $orderAddressRepository,
        Copy $objectCopyService,
        \Magento\Framework\Api\DataObjectHelper $dataObjectHelper,
        \Riki\Subscription\Model\Emulator\Order\AddressRepository $emulatorOrderAddressRepository
    )
    {
        parent::__construct($orderAddressRepository, $objectCopyService, $dataObjectHelper);
        $this->orderAddressRepository = $emulatorOrderAddressRepository;
    }
}