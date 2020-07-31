<?php
namespace Riki\Wamb\Observer;

class ApplyWambRule implements \Magento\Framework\Event\ObserverInterface
{
    /**
     * @var \Riki\Wamb\Model\RuleApplier
     */
    protected $ruleApplier;

    /**
     * @var \Riki\Wamb\Helper\Logger
     */
    protected $loggerHelper;

    /**
     * ApplyWambRule constructor.
     *
     * @param \Riki\Wamb\Helper\Logger $loggerHelper
     * @param \Riki\Wamb\Model\RuleApplier $ruleApplier
     */
    public function __construct(
        \Riki\Wamb\Helper\Logger $loggerHelper,
        \Riki\Wamb\Model\RuleApplier $ruleApplier
    ) {
        $this->loggerHelper = $loggerHelper;
        $this->ruleApplier = $ruleApplier;
    }

    /**
     * {@inheritdoc}
     *
     * @param \Magento\Framework\Event\Observer $observer
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $order = $observer->getEvent()->getOrder();
        if (!$order instanceof \Magento\Sales\Model\Order || !$order->getId()) {
            return;
        }

        try {
            $ruleId = $this->ruleApplier->validate($order);
            if (!$ruleId) {
                return;
            }

            $this->ruleApplier->apply($order, $ruleId);
        } catch (\Exception $e) {
            $this->loggerHelper->getGeneralLogger()->critical($e);
        }
    }
}