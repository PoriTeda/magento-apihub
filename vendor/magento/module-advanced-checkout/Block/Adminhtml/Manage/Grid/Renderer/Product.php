<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

/**
 * Adminhtml grid product name column renderer
 *
 * @author     Magento Core Team <core@magentocommerce.com>
 */
namespace Magento\AdvancedCheckout\Block\Adminhtml\Manage\Grid\Renderer;

class Product extends \Magento\Backend\Block\Widget\Grid\Column\Renderer\Text
{
    /**
     * Render product name to add Configure link
     *
     * @param   \Magento\Framework\DataObject $row
     * @return  string
     */
    public function render(\Magento\Framework\DataObject $row)
    {
        $rendered = parent::render($row);
        $listType = $this->getColumn()->getGrid()->getListType();
        if ($row instanceof \Magento\Catalog\Model\Product) {
            $product = $row;
        } elseif (($row instanceof \Magento\Wishlist\Model\Item) || ($row instanceof \Magento\Sales\Model\Order\Item)) {
            $product = $row->getProduct();
        }
        if ($product->canConfigure()) {
            $style = '';
            $prodAttributes = sprintf('list_type = "%s" item_id = %s', $listType, $row->getId());
        } else {
            $style = 'disabled';
            $prodAttributes = 'disabled="disabled"';
        }
        return sprintf(
            '<a href="javascript:void(0)" %s class="action-configure %s">%s</a>',
            $prodAttributes,
            $style,
            __('Configure')
        ) . $rendered;
    }
}
