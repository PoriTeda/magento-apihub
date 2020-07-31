<?php

namespace Riki\StockPoint\Observer;

class CreateQuoteForFreeMachine implements \Magento\Framework\Event\ObserverInterface
{
    /**
     * @var \Magento\Quote\Model\QuoteFactory
     */
    protected $quoteFactory;
    /**
     * @var \Magento\Quote\Model\Quote\AddressFactory
     */
    protected $quoteAddressFactory;
    /**
     * @var \Riki\SubscriptionMachine\Helper\Order\Generate
     */
    protected $freeMachineHelper;

    /**
     * Create Order For Free Machine constructor.
     *
     * @param \Riki\SubscriptionMachine\Helper\Order\Generate $freeMachineHelper
     */
    public function __construct(
        \Magento\Quote\Model\QuoteFactory $quoteFactory,
        \Magento\Quote\Model\Quote\AddressFactory $quoteAddressFactory,
        \Riki\SubscriptionMachine\Helper\Order\Generate $freeMachineHelper
    ) {
        $this->quoteFactory = $quoteFactory;
        $this->quoteAddressFactory = $quoteAddressFactory;
        $this->freeMachineHelper = $freeMachineHelper;
    }

    /**
     * for a case order is stock point order, create separately order for all free machine items
     *
     * @param \Magento\Framework\Event\Observer $observer
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        /** @var \Magento\Quote\Model\Quote $quote */
        $quote = $observer->getEvent()->getQuote();

        if ($quote instanceof \Riki\Subscription\Model\Emulator\Cart) {
            return;
        }

        /*reject out of stock order*/
        if ($quote->getData('is_oos_order')) {
            return;
        }

        //doesn't process for machine api
        if ($quote->getOrderChannel()=='machine_maintenance') {
            return;
        }

        /*do not handle for none stock point order*/
        if (!$quote->getData(\Riki\Subscription\Helper\Order\Data::IS_STOCK_POINT_ORDER)) {
            return;
        }

        /**
         * flag to avoid free machine will be added again for stock point free machine order
         * stock point free machine order is a separately order
         *      which was created with only free machine for stock point profile
         */
        if ($quote->getData('free_machine_order')) {
            return;
        }

        /*customer data*/
        $customer = $quote->getCustomer();

        /*is ambassador customer - do not need to handle for customer which membership is not ambassador*/
        $isAmbassadorCustomer = $this->freeMachineHelper->isAmbassadorCustomer($customer);

        if (!$isAmbassadorCustomer) {
            return;
        }

        $courseId = $quote->getData('riki_course_id');

        if (!$courseId) {
            return;
        }

        $frequencyId = $quote->getData('riki_frequency_id');

        if (!$frequencyId) {
            return;
        }

        /*shipping address from customer*/
        $profileData = $observer->getEvent()->getProfile();

        /*profile data*/
        if (empty($profileData)) {
            return;
        }

        /** @var \Riki\Subscription\Logger\LoggerFreeMachine $logger */
        $logger = $this->freeMachineHelper->getFreeMachineLogger();

        $logger->info(
            'Profile #'.$profileData['profile_id'].' - Start to create free machine quote'
        );

        $freeMachineQuote = $this->createFreeMachineQuoteByOriginalQuote(
            $quote,
            $customer,
            $profileData
        );

        $this->freeMachineHelper->addFreeMachineToSpecifiedQuote(
            $freeMachineQuote,
            $customer,
            $courseId,
            $frequencyId,
            $quote
        );

        if (!$freeMachineQuote->getAllItems()) {
            $logger->info(
                'Profile #'.$profileData['profile_id'].' - Free machine item is empty.'
            );
            return;
        }

        $logger->info(
            'Profile #'.$profileData['profile_id'].
            ' - Create new cart for free machine success - Cart Id #'.
            $freeMachineQuote->getId()
        );

        /*store free machine cart data for original quote*/
        $quote->setData('free_machine_cart_id', $freeMachineQuote->getId());
    }

    /**
     * @param $quote
     * @param $customer
     * @param $profile
     * @return \Riki\Catalog\Model\Quote
     */
    private function createFreeMachineQuoteByOriginalQuote(
        $quote,
        $customer,
        $profile
    ) {
        /** @var \Riki\Catalog\Model\Quote $freeMachineQuote */
        $freeMachineQuote= $this->quoteFactory->create();
        /*flag to avoid free machine will be added again for stock point free machine order*/
        $freeMachineQuote->setData('free_machine_order', true);

        $freeMachineQuote->setIsActive(0);
        $freeMachineQuote->setStore($quote->getStore());

        $freeMachineQuote->setFreeOfCharge(0);

        /*flag to know this is subscription order*/
        $freeMachineQuote->setData('profile_id', $profile['profile_id']);
        $freeMachineQuote->setData(
            \Riki\Subscription\Helper\Order\Data::IS_PROFILE_GENERATED_ORDER_KEY,
            true
        );

        $quoteBillingAddress = $this->quoteAddressFactory->create();
        $billingAddress = $quoteBillingAddress->importCustomerAddressData($profile['billing_address']);

        $quoteShippingAddress = $this->quoteAddressFactory->create();
        $shippingAddress = $quoteShippingAddress->importCustomerAddressData($profile['shipping_address']);

        $freeMachineQuote->assignCustomerWithAddressChange($customer, $billingAddress, $shippingAddress);

        return $freeMachineQuote;
    }
}
