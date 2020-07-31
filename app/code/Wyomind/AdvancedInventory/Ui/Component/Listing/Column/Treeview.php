<?php

/**
 * Copyright © 2016 Wyomind. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Wyomind\AdvancedInventory\Ui\Component\Listing\Column;

use Magento\Ui\Component\Listing\Columns\Column;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;

/**
 * Render column block in the order grid
 */
class Treeview extends Column
{

    protected $_helperData = null;
    protected $_helperPermissions = null;
    protected $_modelStockFactory = null;
    protected $_modelPosFactory = null;
    protected $_requestInterface = null;
    protected $_urlInterface = null;

    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        \Wyomind\AdvancedInventory\Helper\Data $helperData,
        \Wyomind\AdvancedInventory\Helper\Permissions $helperPermissions,
        \Wyomind\AdvancedInventory\Model\StockFactory $modelStockFactory,
        \Wyomind\PointOfSale\Model\PointOfSaleFactory $modelPosFactory,
        \Wyomind\AdvancedInventory\Block\Adminhtml\Assignation\Column $blockColumn,
        \Magento\Framework\App\RequestInterface $requestInterface,
        \Magento\Framework\UrlInterface $urlInterface,
        array $components = [],
        array $data = []
    ) {

        $this->_helperData = $helperData;
        $this->_helperPermissions = $helperPermissions;
        $this->_blockColumn = $blockColumn;
        $this->_modelStockFactory = $modelStockFactory;
        $this->_modelPosFactory = $modelPosFactory;
        $this->_requestInterface = $requestInterface;

        $this->_urlInterface = $urlInterface;

        parent::__construct($context, $uiComponentFactory, $components, $data);
    }

    public function prepareDataSource(array $dataSource)
    {




        if (isset($dataSource['data']['items'])) {
            foreach ($dataSource['data']['items'] as & $item) {
                $output = null;
                if (in_array($item['type_id'], $this->_helperData->getProductTypes())) {
                    if (!$this->_helperPermissions->hasAllPermissions()) {
                        if ($this->_modelStockFactory->create()->isMultiStockEnabledByProductId($item['entity_id'])) {
                            foreach ($this->_helperPermissions->getUserPermissions() as $p) {
                                if ($p) {
                                    $stock = $this->_modelStockFactory->create()->getStockByProductIdAndPlaceId($item['entity_id'], $p);
                                    $output .= "<div style='font-size:11px;'>" . $this->_modelPosFactory->create()->load($p)->getName() . " (" . $stock->getQuantityInStock() . ")</div> ";
                                }
                            }
                        }
                    } else {
                        if ($this->_modelStockFactory->create()->isMultiStockEnabledByProductId($item['entity_id'])) {
                            $output .= "<div id='stock-treeview-" . $item['entity_id'] . "' onclick='jQuery(this).parents(\"TD\").eq(0).off(\"click\");'>";
                            $output.="<a class='treeview' identifier='stock-treeview-" . $item['entity_id'] . "' url=" . $this->_urlInterface->getUrl("advancedinventory/stocks/view", ["id" => $item['entity_id']]) . " href='javascript:void(0)'>" . __("Show stock details") . "</a>";
                            $output .= "</div>";
                        }
                    }
                }
                $item[$this->getData('name')] = ($output === null) ? "-" : $output;
            }
        }

        return $dataSource;
    }
}
