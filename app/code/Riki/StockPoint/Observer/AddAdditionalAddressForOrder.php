<?php

namespace Riki\StockPoint\Observer;

use Riki\Subscription\Helper\Order\Data as HelperOrderData;

class AddAdditionalAddressForOrder implements \Magento\Framework\Event\ObserverInterface
{
    /**
     * @var \Magento\Quote\Model\Quote\Address\ToOrderAddress
     */
    protected $convertQuoteAddressToOrderAddress;

    /**
     * AddAdditionalAddressForOrder constructor.
     *
     * @param \Magento\Quote\Model\Quote\Address\ToOrderAddress $toOrderAddress
     */
    public function __construct(
        \Magento\Quote\Model\Quote\Address\ToOrderAddress $toOrderAddress
    ) {
        $this->convertQuoteAddressToOrderAddress = $toOrderAddress;
    }

    /**
     *
     * @param \Magento\Framework\Event\Observer $observer
     * @return void
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        /** @var \Riki\Catalog\Model\Quote $quote */
        $quote = $observer->getQuote();

        if ($quote instanceof \Riki\Subscription\Model\Emulator\Cart) {
            return;
        }

        /**
         * It only checks profile has to use the address of the stock point
         */
        if ($quote->getData(HelperOrderData::PROFILE_GENERATE_USE_STOCK_POINT_ADDRESS)) {

            /** @var \Magento\Sales\Model\Order $order */
            $order = $observer->getOrder();

            /*current order address - normally, this is billing and shipping address*/
            $orderAddress = $order->getAddresses();

            /*additional address for order - only exists for case that profile use stock point address*/
            $customerShippingAddress = $quote->getCustomerShippingAddress();

            /*
             * validate customer address id to avoid a case that quote return
             * an empty object with only address type and quote id
            */
            if ($customerShippingAddress && !empty($customerShippingAddress->getCustomerAddressId())) {
                $orderAddress[] = $this->convertQuoteAddressToOrderAddress->convert(
                    $customerShippingAddress,
                    [
                        'address_type' => \Riki\Quote\Model\Quote\Address::ADDRESS_TYPE_CUSTOMER,
                        'email' => $quote->getCustomerEmail()
                    ]
                );
                /*will add new record to sales_order_address table which address type is customer*/
                $order->setAddresses($orderAddress);
            }
        }
    }
}
