<?php

namespace Bluecom\PaymentFee\Block\Adminhtml\Sales;

class Totals extends \Magento\Framework\View\Element\Template
{

    /**
     * Helper
     *
     * @var \Bluecom\PaymentFee\Helper\Data
     */
    protected $_dataHelper;

    /**
     * Currency
     *
     * @var \Magento\Directory\Model\Currency
     */
    protected $_currency;

    /**
     * Init
     *
     * @param \Magento\Framework\View\Element\Template\Context $context    context
     * @param \Bluecom\PaymentFee\Helper\Data                  $dataHelper data helper
     * @param \Magento\Directory\Model\Currency                $currency   currency
     * @param array                                            $data       data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Bluecom\PaymentFee\Helper\Data $dataHelper,
        \Magento\Directory\Model\Currency $currency,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->_dataHelper = $dataHelper;
        $this->_currency = $currency;
    }

    /**
     * Retrieve current order model instance
     *
     * @return \Magento\Sales\Model\Order
     */
    public function getOrder()
    {
        return $this->getParentBlock()->getOrder();
    }

    /**
     * Get Source
     *
     * @return mixed
     */
    public function getSource()
    {
        return $this->getParentBlock()->getSource();
    }

    /**
     * Get Currency Symbol
     *
     * @return string
     */
    public function getCurrencySymbol()
    {
        return $this->_currency->getCurrencySymbol();
    }

    /**
     * Init Totals
     *
     * @return $this
     */
    public function initTotals()
    {
        $this->getParentBlock();
        $this->getOrder();
        $this->getSource();

        $total = new \Magento\Framework\DataObject(
            [
                'code' => 'paymentfee',
                'value' => $this->getSource()->getFee(),
                'base_value' => $this->getSource()->getBaseFee(),
                'label' => __('Payment Fee'),
            ]
        );
        $this->getParentBlock()->addTotal($total, 'shipping');

        return $this;
    }
}