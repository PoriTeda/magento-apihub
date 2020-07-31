<?php

namespace Riki\BackOrder\Plugin\DeliveryType\Model;

use \Riki\BackOrder\Helper\Data  as BackOrderHelper;

class QuoteItemAddressDdateProcessor
{
    protected $_helper;

    protected $_datetime;



    /**
     * @param BackOrderHelper $helper
     * @param \Magento\Framework\Stdlib\DateTime\DateTime $date
     */
    public function __construct(
        BackOrderHelper $helper,
        \Magento\Framework\Stdlib\DateTime\DateTime $date
    ){
        $this->_helper = $helper;
        $this->_datetime = $date;
    }

    /**
     * add back order info to delivery group data
     *
     * @param \Riki\DeliveryType\Model\QuoteItemAddressDdateProcessor $subject
     * @param \Closure $proceed
     * @param \Magento\Customer\Api\Data\AddressInterface $customerAddressInterface
     * @param \Magento\Quote\Api\Data\CartInterface $cart
     * @param array $cartItems
     * @return mixed
     */
    public function aroundCalDeliveryDateFollowAddressItem(
        \Riki\DeliveryType\Model\QuoteItemAddressDdateProcessor $subject,
        \Closure $proceed,
        \Magento\Customer\Api\Data\AddressInterface $customerAddressInterface,
        \Magento\Quote\Api\Data\CartInterface $cart,
        array $cartItems
    ){

        $result = $proceed(
            $customerAddressInterface,
            $cart,
            $cartItems
        );

        $hanpukaiDeliveryDate = $this->_helper->getHanpukaiDeliveryDateFromCart($cart);

        foreach ($result as $index => $deliveryData) {
            if ($hanpukaiDeliveryDate) {
                $result[$index]['first_date'] = $hanpukaiDeliveryDate;
            } else {
                $firstDateTimeStamp = $this->_helper->getDateTimeObj()->timestamp();

                if (isset($deliveryData['deliverydate']) && is_array($deliveryData['deliverydate'])) {
                    $whDeliveryDateTimeStamp = $this->_helper->getDateTimeObj()
                            ->timestamp(end($deliveryData['deliverydate'])) + 24 * 60 * 60;

                    $firstDateTimeStamp = max($whDeliveryDateTimeStamp, $firstDateTimeStamp);
                }

                $result[$index]['first_date'] = $this->_helper->getDateTimeObj()->date('Y-m-d', $firstDateTimeStamp);
            }
        }

        return $result;
    }
}