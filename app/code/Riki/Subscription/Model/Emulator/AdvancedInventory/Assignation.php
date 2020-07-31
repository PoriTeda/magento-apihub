<?php

namespace Riki\Subscription\Model\Emulator\AdvancedInventory;

class Assignation extends \Riki\AdvancedInventory\Model\Assignation
{
    public function __construct(
        \Riki\Framework\Helper\Cache\FunctionCache $functionCache,
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
        \Magento\Sales\Model\Order\ItemFactory $itemFactory,
        \Riki\Sales\Helper\ConnectionHelper $connectionHelper,
        \Riki\Subscription\Model\Emulator\OrderFactory $emulatorOrderFactory,
        \Riki\Subscription\Model\Emulator\Order\AddressFactory $emulatorOrderAddressFactory,
        \Riki\Subscription\Model\Emulator\ResourceModel\AdvancedInventory\Order\Item\CollectionFactory $emulatorOrderItemCollectionFactory,
        \Riki\Sales\Helper\Address $addressHelper,
        \Magento\Catalog\Api\ProductRepositoryInterface $productRepository,
        \Riki\AdvancedInventory\Helper\Assignation $assignationHelper,
        \Riki\AdvancedInventory\Api\StockManagementInterface $stockManagement,
        \Riki\AdvancedInventory\Model\AssignationUpdateValidator $assignationUpdateValidator,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Framework\Model\ResourceModel\AbstractResource $abstractResource = null,
        \Magento\Framework\Data\Collection\AbstractDb $abstactDb = null,
        array $data = []
    ) {
        parent::__construct($functionCache, $context, $registry, $helperCore, $helperData, $journalHelper, $stockFactory, $posFactory, $requestInterface, $stockRegistry, $orderFactory, $orderItemCollectionFactory, $orderItemRepositery, $modelAddressFactory, $regionFactory, $appResource, $logger, $itemFactory, $connectionHelper, $addressHelper, $productRepository, $assignationHelper, $stockManagement, $assignationUpdateValidator, $scopeConfig, $abstractResource, $abstactDb, $data);
        $this->_orderFactory = $emulatorOrderFactory;
        $this->_modelAddressFactory = $emulatorOrderAddressFactory;
        $this->_orderItemCollectionFactory = $emulatorOrderItemCollectionFactory;
    }


    /**
     * @param \Magento\Sales\Model\Order $order
     * @param bool $updateStock
     * @return array
     */
    public function generateAssignationByOrder(\Magento\Sales\Model\Order $order, $updateStock = false)
    {
        $this->_productIdsToAssignedQty = [];

        /*group item by address*/
        $groupItemByAddress = $this->groupOrderItems($order);

        $allowMultipleAssign = $this->assignationHelper->isAllowMultipleAssignation($order);

        $assignTo = $this->generateAssignationReadOnly($groupItemByAddress['groups'], $allowMultipleAssign);

        unset($assignTo['status']);
        unset($assignTo[self::ASSIGN_TO_WAREHOUSE_STATUS_KEY]);

        return ["inventory" => $assignTo, "log" => $this->log];
    }

    /**
     * @param \Magento\Sales\Model\Order $order
     * @return array
     */
    public function groupOrderItems(\Magento\Sales\Model\Order $order)
    {
        $result = [];

        $productIds = [];

        $groupItemAddress = [];

        $availablePlaces = $this->assignationHelper->getAvailablePlacesByOrder($order);

        $orderedItems = $this->getAssignationByOrderId($order->getId())->getItems();

        $shippingAddress = $order->getShippingAddress();

        $groupItemAddress['address'] = $shippingAddress;

        $groupItemAddress['destination'] = [
            'country_code'    =>  $shippingAddress->getCountryId(),
            'region_code'     =>  $shippingAddress->getData('region_code'),
            'postcode'        =>  $shippingAddress->getPostcode()
        ];

        $groupItemAddress['places'] = $availablePlaces;

        $groupItemAddress['items'] = [];

        foreach ($orderedItems as $item) {
            $groupItemAddress['items'][] = $item->getData();
            $productIds[] = $item->getProductId();
            $groupItemAddress['delivery_type']  = $item->getData('delivery_type');
        }

        $result[$shippingAddress->getId()] = $groupItemAddress;

        return [
            'product_ids'   =>  $productIds,
            'groups'    =>  $result
        ];
    }

    /**
     * @param $productId
     * @param $placeId
     * @param $qtyToAssign
     * @param $itemId
     * @return array
     */
    public function checkAvailability(
        $productId,
        $placeId,
        $qtyToAssign,
        $itemId
    ) {

        if (!isset($this->_productIdToQtyProductLevel[$productId])) {
            $globalInventory = $this->_stockFactory->create()->getStockSettings($productId);
            $this->_productIdToQtyProductLevel[$productId] = $globalInventory->getQuantityInStock();
        }

        $qtyProductLevel = $this->_productIdToQtyProductLevel[$productId];

        $inventory = $this->_stockFactory->create()->getStockSettings($productId, $placeId, [], $itemId);
        $inventory->setQuantityInStock(999999);

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
        } elseif ($qty >= $inventory->getMinQty()) {
            $this->log .= "Qty is available!\r\n";
            $remainingQtyAssign = 0;
            $qtyAssigned = $qtyToAssign;
            $status = 3;
        } elseif ($inventory->getBackorderableAtStockLevel()
            && $qtyProductLevel - $qtyToAssign < $inventory->getMinQty()
        ) {
            if (($inventory->getBackorderlimitAtStockLevel() + $inventory->getQuantityInStock()) - $qtyToAssign >= $inventory->getMinQty()) {
                $this->log .= "Backorder allowed!\r\n";
                $remainingQtyAssign = 0;
                $qtyAssigned = $qtyToAssign;
                $status = 2;
            } else {
                $this->log .= "Backorder is over limit!\r\n";
                $status = 0;
            }
        } elseif ($inventory->getQuantityInStock() > $inventory->getMinQty() && $qty < 0) {
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
}
