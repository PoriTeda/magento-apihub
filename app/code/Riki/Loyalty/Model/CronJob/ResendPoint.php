<?php

namespace Riki\Loyalty\Model\CronJob;

class ResendPoint
{
    /**
     * @var \Riki\Loyalty\Model\Conversion
     */
    protected $_conversionModel;

    /**
     * @var \Riki\Loyalty\Model\Reward
     */
    protected $_rewardModel;

    /**
     * @var \Magento\Sales\Model\ResourceModel\Order\Collection
     */
    protected $_orderCollection;

    /**
     * @var \Riki\Loyalty\Logger\Cron
     */
    protected $_logger;

    /**
     * ResendPoint constructor.
     * @param \Riki\Loyalty\Model\Conversion $conversion
     * @param \Magento\Sales\Model\ResourceModel\Order\CollectionFactory $orderCollection
     * @param \Riki\Loyalty\Model\Reward $reward
     * @param \Riki\Loyalty\Logger\Cron $logger
     */
    public function __construct(
        \Riki\Loyalty\Model\Conversion $conversion,
        \Magento\Sales\Model\ResourceModel\Order\CollectionFactory $orderCollection,
        \Riki\Loyalty\Model\Reward $reward,
        \Riki\Loyalty\Logger\Cron $logger
    ) {
        $this->_conversionModel = $conversion;
        $this->_orderCollection = $orderCollection;
        $this->_rewardModel = $reward;
        $this->_logger = $logger;
    }

    /**
     * Resend point to consumerDB
     *
     * @return $this
     */
    public function execute()
    {
        $this->_logger->info('Start cron resend shopping point to consumerDB');
        $ordersNo = $this->_rewardModel->getResource()->getOrderInError();
        if (!sizeof($ordersNo)) {
            return $this;
        }
        $orderCollection = $this->_orderCollection->create();
        $orderCollection->addFieldToFilter('increment_id', ['in' => $ordersNo]);
        if (!$orderCollection->getSize()) {
            return $this;
        }
        foreach ($orderCollection as $order) {
            $this->_conversionModel->toShoppingPoint($order, \Riki\Loyalty\Model\Reward::STATUS_ERROR);
        }
        $this->_logger->info('Order was resent shopping point: '. implode(', ', $ordersNo));
        return $this;
    }
}
