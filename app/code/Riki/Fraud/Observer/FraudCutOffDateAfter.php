<?php
namespace Riki\Fraud\Observer;

class FraudCutOffDateAfter implements \Magento\Framework\Event\ObserverInterface
{
    /**
     * @var \Magento\Framework\Message\ManagerInterface
     */
    protected $_messageManager;
    /**
     * @var \Riki\Fraud\Model\ScoreFactory
     */
    protected $_scoreFactory;
    /**
     * @var \Riki\Fraud\Helper\CedynaThreshold
     */
    protected $_cedynaHelper;

    /**
     * @var \Magento\Framework\Registry
     */
    protected $registry;

    /**
     * FraudCutOffDateAfter constructor.
     *
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\Message\ManagerInterface $messageManager
     * @param \Riki\Fraud\Model\ScoreFactory $scoreFactory
     * @param \Riki\Fraud\Helper\CedynaThreshold $helper
     */
    public function __construct(
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Message\ManagerInterface $messageManager,
        \Riki\Fraud\Model\ScoreFactory $scoreFactory,
        \Riki\Fraud\Helper\CedynaThreshold $helper
    ) {
        $this->registry = $registry;
        $this->_messageManager = $messageManager;
        $this->_scoreFactory = $scoreFactory;
        $this->_cedynaHelper = $helper;
    }


    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        if ($this->registry->registry('disable_riki_check_fraud_sales_order_place_before' )) {
            return;
        }

        $order = $observer->getEvent()->getOrder();

        if ($order->getData('is_generate') == 1) {
            return;
        }

        /**
         * Ticket 9144
         * Performance api
         * Don't process for machine api
         */
        if ($order->getOrderChannel() == \Riki\Sales\Model\Config\Source\OrderChannel::ORDER_CHANEL_TYPE_MACHINE_API) {
            return;
        }

        $payment = $order->getPayment();

        /*check cedyna threshold exceed for this order*/
        if( !empty($payment) && $payment->getMethod() == \Riki\Sales\Model\Order\PaymentMethod::PAYMENT_METHOD_INVOICED ){
            $this->_cedynaHelper->isThresholdExceed($order);
        }

        /* check suspicious for order */
        $score = $this->_scoreFactory->create();

        /* in the case redirect to paygent , fraud check handle after received response from Paygent */
        if ($payment->getPaygentUrl() || $order->getUseIvr()) {
            $score->setFraudData($order);
        } else {
            $validateFraud = $score->checkFraudScore($order, false);

            /*add warning message after place order success*/
            if (!empty($validateFraud['warningMessage'])) {
                foreach ($validateFraud['warningMessage'] as $msg) {
                    $this->_messageManager->addNotice($msg);
                }
            }
        }

        return $this;
    }
}
