<?php
/**
 * The technical support is guaranteed for all modules proposed by Wyomind.
 * The below code is obfuscated in order to protect the module's copyright as well as the integrity of the license and of the source code.
 * The support cannot apply if modifications have been made to the original source code (https://www.wyomind.com/terms-and-conditions.html).
 * Nonetheless, Wyomind remains available to answer any question you might have and find the solutions adapted to your needs.
 * Feel free to contact our technical team from your Wyomind account in My account > My tickets.
 * Copyright © 2017 Wyomind. All rights reserved.
 * See LICENSE.txt for license details.
 */
namespace Wyomind\AdvancedInventory\Controller\Adminhtml\Catalog\Product;
class Save
{
    public $x1e = null;
    public $xba = null;
    public $x34 = null;
    public $coreHelper = null;
    public $helperData = null;
    public $stockModel = null;
    public $stockRegistry = null;
    public $journalHelper = null;
    public $permissionsHelper = null;
    public $posModel = null;
    public $error = 'Invalid license';

    public function __construct(
        \Wyomind\Core\Helper\Data $coreHelper,
        \Wyomind\AdvancedInventory\Helper\Data $helperData,
        \Wyomind\AdvancedInventory\Model\Stock $stockModel,
        \Wyomind\AdvancedInventory\Model\Item $itemModel,
        \Wyomind\PointOfSale\Model\PointOfSale $posModel,
        \Magento\CatalogInventory\Model\StockRegistry $stockRegistry,
        \Wyomind\AdvancedInventory\Helper\Journal $journalHelper,
        \Wyomind\AdvancedInventory\Helper\Permissions $permissionsHelper
    ) {
        $coreHelper->constructor($this, func_get_args());
        $this->coreHelper = $coreHelper;
        $this->helperData = $helperData;
        $this->stockModel = $stockModel;
        $this->itemModel = $itemModel;
        $this->stockRegistry = $stockRegistry;
        $this->journalHelper = $journalHelper;
        $this->permissionsHelper = $permissionsHelper;
        $this->posModel = $posModel;
    }

