<?php

/* *
 * Copyright © 2016 Wyomind. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Wyomind\AdvancedInventory\Block\Adminhtml\Catalog\Product\Edit;

/**
 * For Magento >= 2.1
 */
class AdvancedInventory extends \Magento\Ui\Component\Form\Fieldset
{

    protected $_fieldFactory = null;
    protected $_requestInterface = null;
    protected $_stockModel = null;
    protected $_posModel = null;
    protected $_dataHelper = null;
    protected $_coreHelper = null;

    public function __construct(
        \Magento\Framework\View\Element\UiComponent\ContextInterface $context,
        \Magento\Ui\Component\Form\FieldFactory $fieldFactory,
        \Magento\Framework\App\RequestInterface $requestInterface,
        \Wyomind\AdvancedInventory\Model\Stock $stockModel,
        \Wyomind\PointOfSale\Model\PointOfSale $posModel,
        \Wyomind\AdvancedInventory\Helper\Data $dataHelper,
        \Wyomind\Core\Helper\Data $coreHelper,
        array $components = [],
        array $data = []
    ) {
    
        parent::__construct($context, $components, $data);
        $this->_fieldFactory = $fieldFactory;
        $this->_requestInterface = $requestInterface;
        $this->_stockModel = $stockModel;
        $this->_posModel = $posModel;
        $this->_dataHelper = $dataHelper;
        $this->_coreHelper = $coreHelper;
    }

