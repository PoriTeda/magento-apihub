<?php
namespace Riki\Subscription\Observer;

use Magento\Framework\Event\Observer as EventObserver;
use Magento\Framework\Event\ObserverInterface;
use Riki\SubscriptionCourse\Model\Course\Type;

class HandleDelayPaymentCaptureFail implements ObserverInterface
{
    /**
     * @var \Riki\Subscription\Logger\DelayPayment
     */
    private $logger;

    /**
     * HandleDelayPaymentCaptureFail constructor.
     * @param \Riki\Subscription\Logger\DelayPayment $logger
     */
    public function __construct(
        \Riki\Subscription\Logger\DelayPayment $logger
    ) {
        $this->logger = $logger;
    }

    /**
     * @param EventObserver $observer
     */
    public function execute(EventObserver $observer)
    {
        /** @var \Magento\Sales\Model\Order $order */
        $order = $observer->getEvent()->getOrder();

        if ($order->getRikiType() == Type::TYPE_ORDER_DELAY_PAYMENT) {
            $error = $observer->getEvent()->getErrorMessage();
            $this->logger->info(__(
                'Order %1 cannot captured successfully due to issue from Paygent: %2',
                $order->getIncrementId(),
                $error
            ));
        }
    }
}