    public function beforeExecute($x1eef)
    {
        if ($x1eef->getRequest()->getParam('id') == null) {
            return;
        }
        try {
            $x1e5d = $this;
            $x1e59 = md5(rand());
            $this->$x1e59 = '';
            $x1e60 = 'error';
            $x1e5d->coreHelper->constructor($x1e5d, $x1e59);
            if ($x1e5d->$x1e59 != md5($x1e59)) {
                throw new \Exception(__($x1e5d->$x1e60));
            }
            $x1ed5 = $this->journalHelper;
            $x1ec9 = $x1eef->getRequest()->getPostValue();
            if (version_compare($this->coreHelper->getMagentoVersion(), '2.1', '>=')) {
                $x1ec9 = $x1ec9['product'];
            }
            $x1ed6 = $x1eef->getRequest()->getParam('id');
            if (isset($x1ec9['inventory'])) {
                $x1e74 = (object)$x1ec9['inventory'];
                if (isset($x1e74->pos_wh)) {
                    $x1d6d = (array)$x1e74->pos_wh;
                } else {
                    $x1d6d = [];
                }
                $x1e9b = $this->stockModel->getStockSettings($x1ed6, false, array_keys($x1d6d));
            }
            $x1d07 = $x1eef->getRequest()->getParam('store_id');
            $x1e80 = $this->permissionsHelper->hasAllPermissions();
            if (isset($x1ec9['inventory']) && isset($x1e74->multistock)) {
                if ($x1e74->multistock === '1') {
                    $x1eed = 0;
                    $x1d14 = 0;
                    foreach ($x1d6d as $x1e19 => $x1de6) {
                        $x1de6 = (object)$x1de6;
                        if ($x1d07 || !$x1e80) {
                            $x1dc7 = 'getQuantity' . $x1e19;
                            $x1d14 += $x1e9b->$x1dc7();
                        }
                        $x1eed += (int)$x1de6->qty;
                    }
                    if ($x1d07 || !$x1e80) {
                        $x1eed = $x1e9b->getQty() - $x1d14 + $x1eed;
                    }
                } else {
                    //if (isset($x1ec9['product']['quantity_and_stock_status']['qty'])) {
                    if (isset($x1ec9['quantity_and_stock_status']['qty'])) {
                        $x1eed = $x1eef->getRequest()->getPostValue()['product']['quantity_and_stock_status']['qty'];
                    } else {
                        $x1eed = 0;
                    }
                }
                $x1e59 = md5(rand());
                $this->$x1e59 = '';
                $x1e5d->coreHelper->constructor($x1e5d, $x1e59);
                if ($x1e5d->$x1e59 != md5($x1e59)) {
                    throw new \Exception(__($x1e5d->$x1e60));
                }
                if ($x1e74->multistock === '1') {
                    $x1e28 = ['id' => $x1e9b->getItemId(), 'product_id' => $x1ed6, 'multistock_enabled' => true,];
                    $x1d98 = $x1e9b->getItemId();
                    if ($x1e9b->getMultistockEnabled() != $x1e74->multistock) {
                        $this->journalHelper->insertRow($x1ed5::SOURCE_PRODUCT, $x1ed5::ACTION_MULTISTOCK, 'P#' . $x1ed6, ['from' => 'off', 'to' => 'on']);
                        $this->itemModel->setData($x1e28)->save();
                        $x1d98 = $this->itemModel->getId();
                    }
                    foreach ($x1d6d as $x1e19 => $x1de6) {
                        $x1de6 = (object)$x1de6;
                        $x1d97 = 'getStockId' . $x1e19;
                        $x1dc7 = 'getQuantity' . $x1e19;
                        $x1de3 = 'getManageStock' . $x1e19;
                        $x1e01 = 'getBackorderAllowed' . $x1e19;
                        $x1e1d = 'getUseConfigSettingForBackorders' . $x1e19;
                        $x1ed2 = 'getBackorderLimit' . $x1e19;
                        $x1ef2 = 'getBackorderExpire' . $x1e19;
                        $x1dv1 = 'getBackorderDeliveryDateAllowed' . $x1e19;
                        $x1dv2 = 'getBackorderFirstDeliveryDate' . $x1e19;
                        $x1e28 = [
                            'id' => $x1e9b->$x1d97(),
                            'item_id' => $x1d98,
                            'place_id' => $x1e19,
                            'product_id' => $x1ed6,
                            'quantity_in_stock' => $x1de6->qty,
                            'manage_stock' => $x1de6->manage_stock,
                            'backorder_allowed' => (isset($x1de6->backorder_allowed)) ? $x1de6->backorder_allowed : 0,
                            'use_config_setting_for_backorders' => (isset($x1de6->use_config_setting_for_backorders)) ? ($x1de6->use_config_setting_for_backorders == '1') ? 1 : 0 : 0,
                            'backorder_limit' => (isset($x1de6->backorder_limit)) ? $x1de6->backorder_limit : null,
                            'backorder_expire' => (isset($x1de6->backorder_expire)) ? $x1de6->backorder_expire : null,
                            'backorder_delivery_date_allowed' => (isset($x1de6->backorder_delivery_date_allowed)) ? ($x1de6->backorder_delivery_date_allowed) : 0,
                            'backorder_first_delivery_date' => (isset($x1de6->backorder_first_delivery_date)) ? $x1de6->backorder_first_delivery_date : null,
                        ];
                        $x1e24 = false;
                        if ($x1e9b->$x1dc7() != $x1de6->qty) {
                            $x1e24 = true;
                            $this->journalHelper->insertRow($x1ed5::SOURCE_PRODUCT, $x1ed5::ACTION_STOCK_QTY, 'P#' . $x1ed6 . ',W#' . $x1e19, ['from' => $x1e9b->$x1dc7(), 'to' => $x1de6->qty]);
                        }
                        if ($x1e9b->$x1de3() != $x1de6->manage_stock) {
                            $x1e24 = true;
                            $this->journalHelper->insertRow($x1ed5::SOURCE_PRODUCT, $x1ed5::ACTION_STOCK_MANAGE_QTY, 'P#' . $x1ed6 . ',W#' . $x1e19, ['from' => $x1e9b->$x1de3(), 'to' => $x1de6->manage_stock]);
                        }
                        if ($x1e9b->$x1e01() != $x1e28['backorder_allowed']) {
                            $x1e24 = true;
                            $this->journalHelper->insertRow($x1ed5::SOURCE_PRODUCT, $x1ed5::ACTION_STOCK_BACKORDERS, 'P#' . $x1ed6 . ',W#' . $x1e19, ['from' => $x1e9b->$x1e01(), 'to' => $x1e28['backorder_allowed']]);
                        }
                        if ($x1e9b->$x1e1d() != $x1e28['use_config_setting_for_backorders']) {
                            $x1e24 = true;
                            $this->journalHelper->insertRow($x1ed5::SOURCE_PRODUCT, $x1ed5::ACTION_STOCK_USE_CONFIG_BACKORDERS, 'P#' . $x1ed6 . ',W#' . $x1e19, ['from' => $x1e9b->$x1e1d(), 'to' => $x1e28['use_config_setting_for_backorders']]);
                        }
                        if ($x1e9b->$x1ed2() != $x1e28['backorder_limit']) {
                            $x1e24 = true;
                            $this->journalHelper->insertRow($x1ed5::SOURCE_PRODUCT, $x1ed5::ACTION_STOCK_BACKORDERS, 'P#' . $x1ed6 . ',W#' . $x1e19, ['from' => $x1e9b->$x1ed2(), 'to' => $x1e28['backorder_limit']]);
                        }
                        if ($x1e9b->$x1ef2() != $x1e28['backorder_expire']) {
                            $x1e24 = true;
                            $this->journalHelper->insertRow($x1ed5::SOURCE_PRODUCT, $x1ed5::ACTION_STOCK_BACKORDERS, 'P#' . $x1ed6 . ',W#' . $x1e19, ['from' => $x1e9b->$x1ef2(), 'to' => $x1e28['backorder_expire']]);
                        }
                        if ($x1e9b->$x1dv1() != $x1e28['backorder_delivery_date_allowed']) {
                            $x1e24 = true;
                            $this->journalHelper->insertRow($x1ed5::SOURCE_PRODUCT, $x1ed5::ACTION_STOCK_BACKORDERS, 'P#' . $x1ed6 . ',W#' . $x1e19, ['from' => $x1e9b->$x1dv1(), 'to' => $x1e28['backorder_delivery_date_allowed']]);
                        }
                        if ($x1e9b->$x1dv2() != $x1e28['backorder_first_delivery_date']) {
                            $x1e24 = true;
                            $this->journalHelper->insertRow($x1ed5::SOURCE_PRODUCT, $x1ed5::ACTION_STOCK_BACKORDERS, 'P#' . $x1ed6 . ',W#' . $x1e19, ['from' => $x1e9b->$x1dv2(), 'to' => $x1e28['backorder_first_delivery_date']]);
                        }
                        if ($x1e24) {
                            $this->stockModel->load($x1e28['id'])->setData($x1e28)->save();
                        }
                    }
                } elseif ($x1e9b->getMultistockEnabled() > $x1e74->multistock) {
                    $this->journalHelper->insertRow($x1ed5::SOURCE_PRODUCT, $x1ed5::ACTION_MULTISTOCK, 'P#' . $x1ed6, ['from' => 'on', 'to' => 'off']);
                    $this->itemModel->setId($x1e9b->getItemId())->delete();
                }
                $x1e59 = md5(rand());
                $this->$x1e59 = '';
                $x1e5d->coreHelper->constructor($x1e5d, $x1e59);
                if ($x1e5d->$x1e59 != md5($x1e59)) {
                    throw new \Exception(__($x1e5d->$x1e60));
                }
                $x1ef4 = $x1eef->getRequest()->getPostValue('product');
                $x1eda = $this->stockRegistry->getStockItem($x1ed6);
                $x1e9b = $this->stockModel->getStockSettings($x1ed6);
                if ($x1e74->multistock) {
                    $x1ef4['stock_data']['use_config_backorders'] = false;
                    $x1ef4['stock_data']['backorders'] = $x1e9b->getBackorderableAtStockLevel();
                }
                if ($this->coreHelper->getStoreConfig('advancedinventory/settings/auto_update_stock_status') || !$x1e80) {
                    $x1eb0 = $x1e9b->getStockStatus();
                } else {
                    $x1e96 = $x1eef->getRequest()->getPostValue();
                    if (isset($x1e96['product']['quantity_and_stock_status']['is_in_stock'])) {
                        $x1eb0 = $x1e96['product']['quantity_and_stock_status']['is_in_stock'];
                    } else {
                        $x1eb0 = $x1e9b->getStockStatus();
                    }
                }
                if ($x1eb0 != $x1eda->getIsInStock()) {
                    $x1ef4['quantity_and_stock_status']['is_in_stock'] = $x1eb0;
                    $x1ec4 = ($x1eb0) ? 'In stock' : 'Out of stock';
                    $x1ec2 = ($x1eda->getIsInStock()) ? 'In stock' : 'Out of stock';
                    $this->journalHelper->insertRow($x1ed5::SOURCE_PRODUCT, $x1ed5::ACTION_IS_IN_STOCK, 'P#' . $x1ed6, ['from' => $x1ec2, 'to' => $x1ec4]);
                }
                if (isset($x1ec9['inventory'])) {
                    if ($x1eda->getQty() != $x1eed) {
                        $this->journalHelper->insertRow($x1ed5::SOURCE_PRODUCT, $x1ed5::ACTION_QTY, 'P#' . $x1ed6, ['from' => $x1eda->getQty(), 'to' => $x1eed]);
                        $x1ef4['quantity_and_stock_status']['qty'] = $x1eed;
                        $x1ef4['stock_data']['qty'] = $x1eed;
                    }
                }
                $x1eef->getRequest()->setPostValue('product', $x1ef4);
            }
        } catch (\Exception $e) {
            throw $e;
        }
    }
}