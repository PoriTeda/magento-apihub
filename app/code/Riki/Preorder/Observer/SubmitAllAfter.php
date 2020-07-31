<?php

namespace Riki\Preorder\Observer;

class SubmitAllAfter implements \Magento\Framework\Event\ObserverInterface
{
    /**
     * @var \Riki\Preorder\Helper\Data $helper
     */
    protected $dataHelper;

    public function __construct(\Riki\Preorder\Helper\Data $helper)
    {
        $this->dataHelper = $helper;
    }

    /**
     * @param \Magento\Framework\Event\Observer $observer
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        if (!$this->dataHelper->preordersEnabled()) {
            return;
        }
        $order = $observer->getEvent()->getOrder();
        if($order instanceof \Riki\Subscription\Model\Emulator\Order){
            return;
        }
        $this->dataHelper->checkNewOrder($order);
    }

}
