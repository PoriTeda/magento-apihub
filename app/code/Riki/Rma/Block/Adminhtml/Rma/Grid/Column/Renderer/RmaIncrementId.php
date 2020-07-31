<?php
namespace Riki\Rma\Block\Adminhtml\Rma\Grid\Column\Renderer;

class RmaIncrementId extends \Magento\Backend\Block\Widget\Grid\Column\Renderer\AbstractRenderer
{
    /**
     * @var \Riki\Framework\Helper\Scope
     */
    protected $scopeHelper;

    /**
     * RmaIncrementId constructor.
     *
     * @param \Riki\Framework\Helper\Scope $scopeHelper
     * @param \Magento\Backend\Block\Context $context
     * @param array $data
     */
    public function __construct(
        \Riki\Framework\Helper\Scope $scopeHelper,
        \Magento\Backend\Block\Context $context,
        array $data = []
    )
    {
        $this->scopeHelper = $scopeHelper;
        parent::__construct($context, $data);
    }


    /**
     * {@inheritdoc}
     */
    public function render(\Magento\Framework\DataObject $row)
    {
        if ($this->scopeHelper->isInFunction(\Riki\Rma\Controller\Adminhtml\Refund\Export\Csv::class . '::execute')) {
            return parent::render($row);
        }
        return $row->getData('entity_id')
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
        $url = $this->getUrl('adminhtml/rma/edit', [
            'id' => $row->getData('entity_id')
        ]);
        $html = '<a target="_blank" href="%s" title="%s">%s</a>';

        return sprintf($html, $url, $row->getData('increment_id'), $row->getData('increment_id'));
    }
}