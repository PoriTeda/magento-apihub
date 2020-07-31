<?php
namespace Bluecom\Paygent\Plugin;

class GetPaymentAgent
{
    /**
     * @var \Bluecom\Paygent\Helper\HistoryHelper
     */
    protected $historyHelper;
    /**
     * @var \Bluecom\Paygent\Logger\Logger
     */
    protected $logger;

    /**
     * GetPaymentAgent constructor.
     *
     * @param \Bluecom\Paygent\Helper\HistoryHelper $historyHelper
     * @param \Bluecom\Paygent\Logger\Logger $logger
     */
    public function __construct(
        \Bluecom\Paygent\Helper\HistoryHelper $historyHelper,
        \Bluecom\Paygent\Logger\Logger $logger
    ) {
        $this->historyHelper = $historyHelper;
        $this->logger = $logger;
    }

    /**
     * Get payment agent from paygent history
     *
     * @param \Riki\SapIntegration\Helper\Data $subject
     * @param $order
     * @return array
     */
    public function beforeGetPaymentAgentFromPaygentHistory(
        \Riki\SapIntegration\Helper\Data $subject,
        $order
    ) {
        if (empty($order->getData('payment_agent'))) {
            $paymentAgent = $this->getPaymentAgentFromHistory($order);
            $order->setData('payment_agent', $paymentAgent);
        }

        return [$order];
    }

    /**
     * Get payment agent from paygent history
     *
     * @param $order
     * @return mixed
     */
    public function getPaymentAgentFromHistory($order)
    {
        if (!empty($order->getData('payment_agent'))) {
            return $order->getData('payment_agent');
        }

        $paymentAgent = $this->historyHelper->getPaymentAgentByOrderIncrementId($order->getIncrementId());

        if (!empty($paymentAgent)) {
            $order->setData('payment_agent', $paymentAgent);
            try {
                $this->logger->info('Update Order Payment Agent For #'. $order->getIncrementId());
                $order->save();
                $this->logger->info('Update Order Payment Agent For #'. $order->getIncrementId(). ' success, new Payment Agent is '.$paymentAgent);
            } catch (\Exception $e) {
                $this->logger->info('Update Order Payment Agent For #'. $order->getIncrementId(). ' failed');
                $this->logger->info('Error: '.$e->getMessage());
            }
        }

        return $paymentAgent;
    }
}
