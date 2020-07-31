<?php

namespace Riki\Sales\Plugin;


class BeforeSendMailOrderChange
{
    /**
     * @var \Magento\Framework\Registry
     */
    protected $_coreRegistry;
    /**
     * @var \Riki\SubscriptionEmail\Model\Order\Email\Sender\OrderSender
     */
    protected $_orderSender;

    /**
     * BeforeSendMailOrderChange constructor.
     * @param \Magento\Framework\Registry $coreRegistry
     * @param \Riki\SubscriptionEmail\Model\Order\Email\Sender\OrderSender $orderSender
     */
    public function __construct(
        \Magento\Framework\Registry $coreRegistry,
        \Riki\SubscriptionEmail\Model\Order\Email\Sender\OrderSender $orderSender
    )
    {
        $this->_coreRegistry = $coreRegistry;
        $this->_orderSender = $orderSender;
    }

    /**
     * Check send email confirm if it's does not send
     */
    public function afterSendMailOrderChange(
        \Riki\Sales\Helper\Email $subject,
        $orderId,
        $emailTemplateId = null
    )
    {
        /**@var \Riki\Sales\Model\Order $order */
        $order = $subject->getOrderData();
        if ($order && !$order->getEmailSent()) {
            $this->_coreRegistry->register('send_mail_confirm_before_email_cancel', $order->getEntityId());
            $this->_orderSender->send($order, true);
        }
        return true;
    }

}