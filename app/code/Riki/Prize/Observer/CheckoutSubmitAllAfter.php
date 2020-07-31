<?php
namespace Riki\Prize\Observer;

class CheckoutSubmitAllAfter implements \Magento\Framework\Event\ObserverInterface
{
    protected $_prizeHelper;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;

    public function __construct(
        \Riki\Prize\Helper\Prize $prizeHelper,
        \Psr\Log\LoggerInterface $loggerInterface
    )
    {
        $this->_prizeHelper = $prizeHelper;
        $this->logger = $loggerInterface;
    }

    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        /** @var \Magento\Sales\Model\Order $order */
        $order = $observer->getEvent()->getOrder();
        /*Simulate doesn't need to add Prize and Winner */
        if ($order instanceof \Riki\Subscription\Model\Emulator\Order) {
            return;
        }
        try{
            $this->_prizeHelper->applyToOrder($order);
        }catch (\Exception $e){
            $this->logger->critical($e);
        }
    }
}
