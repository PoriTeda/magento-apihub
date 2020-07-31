<?php

namespace Riki\ShipLeadTime\Plugin\Quote\Api;

class ShippingAddressManagementInterface
{
    /** @var \Riki\PointOfSale\Helper\Data  */
    protected $pointOfSalesHelper;

    /** @var \Riki\ShipLeadTime\Helper\Data  */
    protected $shipLeadTimeHelper;

    protected $customerAddressRepository;

    /**
     * ShippingAddressManagementInterface constructor.
     * @param \Riki\PointOfSale\Helper\Data $pointOfSalesHelper
     * @param \Riki\ShipLeadTime\Helper\Data $shipLeadTimeHelper
     */
    public function __construct(
        \Riki\PointOfSale\Helper\Data $pointOfSalesHelper,
        \Riki\ShipLeadTime\Helper\Data $shipLeadTimeHelper,
        \Magento\Customer\Api\AddressRepositoryInterface $addressRepository,
        \Riki\Directory\Helper\Data $directoryHelper
    )
    {
        $this->pointOfSalesHelper = $pointOfSalesHelper;
        $this->shipLeadTimeHelper = $shipLeadTimeHelper;
        $this->customerAddressRepository = $addressRepository;
    }

    /**
     * @param \Riki\Quote\Api\ShippingAddressManagementInterface $shippingAddressManagement
     * @param \Closure $proceed
     * @param \Magento\Quote\Model\Quote $quote
     * @param $addressId
     * @return bool
     */
    public function aroundCanAssignAddressToQuote(
        \Riki\Quote\Api\ShippingAddressManagementInterface $shippingAddressManagement,
        \Closure $proceed,
        \Magento\Quote\Model\Quote $quote,
        $addressId
    )
    {
        $result = $proceed($quote, $addressId);

        if ($result) {

            $places = $this->shipLeadTimeHelper->getShipLeadTimeByQuoteAndAddressForListItem($quote, $addressId);

            if (empty($places)) {

                $address = $this->customerAddressRepository->getById($addressId);

                $quote->addErrorInfo(
                    'warehouse',
                    'advanced_inventory',
                    null,
                    __('Unable to ship to %1 now.', $address->getRegion()->getRegion())
                );

                return false;
            } else {
                $quote->removeErrorInfosByParams('warehouse', ['origin' => 'advanced_inventory', 'code' => null]);
            }
        }

        return $result;
    }
}
