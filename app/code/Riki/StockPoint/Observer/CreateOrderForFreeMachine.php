<?php

namespace Riki\StockPoint\Observer;

class CreateOrderForFreeMachine implements \Magento\Framework\Event\ObserverInterface
{
    /**
     * @var \Magento\Framework\Event\ManagerInterface
     */
    protected $eventManager;
    /**
     * @var \Magento\Quote\Model\QuoteFactory
     */
    protected $quoteFactory;
    /**
     * @var \Magento\Quote\Model\QuoteManagement
     */
    protected $quoteManagement;
    /**
     * @var \Riki\SubscriptionMachine\Helper\Order\Generate
     */
    protected $freeMachineHelper;

    /**
     * CreateOrderForFreeMachine constructor.
     *
     * @param \Magento\Framework\Event\ManagerInterface $eventManager
     * @param \Magento\Quote\Model\QuoteFactory $quoteFactory
     * @param \Magento\Quote\Model\QuoteManagement $quoteManagement
     * @param \Riki\SubscriptionMachine\Helper\Order\Generate $freeMachineHelper
     */
    public function __construct(
        \Magento\Framework\Event\ManagerInterface $eventManager,
        \Magento\Quote\Model\QuoteFactory $quoteFactory,
        \Magento\Quote\Model\QuoteManagement $quoteManagement,
        \Riki\SubscriptionMachine\Helper\Order\Generate $freeMachineHelper
    ) {
        $this->eventManager = $eventManager;
        $this->quoteFactory = $quoteFactory;
        $this->quoteManagement = $quoteManagement;
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

        /*do not handle for none stock point order*/
        if (!$quote->getData(\Riki\Subscription\Helper\Order\Data::IS_STOCK_POINT_ORDER)) {
            return;
        }

        /**
         * free machine cart id,
         *      has been generated while main order submitted (event check submit before)
         */
        $freeMachineQuoteId = $quote->getData('free_machine_cart_id');

        if (!$freeMachineQuoteId) {
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

        /** @var \Magento\Sales\Model\Order $quote */
        $order = $observer->getEvent()->getOrder();

        /*shipping address from customer*/
        $profileData = $observer->getEvent()->getProfile();

        $logger = $this->freeMachineHelper->getFreeMachineLogger();

        $freeMachineQuote = $this->getQuoteById($freeMachineQuoteId);

        if (!$freeMachineQuote) {
            $logger->info(
                'Profile #'.$profileData['profile_id'].
                ' - Free machine cart id #'.$freeMachineQuote->getId().
                ' is invalid'
            );

            return;
        }

        $logger->info(
            'Profile #'.$profileData['profile_id'].
            ' - Start to create free machine order for cart id #'.$freeMachineQuoteId
        );

        if (!$freeMachineQuote->getAllItems()) {
            $logger->info(
                'Profile #'.$profileData['profile_id'].' - Free machine cart is empty.'
            );
            return;
        }

        $this->placeFreeMachineOrder($freeMachineQuote, $order, $profileData, $logger);
    }

    /**
     * @param $freeMachineQuote
     * @param $profile
     */
    private function addShippingAndPaymentMethodForQuote($freeMachineQuote, $profile)
    {
        $freeMachineQuote->getShippingAddress()->setCollectShippingRates(true)->setFreeShipping(true);
        $freeMachineQuote->getShippingAddress()->setShippingMethod(
            $profile['shipping_method']
        )->collectShippingRates();

        $shippingRate = $freeMachineQuote->getShippingAddress()->getShippingRateByCode($profile['shipping_method']);
        if ($shippingRate) {
            $shippingDescription= $shippingRate->getCarrierTitle() . ' - ' . $shippingRate->getMethodTitle();
            $freeMachineQuote->getShippingAddress()->setShippingDescription(trim($shippingDescription, ' -'));
        }

        $freeMachineQuote->getShippingAddress()->setSaveInAddressBook(false);

        /*set this flag to avoid collect total for free machine order*/
        $freeMachineQuote->setTotalsCollectedFlag(true);

        $paymentData = [
            \Magento\Quote\Api\Data\PaymentInterface::KEY_METHOD =>
                \Magento\Payment\Model\Method\Free::PAYMENT_METHOD_FREE_CODE,
            'checks' => [
                \Magento\Payment\Model\Method\AbstractMethod::CHECK_USE_FOR_COUNTRY,
                \Magento\Payment\Model\Method\AbstractMethod::CHECK_USE_FOR_CURRENCY,
                \Magento\Payment\Model\Method\AbstractMethod::CHECK_ORDER_TOTAL_MIN_MAX,
                \Magento\Payment\Model\Method\AbstractMethod::CHECK_ZERO_TOTAL,
            ]
        ];

        $freeMachineQuote->getPayment()->setQuote($freeMachineQuote);
        $freeMachineQuote->getPayment()->importData($paymentData);
        $freeMachineQuote->setInventoryProcessed(false);
    }

    /**
     * place free machine order
     *
     * @param $freeMachineQuote
     * @param $originalOrder
     * @param $profile
     * @param $logger
     */
    private function placeFreeMachineOrder($freeMachineQuote, $originalOrder, $profile, $logger)
    {
        /*place new order*/
        try {
            $this->addShippingAndPaymentMethodForQuote($freeMachineQuote, $profile);

            $freeMachineQuote->save();

            $this->eventManager->dispatch(
                'checkout_submit_before',
                ['quote' => $freeMachineQuote]
            );

            $freeMachineOrder = $this->quoteManagement->submit($freeMachineQuote);

            if (null == $freeMachineOrder) {
                throw new \Magento\Framework\Exception\LocalizedException(
                    __('Cannot place free machine order.')
                );
            }

            $logger->info(
                'Profile #'.$profile['profile_id'].
                ' - Create free machine order success #'.
                $freeMachineOrder->getIncrementId()
            );

            $this->placeFreeMachineOrderSuccess(
                $freeMachineOrder,
                $freeMachineQuote,
                $originalOrder,
                $logger
            );
        } catch (\Exception $e) {
            $logger->info(
                'Profile #'.$profile['profile_id'].
                ' - Create free machine order has an error.'
            );

            $logger->info(
                'Profile #'.$profile['profile_id'].
                ' - '.
                $e->getMessage()
            );
        }
    }

    /**
     * after place free machine order success, sync some data from main order
     *
     * @param $freeMachineOrder
     * @param $freeMachineQuote
     * @param $originalOrder
     * @param $logger
     */
    private function placeFreeMachineOrderSuccess(
        $freeMachineOrder,
        $freeMachineQuote,
        $originalOrder,
        $logger
    ) {
        $freeMachineOrder->setData(
            \Riki\Subscription\Helper\Order\Data::IS_PROFILE_GENERATED_ORDER_KEY,
            1
        );

        /*linked new order to original order profile*/
        $freeMachineOrder->setData(
            'subscription_profile_id',
            $originalOrder->getData('subscription_profile_id')
        );

        /*same order time with original order*/
        $freeMachineOrder->setData(
            'subscription_order_time',
            $originalOrder->getData('subscription_order_time')
        );

        /*same type with original order*/
        $freeMachineOrder->setData(
            'riki_type',
            $originalOrder->getData('riki_type')
        );

        /*set default value to pass Fraud validation*/
        $freeMachineOrder->setData('fraud_score', 50);
        $freeMachineOrder->setData('fraud_status', 'accept');

        /*do not send mail when generate order subscription*/
        $freeMachineOrder->setEmailSent(1);

        try {
            $freeMachineOrder->save();
        } catch (\Exception $e) {
            $logger->info(
                'Profile #'.$originalOrder->getData('subscription_profile_id').
                ' - Create free machine order has an error.'
            );
            $logger->info(
                'Profile #'.$originalOrder->getData('subscription_profile_id').
                ' - '.
                $e->getMessage()
            );
        }

        $this->eventManager->dispatch(
            'checkout_submit_all_after',
            [
                'order' => $freeMachineOrder,
                'quote' => $freeMachineQuote
            ]
        );
    }

    /**
     * Get quote data by id
     *
     * @param $freeMachineQuoteId
     * @return bool|\Riki\Catalog\Model\Quote
     */
    protected function getQuoteById($freeMachineQuoteId)
    {
        /** @var \Riki\Catalog\Model\Quote $freeMachineQuote */
        $freeMachineQuote= $this->quoteFactory->create();

        $freeMachineQuote->load($freeMachineQuoteId);

        if ($freeMachineQuote->getId()) {
            return $freeMachineQuote;
        }

        return false;
    }
}
