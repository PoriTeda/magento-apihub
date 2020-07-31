<?php

namespace Riki\BackOrder\Plugin\RikiSales\Block\Adminhtml\Order\View;

class DeliveryInfo
{
    protected $_helper;

    /**
     * @param \Riki\BackOrder\Helper\Data $helper
     */
    public function __construct(
        \Riki\BackOrder\Helper\Data $helper
    ) {
        $this->_helper = $helper;
    }

    /**
     * add back order data
     *
     * @param \Riki\Sales\Block\Adminhtml\Order\View\DeliveryInfo $subject
     * @param array $result
     * @return array
     */
    public function afterPrepareGroupDeliveryAddressData(
        \Riki\Sales\Block\Adminhtml\Order\View\DeliveryInfo $subject,
        array $result
    ) {

        foreach ($result as $index => $addressData) {
            foreach ($addressData['delivery'] as $deliveryType => $deliveryData) {
                $firstDateTimeStamp = $this->_helper->getDateTimeObj()->timestamp();

                if (isset($deliveryData['deliverydate']) && is_array($deliveryData['deliverydate'])) {
                    $whDeliveryDateTimeStamp = $this->_helper->getDateTimeObj()
                            ->timestamp(end($deliveryData['deliverydate'])) + 24 * 60 * 60;

                    $firstDateTimeStamp = max($whDeliveryDateTimeStamp, $firstDateTimeStamp);
                }

                $result[$index]['delivery'][$deliveryType]['first_date'] =
                    $this->_helper->getDateTimeObj()->date('Y-m-d', $firstDateTimeStamp);
            }
        }

        return $result;
    }
}