    public function getChildComponents()
    {

        $fieldName = "inventory]";

        // parameters
        $productId = $this->_requestInterface->getParam('id');
        
        
        $storeId = $this->_requestInterface->getParam('store');

        // POS ids
        if (!$storeId) {
            $pointOfSales = $this->_posModel->getCollection();
        } else {
            $pointOfSales = $this->_posModel->getPlacesByStoreId($storeId);
        }
        $ids = [];
        foreach ($pointOfSales as $pointOfSale) {
            $ids[] = $pointOfSale->getPlaceId();
        }

        // Stocks
        $stocks = $this->_stockModel->getStockSettings($productId, null, $ids);

        // Mage version ?
        $fieldInstance = $this->_fieldFactory->create();
        $fieldInstance->setData(
            [
                    'config' => [
                        'label' => "",
                        'value' => $this->_coreHelper->getMagentoVersion(),
                        'formElement' => 'hidden'
                    ],
                    'name' => $fieldName . "[mage"
                ]
        );
        $fieldInstance->prepare();
        $this->addComponent($fieldName . "[mage", $fieldInstance);



        // auto update stock status ?
        $fieldInstance = $this->_fieldFactory->create();
        $fieldInstance->setData(
            [
                    'config' => [
                        'label' => "",
                        'value' => $this->_coreHelper->getStoreConfig("advancedinventory/settings/auto_update_stock_status"),
                        'formElement' => 'hidden'
                    ],
                    'name' => $fieldName . "[auto_update_stock_status"
                ]
        );
        $fieldInstance->prepare();
        $this->addComponent($fieldName . "[auto_update_stock_status", $fieldInstance);

        
        // product id
        $fieldInstance = $this->_fieldFactory->create();
        $fieldInstance->setData(
            [
                    'config' => [
                        'label' => "",
                        'value' => $productId,
                        'formElement' => "hidden"
                    ],
                    'name' => $fieldName . "[product_id"
                ]
        );
        $fieldInstance->prepare();
        $this->addComponent($fieldName . "[product_id", $fieldInstance);
        
        if ($productId == null || !$this->_dataHelper->isProductAllowed($productId)) {
            return parent::getChildComponents();
        }
        
        // manage local stocks
        $fieldInstance = $this->_fieldFactory->create();
        if (!$storeId) {
            $fieldInstance->setData(
                [
                        'config' => [
                            'label' => __('Manage Local Stocks'),
                            'value' => $stocks->getMultistockEnabled(),
                            'formElement' => 'select',
                            'options' => [
                                ["value" => 0, "label" => __('No')],
                                ["value" => 1, "label" => __('Yes')],
                            ]
                        ],
                        'name' => $fieldName . "[multistock"
                    ]
            );
        } else {
            $fieldInstance->setData(
                [
                        'config' => [
                            'label' => __('Manage Local Stocks'),
                            'value' => $stocks->getMultistockEnabled(),
                            'formElement' => 'hidden'
                        ],
                        'name' => $fieldName . "[multistock"
                    ]
            );
        }
        $fieldInstance->prepare();
        $this->addComponent($fieldName . "[multistock", $fieldInstance);

        // POS/Warehouses
        $counter = 0;
        foreach ($pointOfSales as $pointOfSale) {
            $posWh = $fieldName . "[pos_wh][" . $pointOfSale->getPlaceId() . "]";

            if ($pointOfSale->getStatus() == 0) {
                $visibility = __('Warehouse (hidden)');
            } else {
                $visibility = __('Point of Sales (visible)');
            }

            $getQuantity = "getQuantity" . $pointOfSale->getPlaceId();
            $getStockId = "getStockId" . $pointOfSale->getPlaceId();
            $getManageStock = "getManageStock" . $pointOfSale->getPlaceId();
            $getDefaultStockManagement = "getDefaultStockManagement" . $pointOfSale->getPlaceId();
            $getUseConfigSettingForBackorders = "getUseConfigSettingForBackorders" . $pointOfSale->getPlaceId();
            $getBackorderAllowed = "getBackorderAllowed" . $pointOfSale->getPlaceId();
            $getBackorderLimit = "getBackordersLimit" . $pointOfSale->getPlaceId();
            $getBackorderExpire = "getBackordersExpire" . $pointOfSale->getPlaceId();
            $getDefaultUseDefaultSettingForBackorder = "getDefaultUseDefaultSettingForBackorder" . $pointOfSale->getPlaceId();
            $getDefaultAllowBackorder = "getDefaultAllowBackorder" . $pointOfSale->getPlaceId();

            // manage stock ?

            $enabled = (($stocks->$getManageStock() && $stocks->$getStockId()) || (!$stocks->$getStockId() && $stocks->$getDefaultStockManagement()));
//            $disabled = (!$stock->$getManageStock() && $stock->$getStockId()) || (!$stock->$getStockId() && !$stock->$getDefaultStockManagement());

            $fieldInstance = $this->_fieldFactory->create();
            $fieldInstance->setData(
                [
                        'config' => [
                            'label' => $pointOfSale->getName(),
                            'value' => $enabled ? "1" : "0",
                            'formElement' => 'select',
                            'options' => [
                                ["value" => 1, "label" => __("Stock management enabled")],
                                ["value" => 0, "label" => __("Stock management disabled")],
                            ],
                            "classname" => "test"
                        ],
                        'name' => $posWh . "[manage_stock"
                    ]
            );
            $fieldInstance->prepare();
            $this->addComponent($posWh . "[manage_stock", $fieldInstance);

            // qty
            $fieldInstance = $this->_fieldFactory->create();
            $fieldInstance->setData(
                [
                        'config' => [
                            'label' => "[" . $visibility . ", code : " . $pointOfSale->getStoreCode() . "]",
                            'additionalClasses' => 'riki-advanced-inventory-qty',
                            'value' => $this->_dataHelper->qtyFormat($stocks->$getQuantity(), $stocks->getIsQtyDecimal()),
                            'formElement' => 'input'
                        ],
                        'name' => $posWh . "[qty"
                    ]
            );
            $fieldInstance->prepare();
            $this->addComponent($posWh . "[qty", $fieldInstance);

            // backorders

            $backorders_checked = null;
            $backorders_value = null;
            $backorders_limit = null;
            $backorders_expire = null;

            if (!$stocks->$getStockId()) {
                $backorders_checked = $stocks->$getDefaultUseDefaultSettingForBackorder();
                $backorders_value = $stocks->$getDefaultAllowBackorder();
            } else {
                $backorders_checked = $stocks->$getUseConfigSettingForBackorders();
                $backorders_value = $stocks->$getBackorderAllowed();
                $backorders_limit = $stocks->$getBackorderLimit();
                $backorders_expire = $stocks->$getBackorderExpire();
            }

            $fieldInstance = $this->_fieldFactory->create();
            $fieldInstance->setData(
                [
                        'config' => [
                            'label' => " ",
                            'value' => $backorders_value,
                            'additionalClasses' => 'riki-advanced-inventory-backorder',
                            'formElement' => 'select',
                            'disabled' => $backorders_checked ? true : false,
                            'riki_id' => 'backorder-select',
                            'options' => [
                                ["value" => 0, "label" => __('No backorders')],
                                ["value" => 1, "label" => __('Allow Qty below 0')],
                                ["value" => 2, "label" => __('Allow Qty below 0 and Notify Customer')],
                            ]
                        ],
                        'name' => $posWh . "[backorder_allowed"
                    ]
            );
            $fieldInstance->prepare();
            $this->addComponent($posWh . "[backorder_allowed", $fieldInstance);

            //Backorders limit
            $fieldInstance = $this->_fieldFactory->create();
            $fieldInstance->setData(
                [
                    'config' => [
                        'label' => __("Back order limit"),
                        'riki_id' => 'backorder-limit',
                        'value' => isset($backorders_limit) ? $backorders_limit : "",
                        'disabled' => $backorders_checked ? true : false,
                        'formElement' => 'input'
                    ],
                    'name' => $posWh . "[backorder_limit"
                ]
            );
            $fieldInstance->prepare();
            $this->addComponent($posWh . "[backorder_limit", $fieldInstance);

            //Backorders expire
            $fieldInstance = $this->_fieldFactory->create();
            $fieldInstance->setData(
                [
                    'config' => [
                        'label' => __("Back expire date"),
                        'default' => $backorders_expire ? date('Y-m-d', strtotime($backorders_expire)) : "",
                        //'disabled' => $backorders_checked ? true : false,
                        'formElement' => 'date'
                    ],
                    'name' => $posWh . "[backorder_expire"
                ]
            );
            $fieldInstance->prepare();
            $this->addComponent($posWh . "[backorder_expire", $fieldInstance);


            // use config backorders setting
            $fieldInstance = $this->_fieldFactory->create();
            $fieldInstance->setData(
                [
                        'config' => [
                            'label' => " ",
                            'description' => __('Use Config Settings'),
                            'checked' => $backorders_checked ? true : false,
                            'value' => $backorders_checked ? '1' : '0',
                            'formElement' => 'checkbox',
                            'valueMap' => [
                                'false' => '0',
                                'true' => '1'
                            ]
                        ],
                        'name' => $posWh . "[use_config_setting_for_backorders"
                    ]
            );
            $fieldInstance->prepare();
            $this->addComponent($posWh . "[use_config_setting_for_backorders", $fieldInstance);
        }


        return parent::getChildComponents();
    }
}
