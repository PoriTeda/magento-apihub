<?php
namespace Riki\ThirdPartyImportExport\Block\Adminhtml\Order\Grid\Renderer;

class ShippingName extends \Magento\Backend\Block\Widget\Grid\Column\Renderer\AbstractRenderer
{
    public function render(\Magento\Framework\DataObject $row)
    {
        $shipping = $row->getShipping();
        if (!$shipping) {
            return '';
        }

        return $shipping->getData('address_last_name') . ' ' . $shipping->getData('address_first_name');
    }
}
