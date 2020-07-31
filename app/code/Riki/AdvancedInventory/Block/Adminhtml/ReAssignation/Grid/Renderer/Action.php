<?php

namespace Riki\AdvancedInventory\Block\Adminhtml\ReAssignation\Grid\Renderer;

use Riki\AdvancedInventory\Model\Config\Source\ReAssignation\Status;

class Action extends \Magento\Backend\Block\Widget\Grid\Column\Renderer\AbstractRenderer
{

    /**
     * Render action
     *
     * @param \Magento\Framework\DataObject $row
     * @return string
     */
    public function render(\Magento\Framework\DataObject $row)
    {
        if ($row->getData('status') == Status::STATUS_WAITING) {
            $url = $this->getUrl('*/*/delete', ['id'    =>  $row->getId()]);

            return '<a href=" ' . $url . '" onclick="return confirm(\' ' . __('Are you sure want to delete it?') . ' \')">' . __('Delete') . '</a>';
        }

        return '';
    }
}
