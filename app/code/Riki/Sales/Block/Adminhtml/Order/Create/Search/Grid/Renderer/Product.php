<?php

namespace Riki\Sales\Block\Adminhtml\Order\Create\Search\Grid\Renderer;


class Product extends \Magento\Sales\Block\Adminhtml\Order\Create\Search\Grid\Renderer\Product
{
    /**
     * Render product name to add Configure link
     *
     * @param   \Magento\Framework\DataObject $row
     * @return  string
     */
    public function render(\Magento\Framework\DataObject $row)
    {
        $rendered = \Magento\Backend\Block\Widget\Grid\Column\Renderer\Text::render($row);

        $isConfigurable = $row->canConfigure();
        $style = $isConfigurable ? '' : 'disabled';
        $prodAttributes = $isConfigurable ? sprintf(
            'list_type = "product_to_add" product_id = %s',
            $row->getId()
        ) : 'disabled="disabled"';
        return sprintf(
            '<a href="javascript:void(0)" class="action-configure %s" %s></a>',
            $style,
            $prodAttributes,
            ''
        ) . $rendered;
    }
}
