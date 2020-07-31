<?php
namespace Riki\Sales\Block\Adminhtml\AdvancedCheckout\Manage\Grid\Renderer;

class Product extends \Magento\AdvancedCheckout\Block\Adminhtml\Manage\Grid\Renderer\Product
{
    /**
     * Render product name to add Configure link
     *
     * @param \Magento\Framework\DataObject $row
     * @return  string
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function render(\Magento\Framework\DataObject $row): string
    {
        $rendered = \Magento\Backend\Block\Widget\Grid\Column\Renderer\Text::render($row);

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
            '<a href="javascript:void(0)" %s class="action-configure %s"></a>',
            $style,
            $prodAttributes,
            ''
        ) . $rendered;
    }
}
