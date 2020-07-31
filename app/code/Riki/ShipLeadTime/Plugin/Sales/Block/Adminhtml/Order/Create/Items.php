<?php
namespace Riki\ShipLeadTime\Plugin\Sales\Block\Adminhtml\Order\Create;

class Items
{
    /** @var \Riki\ShipLeadTime\Helper\Data  */
    protected $shipLeadTimeHelper;

    /** @var \Riki\Sales\Helper\Admin  */
    protected $salesAdminHelper;

    protected $defaultCustomerAddressId;

    /**
     * Items constructor.
     * @param \Riki\ShipLeadTime\Helper\Data $shipLeadTimeHelper
     */
    public function __construct(
        \Riki\ShipLeadTime\Helper\Data $shipLeadTimeHelper,
        \Riki\Sales\Helper\Admin $salesAdminHelper
    )
    {
        $this->shipLeadTimeHelper = $shipLeadTimeHelper;
        $this->salesAdminHelper = $salesAdminHelper;
    }

    /**
     * @param \Magento\Sales\Block\Adminhtml\Order\Create\Items $subject
     * @param array $result
     * @return array
     */
    public function afterGetItems(
        \Magento\Sales\Block\Adminhtml\Order\Create\Items $subject,
        array $result
    )
    {
        $quote = $subject->getQuote();

        $errors = [];

        if ($this->salesAdminHelper->isMultipleShippingAddressCart()) {

            $items = $quote->getAllItems();

            $addressToItems = [];

            foreach ($items as $item) {
                $addressId = $item->getData('address_id');

                if (!$addressId) {
                    $addressId = $this->getDefaultCustomerAddressId($quote);
                }

                if (!isset($addressToItems[$addressId])) {
                    $addressToItems[$addressId] = [];
                }

                $addressToItems[$addressId][] = $item->getId();
            }

            foreach ($addressToItems as $addressId  =>  $itemIds) {
                $errors = $errors + $this->shipLeadTimeHelper->validateQuoteAddress($quote, $addressId, $itemIds);
            }

        } else {

            $address = $quote->getShippingAddress();

            if ($address->getRegionId()) {
                $errors = $this->shipLeadTimeHelper->validateQuoteRegion($quote, $address->getRegionModel()->getCode());
            }
        }

        $this->shipLeadTimeHelper->prepareQuoteLeadTimeError($quote, $errors);

        return $result;
    }

    /**
     * @param \Magento\Quote\Model\Quote $quote
     * @return \Magento\Framework\DataObject
     */
    protected function getDefaultCustomerAddressId(\Magento\Quote\Model\Quote $quote)
    {

        if (is_null($this->defaultCustomerAddressId)) {
            $this->defaultCustomerAddressId = $this->salesAdminHelper->getAddressHelper()->getDefaultShippingAddress($quote->getCustomer());
        }

        return $this->defaultCustomerAddressId;
    }
}