<?php

namespace Bluecom\Paygent\Observer;

class OrderCancelAfter implements \Magento\Framework\Event\ObserverInterface
{
    /**
     * @var \Bluecom\Paygent\Model\Reauthorize
     */
    protected $reauthorize;
    /**
     * @var $logger \Psr\Log\LoggerInterface
     */
    protected $logger;

    public function __construct(
        \Bluecom\Paygent\Model\Reauthorize $reauthorize,
        \Psr\Log\LoggerInterface $loggerInterface
    ) {
        $this->reauthorize = $reauthorize;
        $this->logger = $loggerInterface;
    }

    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $order = $observer->getEvent()->getOrder();        
        if (!$order->getId()) {
            return false;
        }
        $payment = $order->getPayment();
        if ($payment->getMethod() != \Bluecom\Paygent\Model\Paygent::CODE ) {
            return false;
        }

        $data = $this->reauthorize->getCollection()
            ->addFieldToFilter('order_id',$order->getId())
            ->setPageSize(1);
        if ($data->getSize()) {
            $row = $data->getFirstItem();
            try {
                $row->delete();
            } catch (\Exception $e) {
                $this->logger->critical($e);
            }
        }
        return $this;
    }
}
