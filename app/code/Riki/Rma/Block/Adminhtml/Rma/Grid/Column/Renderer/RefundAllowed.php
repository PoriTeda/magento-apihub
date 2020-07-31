<?php

namespace Riki\Rma\Block\Adminhtml\Rma\Grid\Column\Renderer;

class RefundAllowed  extends \Magento\Backend\Block\Widget\Grid\Column\Renderer\Options\Extended
{
    /**
     * @var \Riki\Framework\Helper\Scope
     */
    protected $scopeHelper;

    /**
     * @var \Riki\Rma\Helper\Amount
     */
    protected $amountHelper;

    /**
     *
     * RefundAllowed constructor.
     *
     * @param \Riki\Framework\Helper\Scope $scopeHelper
     * @param \Magento\Backend\Block\Context $context
     * @param \Magento\Backend\Block\Widget\Grid\Column\Renderer\Options\Converter $converter
     * @param \Riki\Rma\Helper\Amount $amountHelper
     * @param array $data
     */
    public function __construct(
        \Riki\Framework\Helper\Scope $scopeHelper,
        \Magento\Backend\Block\Context $context,
        \Magento\Backend\Block\Widget\Grid\Column\Renderer\Options\Converter $converter,
        \Riki\Rma\Helper\Amount $amountHelper,
        array $data = [])
    {
        $this->scopeHelper = $scopeHelper;
        $this->amountHelper = $amountHelper;
        parent::__construct($context, $converter, $data);
    }

    public function render(\Magento\Framework\DataObject $row)
    {
        $func = \Riki\Rma\Controller\Adminhtml\Refund\Export\Csv::class . '::execute';
        if ($this->scopeHelper->isInFunction($func)) {
            return parent::render($row);
        }
        return $row->getData('refund_allowed') ? __('Yes') : __('No');
    }
}