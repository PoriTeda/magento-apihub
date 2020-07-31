<?php
namespace Riki\Rma\Block\Adminhtml\Rma\Grid\Column\Renderer;

use Riki\Rma\Api\Data\Rma\RefundStatusInterface;

class RefundMethod extends \Magento\Backend\Block\Widget\Grid\Column\Renderer\Options\Extended
{
    /**
     * @var $completedRefundStatus array
     */
    private $completedRefundStatus = [
        RefundStatusInterface::BT_COMPLETED,
        RefundStatusInterface::CARD_COMPLETED,
        RefundStatusInterface::CHECK_ISSUED,
        RefundStatusInterface::MANUALLY_CARD_COMPLETED
    ];
    /**
     * @var \Riki\Rma\Helper\Refund
     */
    protected $refundHelper;

    /**
     * @var \Riki\Rma\Helper\Data
     */
    protected $dataHelper;

    /**
     * @var \Riki\Framework\Helper\Scope
     */
    protected $scopeHelper;

    /**
     * RefundMethod constructor.
     *
     * @param \Riki\Framework\Helper\Scope $scopeHelper
     * @param \Riki\Rma\Helper\Data $dataHelper
     * @param \Riki\Rma\Helper\Refund $refundHelper
     * @param \Magento\Backend\Block\Context $context
     * @param \Magento\Backend\Block\Widget\Grid\Column\Renderer\Options\Converter $converter
     * @param array $data
     */
    public function __construct(
        \Riki\Framework\Helper\Scope $scopeHelper,
        \Riki\Rma\Helper\Data $dataHelper,
        \Riki\Rma\Helper\Refund $refundHelper,
        \Magento\Backend\Block\Context $context,
        \Magento\Backend\Block\Widget\Grid\Column\Renderer\Options\Converter $converter,
        array $data = [])
    {
        $this->scopeHelper = $scopeHelper;
        $this->dataHelper = $dataHelper;
        $this->refundHelper = $refundHelper;
        parent::__construct($context, $converter, $data);
    }

    /**
     * {@inheritdoc}
     *
     * @param \Magento\Framework\DataObject $row
     *
     * @return string
     */
    public function render(\Magento\Framework\DataObject $row)
    {
        if (!$this->_authorization->isAllowed('Riki_Rma::rma_refund_actions_save_method')) {
            return parent::render($row);
        }
        if (!$row->getData('refund_method')) {
            return parent::render($row);
        }

        $func = \Riki\Rma\Controller\Adminhtml\Refund\Export\Csv::class . '::execute';
        if ($this->scopeHelper->isInFunction($func)) {
            return parent::render($row);
        }
        if (in_array($row->getData('refund_status'), $this->completedRefundStatus)) {
            $refundMethod = $row->getData('refund_method');
            $allPaymentMethods = $this->refundHelper->getPaymentMethods();
            if (isset($allPaymentMethods[$refundMethod]['title'])) {
                return $allPaymentMethods[$refundMethod]['title'];
            }
        }
        $methodCode = $this->dataHelper->getRmaOrderPaymentMethodCode($row);
        $methods = $this->refundHelper->getRefundMethodsByPaymentMethod($methodCode, $row);
        $html = '<select onchange="javascript:updateRefundMethod(this)" data-id="' . $row->getId() .'" data-increment-id="' . $row->getData('increment_id') . '">';
        foreach ($methods as $value => $label) {
            $html .= '<option value="' . $value . '" '
                . ($row->getData('refund_method') == $value ? 'data-selected selected': '')
                . ' data-title="' . $label .'">' . $label . '</option>';
        }
        $html .= '</select>';

        return $html;
    }
}
