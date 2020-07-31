<?php

namespace Riki\BackOrder\Plugin\RikiSales\Block\Adminhtml\Order\Create;

class Delivery
{
    protected $_helper;

    /**
     * @param \Riki\BackOrder\Helper\Data $helper
     */
    public function __construct(
        \Riki\BackOrder\Helper\Data $helper
    ){
        $this->_helper = $helper;
    }

    /**
     * add back order data
     *
     * @param \Riki\Sales\Block\Adminhtml\Order\Create\Delivery $subject
     * @param array $result
     * @return array
     */
    public function afterPrepareGroupDeliveryAddressData(
        \Riki\Sales\Block\Adminhtml\Order\Create\Delivery $subject,
        array $result
    ) {

        // if back order data was added
        foreach ($result as $index => $addressData) {
            foreach ($addressData['ddate_info'] as $deliveryType => $deliveryData) {
                if (isset($result[$index]['ddate_info'][$deliveryType]['back_order'])) {
                    return $result;
                }
            }
        }

        $hanpukaiDeliveryDate = $this->_helper->getHanpukaiDeliveryDateFromCart($subject->getQuote());

        foreach ($result as $index => $addressData) {
            foreach ($addressData['ddate_info'] as $deliveryType => $deliveryData) {
                if ($hanpukaiDeliveryDate) {
                    $result[$index]['ddate_info'][$deliveryType]['first_date'] = $hanpukaiDeliveryDate;
                } else {
                    $firstDateTimeStamp = $this->_helper->getDateTimeObj()->timestamp();

                    if (isset($deliveryData['deliverydate']) && is_array($deliveryData['deliverydate'])) {
                        $whDeliveryDateTimeStamp = $this->_helper->getDateTimeObj()
                                ->timestamp(end($deliveryData['deliverydate'])) + 24 * 60 * 60;

                        $firstDateTimeStamp = max($whDeliveryDateTimeStamp, $firstDateTimeStamp);
                    }

                    $result[$index]['ddate_info'][$deliveryType]['first_date'] =
                        $this->_helper->getDateTimeObj()->date('Y-m-d', $firstDateTimeStamp);
                }
            }
        }

        return $result;
    }
}