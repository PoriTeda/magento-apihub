<?php
namespace Riki\AdvancedInventory\Block\Adminhtml\OutOfStock\Grid\Column\Renderer;

class Type extends \Magento\Backend\Block\Widget\Grid\Column\Renderer\AbstractRenderer
{

    /**
     * {@inheritdoc}
     */
    public function render(\Magento\Framework\DataObject $row)
    {
        if (!empty($row->getData('salesrule_id'))) {
            return __('Free Gift');
        }
        if (!empty($row->getData('prize_id'))) {
            return __('Free Prize');
        }

        return __('Normal Product');
    }
}