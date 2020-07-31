<?php

namespace Riki\SubscriptionMachine\Observer;

class AddFreeMachine implements \Magento\Framework\Event\ObserverInterface
{
    protected $freeMachineHelper;

    /**
     * AddFreeMachine constructor.
     * @param \Riki\SubscriptionMachine\Helper\Order\Generate $freeMachineHelper
     */
    public function __construct(
        \Riki\SubscriptionMachine\Helper\Order\Generate $freeMachineHelper
    ) {
        $this->freeMachineHelper = $freeMachineHelper;
    }

    /**
     * @param \Magento\Framework\Event\Observer $observer
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        /** @var \Magento\Quote\Model\Quote $quote */
        $quote = $observer->getEvent()->getQuote();

        if ($quote instanceof \Riki\Subscription\Model\Emulator\Cart) {
            return;
        }

        //doesn't process for machine api
        if ($quote->getOrderChannel() == 'machine_maintenance') {
            return;
        }

        /**
         * do not add free machine item to stock point cart
         * for this case, free machine item will be created separately order
         */
        if ($quote->getData(\Riki\Subscription\Helper\Order\Data::IS_STOCK_POINT_ORDER)) {
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

        if ($courseId) {
            $frequencyId = $quote->getData('riki_frequency_id');
            if ($frequencyId) {
                $this->freeMachineHelper->addFreeMachineIntoOrderSubscription(
                    $quote,
                    $customer,
                    $courseId,
                    $frequencyId
                );
            }
        }
    }
}
