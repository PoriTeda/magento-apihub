<?php

namespace Wyomind\AdvancedInventory\Block\Adminhtml\Stocks\Renderer;

class StoreViewQty extends \Magento\Backend\Block\Widget\Grid\Column\Renderer\AbstractRenderer
{

    protected $_helperCore;
    protected $_helperData;
    protected $_helperPermissions;
    protected $_stockModel;
    protected $_posModel;

    public function __construct(
        \Magento\Backend\Block\Context $context,
        \Wyomind\Core\Helper\Data $helperCore,
        \Wyomind\AdvancedInventory\Helper\Data $helperData,
        \Wyomind\AdvancedInventory\Helper\Permissions $helperPermissions,
        \Wyomind\AdvancedInventory\Model\Stock $stockModel,
        \Wyomind\PointOfSale\Model\PointOfSale $posModel,
        array $data = []
    ) {

        $this->_helperCore = $helperCore;
        $this->_helperData = $helperData;
        $this->_helperPermissions = $helperPermissions;
        $this->_stockModel = $stockModel;
        $this->_posModel = $posModel;
        parent::__construct($context, $data);
    }

    public function render(\Magento\Framework\DataObject $row)
    {
        $html = null;
        $inventory = $this->_stockModel->getStockSettings($row->getId());
        if ($inventory->getManagedAtProductLevel()) {
            if (in_array($row->getTypeId(), $this->_helperData->getProductTypes())) {
                if ($this->getColumn()->getStoreId()) {
                    $places = $this->_posModel->getPlacesByStoreId($this->getColumn()->getStoreId());
                } else {
                    $places = $this->_posModel->getPlaces();
                }


                $html = (int) 0;
                foreach ($places as $p) {
                    if ($this->_helperPermissions->isAllowed($p->getPlaceId())) {
                        $data = $this->_stockModel->getStockByProductIdAndPlaceId($row->getId(), $p->getPlaceId());

                        $html += $data->getQuantityInStock();
                    }
                }
            } else {
                $html = __("-");
            }
            $enabled = ($row->getMultistockEnabled()) ? 'enabled' : 'disabled';
            return "<span class='GlobalQty' id='GlobalQty_" . $row->getId() . "' multistock='" . $enabled . "'>" . $html . "</span>";
        } else {
            return __("X");
        }
    }
}
