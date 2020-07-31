<?php

/*
 * Copyright Â© 2015 Wyomind. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Wyomind\AdvancedInventory\Model;

class Assignation extends \Magento\Framework\Model\AbstractModel
{

    protected $_itemRegistry = [];
    public $log = null;
    public $order = null;
    protected $_helperCore;
    protected $_helperData;
    protected $_journalHelper;
    protected $_stockFactory;
    protected $_posFactory;
    protected $_requestInterface;
    protected $_stockRegistry;
    protected $_orderFactory;
    protected $_modelAddressFactory;
    protected $_orderItemCollectionFactory;
    protected $_regionFactory;
    protected $_appResource;
    protected $_logger = null;

    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Wyomind\Core\Helper\Data $helperCore,
        \Wyomind\AdvancedInventory\Helper\Data $helperData,
        \Wyomind\AdvancedInventory\Helper\Journal $journalHelper,
        \Wyomind\AdvancedInventory\Model\StockFactory $stockFactory,
        \Wyomind\PointOfSale\Model\PointOfSaleFactory $posFactory,
        \Magento\Framework\App\RequestInterface $requestInterface,
        \Magento\CatalogInventory\Model\StockRegistry $stockRegistry,
        \Magento\Sales\Model\OrderFactory $orderFactory,
        \Wyomind\AdvancedInventory\Model\ResourceModel\Order\Item\CollectionFactory $orderItemCollectionFactory,
        \Magento\Sales\Model\Order\ItemRepository $orderItemRepositery,
        \Magento\Sales\Model\Order\AddressFactory $modelAddressFactory,
        \Magento\Directory\Model\RegionFactory $regionFactory,
        \Magento\Framework\App\ResourceConnection $appResource,
        \Wyomind\AdvancedInventory\Logger\Logger $logger,
        \Magento\Framework\Model\ResourceModel\AbstractResource $abstractResource = null,
        \Magento\Framework\Data\Collection\AbstractDb $abstactDb = null,
        array $data = []
    ) {
    

        $this->_helperCore = $helperCore;
        $this->_helperData = $helperData;
        $this->_journalHelper = $journalHelper;
        $this->_stockFactory = $stockFactory;
        $this->_posFactory = $posFactory;
        $this->_requestInterface = $requestInterface;
        $this->_stockRegistry = $stockRegistry;
        $this->_orderFactory = $orderFactory;
        $this->_modelAddressFactory = $modelAddressFactory;
        $this->_regionFactory = $regionFactory;
        $this->_appResource = $appResource;
        $this->_orderItemCollectionFactory = $orderItemCollectionFactory;
        $this->_orderItemRepositery = $orderItemRepositery;
        parent::__construct($context, $registry, $abstractResource, $abstactDb, $data);
        $this->_logger = $logger;
    }

    public function _construct()
    {
        $this->_init('Wyomind\AdvancedInventory\Model\ResourceModel\Assignation');
    }

    public function getAssignationRules(
        $method,
        $rules
    ) {
    
        switch ($method) {
            case 0:
                return null;
            case 1:
                return '*';
            case 2:
                return $rules;
        }
    }

    protected function getRules($stringrules)
    {
        return explode("\n", $stringrules);
    }

    protected function addressMatch(
        $addressFilter,
        $address
    ) {
    
        $excluding = false;
        $addressFilter = trim($addressFilter);
        $addressFilter = str_replace(
            ['\(', '\)', '\,'],
            ['__opening_parenthesis__', '__closing_parenthesis__', '__comma__'],
            $addressFilter
        );
        if ($addressFilter == '*') {
            $this->log .='      country code ' . $address['country_code'] . ' matches' . "\r\n";
            return true;
        }
        $result = [];
        if (preg_match('#\* *- *\((.*)\)#s', $addressFilter, $result)) {
            $addressFilter = $result[1];
            $excluding = true;
        }
        $tmpAddressFilterArray = explode(',', trim($addressFilter));
        $concat = false;
        $concatened = '';
        $addressFilterArray = [];
        $i = 0;
        $countTmpAddressFilterArray = count($tmpAddressFilterArray);
        foreach ($tmpAddressFilterArray as $addressFilter) {
            if ($concat) {
                $concatened .= ',' . $addressFilter;
            } else {
                if ($i < $countTmpAddressFilterArray - 1 && preg_match('#\(#', $addressFilter)) {
                    $concat = true;
                    $concatened .= $addressFilter;
                } else {
                    $addressFilterArray[] = $addressFilter;
                }
            }
            if (preg_match('#\)#', $addressFilter)) {
                $addressFilterArray[] = $concatened;
                $concatened = '';
                $concat = false;
            }
            $i++;
        }
        foreach ($addressFilterArray as $addressFilter) {
            $addressFilter = trim($addressFilter);
            if (preg_match('#([A-Z]{2}) *(-)? *(?:\( *(-)? *(.*)\))?#s', $addressFilter, $result)) {
                $countryCode = $result[1];
                if ($address['country_code'] == $countryCode) {
                    $this->log .='      country code ' . $address['country_code'] . ' matches' . "\r\n";
                    if (!isset($result[4]) || $result[4] == '') {
                        return !$excluding;
                    } else {
                        $regionCodes = explode(',', $result[4]);
                        $inArray = false;
                        $countRegionCodes = count($regionCodes);
                        for ($i = $countRegionCodes; --$i >= 0;) {
                            $code = trim(
                                str_replace(
                                    ['__opening_parenthesis__', '__closing_parenthesis__', '__comma__'],
                                    ['(', ')', ','],
                                    $regionCodes[$i]
                                )
                            );
                            $regionCodes[$i] = $code;
                            if ($address['region_code'] === $code) {
                                $this->log .='      region code ' . $address['region_code'] . ' matches' . "\r\n";
                                $inArray = true;
                            } elseif ($address['postcode'] === $code) {
                                $this->log .='      postcode ' . $address['postcode'] . ' matches' . "\r\n";
                                $inArray = true;
                            } elseif (mb_substr($code, 0, 1) == '/' && mb_substr($code, mb_strlen($code) - 1, 1) == '/' && @preg_match($code, $address['postcode'])) {
                                $this->log .='      postcode ' . $address['postcode'] . ' matches ' . htmlentities($code) . "\r\n";
                                $inArray = true;
                            } elseif (strpos($code, '*') !== false && preg_match('/^' . str_replace('*', '(?:.*)', $code) . '$/', $address['postcode'])) {
                                $this->log .='      postcode ' . $address['postcode'] . ' matches ' . htmlentities($code) . "\r\n";
                                $inArray = true;
                            }
                            if ($inArray) {
                                break;
                            }
                        }
                        if (!$inArray) {
                            $this->log .='      region code ' . $address['region_code'] . ' and postcode' . $address['postcode'] . ' don\'t match' . "\r\n";
                        }
                        // Vérification stricte
                        $excludingRegion = $result[2] == '-' || $result[3] == '-';
                        if ($excludingRegion && !$inArray || !$excludingRegion && $inArray) {
                            return !$excluding;
                        }
                    }
                } else {
                    $this->log .='      country code ' . $address['country_code'] . ' doesn\'t matches' . "\r\n";
                }
            }
        }
        return $excluding;
    }

    public function checkAvailability(
        $productId,
        $placeId,
        $qtyToAssign,
        $itemId
    ) {
    

        $inventory = $this->_stockFactory->create()->getStockSettings($productId, $placeId, [], $itemId);
        $this->log .= "* * * * * Checking availability, " . $qtyToAssign . " to assign" . ", " . (float) $inventory['quantity_in_stock'] . " in stock \r\n";
        $qty = $inventory->getQuantityInStock() - $qtyToAssign;
        $remainingQtyAssign = $qtyToAssign;
        $qtyAssigned = 0;
        $mutipleAssignation = $this->_helperCore->getStoreConfig("advancedinventory/settings/multiple_assignation_enabled");
        $this->log .= "* * * * * * ";

        if (!$inventory->getManagedAtStockLevel()) {
            $this->log .= "Qty management disabled!\r\n";
            $remainingQtyAssign = 0;
            $qtyAssigned = $qtyToAssign;
            $status = 4;
        } elseif ($qty >= 0) {
            $this->log .= "Qty is available!\r\n";
            $remainingQtyAssign = 0;
            $qtyAssigned = $qtyToAssign;
            $status = 3;
        } elseif ($inventory->getBackorderableAtStockLevel()) {
            $this->log .= "Backorder allowed!\r\n";
            $remainingQtyAssign = 0;
            $qtyAssigned = $qtyToAssign;
            $status = 2;
        } elseif ($inventory->getQuantityInStock() > 0 && $qty < 0) {
            if ($mutipleAssignation) {
                $this->log .= "Qty is partialy available!\r\n";
                $remainingQtyAssign = $qtyToAssign - $inventory->getQuantityInStock();
                $qtyAssigned = $inventory->getQuantityInStock();
                $status = 1;
            } else {
                $this->log .= "Qty is not completely available!\r\n";
                $status = 1;
            }
        } else {
            $this->log .= "Qty is not available!\r\n";
            $status = 0;
        }

        return ["status" => $status, "remaining_qty_to_assign" => $remainingQtyAssign, "qty_assigned" => $this->_helperData->qtyFormat($qtyAssigned, $inventory->getIsQtyDecimal())];
    }

    public function run($entityId)
    {
        if ($this->order == null) {
            $order = $this->_orderFactory->create()->load($entityId);
        } else {
            $order = $this->order;
        }
        $assignTo = ["place_ids" => []];
        $this->log .= "\r\n-----------------------------------------------------------------------------\r\n";
        $this->log .= "------------Start assignation process for order #" . $order->getIncrementId() . " ------------------\r\n";
        $this->log .= "-----------------------------------------------------------------------------\r\n\r\n";

        $this->log .= "Shipping method : " . $order->getShippingMethod() . "\r\n\r\n";



        if ($order->getShippingAddress()) {
            $shippingId = $order->getShippingAddress()->getId();
            $address = $this->_modelAddressFactory->create()->load($shippingId);
        } else {
            $address = null;
        }


        if (strstr($order->getShippingMethod(), "pickupatstore") !== false) {
            $orderedItems = $this->getAssignationByOrderId($entityId);
            $placeId = str_replace("pickupatstore_pickupatstore_", "", $order->getShippingMethod());
            $this->log .= "* * * * * Assign to warehouse ID : " . $placeId . "\r\n\r\n";
            foreach ($orderedItems->getData() as $item) {
                $qtyToAssign = $item["qty_to_assign"];
                $this->log .= "* * * * * * * Assign to warehouse ID : " . $placeId . ", Qty assigned = " . $qtyToAssign . "\r\n\r\n";
                $assignTo["items"][$item["item_id"]]["pos"][$placeId]["qty_assigned"] = $qtyToAssign;
                $assignTo["items"][$item["item_id"]]["product_id"] = $item["product_id"];
            }
            $assignTo["place_ids"][0] = $placeId;
        } elseif ($address == null) {
            $this->log .= "* * * * * Not Assigned (no shipping address) \r\n\r\n";
        } else {
            $destination['country_code'] = $address->getCountryId();
            $regions = $this->_regionFactory->create();
            $destination['region_code'] = $regions->load($address->getRegionId())->getCode();
            $destination['postcode'] = $address->getPostcode();

            $this->log .= "Shipped to : " . $destination['country_code'] . ',' . $destination['region_code'] . ',' . $destination['postcode'] . "\r\n\r\n";

            $places = $this->_posFactory->create()->getPlacesByStoreId($order->getStoreId());
            $orderedItems = $this->getAssignationByOrderId($entityId);

            if ($this->_helperCore->getStoreConfig("advancedinventory/settings/multiple_assignation_enabled")) {
                foreach ($orderedItems->getData() as $k => $item) {
                    $qtyToAssign = $item["qty_to_assign"];
                    $this->log .= "\r\n- - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -\r\n"
                            . "* Checking availability for : " . $item['name'] . " [ SKU " . $item['sku'] . " ] [ ID " . $item["product_id"] . " ], Ordered Qty : " . $item["qty_to_assign"] . "\r\n";

                    if ($item["multistock_enabled"]) {
                        foreach ($places as $place) {
                            $this->log .= ". . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . .\r\n";
                            $this->log .= "* * Checking warehouse : " . $place->getName() . " [" . $place->getStoreCode() . "]\r\n";

                            $rules = $this->getAssignationRules($place->getUseAssignationRules(), $place->getInventoryAssignationRules());
                            foreach ($this->getRules($rules) as $rule) {
                                $this->log .= "* * * Checking rule '" . trim($rule) . "' \r\n";
                                if ($rule == '*' || $this->addressMatch($rule, $destination)) {
                                    $this->log .="* * * * This rule matches!\r\n";
                                    $available = $this->checkAvailability($item["product_id"], $place->getPlaceId(), $qtyToAssign, $item['item_id']);
                                    if ($qtyToAssign < 1) {
                                        continue 2;
                                    }
                                    $qtyToAssign = $available['remaining_qty_to_assign'];
                                    if ($available['status'] >= 1) {
                                        $this->log .= "* * * * * * * Assign to warehouse ID : " . $place->getPlaceId() . ", Qty assigned = " . $available['qty_assigned'] . "\r\n\r\n";
                                        $assignTo["place_ids"][] = $place->getPlaceId();
                                        $assignTo["items"][$item["item_id"]]["pos"][$place->getPlaceId()]["qty_assigned"] = $available['qty_assigned'];
                                        $assignTo["items"][$item["item_id"]]["product_id"] = $item["product_id"];
                                        continue 2;
                                    }
                                    if ($available['status'] >= 2) {
                                        continue 3;
                                    }
                                    continue 1;
                                } else {
                                    $this->log .= "* * * * This rule doesn't match!\r\n";
                                }
                            }
                        }
                    } else {
                        $this->log .= "* * Multi-stock is disabled\r\n";
                        $inventory = $this->_stockFactory->create()->getStockSettings($item["product_id"]);
                        $qtyToAssign = 0;
                        if ($inventory->getManageStockAtProductLevel()) {
                            $newQty = $inventory->getQty() - $item["qty_to_assign"];
                            $this->log .= "* * * Stock decremented (Qty : $newQty)\r\n";
                        } else {
                            $this->log .= "* * * Stock is not managed\r\n";
                        }
                    }
                    if ($qtyToAssign) {
                        $assignTo["place_ids"][] = 0;
                    }
                }
                $this->log .= "\r\n-----------------------------------------------------------------------------\r\n";
                $this->log .= "\r\n-----------------------------------------------------------------------------\r\n";
            } else {
                $countOrderedItems = count($orderedItems->getData());
                foreach ($places as $place) {
                    $this->log .= "\r\n- - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -\r\n";
                    $this->log .= "* Checking warehouse : " . $place->getName() . " [" . $place->getStoreCode() . "]\r\n";

                    foreach ($orderedItems->getData() as $item) {
                        $qtyToAssign = $item["qty_to_assign"];
                        if ($item["multistock_enabled"]) {
                            $this->log .= ". . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . .\r\n";
                            $this->log .= "* * Checking rules for " . $item['name'] . " [ SKU " . $item['sku'] . " ] [ ID " . $item["product_id"] . " ], Ordered Qty : " . $item["qty_to_assign"] . "\r\n";
                            $rules = $this->getAssignationRules($place->getUseAssignationRules(), $place->getInventoryAssignationRules());
                            foreach ($this->getRules($rules) as $rule) {
                                $this->log .= "* * * Checking rule '" . trim($rule) . "' \r\n";
                                if ($rule == '*' || $this->addressMatch($rule, $destination, false)) {
                                    $this->log .="* * * * This rule matches!\r\n";

                                    $available = $this->checkAvailability($item["product_id"], $place->getPlaceId(), $qtyToAssign, $item['item_id']);

                                    if ($available['status'] >= 2) {
                                        $this->log .= "* * * * * * * Assign to warehouse ID : " . $place->getPlaceId() . ", Qty assigned = " . $available['qty_assigned'] . "\r\n\r\n";
                                        $assignTo["items"][$item["item_id"]]["pos"][$place->getPlaceId()]["qty_assigned"] = $available['qty_assigned'];
                                        $assignTo["items"][$item["item_id"]]["product_id"] = $item["product_id"];
                                        $assignTo["place_ids"][0] = $place->getPlaceId();
                                        continue 2;
                                    } else {
                                        $this->log .= "* * * * * * * Can't assign to warehouse ID : " . $place->getPlaceId() . ", Assignation cancelled\r\n\r\n";
                                        $assignTo = ["place_ids" => [0]];
                                        continue 3;
                                    }
                                } else {
                                    $this->log .= "* * * * This rule doesn't match!\r\n";
                                }
                            }
                        } else {
                            $this->log .= "* * Multi-stock is disabled\r\n";
                            $inventory = $this->_stockFactory->create()->getStockSettings($item["product_id"]);

                            if ($inventory->getManageStockAtProductLevel()) {
                                $newQty = $inventory->getQty() - $item["qty_to_assign"];
                                $this->log .= "* * * Stock decremented (Qty : $newQty)\r\n";
                            } else {
                                $this->log .= "* * * Stock is not managed\r\n";
                            }
                        }
                    }
                    if (isset($assignTo["items"]) && count($assignTo["items"]) == $countOrderedItems) {
                        $this->log .= "\r\n-----------------------------------------------------------------------------\r\n";
                        $this->log .= "\r\n Assignation found!\r\n";
                        $this->log .= "\r\n-----------------------------------------------------------------------------\r\n";
                        break;
                    }
                }
            }
        }
        sort($assignTo["place_ids"]);
        $assignTo["place_ids"] = implode(",", array_unique($assignTo["place_ids"]));

        if ($this->_helperCore->getDefaultConfig("advancedinventory/system/log_enabled")) {
            $this->_logger->notice($this->log);
        }

        return ["inventory" => $assignTo, "log" => $this->log];
    }

    public function refund($observer)
    {
        $data = $this->_requestInterface->getPost("creditmemo");
        $journal = $this->_journalHelper;
        $creditmemo = $observer->getEvent()->getCreditmemo();
        $orderId = $observer->getEvent()->getCreditmemo()->getOrderId();
        $items = $creditmemo->getAllItems();
        foreach ($items as $item) {
            if (in_array($this->_orderItemRepositery->get($item->getOrderItemId())->getProductType(), $this->_helperData->getProductTypes())) {
                $id = $this->_orderItemRepositery->get($item->getOrderItemId())->getParentItemId();


                if ($id == null) {
                    $id = $item->getOrderItemId();
                }

                $placeId = $data["items"][$id]['back_to_stock'];
                if (isset($data["items"][$id]['qty'])) {
                    $qty = $data["items"][$id]['qty'];
                } else {
                    $qty = 0;
                }

                if ($placeId) {
                    $assignation = $this->getAssignationByItemIdAndPlaceId($item->getOrderItemId(), $placeId);
                    $this->_journalHelper->insertRow($journal::SOURCE_REFUND, $journal::ACTION_STOCK_QTY, "O#$orderId,P#" . $item->getProductId() . ",W#$placeId", ["from" => $assignation->getQtyAssigned(), "to" => $assignation->getQtyAssigned() - $qty]);

                    $assignation->setQtyReturned($assignation->getQtyReturned() + $qty)->save();
                    $stockFactory = $this->_stockFactory->create()->getStockByProductIdAndPlaceId($item->getProductId(), $placeId);
                    $stockFactory->setQuantityInStock($stockFactory->getQuantityInStock() + $qty)->save();
                    $stockRegistry = $this->_stockRegistry->getStockItem($item->getProductId(), "product_id");
                    $this->_journalHelper->insertRow($journal::SOURCE_REFUND, $journal::ACTION_QTY, "O#$orderId,P#" . $item->getProductId(), ["from" => $stockRegistry->getQty(), "to" => $stockRegistry->getQty() + $qty]);

                    $stockRegistry->setQty($stockRegistry->getQty() + $qty)->save();

                    if ($this->_helperCore->getStoreConfig("advancedinventory/settings/auto_update_stock_status")) {
                        $inventory = $this->_stockFactory->create()->getStockSettings($item['product_id']);
                        $isInStockAfter = $inventory->getStockStatus() ? "In stock" : "Out of stock";
                        $isInStockBefore = $stockRegistry->getIsInStock() ? "In stock" : "Out of stock";
                        if ($inventory->getStockStatus() != $stockRegistry->getIsInStock()) {
                            $this->_journalHelper->insertRow($journal::SOURCE_REFUND, $journal::ACTION_IS_IN_STOCK, "O#$orderId,P#" . $item->getProductId(), ["from" => $isInStockBefore, "to" => $isInStockAfter]);
                        }
                        $stockRegistry->setIsInStock($inventory->getStockStatus())->save();
                    }
                }
            }
        }
    }

    public function cancel($entityId)
    {
        $journal = $this->_journalHelper;
        $items = $this->getAssignationByOrderId($entityId);
        foreach ($items->getData() as $item) {
            $productId = $item['product_id'];
            $itemId = $item['item_id'];
            $assignation = $this->getAssignationByItemId($itemId);
            $placeIds = [];
            foreach ($assignation as $line) {
                $placeId = $line->getPlaceId();
                if (!isset($placeIds[$placeId])) {
                    $placeIds[$placeId] = $line->getQtyAssigned();
                } else {
                    $placeIds[$placeId]+=$line->getQtyAssigned();
                }

                $line->load($line->getId())->setQtyReturned($line->getQtyAssigned())->save();
            }

            foreach ($placeIds as $placeId => $value) {
                if ($value) {
                    $stockFactory = $this->_stockFactory->create()->getStockByProductIdAndPlaceId($item['product_id'], $placeId);
                    $this->_journalHelper->insertRow($journal::SOURCE_CANCEL, $journal::ACTION_STOCK_QTY, "O#$entityId,P#" . $productId . ",W#$placeId", ["from" => $stockFactory->getQuantityInStock(), "to" => $stockFactory->getQuantityInStock() + $value]);
                    $stockFactory->setQuantityInStock($stockFactory->getQuantityInStock() + $value)->save();
                }
            }

            $stockRegistry = $this->_stockRegistry->getStockItem($item['product_id'], "product_id");
            $this->_journalHelper->insertRow($journal::SOURCE_CANCEL, $journal::ACTION_QTY, "O#$entityId,P#" . $productId, ["from" => $stockRegistry->getQty(), "to" => $stockRegistry->getQty() + $item['qty_ordered']]);

            $stockRegistry->setQty($stockRegistry->getQty() + $item['qty_ordered'])->save();

            if ($this->_helperCore->getStoreConfig("advancedinventory/settings/auto_update_stock_status")) {
                $inventory = $this->_stockFactory->create()->getStockSettings($item['product_id']);
                $isInStockAfter = $inventory->getStockStatus() ? "In stock" : "Out of stock";
                $isInStockBefore = $stockRegistry->getIsInStock() ? "In stock" : "Out of stock";
                if ($inventory->getStockStatus() != $stockRegistry->getIsInStock()) {
                    $this->_journalHelper->insertRow($journal::SOURCE_CANCEL, $journal::ACTION_IS_IN_STOCK, "O#$entityId,P#" . $productId, ["from" => $isInStockBefore, "to" => $isInStockAfter]);
                }
                $stockRegistry->setIsInStock($inventory->getStockStatus())->save();
            }
        }
        $order = $this->_orderFactory->create()->load($entityId);
        $order->setAssignedTo(0)->save();
        $this->_journalHelper->insertRow($journal::SOURCE_CANCEL, $journal::ACTION_ASSIGNATION, "O#$entityId", ["from" => $order->getAssignedTo(), "to" => 0]);

        $connection = $this->_appResource->getConnection('core_write');
        $tableSog = $this->_appResource->getTableName('sales_order_grid');
        $connection->update($tableSog, ["assigned_to" => 0], "entity_id = '" . $entityId . "'");
        return true;
    }

    public function insert(
        $entityId,
        $data
    ) {
    

        $journal = $this->_journalHelper;
        if (isset($data["inventory"]['items'])) {
            foreach ($data["inventory"]['items'] as $itemId => $item) {
                $quantity = 0;

                foreach ($item["pos"] as $placeId => $pos) {
                    $update['place_id'] = $placeId;
                    $update['item_id'] = $itemId;
                    $update['id'] = null;
                    $update['qty_assigned'] = (float) $pos['qty_assigned'];

                    $quantity+=$pos['qty_assigned'];
                    $this->load(null)->setData($update);

                    $stockFactory = $this->_stockFactory->create()->getStockByProductIdAndPlaceId($item['product_id'], $placeId);
                    $this->_journalHelper->insertRow($journal::SOURCE_PURCHASE, $journal::ACTION_STOCK_QTY, "O#$entityId,P#" . $item['product_id'] . ",W#$placeId", ["from" => $stockFactory->getQuantityInStock(), "to" => ($stockFactory->getQuantityInStock() + - $pos['qty_assigned'])]);
                    $stockFactory->setQuantityInStock($stockFactory->getQuantityInStock() - $pos['qty_assigned'])->save();

                    $this->save();
                }

                $stockRegistry = $this->_stockRegistry->getStockItem($item['product_id'], "product_id");


                $this->_journalHelper->insertRow($journal::SOURCE_PURCHASE, $journal::ACTION_QTY, "O#$entityId,P#" . $item['product_id'], ["from" => $stockRegistry->getQty(), "to" => $stockRegistry->getQty() - $quantity]);
                $stockRegistry->setQty($stockRegistry->getQty() - $quantity)->save();


                if ($this->_helperCore->getStoreConfig("advancedinventory/settings/auto_update_stock_status")) {
                    $inventory = $this->_stockFactory->create()->getStockSettings($item['product_id']);
                    $isInStockAfter = $inventory->getStockStatus() ? "In stock" : "Out of stock";
                    $isInStockBefore = $stockRegistry->getIsInStock() ? "In stock" : "Out of stock";
                    if ($inventory->getStockStatus() != $stockRegistry->getIsInStock()) {
                        $this->_journalHelper->insertRow($journal::SOURCE_PURCHASE, $journal::ACTION_IS_IN_STOCK, "O#$entityId,P#" . $item['product_id'], ["from" => $isInStockBefore, "to" => $isInStockAfter]);
                    }
                    $stockRegistry->setIsInStock($inventory->getStockStatus())->save();
                }
            }

            $order = $this->_orderFactory->create()->load($entityId);


            $this->_journalHelper->insertRow($journal::SOURCE_PURCHASE, $journal::ACTION_ASSIGNATION, "O#$entityId", ["from" => $order->getAssignedTo(), "to" => $data["inventory"]["place_ids"]]);

            $order->setAssignedTo($data["inventory"]["place_ids"])->save();
            $connection = $this->_appResource->getConnection('core_write');
            $tableSog = $this->_appResource->getTableName('sales_order_grid');
            $connection->update($tableSog, ["assigned_to" => $data["inventory"]["place_ids"]], "entity_id = '" . $entityId . "'");
        }
        return true;
    }

    public function update(
        $entityId,
        $data
    ) {
    
        try {
            $placeIds = [];
            $journal = $this->_journalHelper;
            foreach ($data["inventory"]['items'] as $itemId => $item) {
                $quantity = 0;
                $stockMovement = 0;
                if (isset($item["pos"])) {
                    foreach ($item["pos"] as $placeId => $pos) {
                        $assignationId = null;
                        if (!$pos['assignation_id']) {
                            $assignation = $this->getAssignationByItemIdAndPlaceId($itemId, $placeId);
                            if (isset($assignation["id"])) {
                                $assignationId = $assignation["id"];
                            }
                        } else {
                            $assignationId = $pos['assignation_id'];
                        }
                        $update['place_id'] = $placeId;
                        $update['item_id'] = $itemId;
                        $update['id'] = $assignationId;
                        $update['qty_assigned'] = (float) $pos['qty_assigned'];

                        $quantity += $pos['qty_assigned'];
                        $this->load($update['id'])->setData($update);

                        if (($pos['qty_original'] - $pos['qty_assigned']) != 0) {
                            $stockMovement += $pos['qty_original'] - $pos['qty_assigned'];
                            $stockFactory = $this->_stockFactory->create()->getStockByProductIdAndPlaceId($item['product_id'], $placeId);

                            $this->_journalHelper->insertRow($journal::SOURCE_ORDER, $journal::ACTION_STOCK_QTY, "O#$entityId,P#" . $item['product_id'] . ",W#$placeId", ["from" => $stockFactory->getQuantityInStock(), "to" => ($stockFactory->getQuantityInStock() + ($pos['qty_original'] - $pos['qty_assigned']))]);
                            $stockFactory->setQuantityInStock($stockFactory->getQuantityInStock() + ($pos['qty_original'] - $pos['qty_assigned']));
                            $stockFactory->save();
                        }

                        if (!in_array($placeId, $placeIds) && $update['qty_assigned'] > 0) {
                            $placeIds[] = $placeId;
                        }
                        $this->save();
                    }
                }
                if ($stockMovement != 0) {
                    $stockRegistry = $this->_stockRegistry->getStockItem($item['product_id'], "product_id");


                    $this->_journalHelper->insertRow($journal::SOURCE_ORDER, $journal::ACTION_QTY, "O#$entityId,P#" . $item['product_id'], ["from" => $stockRegistry->getQty(), "to" => $stockRegistry->getQty() + $stockMovement]);
                    $stockRegistry->setQty($stockRegistry->getQty() + $stockMovement)->save();


                    if ($this->_helperCore->getStoreConfig("advancedinventory/settings/auto_update_stock_status")) {
                        $inventory = $this->_stockFactory->create()->getStockSettings($item['product_id']);
                        $isInStockAfter = $inventory->getStockStatus() ? "In stock" : "Out of stock";
                        $isInStockBefore = $stockRegistry->getIsInStock() ? "In stock" : "Out of stock";
                        if ($inventory->getStockStatus() != $stockRegistry->getIsInStock()) {
                            $this->_journalHelper->insertRow($journal::SOURCE_ORDER, $journal::ACTION_IS_IN_STOCK, "O#$entityId,P#" . $item['product_id'], ["from" => $isInStockBefore, "to" => $isInStockAfter]);
                        }
                        $stockRegistry->setIsInStock($inventory->getStockStatus())->save();
                    }
                }
                if ($quantity < $item["qty_to_assign"]) {
                    $placeIds[] = 0;
                }
            }
            $order = $this->_orderFactory->create()->load($entityId);
            sort($placeIds);
            $assignedTo = implode(",", $placeIds);
            $this->_journalHelper->insertRow($journal::SOURCE_ORDER, $journal::ACTION_ASSIGNATION, "O#$entityId", ["from" => $order->getAssignedTo(), "to" => $assignedTo]);

            $order->setAssignedTo($assignedTo)->save();
            $connection = $this->_appResource->getConnection('core_write');
            $tableSog = $this->_appResource->getTableName('sales_order_grid');

            $connection->update($tableSog, ["assigned_to" => implode(",", $placeIds)], "entity_id = '" . $entityId . "'");
            return true;
        } catch (\Exception $e) {
            throw new \Exception("Error :" . $e->getMessage());
        }
    }

    public function getAssignationByItemIdAndPlaceId(
        $itemId,
        $placeId
    ) {
    

        $collection = $this->getCollection()->addFieldToFilter("item_id", ["eq" => $itemId])->addFieldToFilter("place_id", ["eq" => $placeId]);
        return $collection->getFirstItem();
    }

    public function getAssignationByItemId($itemId)
    {

        $collection = $this->getCollection()->addFieldToFilter("item_id", ["eq" => $itemId]);


        return $collection;
    }

    public function getAssignationRequired($orderId)
    {
        $items = $this->getAssignationByOrderId($orderId);

        foreach ($items->getData() as $item) {
            if ($item['multistock_enabled']) {
                return true;
            }
        }
        return false;
    }

    public function getAssignationByOrderId(
        $orderId,
        $itemId = false
    ) {
    
        if (!isset($this->_itemRegistry[$orderId])) {
            $collection = $this->_orderItemCollectionFactory->create()->getAssignationByOrderId($orderId, $itemId);
            $this->_itemRegistry[$orderId] = $collection;
        }
        return $this->_itemRegistry[$orderId];
    }
}
