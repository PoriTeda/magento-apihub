<?php
namespace Riki\ThirdPartyImportExport\Block\Adminhtml\Order\Grid\Renderer;

class OrderTotal extends \Magento\Backend\Block\Widget\Grid\Column\Renderer\AbstractRenderer
{
    public function render(\Magento\Framework\DataObject $row)
    {
        return $row->formatPrice($row->resetGrandTotal()->getGrandTotal());
    }
}
