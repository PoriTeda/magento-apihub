<?php

namespace Riki\Rma\Block\Adminhtml\Rma\Edit\Tab\Items\Grid\Renderer;

class ReturnWrappingFinal extends \Magento\Backend\Block\Widget\Grid\Column\Renderer\AbstractRenderer
{
    /**
     * {@inheritdoc}
     */
    public function render(\Magento\Framework\DataObject $row)
    {
        return '<div data-bind="scope: \'returnWrappingItemExpr'. $row->getId() . '\'"><span data-bind="text: result"></span></div>';
    }
}