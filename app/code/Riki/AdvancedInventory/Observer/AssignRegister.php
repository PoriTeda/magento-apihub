<?php
namespace Riki\AdvancedInventory\Observer;

class AssignRegister implements \Magento\Framework\Event\ObserverInterface
{
    const WAITING = 1;

    const PROCESSING = 2;

    const ASSIGNED = 3;

    protected $registry;

    /**
     * {@inheritdoc}
     *
     * @param \Magento\Framework\Event\Observer $observer
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        /** @var \Magento\Sales\Model\Order $order */
        $order = $observer->getEvent()->getOrder();
        if (!$order instanceof \Magento\Sales\Model\Order) {
            return;
        }

        if (!$order->getQuoteId()) {
            return;
        }

        if ($this->getWaitingByQuoteId($order->getQuoteId())) {
            return;
        }

        $this->setWaitingByQuoteId($order->getQuoteId());
    }

    /**
     * Get waiting cart to run assignation
     *
     * @param $quoteId
     *
     * @return bool
     */
    public function getWaitingByQuoteId($quoteId)
    {
        return isset($this->registry[$quoteId]) && $this->registry[$quoteId] == static::WAITING;
    }


    /**
     * Set cart as waiting
     *
     * @param $quoteId
     *
     * @return $this
     */
    public function setWaitingByQuoteId($quoteId)
    {
        $this->registry[$quoteId] = static::WAITING;

        return $this;
    }

    /**
     * Set cart as processing
     *
     * @param $quoteId
     *
     * @return $this
     */
    public function setProcessingByQuoteId($quoteId)
    {
        if (!isset($this->registry[$quoteId])) {
            return $this;
        }

        $this->registry[$quoteId] = static::PROCESSING;

        return $this;
    }

    /**
     * Set cart as assigned
     *
     * @param $quoteId
     *
     * @return $this
     */
    public function setAssignedByQuoteId($quoteId)
    {
        if (!isset($this->registry[$quoteId])) {
            return $this;
        }

        $this->registry[$quoteId] = static::ASSIGNED;

        return $this;
    }
}