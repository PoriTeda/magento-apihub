<?php

namespace Wyomind\AdvancedInventory\Block\Adminhtml\Stocks\Renderer;

class Actions extends \Magento\Backend\Block\Widget\Grid\Column\Renderer\Action
{

    protected $_helperCore;
    protected $_helperData;
    protected $_posModel;
    protected $_helperPermissions;
    protected $_stockModel;

    public function __construct(
        \Magento\Backend\Block\Context $context,
        \Wyomind\Core\Helper\Data $helperCore,
        \Wyomind\AdvancedInventory\Helper\Data $helperData,
        \Wyomind\PointOfSale\Model\PointOfSale $posModel,
        \Wyomind\AdvancedInventory\Helper\Permissions $helperPermissions,
        \Wyomind\AdvancedInventory\Model\Stock $stockModel,
        \Magento\Framework\Json\EncoderInterface $jsonEncoder,
        array $data = []
    ) {
        $this->_adminStore = \Magento\Store\Model\Store::DEFAULT_STORE_ID;
        $this->_helperCore = $helperCore;
        $this->_helperData = $helperData;
        $this->_posModel = $posModel;
        $this->_helperPermissions = $helperPermissions;
        $this->_stockModel = $stockModel;
        parent::__construct($context, $jsonEncoder, $data);
    }

    public function getCurrentUrl($row)
    {
        $url = $this->getUrl(
            'advancedinventory/stocks/save',
            [
                'id' => $row->getId(),
                "is_admin" => $this->_helperPermissions->hasAllPermissions(),
                "store_id" => $this->getRequest()->getParam('store', 0)
            ]
        );
        return $url;
    }

    public function render(\Magento\Framework\DataObject $row)
    {

        $inventory = $this->_stockModel->getStockSettings($row->getId());
        $actions = [];
        if ($inventory->getManagedAtProductLevel()) {
            if (in_array($row->getTypeId(), $this->_helperData->getProductTypes())) {
                $actions[] = [
                    'url' => "javascript:InventoryManager.saveStocks('" . $this->getCurrentUrl($row) . "','" . $row->getId() . "')",
                    'caption' => __('Save change'),
                    'id' => 'save',
                    'params' => [
                        'url' => $this->getCurrentUrl($row),
                        'id' => $row->getId()
                    ]
                ];
                if ($this->_helperPermissions->hasAllPermissions()) {
                    if ($this->_posModel->getPlaces()->count()) {
                        if ($this->getRequest()->getParam('store') == $this->_adminStore) {
                            $actions[] = [
                                'caption' => (!$inventory->getMultistockEnabled()) ? __("Enable multi-stock") : __("Disable multi-stock"),
                                'url' => "javascript:InventoryManager.enableMultiStock('grid'," . $row->getId() . ")",
                                'id' => 'enable'
                            ];
                        }
                    }
                }
            }
        }
        if ($this->_helperPermissions->hasAllPermissions()) {
            $actions[] = [
                'url' => $this->getUrl('catalog/product/edit', ['id' => $row->getId(), "active_tab" => "advanced-inventory"]),
                'caption' => __('Edit product'),
                'popup' => true,
                'id' => 'edit'
            ];
        }

        $this->getColumn()->setActions(
            $actions
        );
        return parent::render($row);
    }
}
