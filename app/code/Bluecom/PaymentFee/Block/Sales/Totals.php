<?php

namespace Bluecom\PaymentFee\Block\Sales;

class Totals extends \Magento\Framework\View\Element\Template
{

    /**
     * Order
     *
     * @var \Magento\Sales\Model\Order
     */
    protected $_order;

    /**
     * Data object
     *
     * @var \Magento\Framework\DataObject
     */
    protected $_source;

    /**
     * Helper data
     *
     * @var \Bluecom\PaymentFee\Helper\Data
     */
    protected $_helperData;

    /**
     * OrderFee constructor.
     *
     * @param \Magento\Framework\View\Element\Template\Context $context    context
     * @param \Bluecom\PaymentFee\Helper\Data                  $helperData helper data
     * @param array                                            $data       data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Bluecom\PaymentFee\Helper\Data $helperData,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->_helperData = $helperData;
    }

    /**
     * Check if we nedd display full tax total info
     *
     * @return bool
     */
    public function displayFullSummary()
    {
        return true;
    }

    /**
     * Get data (totals) source model
     *
     * @return \Magento\Framework\DataObject
     */
    public function getSource()
    {
        return $this->_source;
    }

    /**
     * Get Store
     *
     * @return \Magento\Store\Model\Store
     */
    public function getStore()
    {
        return $this->_order->getStore();
    }

    /**
     * Get order
     *
     * @return \Magento\Sales\Model\Order
     */
    public function getOrder()
    {
        return $this->_order;
    }

    /**
     * Get Label Properties
     *
     * @return array
     */
    public function getLabelProperties()
    {
        return $this->getParentBlock()->getLabelProperties();
    }

    /**
     * Get value properties
     *
     * @return array
     */
    public function getValueProperties()
    {
        return $this->getParentBlock()->getValueProperties();
    }

    /**
     * Initialize payment fee totals
     *
     * @return $this
     */
    public function initTotals()
    {
        $parent = $this->getParentBlock();
        $this->_order = $parent->getOrder();
        $this->_source = $parent->getSource();
        if (!$this->_source->getFee()) {
            return $this;
        }
        $fee = new \Magento\Framework\DataObject(
            [
                'code' => 'paymentfee',
                'strong' => false,
                'value' => $this->_source->getFee(),
                'label' => __('Payment Fee'),
            ]
        );

        $parent->addTotal($fee, 'fee');
        return $this;
    }
}


