<?php

namespace Bluecom\Paygent\Model;

class Handle
{
    /**
     * @var \Magento\Sales\Model\Order
     */
    protected $orderModel;
    /**
     * @var \Magento\Sales\Model\Order\Email\Sender\OrderSender
     */
    protected $orderSender;
    /**
     * @var \Riki\Sales\Model\OrderCutoffDate
     */
    protected $cutoffDate;
    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;

    /**
     * @var ScoreFactory
     */
    protected $_scoreFactory;

    /**
     * Handle constructor.
     *
     * @param \Magento\Sales\Model\Order                          $order        Order
     * @param \Magento\Sales\Model\Order\Email\Sender\OrderSender $orderSender  OrderSender
     * @param \Psr\Log\LoggerInterface                            $logger       LoggerInterface
     * @param \Riki\Fraud\Model\ScoreFactory                      $scoreFactory ScoreFactory
     */
    public function __construct(
        \Magento\Sales\Model\Order $order,
        \Magento\Sales\Model\Order\Email\Sender\OrderSender $orderSender,
        \Psr\Log\LoggerInterface $logger,
        \Riki\Fraud\Model\ScoreFactory $scoreFactory
    ) {
        $this->orderModel = $order;
        $this->orderSender = $orderSender;
        $this->logger = $logger;
        $this->_scoreFactory = $scoreFactory;
    }

}