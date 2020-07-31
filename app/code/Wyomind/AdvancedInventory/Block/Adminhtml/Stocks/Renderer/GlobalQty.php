<?php

namespace Wyomind\AdvancedInventory\Block\Adminhtml\Stocks\Renderer;

class GlobalQty extends \Magento\Backend\Block\Widget\Grid\Column\Renderer\AbstractRenderer
{

    protected $_helperCore;
    protected $_stockModel;
    protected $_helperData;

    public function __construct(
        \Magento\Backend\Block\Context $context,
        \Wyomind\Core\Helper\Data $helperCore,
        \Wyomind\AdvancedInventory\Model\Stock $stockModel,
        \Wyomind\AdvancedInventory\Helper\Data $helperData,
        array $data = []
    ) {
        $this->_helperCore = $helperCore;
        $this->_stockModel = $stockModel;
        $this->_helperData = $helperData;
        parent::__construct($context, $data);
    }

    public function render(\Magento\Framework\DataObject $row)
    {
        $backorderable = null;
        $inventory = $this->_stockModel->getStockSettings($row->getId());
        if (!in_array($row->getTypeId(), $this->_helperData->getProductTypes())) {
            return __("-");
        } else {
            if ($inventory->getManagedAtProductLevel()) {
                if ($inventory->getMultistockEnabled()) {
                    $html = $this->_helperData->qtyFormat($inventory->getQuantityInStock(), $inventory->getIsQtyDecimal());
                    $display = 'none';
                } else {
                    $display = 'block';
                    $html = "<input class = 'keydown inventory_input' type = 'text' value = '" . $this->_helperData->qtyFormat($row->getQty(), $row->getIsQtyDecimal()) . "' />";
                }
                if ($inventory->getBackorderableAtProductLevel() && !$inventory->getMultistockEnabled()) {
                    $backorderable = "<div class='backorderable ai-msg warning' style='display:" . $display . "' title='" . __('Backorder allowed') . "' >Backorder allowed</div>";
                }

                $enabled = ($inventory->getMultistockEnabled()) ? 'enabled' : 'disabled';
                return "<span class = 'GlobalQty' id = 'GlobalQty_" . $row->getId() . "' multistock = '" . $enabled . "'>" . $html . "</span>" . $backorderable;
            } else {
                return __("X");
            }
        }
    }
}
