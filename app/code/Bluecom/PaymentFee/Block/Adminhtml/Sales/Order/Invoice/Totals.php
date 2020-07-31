<?php

namespace Bluecom\PaymentFee\Block\Adminhtml\Sales\Order\Invoice;

class Totals extends \Magento\Framework\View\Element\Template
{
    /**
     * Order invoice
     *
     * @var \Magento\Sales\Model\Order\Invoice|null
     */
    protected $_invoice = null;

    /**
     * Source
     *
     * @var \Magento\Framework\DataObject
     */
    protected $_source;

    /**
     * OrderFee constructor.
     *
     * @param \Magento\Framework\View\Element\Template\Context $context context
     * @param array                                            $data    data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        array $data = []
    ) {
        parent::__construct($context, $data);
    }

    /**
     * Get data (totals) source model
     *
     * @return \Magento\Framework\DataObject
     */
    public function getSource()
    {
        return $this->getParentBlock()->getSource();
    }

    /**
     * Get invoice
     *
     * @return mixed
     */
    public function getInvoice()
    {
        return $this->getParentBlock()->getInvoice();
    }

    /**
     * Initialize payment fee totals
     *
     * @return $this
     */
    public function initTotals()
    {
        $this->getParentBlock();
        $this->getInvoice();
        $this->getSource();

        if (!$this->getSource()->getFee()) {
            return $this;
        }
        $fee = new \Magento\Framework\DataObject(
            [
                'code' => 'paymentfee',
                'strong' => false,
                'value' => $this->getSource()->getFee(),
                'label' => __('Payment Fee'),
            ]
        );

        $this->getParentBlock()->addTotalBefore($fee, 'grand_total');
        return $this;
    }
}