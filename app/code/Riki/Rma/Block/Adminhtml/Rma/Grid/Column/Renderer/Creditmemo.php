<?php
namespace Riki\Rma\Block\Adminhtml\Rma\Grid\Column\Renderer;

class Creditmemo extends \Magento\Backend\Block\Widget\Grid\Column\Renderer\AbstractRenderer
{
    /**
     * @var \Riki\Framework\Helper\Scope
     */
    protected $scopeHelper;

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
        array $data = []
    ) {
        $this->scopeHelper = $scopeHelper;
        parent::__construct($context, $data);
    }

    /**
     * {@inheritdoc}
     */
    public function render(\Magento\Framework\DataObject $row)
    {
        $func = \Riki\Rma\Controller\Adminhtml\Refund\Export\Csv::class . '::execute';
        if ($this->scopeHelper->isInFunction($func)) {
            return $row->getData('creditmemo_increment_id');
        }

        return $row->getData('creditmemo_id')
            ? $this->getHtml($row)
            : parent::render($row);
    }

    /**
     * Get html content
     *
     * @param \Magento\Framework\DataObject $row
     * @return string
     */
    protected function getHtml(\Magento\Framework\DataObject $row)
    {
        $url = $this->getUrl('sales/order_creditmemo/view', [
            'creditmemo_id' => $row->getData('creditmemo_id')
        ]);
        $html = '<a target="_blank" href="%s" title="%s">%s</a>';

        return sprintf($html, $url, $row->getData('creditmemo_increment_id'), $row->getData('creditmemo_increment_id'));
    }
}