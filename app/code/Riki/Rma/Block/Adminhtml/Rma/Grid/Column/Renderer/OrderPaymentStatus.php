<?php

namespace Riki\Rma\Block\Adminhtml\Rma\Grid\Column\Renderer;

class OrderPaymentStatus extends \Magento\Backend\Block\Widget\Grid\Column\Renderer\AbstractRenderer
{
    /**
     * @var \Riki\Framework\Helper\Scope
     */
    protected $scopeHelper;

    /**
     * @var \Riki\Rma\Helper\Refund
     */
    protected $refundHelper;

    /**
     * Creditmemo constructor.
     *
     * @param \Riki\Framework\Helper\Scope $scopeHelper
     * @param \Magento\Backend\Block\Context $context
     * @param array $data
     */
    public function __construct(
        \Riki\Framework\Helper\Scope $scopeHelper,
        \Magento\Backend\Block\Context $context,
        \Riki\Rma\Helper\Refund $refundHelper,
        array $data = []
    )
    {
        $this->scopeHelper = $scopeHelper;
        $this->refundHelper = $refundHelper;
        parent::__construct($context, $data);
    }

    /**
     * {@inheritdoc}
     */
    public function render(\Magento\Framework\DataObject $row)
    {
        $title = $this->getTitleOrderPaymentStatus($row);
        $func = \Riki\Rma\Controller\Adminhtml\Refund\Export\Csv::class . '::execute';
        if ($this->scopeHelper->isInFunction($func)) {
            return $title;
        }
        return $title;
    }

    /**
     * Get title order payment status
     *
     * @param $row
     * @return null
     */
    public function getTitleOrderPaymentStatus($row)
    {
        $order = $row->getOrder();
        if ($order) {
            $payment = $order->getPayment();
            if ($payment) {
                return $this->getTitleMethod($payment);
            }
        }
        return null;
    }

    /**
     * @param $payment
     * @return mixed
     */
    public function getTitleMethod($payment)
    {
        $methods = $this->refundHelper->getEnablePaymentMethods();
        $code = $payment->getMethodInstance()->getCode();
        if (isset($methods[$code]) && isset($methods[$code]['title'])) {
            return __(trim($methods[$code]['title']));
        }

        return $payment->getMethodInstance()->getTitle();
    }

}