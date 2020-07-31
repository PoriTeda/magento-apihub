<?php

namespace Riki\Fraud\Observer;

class FraudOrderPlaceBefore implements \Magento\Framework\Event\ObserverInterface
{
    /**
     * @var \Magento\Framework\Registry
     */
    protected $registry;
    /**
     * @var \Riki\Fraud\Model\ScoreFactory
     */
    protected $scoreFactory;

    /**
     * FraudOrderPlaceBefore constructor.
     *
     * @param \Magento\Framework\Registry $registry
     * @param \Riki\Fraud\Model\ScoreFactory $scoreFactory
     */
    public function __construct(
        \Magento\Framework\Registry $registry,
        \Riki\Fraud\Model\ScoreFactory $scoreFactory
    ) {
        $this->registry = $registry;
        $this->scoreFactory = $scoreFactory;
    }

    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        if ($this->registry->registry('disable_riki_check_fraud_sales_order_place_before' )) {
            return;
        }

        /*get order data from observer*/
        $order = $observer->getEvent()->getOrder();

        /*do not need to check and block order for simulate subscription case */
        if ($order instanceof \Riki\Subscription\Model\Emulator\Order) {
            return;
        }

        /*block create new order by back order logic*/
        if($quote = $this->registry->registry('quote_admin')) {
            if ($quote->getData('is_generate')) {
                return;
            }
        }

        /**
         * Ticket 9144
         * Performance api
         * Don't process for machine api
         */
        if($order->getOrderChannel() == 'machine_maintenance') {
            return;
        }

        /*block create new order by fraud extension*/
        $this->blockOrderByFraudExt($order);
    }

    /**
     * Block create new order by fraud extension
     *
     * @param $order
     * @throws \Exception
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function blockOrderByFraudExt($order)
    {
        /** @var \Riki\Fraud\Model\Score $score */
        $score = $this->scoreFactory->create();

        /*get list rules blocks this order*/
        $fraudCheck = $score->getFraudObject($order);

        if (!empty($fraudCheck) && !empty($fraudCheck['status'])) {

            /*block create new order if rule status is reject*/
            if ($fraudCheck['status'] == \Riki\Fraud\Model\Score::STATUS_REJECT) {

                if (!empty($fraudCheck['rejectRule'])) {
                    /*send notification email for this order*/
                    $score->emailWarning($order, $fraudCheck['rejectRule']);
                }

                /*push error message*/
                if (!empty($fraudCheck['rejectMessage'])) {
                    /*error message*/
                    $errMsg = $fraudCheck['rejectMessage'][0];
                } else {
                    $errMsg = 'Placing order is rejected.';
                }
                throw new \Magento\Framework\Exception\LocalizedException(__($errMsg));
            }
        }
    }
}
