<?php

namespace Riki\AdvancedInventory\Model;

use Magento\Backend\App\Area\FrontNameResolver;
use Magento\Framework\Exception\LocalizedException;
use Wyomind\AdvancedInventory\Helper\Journal  as JournalHelper;
use Riki\CreateProductAttributes\Model\Product\CaseDisplay;
use Wyomind\AdvancedInventory\Helper\Journal;

class Assignation extends \Wyomind\AdvancedInventory\Model\Assignation
{
    const STOCK_STATUS_LEAD_TIME_INACTIVE = -1;
    const STOCK_STATUS_UNAVAILABLE = 0;
    const STOCK_STATUS_AVAILABLE_PARTIAL = 1;
    const STOCK_STATUS_AVAILABLE_BACK_ORDER = 2;
    const STOCK_STATUS_AVAILABLE = 3;
    const STOCK_STATUS_AVAILABLE_DISABLE_MANAGE = 4;
    const ASSIGNED_WAREHOUSE_ID = 'assigned_warehouse_id';

    const ASSIGN_TO_WAREHOUSE_STATUS_KEY = 'wh_assign_status';

    const SKIP_ORDER_ASSIGN_FLAG = 'skip_order_assign_flag';

    protected $itemFactory;
    /**
     * @var \Magento\Framework\DB\Adapter\AdapterInterface
     */
    protected $salesConnection;

    /**
     * @var \Riki\Sales\Helper\Address
     */
    protected $addressHelper;

    /**
     * @var \Magento\Catalog\Api\ProductRepositoryInterface
     */
    protected $productRepository;

    /**
     * @var \Riki\AdvancedInventory\Api\StockManagementInterface
     */
    protected $adStockManagementInterface;

    /**
     * @var \Riki\AdvancedInventory\Api\StockRepositoryInterface
     */
    protected $adStockRepositoryInterface;

    /**
     * @var \Riki\Framework\Helper\Cache\FunctionCache
     */
    protected $functionCache;

    /**
     * @var \Riki\AdvancedInventory\Helper\Assignation
     */
    protected $assignationHelper;

    /**
     * @var AssignationUpdateValidator
     */
    protected $assignationUpdateValidator;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @var \Magento\Framework\App\State
     */
    private $appState;

    protected $productIdsToAssignedQty = [];

    protected $assignationByOrder = [];

    /**
     * DEFAULT TOYO = 1;
     */
    const TYPE_WAREHOUSE = 1;

    /**
     * Assignation constructor.
     * @param \Riki\Framework\Helper\Cache\FunctionCache $functionCache
     * @param \Magento\Framework\Model\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Wyomind\Core\Helper\Data $helperCore
     * @param \Wyomind\AdvancedInventory\Helper\Data $helperData
     * @param JournalHelper $journalHelper
     * @param \Wyomind\AdvancedInventory\Model\StockFactory $stockFactory
     * @param \Wyomind\PointOfSale\Model\PointOfSaleFactory $posFactory
     * @param \Magento\Framework\App\RequestInterface $requestInterface
     * @param \Magento\CatalogInventory\Model\StockRegistry $stockRegistry
     * @param \Magento\Sales\Model\OrderFactory $orderFactory
     * @param \Wyomind\AdvancedInventory\Model\ResourceModel\Order\Item\CollectionFactory $orderItemCollectionFactory
     * @param \Magento\Sales\Model\Order\ItemRepository $orderItemRepository
     * @param \Magento\Sales\Model\Order\AddressFactory $modelAddressFactory
     * @param \Magento\Directory\Model\RegionFactory $regionFactory
     * @param \Magento\Framework\App\ResourceConnection $appResource
     * @param \Wyomind\AdvancedInventory\Logger\Logger $logger
     * @param \Magento\Sales\Model\Order\ItemFactory $itemFactory
     * @param \Riki\Sales\Helper\ConnectionHelper $connectionHelper
     * @param \Riki\Sales\Helper\Address $addressHelper
     * @param \Magento\Catalog\Api\ProductRepositoryInterface $productRepository
     * @param \Riki\AdvancedInventory\Helper\Assignation $assignationHelper
     * @param \Riki\AdvancedInventory\Api\StockManagementInterface $stockManagement
     * @param AssignationUpdateValidator $assignationUpdateValidator
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Framework\Model\ResourceModel\AbstractResource|null $abstractResource
     * @param \Magento\Framework\Data\Collection\AbstractDb|null $abstractDb
     * @param array $data
     */
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
        \Magento\Sales\Model\Order\ItemRepository $orderItemRepository,
        \Magento\Sales\Model\Order\AddressFactory $modelAddressFactory,
        \Magento\Directory\Model\RegionFactory $regionFactory,
        \Magento\Framework\App\ResourceConnection $appResource,
        \Wyomind\AdvancedInventory\Logger\Logger $logger,
        \Magento\Sales\Model\Order\ItemFactory $itemFactory,
        \Riki\Sales\Helper\ConnectionHelper $connectionHelper,
        \Riki\Sales\Helper\Address $addressHelper,
        \Magento\Catalog\Api\ProductRepositoryInterface $productRepository,
        \Riki\AdvancedInventory\Helper\Assignation $assignationHelper,
        \Riki\AdvancedInventory\Api\StockManagementInterface $stockManagement,
        \Riki\AdvancedInventory\Model\AssignationUpdateValidator $assignationUpdateValidator,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Framework\Model\ResourceModel\AbstractResource $abstractResource = null,
        \Magento\Framework\Data\Collection\AbstractDb $abstractDb = null,
        array $data = []
    ) {
        $this->functionCache = $functionCache;
        $this->itemFactory = $itemFactory;
        $this->salesConnection = $connectionHelper->getSalesConnection();
        $this->addressHelper = $addressHelper;
        $this->productRepository = $productRepository;
        $this->assignationHelper = $assignationHelper;
        $this->adStockManagementInterface = $stockManagement;
        $this->adStockRepositoryInterface = $assignationHelper->getStockRepositoryInterface();
        $this->assignationUpdateValidator = $assignationUpdateValidator;
        $this->assignationUpdateValidator->setAssignationModel($this);
        $this->scopeConfig = $scopeConfig;
        $this->appState = $context->getAppState();

        parent::__construct(
            $context,
            $registry,
            $helperCore,
            $helperData,
            $journalHelper,
            $stockFactory,
            $posFactory,
            $requestInterface,
            $stockRegistry,
            $orderFactory,
            $orderItemCollectionFactory,
            $orderItemRepository,
            $modelAddressFactory,
            $regionFactory,
            $appResource,
            $logger,
            $abstractResource,
            $abstractDb,
            $data
        );
    }

    /**
     * @return \Riki\AdvancedInventory\Helper\Assignation
     */
    public function getAssignationHelper()
    {
        return $this->assignationHelper;
    }

    /**
     * @param \Magento\Sales\Model\Order $order
     * @param bool $updateStock
     * @return array
     * @throws \Exception
     */
    public function generateAssignationByOrder(\Magento\Sales\Model\Order $order, $updateStock = false)
    {
        $this->order = $order;

        if (!$this->assignationHelper->canAssignOrder($order)) {
            return [
                "inventory" => ["place_ids" => ""],
                "log" => ""
            ];
        }

        $this->productIdsToAssignedQty = [];

        $assignTo = ["place_ids" => []];
        $this->log .= "\r\n-----------------------------------------------------------------------------\r\n";
        $this->log .= "------------Start assignation process for order #" . $order->getIncrementId() ."-".$order->getQuoteId().
            " ------------------\r\n";
        $this->log .= "-----------------------------------------------------------------------------\r\n\r\n";

        $this->log .= "Shipping method : " . $order->getShippingMethod() . "\r\n\r\n";

        $exception = null;

        if (stripos($order->getShippingMethod(), 'pickupatstore') !== false) {
            $assignation = substr($order->getShippingMethod(), stripos($order->getShippingMethod(), '_') + 1);
            $this->log .= "* * * * * Assign to warehouse ID : " . $assignation . "\r\n\r\n";
        } else {
            /*group item by address*/
            $groupItemByAddress = $this->groupOrderItems($order);

            $allowMultipleAssign = $this->assignationHelper->isAllowMultipleAssignation($order);

            if ($updateStock) {
                try {
                    $assignTo = $this->generateAssignationAndUpdateStock($groupItemByAddress, $allowMultipleAssign);
                } catch (\Exception $e) {
                    $exception = $e;
                }
            } else {
                $assignTo = $this->generateAssignationReadOnly($groupItemByAddress['groups'], $allowMultipleAssign);
            }
        }

        $scope = in_array($this->appState->getAreaCode(), ['crontab', FrontNameResolver::AREA_CODE])
            ? \Magento\Framework\App\Config\ScopeConfigInterface::SCOPE_TYPE_DEFAULT
            : \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE;
        if ($this->scopeConfig->getValue('advancedinventory/system/log_enabled', $scope)) {
            $this->_logger->notice($this->log);
        }

        if (isset($exception)) {
            throw $exception;
        }

        unset($assignTo[self::ASSIGN_TO_WAREHOUSE_STATUS_KEY]);

        return ["inventory" => $assignTo, "log" => $this->log];
    }

    /**
     * @param array $groupItemByAddress
     * @param $allowMultipleAssign
     * @return array
     * @throws LocalizedException
     * @throws \Exception
     */
    protected function generateAssignationAndUpdateStock(array $groupItemByAddress, $allowMultipleAssign)
    {
        /*need to generate assignation data */
        $assignation = false;

        return $this->updateStockByAssignationData(
            $allowMultipleAssign,
            $assignation,
            $groupItemByAddress['product_ids'],
            $groupItemByAddress['groups'],
            true
        );
    }

    /**
     * update stock data by assignation data
     *
     * @param $allowMultipleAssign
     * @param bool $assignTo
     * @param array $productIds
     * @param array $groupItemByAddress
     * @param bool $deductStock
     *
     * @return array|bool
     *
     * @throws LocalizedException
     * @throws \Exception
     */
    public function updateStockByAssignationData(
        $allowMultipleAssign,
        $assignTo = false,
        array $productIds = [],
        array $groupItemByAddress = [],
        $deductStock = true
    ) {
        /** @var \Riki\AdvancedInventory\Model\ResourceModel\Stock $stockResource */
        $stockResource = $this->_stockFactory->create()->getResource();

        $transactionConn = $stockResource->getTransactionConnection();
        $transactionConn->beginTransaction();

        try {
            if (empty($productIds)) {
                $productIds = $this->getProductListByAssignationData($assignTo);
            }

            $stockData = $stockResource->lockProductsStocks($productIds);

            $stockAssign = [];

            if (!$assignTo) {
                $assignTo = $this->generateAssignationReadOnly($groupItemByAddress, $allowMultipleAssign);
            } else {
                $assignTo['status'] = true;
            }

            $assignedPlaceIds = explode(',', $assignTo['place_ids']);

            if (isset($assignTo['status']) &&
                $assignTo['status'] &&
                !empty($assignedPlaceIds) &&
                !in_array(0, $assignedPlaceIds)
            ) {
                if (!empty($assignTo['items'])) {
                    $this->updateWarehouseStockByAssignData($assignTo['items'], $stockResource, $deductStock);
                }
            } else {
                $messages = [];

                $message = $this->getAssignationErrorMessages($assignTo);

                if (!empty($message)) {
                    $messages[] = $message;
                }

                if (!empty($assignTo['message'])) {
                    $messages[] = $assignTo['message'];
                }

                if (empty($messages)) {
                    $messages = [__('Not all of your products are available in the requested quantity.')];
                }

                throw new \Riki\AdvancedInventory\Exception\AssignationException(__(implode('; ', $messages)));
            }

            $transactionConn->commit();

            $this->_eventManager->dispatch('validate_product_stock_after_assigned', [
                'stockData' => $stockData,
                'stockAssign' => $stockAssign,
                'order' => $this->order
            ]);
        } catch (LocalizedException $e) {
            $this->log .= $e->getMessage();
            $transactionConn->rollBack();
            throw $e;
        } catch (\Exception $e) {
            $this->_logger->critical($e);
            $transactionConn->rollBack();
            throw $e;
        }

        return $assignTo;
    }

    /**
     * @param array $assignTo
     * @return \Magento\Framework\Phrase|null
     */
    protected function getAssignationErrorMessages(array $assignTo)
    {
        if (empty($assignTo['items'])) {
            $assignTo['items'] = [];
        }

        /** @var \Magento\Sales\Model\Order $order */
        $order = $this->order;

        $qtyAssignedItems = [];

        foreach ($assignTo['items'] as $itemId => $itemAssignedData) {
            $qtyAssigned = 0;

            foreach ($itemAssignedData['pos'] as $placeId => $itemPlaceAssignedData) {
                $qtyAssigned += $itemPlaceAssignedData['qty_assigned'];
            }

            $qtyAssignedItems[$itemId] = $qtyAssigned;
        }

        $errorNames = [];

        /** @var \Magento\Sales\Model\Order\Item $orderItem */
        foreach ($order->getItemsCollection() as $orderItem) {
            if ($orderItem->getHasChildren()) {
                continue;
            }

            $assignedItems = array_keys($qtyAssignedItems);

            if ((!in_array($orderItem->getId(), $assignedItems) && !$orderItem->getData('chirashi'))
                || (in_array($orderItem->getId(), $assignedItems)
                    && $qtyAssignedItems[$orderItem->getId()] < $orderItem->getQtyOrdered()
                )
            ) {
                if ($parentItem = $orderItem->getParentItem()) {
                    $errorNames[] = $parentItem->getName();
                } else {
                    $errorNames[] = $orderItem->getName();
                }
            }
        }

        if (!empty($errorNames)) {
            return __(
                '%1 is out of stock. ' .
                'I am sorry to trouble you but please delete the item and select the Confirm order button again. ' .
                'To delete an item select Addressee and payment method at the top ' .
                'you can return to the previous screen and delete it.',
                implode(', ', array_unique($errorNames))
            );
        }

        return null;
    }

    /**
     * @param array $assignItemsData
     * @param ResourceModel\Stock $stockResource
     * @param bool $deductStock
     * @return $this
     */
    private function updateWarehouseStockByAssignData(
        array $assignItemsData,
        \Riki\AdvancedInventory\Model\ResourceModel\Stock $stockResource,
        $deductStock = false
    ) {
        foreach ($assignItemsData as $itemId => $item) {
            if (empty($stockAssign[$item['product_id']])) {
                $stockAssign[$item['product_id']] = [];
                $stockAssign[$item['product_id']]['assgined'] = 0;
            }

            foreach ($item["pos"] as $placeId => $pos) {
                if (!isset($stockAssign[$item['product_id']][$placeId])) {
                    $stockAssign[$item['product_id']][$placeId] = 0;
                }

                if ($deductStock) {
                    $stockAssign[$item['product_id']]['assgined'] -= $pos['qty_assigned'];
                    $stockAssign[$item['product_id']][$placeId] -= $pos['qty_assigned'];
                    $stockResource->correctItemsQty([$item['product_id'] => $pos['qty_assigned']], $placeId, '-');
                } else {
                    $stockAssign[$item['product_id']]['assgined'] += $pos['qty_assigned'];
                    $stockAssign[$item['product_id']][$placeId] += $pos['qty_assigned'];
                    $stockResource->correctItemsQty([$item['product_id'] => $pos['qty_assigned']], $placeId, '+');
                }
            }
        }

        return  $this;
    }

    /**
     * @param array $groupItemByAddress
     * @param $allowMultipleAssign
     * @return array
     */
    protected function generateAssignationReadOnly(array $groupItemByAddress, $allowMultipleAssign)
    {
        $assignTo = [
            'place_ids' => [],
            self::ASSIGN_TO_WAREHOUSE_STATUS_KEY  =>  []
        ];

        /*get assignation data for each address and push to $assignTo*/
        foreach ($groupItemByAddress as $groupItem) {
            $assignTo = $this->assignationProcess($assignTo, $groupItem, $allowMultipleAssign);
        }

        $this->_eventManager->dispatch('sales_order_generated_assignation_after', [
            'order' =>  $this->order,
            'assignation_data' => $assignTo
        ]);

        sort($assignTo["place_ids"]);

        $assignTo["place_ids"] = implode(",", array_unique($assignTo["place_ids"]));

        return $assignTo;
    }

    /**
     * @param $order
     * @return array
     * @throws \Exception
     */
    public function run($order)
    {
        $this->productIdsToAssignedQty = [];

        if ($this->order != null) {
            $order = $this->order;
        }

        if (!$order instanceof  \Magento\Sales\Model\Order) {
            $order = $this->_orderFactory->create()->load($order);
        }

        if ($assignTo = $order->getData('assignation')) {
            try {
                $assignTo = \Zend_Json::decode($assignTo);

                if (!empty($assignTo['place_ids']) && !empty($assignTo['items'])) {
                    return ["inventory" => $assignTo, "log" => $this->log];
                }
            } catch (\Exception $e) {
                return null;
            }
        }

        $defaultConnection = $this->getResource()->getConnection();
        $salesConnection = $order->getResource()->getConnection();

        try {
            $defaultConnection->beginTransaction();
            $salesConnection->beginTransaction();

            $newAssignation = $this->generateAssignationByOrder($order, false);

            $this->update($order->getId(), $this->generateUpdateDataForNewAssignation($order, $newAssignation['inventory']));

            $defaultConnection->commit();
            $salesConnection->commit();

            return $newAssignation;
        } catch (\Exception $e) {
            $defaultConnection->rollBack();
            $salesConnection->rollBack();
            throw $e;
        }
    }

    /**
     * @param \Magento\Sales\Model\Order $order
     * @param array $newAssignationData
     * @return array
     */
    protected function generateUpdateDataForNewAssignation(\Magento\Sales\Model\Order $order, array $newAssignationData)
    {
        $warehouses = $this->assignationHelper->getPointOfSaleHelper()->getPlaces();

        $result = [];

        /** @var \Magento\Sales\Model\Order\Item $item */
        foreach ($order->getItemsCollection() as $item) {
            if (!$item->getHasChildren()) {
                $pos = [];

                foreach ($warehouses as $warehouse) {
                    $placeId = $warehouse->getId();
                    if (isset($newAssignationData['items'][$item->getId()]['pos'][$placeId])) {
                        $assignedQty = $newAssignationData['items'][$item->getId()]['pos'][$placeId]['qty_assigned'];
                    } else {
                        $assignedQty = 0;
                    }
                    $pos[$placeId]['qty_assigned'] = $assignedQty;
                }

                $result[$item->getId()] = [
                    'product_id'    =>  $item->getProductId(),
                    'qty_to_assign' =>  $item->getQtyOrdered(),
                    'pos'   =>  $pos
                ];
            }
        }

        return [
            'inventory' =>  ['items'    =>  $result],
            'updated_by'    =>  $this->_registry->registry('assignation_update_by')
        ];
    }

    /**
     * @param $assignTo
     * @param $orderedItemsArray
     * @param $allowMultipleAssignation
     * @return array
     */
    public function assignationProcess($assignTo, $orderedItemsArray, $allowMultipleAssignation)
    {
        $assignTo['status'] = isset($assignTo['status']) ? $assignTo['status'] : true;

        $places = $orderedItemsArray['places'];

        if (empty($places)) {
            $assignTo['status'] = false;
            return $assignTo;
        }

        $destination = $orderedItemsArray['destination'];
        $this->log .= "Shipped to : " . $destination['country_code'] . ',' . $destination['region_code'] . ',' .
            $destination['postcode'] . "\r\n\r\n";

        $orderedItemsArray = $this->prepareDataBeforeProcessAssignation($orderedItemsArray);

        /*============== RIKI WAREHOUSE CUSTOMIZE ==============*/
        if ($allowMultipleAssignation) {
            $assignTo = $this->assignForCaseAllowMultipleAssign($assignTo, $places, $orderedItemsArray['items']);
        } else {
            $assignTo = $this->assignForCaseNotAllowMultipleAssign($orderedItemsArray['items'], $places, $assignTo);

            if ((isset($assignTo['status']) && !$assignTo['status']) ||
                !isset($assignTo['items']) || ( count($assignTo["items"]) < count($orderedItemsArray['items']))
            ) {
                $assignTo['status'] = false;
            }
        }

        return $assignTo;
    }

    /**
     * @param array $assignTo
     * @param array $places
     * @param array $itemsData
     * @return array
     */
    private function assignForCaseAllowMultipleAssign(array $assignTo, array $places, array $itemsData)
    {
        //check available warehouse can cover all item in cart
        $capacity = $this->getAssignCapacityWarehouse($itemsData, $places);

        /**
         * Scenario 2
         * All items in cart only available in a warehouses
         * In the case Magento needs to select this warehouse
         *
         */
        if ($fullStockWh = $this->getFullStockWarehouse($places, $itemsData, $capacity)) {
            $placeId = $fullStockWh->getPlaceId();

            foreach ($itemsData as $k => $item) {
                if ($item["multistock_enabled"]) {
                    $assignTo = $this->assignProductToFullAvailableWarehouse($assignTo, $placeId, $item);
                } else {
                    $this->log .= "* * Multi-stock is disabled\r\n";
                }
            }
        } else { // Scenario 3 , split cart to both of warehouse
            $bundleWh = $this->getAvailablePlaceForBundle($capacity, $itemsData);

            foreach ($itemsData as $k => $item) {
                $assignTo[self::ASSIGN_TO_WAREHOUSE_STATUS_KEY][$item['item_id']] = [];

                $this->log .= "\r\n- - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -\r\n"
                    . "* Checking availability for : " . $item['name'] . " [ SKU " . $item['sku'] . " ] [ ID " .
                    $item["product_id"] . " ], Ordered Qty : " . $item["qty_to_assign"] . "\r\n";

                if ($item["multistock_enabled"]) {
                    $availablePlace = $this->getFirstAvailablePlaceForProduct($capacity, $item, $bundleWh, $places);

                    if ($availablePlace) {
                        $assignTo = $this->assignProductToFullAvailableWarehouse(
                            $assignTo,
                            $availablePlace->getId(),
                            $item
                        );
                    } elseif (!isset($item["parent_item_id"])) {
                        $assignTo = $this->assignProductToMultipleWarehouse($assignTo, $places, $item);
                    } else {
                        $assignTo['status'] = false;
                    }
                } else {
                    $this->log .= "* * Multi-stock is disabled\r\n";
                }
            }
        }

        return $assignTo;
    }

    /**
     * @param array $assignTo
     * @param $placeId
     * @param array $item
     * @return array
     */
    private function assignProductToFullAvailableWarehouse(array $assignTo, $placeId, array $item)
    {
        $qtyToAssign = $item["qty_to_assign"];

        $this->productIdsToAssignedQty[$item["product_id"]][$placeId] = $this->getSumAssignedQtyProductInPlace(
            $item['product_id'],
            $placeId
        );

        $this->productIdsToAssignedQty[$item["product_id"]][$placeId] += $qtyToAssign;

        $assignTo["place_ids"][] = $placeId;

        $assignTo["items"][$item["item_id"]] = $this->prepareAssignationDataForItem($item);

        $assignTo["items"][$item["item_id"]]["pos"][$placeId]["qty_assigned"] = $qtyToAssign;

        return $assignTo;
    }

    /**
     * @param array $assignTo
     * @param array $places
     * @param array $item
     * @return array
     */
    private function assignProductToMultipleWarehouse(array $assignTo, array $places, array $item)
    {
        $qtyToAssign = $item["qty_to_assign"];

        foreach ($places as $place) {
            $placeId = $place->getPlaceId();
            $this->log .= ". . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . .\r\n";
            $this->log .= "* * Checking warehouse : " . $place->getName() . " [" . $place->getStoreCode() . "]\r\n";

            $available = $this->getAvailableStockInfo(
                $item,
                $placeId,
                $this->getSumAssignedQtyProductInPlace($item['product_id'], $placeId) + $qtyToAssign
            );

            $assignTo[self::ASSIGN_TO_WAREHOUSE_STATUS_KEY][$item['item_id']][$placeId] = $available['status'];

            if ($qtyToAssign < 1) {
                return $assignTo;
            }

            $qtyToAssign = $available['remaining_qty_to_assign'];

            if ($available['status'] >= self::STOCK_STATUS_AVAILABLE_PARTIAL) {
                $qtyAssigned = $available['qty_assigned'] -
                    $this->productIdsToAssignedQty[$item["product_id"]][$placeId];

                $this->log .= "* * * * * * * Assign to warehouse ID : " . $placeId . ", Qty assigned = " .
                    $qtyAssigned . "\r\n\r\n";

                $assignTo["place_ids"][] = $placeId;

                if (!isset($assignTo["items"][$item["item_id"]])) {
                    $assignTo["items"][$item["item_id"]] = $this->prepareAssignationDataForItem($item);
                }

                $assignTo["items"][$item["item_id"]]["pos"][$placeId]["qty_assigned"] = $qtyAssigned;

                $this->productIdsToAssignedQty[$item["product_id"]][$placeId] = $available['qty_assigned'];
            }
            if ($available['status'] >= self::STOCK_STATUS_AVAILABLE_BACK_ORDER) {
                return $assignTo;
            }
        }

        if ($qtyToAssign > 0) {
            $assignTo['status'] = false;
        }

        return $assignTo;
    }

    /**
     * @param array $itemsData
     * @param array $places
     * @param array $assignTo
     * @return array
     */
    private function assignForCaseNotAllowMultipleAssign(array $itemsData, array $places, array $assignTo)
    {
        $countOrderedItems = count($itemsData);
        foreach ($places as $place) {
            $placeId = $place->getPlaceId();

            $this->log .= "\r\n- - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -\r\n";
            $this->log .= "* Checking warehouse : " . $place->getName() . " [" . $place->getStoreCode() . "]\r\n";

            foreach ($itemsData as $item) {
                $assignTo[self::ASSIGN_TO_WAREHOUSE_STATUS_KEY][$item['item_id']] = [];

                $qtyToAssign = $item["qty_to_assign"];
                if ($item["multistock_enabled"]) {
                    $this->log .= ". . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . .\r\n";
                    $this->log .= "* * Checking rules for " . $item['name'] . " [ SKU " . $item['sku'] . " ] [ ID " .
                        $item["product_id"] . " ], Ordered Qty : " . $item["qty_to_assign"] . "\r\n";

                    $available = $this->getAvailableStockInfo(
                        $item,
                        $placeId,
                        $this->getSumAssignedQtyProductInPlace($item['product_id'], $placeId) + $qtyToAssign
                    );

                    $assignTo[self::ASSIGN_TO_WAREHOUSE_STATUS_KEY][$item['item_id']][$placeId] = $available['status'];

                    if ($available['status'] >= self::STOCK_STATUS_AVAILABLE_BACK_ORDER) {
                        $this->log .= "* * * * * * * Assign to warehouse ID : " . $placeId . ", Qty assigned = " .
                            $available['qty_assigned'] . "\r\n\r\n";

                        $assignTo["items"][$item["item_id"]] = $this->prepareAssignationDataForItem($item);

                        $assignTo["items"][$item["item_id"]]["pos"][$placeId]["qty_assigned"] =
                            $available['qty_assigned']
                            - $this->getSumAssignedQtyProductInPlace($item['product_id'], $placeId);

                        $assignTo["place_ids"][] = $placeId;

                        $this->productIdsToAssignedQty[$item["product_id"]][$placeId] = $available['qty_assigned'];

                        continue;
                    } elseif ($available['status'] == self::STOCK_STATUS_LEAD_TIME_INACTIVE) {
                        $assignTo = [
                            "place_ids" => [0],
                            "message" => __("I'm sorry. Your item is a situation " .
                                "you can not deliver to your prefecture. " .
                                "Sorry to inconvenience you, but would you like to delete " .
                                "this product or change it to another product?")
                        ];
                        continue 2;
                    } else {
                        $this->log .= "* * * * * * * Can't assign to warehouse ID : " . $placeId .
                            ", Assignation cancelled\r\n\r\n";
                        $assignTo = ["place_ids" => [0]];
                        continue 2;
                    }
                } else {
                    $this->log .= "* * Multi-stock is disabled\r\n";
                }
            }
            if (isset($assignTo["items"]) && count($assignTo["items"]) == $countOrderedItems) {
                $this->log .= "\r\n-----------------------------------------------------------------------------\r\n";
                $this->log .= "\r\n Assignation found!\r\n";
                $this->log .= "\r\n-----------------------------------------------------------------------------\r\n";
                break;
            }
        }

        return $assignTo;
    }

    /**
     * @param $places
     * @param $itemsData
     * @param array $capacity
     * @return null
     */
    private function getFullStockWarehouse($places, $itemsData, array $capacity)
    {
        $numberItemInCart = count($itemsData);

        $wh = null;

        foreach ($capacity as $key => $value) {
            if (count($value) == $numberItemInCart) {
                $wh = $key;
                break;
            }
        }

        if ($wh) {
            foreach ($places as $place) {
                if ($place->getStoreCode() == $wh) {
                    return $place;
                }
            }
        }

        return null;
    }

    /**
     * @param array $capacity
     * @param array $itemData
     * @param array $bundleWh
     * @param array $places
     * @return bool|mixed
     */
    private function getFirstAvailablePlaceForProduct(array $capacity, array $itemData, array $bundleWh, array $places)
    {
        if (isset($itemData['parent_item_id'])) {
            foreach ($places as $place) {
                if (in_array($itemData["parent_item_id"], $bundleWh[$place->getStoreCode()])) {
                    return $place;
                }
            }
        } else {
            foreach ($capacity as $storeCode => $itemIds) {
                if (in_array($itemData['item_id'], $itemIds)) {
                    foreach ($places as $place) {
                        if ($place->getStoreCode() == $storeCode) {
                            return $place;
                        }
                    }
                }
            }
        }

        return false;
    }

    /**
     * @param $productId
     * @param $placeId
     * @return mixed
     */
    private function getSumAssignedQtyProductInPlace($productId, $placeId)
    {
        if (!isset($this->productIdsToAssignedQty[$productId])) {
            $this->productIdsToAssignedQty[$productId] = [];
        }

        if (!isset($this->productIdsToAssignedQty[$productId][$placeId])) {
            $this->productIdsToAssignedQty[$productId][$placeId] = 0;
        }

        return $this->productIdsToAssignedQty[$productId][$placeId];
    }

    /**
     * @param array $capacity
     * @param array $itemsData
     * @return array
     */
    private function getAvailablePlaceForBundle(array $capacity, array $itemsData)
    {
        $bundleItem = $this->groupBundleItems($itemsData);

        $bundleWh = [];

        foreach ($capacity as $placeCode => $childrenIds) {
            $bundleWh[$placeCode] = [];

            foreach ($bundleItem as $parentId => $children) {
                $available = true;

                foreach ($children as $childId => $childData) {
                    if (!in_array($childData['item_id'], $childrenIds)) {
                        $available = false;
                        break;
                    }
                }

                if ($available) {
                    $bundleWh[$placeCode][] = $parentId;
                }
            }
        }

        return $bundleWh;
    }

    /**
     * @param array $itemsData
     * @return array
     */
    private function groupBundleItems(array $itemsData)
    {
        $bundleItem = [];

        foreach ($itemsData as $k => $item) {
            if ($item["parent_item_id"] && !isset($bundleItem[$item["parent_item_id"]])) {
                $bundleItem[$item["parent_item_id"]] = [];
            }
            if ($item["parent_item_id"] && !array_key_exists($item["item_id"], $bundleItem[$item["parent_item_id"]])) {
                $bundleItem[$item["parent_item_id"]][$item["item_id"]] = $item;
            }
        }

        return $bundleItem;
    }

    /**
     * @param array $itemsData
     * @param array $places
     * @return array
     */
    private function getAssignCapacityWarehouse(array $itemsData, array $places)
    {
        //check available warehouse can cover all item in cart
        $capacity = [];

        foreach ($places as $place) {
            $placeId = $place->getPlaceId();

            //calculate which warehouse can cover all item in order
            $capacity[$place->getStoreCode()] = [];

            $productIdsToQty = [];

            foreach ($itemsData as $k => $item) {
                if (!isset($productIdsToQty[$item["product_id"]])) {
                    $productIdsToQty[$item["product_id"]] = 0;
                }

                if (!isset($this->productIdsToAssignedQty[$item["product_id"]])) {
                    $this->productIdsToAssignedQty[$item["product_id"]] = [];
                }

                if (!isset($this->productIdsToAssignedQty[$item["product_id"]][$placeId])) {
                    $this->productIdsToAssignedQty[$item["product_id"]][$placeId] = 0;
                }

                $productIdsToQty[$item["product_id"]] += $item["qty_to_assign"];

                $qtyToAssign = $productIdsToQty[$item["product_id"]] +
                    $this->productIdsToAssignedQty[$item["product_id"]][$placeId];

                if ($item["multistock_enabled"]) {
                    $available = $this->getAvailableStockInfo($item, $placeId, $qtyToAssign);
                    if ($available['remaining_qty_to_assign'] == 0) {
                        $capacity[$place->getStoreCode()][] = $item["item_id"];
                    }
                }
            }
        }

        return $capacity;
    }

    /**
     * @param $orderedItemsArray
     * @return mixed
     */
    protected function prepareDataBeforeProcessAssignation($orderedItemsArray)
    {
        $regionCode = $orderedItemsArray['destination']['region_code'];

        foreach ($orderedItemsArray['items'] as $k => $item) {
            $orderedItemsArray['items'][$k]['region_code'] = $regionCode;
        }

        return $orderedItemsArray;
    }

    /**
     * @param array $orderedItemData
     * @return array
     */
    public function prepareAssignationDataForItem(array $orderedItemData)
    {
        $result = [];

        $result["product_id"] = $orderedItemData["product_id"];
        // riki data
        $result["base_price"] = $orderedItemData["base_price_incl_tax"];
        $result["price"] = $orderedItemData["price_incl_tax"];
        $result["gw_price"] = $orderedItemData["gw_price"];
        $result["gw_base_price"] = $orderedItemData["gw_base_price"];
        $result["gw_tax_amount"] = $orderedItemData["gw_tax_amount"];
        $result["gw_base_tax_amount"] = $orderedItemData["gw_base_tax_amount"];
        $result["base_discount"] = $orderedItemData["base_discount_amount"] / $orderedItemData['qty_ordered'];
        $result["discount"] = $orderedItemData["discount_amount"] / $orderedItemData['qty_ordered'];
        $result['tax_amount'] = $orderedItemData['tax_amount'] / $orderedItemData['qty_ordered'];

        return $result;
    }

    /**
     * Get Rules warehouse
     *
     * @param $stringrules
     * @return array
     */
    public function getRules($stringrules)
    {
        return explode("\n", $stringrules);
    }

    /**
     * @param $entityId
     * @param $data
     * @param bool $subtractWhStock
     * @return bool
     */
    public function insertWithUpdateWhStock($entityId, $data, $subtractWhStock = false)
    {
        $journal = $this->_journalHelper;
        if (isset($data["inventory"]['items'])) {
            $productsId = [];

            $productsAssignedQty = [];

            foreach ($data["inventory"]['items'] as $itemId => $item) {
                $quantity = 0;
                $productsId[] = $item['product_id'];

                foreach ($item["pos"] as $placeId => $pos) {
                    $qtyAssignedKey = $item['product_id'] . '-' . $placeId;
                    $update['place_id'] = $placeId;
                    $update['item_id'] = $itemId;
                    $update['id'] = null;
                    $update['qty_assigned'] = (float) $pos['qty_assigned'];

                    $quantity += $pos['qty_assigned'];

                    if (!isset($productsAssignedQty[$qtyAssignedKey])) {
                        $productsAssignedQty[$qtyAssignedKey] = 0;
                    }

                    $this->load(null)->setData($update);

                    $stockFactory = $this->_stockFactory->create()->getStockByProductIdAndPlaceId(
                        $item['product_id'],
                        $placeId
                    );

                    if ($stockFactory->getId()) {
                        if ($subtractWhStock) {
                            $this->_journalHelper->insertRow(
                                $journal::SOURCE_PURCHASE,
                                $journal::ACTION_STOCK_QTY,
                                "O#$entityId,P#" . $item['product_id'] . ",W#$placeId",
                                [
                                    'from' => $stockFactory->getQuantityInStock()
                                        - $productsAssignedQty[$qtyAssignedKey],
                                    'to' => ($stockFactory->getQuantityInStock()
                                        - $productsAssignedQty[$qtyAssignedKey]
                                        - $pos['qty_assigned'])
                                ]
                            );
                            $stockFactory->setQuantityInStock(
                                $stockFactory->getQuantityInStock() - $pos['qty_assigned']
                            )->save();
                        } else {
                            $this->_journalHelper->insertRow(
                                $journal::SOURCE_PURCHASE,
                                $journal::ACTION_STOCK_QTY,
                                "O#$entityId,P#" . $item['product_id'] . ",W#$placeId",
                                [
                                    'from' => $stockFactory->getQuantityInStock()
                                        + $productsAssignedQty[$qtyAssignedKey]
                                        + $pos['qty_assigned'],
                                    'to' => $stockFactory->getQuantityInStock() + $productsAssignedQty[$qtyAssignedKey]
                                ]
                            );
                        }
                    }

                    $productsAssignedQty[$qtyAssignedKey] += $pos['qty_assigned'];

                    $this->save();
                }

                $this->functionCache->invalidateByCacheTag(['stock_update_qty_' . $item['product_id']]);
            }

            $productsId = array_unique($productsId);

            foreach ($productsId as $productId) {
                $this->adStockManagementInterface->updateCatalogInventoryStock(
                    $productId,
                    Journal::SOURCE_PURCHASE,
                    $entityId
                );
            }

            $this->_journalHelper->insertRow(
                $journal::SOURCE_PURCHASE,
                $journal::ACTION_ASSIGNATION,
                "O#$entityId",
                ["from" => $data["inventory"]["place_ids"], "to" => $data["inventory"]["place_ids"]]
            );
        }
        return true;
    }

    /**
     * @param $entityId
     * @param $data
     * @return bool
     * @throws \Exception
     */
    public function insert(
        $entityId,
        $data
    ) {

        $this->insertWithUpdateWhStock($entityId, $data, true);

        if (isset($data["inventory"]["place_ids"])) {
            $order = $this->_orderFactory->create()->load($entityId);
            $order->setAssignedTo($data["inventory"]["place_ids"])->save();
        }

        return true;
    }

    /**
     * @param $entityId
     * @param $data
     * @return bool
     * @throws \Exception
     */
    public function update(
        $entityId,
        $data
    ) {

        /** @var \Magento\Sales\Model\Order $order */
        $order = $this->_orderFactory->create()->load($entityId);

        $orderCollection = $order->getItemsCollection();

        $newAssignation = [
            'place_ids' =>  '',
            'items' =>  []
        ];

        $defaultConnection = $this->getResource()->getConnection();
        $salesConnection = $order->getResource()->getConnection();

        try {
            $defaultConnection->beginTransaction();
            $salesConnection->beginTransaction();

            if (!$order || !$order->getId()) {
                throw new LocalizedException(__('Order does not exist.'));
            }

            $this->assignationUpdateValidator->validate($order, $data["inventory"]['items']);

            $assignedItems = $this->getAssignationItemByOrder($order);

            // start to update
            $placeIds = [];
            foreach ($data["inventory"]['items'] as $itemId => $item) {
                $quantity = 0;
                $stockMovement = 0;
                foreach ($item["pos"] as $placeId => $pos) {
                    list($assignationId, $originAssignedQty) = $this->getOriginAssignData(
                        $itemId,
                        $placeId,
                        $assignedItems
                    );

                    $update['place_id'] = $placeId;
                    $update['item_id'] = $itemId;
                    $update['id'] = $assignationId;
                    $update['qty_assigned'] = (float) $pos['qty_assigned'];

                    $quantity += $pos['qty_assigned'];
                    $this->load($update['id'])->setData($update);

                    if (($originAssignedQty - $pos['qty_assigned']) != 0) {
                        $stockMovement += $originAssignedQty - $pos['qty_assigned'];
                        $this->updateQuantityInStockByPlace(
                            $item['product_id'],
                            $placeId,
                            $entityId,
                            $originAssignedQty - $pos['qty_assigned']
                        );
                    }

                    if ($update['qty_assigned'] > 0) {
                        if (!in_array($placeId, $placeIds)) {
                            $placeIds[] = $placeId;
                        }

                        if (!isset($newAssignation['items'][$itemId])) {
                            $newAssignation['items'][$itemId] = $this->prepareAssignationDataForItem(
                                $orderCollection->getItemById($itemId)->getData()
                            );
                            $newAssignation['items'][$itemId]['pos'] = [];
                        }

                        $newAssignation['items'][$itemId]['pos'][$placeId] =
                            ['qty_assigned' => $update['qty_assigned']];
                    }

                    $this->save();
                }
                if ($stockMovement != 0) {
                    $this->updateStockMovement($item['product_id'], $entityId, $stockMovement);
                }
                if ($quantity < $item["qty_to_assign"]) {
                    $placeIds[] = 0;
                }
            }

            $this->saveNewOrderAssignationAfterUpdate(
                $order,
                $newAssignation,
                array_filter($placeIds),
                isset($data['updated_by'])? $data['updated_by'] : null
            );

            $this->_eventManager->dispatch('riki_order_assignation_update_after', ['order' =>  $order]);

            $defaultConnection->commit();
            $salesConnection->commit();

            return true;
        } catch (\Exception $e) {
            $defaultConnection->rollBack();
            $salesConnection->rollBack();
            throw $e;
        }
    }

    /**
     * @param $itemId
     * @param $placeId
     * @param array $assignedItems
     * @return array
     */
    private function getOriginAssignData($itemId, $placeId, array $assignedItems)
    {
        $assignationId = null;
        $originAssignedQty = 0;

        $itemPlaceKey = $itemId . '-' . $placeId;

        if (isset($assignedItems[$itemPlaceKey])) {
            $assignationId = $assignedItems[$itemPlaceKey]->getId();
            $originAssignedQty = $assignedItems[$itemPlaceKey]->getData('qty_assigned');
        }

        return [$assignationId, $originAssignedQty];
    }

    /**
     * @param \Magento\Sales\Model\Order $order
     * @param array $newAssignation
     * @param array $placeIds
     * @param null $updatedBy
     * @return $this
     */
    private function saveNewOrderAssignationAfterUpdate(
        \Magento\Sales\Model\Order $order,
        array $newAssignation,
        array $placeIds,
        $updatedBy = null
    ) {
        $orderId = $order->getId();
        sort($placeIds);
        $assignedTo = implode(",", $placeIds);
        $this->_journalHelper->insertRow(
            JournalHelper::SOURCE_ORDER,
            JournalHelper::ACTION_ASSIGNATION,
            "O#$orderId",
            ["from" => $order->getAssignedTo(), "to" => $assignedTo]
        );

        $newAssignation['place_ids'] = $assignedTo;
        $order->setAssignedTo($assignedTo)
            ->setAssignation(\Zend_Json::encode($newAssignation));

        if ($updatedBy) {
            $order->addStatusHistoryComment(__('Assignation was updated by %1', $updatedBy));
        }
        $order->save();

        return $this;
    }

    /**
     * @param $productId
     * @param $placeId
     * @param $orderId
     * @param $updateQty
     * @return $this
     */
    private function updateQuantityInStockByPlace($productId, $placeId, $orderId, $updateQty)
    {
        $stockFactory = $this->_stockFactory->create()->getStockByProductIdAndPlaceId($productId, $placeId);

        if ($stockFactory->getId()) {
            $this->_journalHelper->insertRow(
                JournalHelper::SOURCE_ORDER,
                JournalHelper::ACTION_STOCK_QTY,
                "O#$orderId,P#" . $productId . ",W#$placeId",
                [
                    "from" => $stockFactory->getQuantityInStock(),
                    "to" => ($stockFactory->getQuantityInStock() + $updateQty)
                ]
            );
            $stockFactory->setQuantityInStock($stockFactory->getQuantityInStock() + $updateQty);
            $stockFactory->save();
        }

        return $this;
    }

    /**
     * @param $productId
     * @param $orderId
     * @param $stockMovement
     * @return $this
     */
    private function updateStockMovement($productId, $orderId, $stockMovement)
    {
        $stockRegistry = $this->_stockRegistry->getStockItem($productId, "product_id");

        $this->_journalHelper->insertRow(
            JournalHelper::SOURCE_ORDER,
            JournalHelper::ACTION_QTY,
            "O#$orderId,P#" . $productId,
            [
                "from" => $stockRegistry->getQty(),
                "to" => $stockRegistry->getQty() + $stockMovement
            ]
        );
        $stockRegistry->setQty($stockRegistry->getQty() + $stockMovement)->save();

        if ($this->_helperCore->getStoreConfig("advancedinventory/settings/auto_update_stock_status")) {
            $inventory = $this->_stockFactory->create()->getStockSettings($productId);
            $isInStockAfter = $inventory->getStockStatus() ? "In stock" : "Out of stock";
            $isInStockBefore = $stockRegistry->getIsInStock() ? "In stock" : "Out of stock";
            if ($inventory->getStockStatus() != $stockRegistry->getIsInStock()) {
                $this->_journalHelper->insertRow(
                    JournalHelper::SOURCE_ORDER,
                    JournalHelper::ACTION_IS_IN_STOCK,
                    "O#$orderId,P#" . $productId,
                    ["from" => $isInStockBefore, "to" => $isInStockAfter]
                );
            }
            $stockRegistry->setIsInStock($inventory->getStockStatus())->save();
        }

        return $this;
    }

    /**
     * @param \Magento\Sales\Model\Order $order
     * @return array
     */
    public function getAssignationItemByOrder(\Magento\Sales\Model\Order $order)
    {
        if (!isset($this->assignationByOrder[$order->getId()])) {
            $collection = $this->getCollection()
                ->addFieldToFilter("item_id", ["in" => $order->getItemsCollection()->getAllIds()]);

            $assignedItemData = [];

            /** @var \Wyomind\AdvancedInventory\Model\Assignation $assignationItem */
            foreach ($collection as $assignationItem) {
                $assignedItemData[$assignationItem->getData('item_id') .
                '-' . $assignationItem->getData('place_id')]  = $assignationItem;
            }

            $this->assignationByOrder[$order->getId()] = $assignedItemData;
        }

        return $this->assignationByOrder[$order->getId()];
    }

    /**
     * @param $orderId
     * @return bool
     */
    public function getAssignationRequired($orderId)
    {
        $items = $this->getAssignationByOrderId($orderId);

        $items = $items->toArray();

        foreach ($items['items'] as $item) {
            if ($item['multistock_enabled']) {
                return true;
            }
        }
        return false;
    }

    /**
     * @param $order
     * @return bool
     * @throws \Exception
     */
    public function cancel($order)
    {
        $entityId = $order->getId();
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

            $revertQty = 0;

            foreach ($placeIds as $placeId => $value) {
                if ($value) {
                    $stockFactory = $this->_stockFactory->create()->getStockByProductIdAndPlaceId(
                        $item['product_id'],
                        $placeId
                    );

                    if ($stockFactory->getId()) {
                        $this->_journalHelper->insertRow(
                            JournalHelper::SOURCE_CANCEL,
                            JournalHelper::ACTION_STOCK_QTY,
                            "O#$entityId,P#" . $productId . ",W#$placeId",
                            [
                                "from" => $stockFactory->getQuantityInStock(),
                                "to" => $stockFactory->getQuantityInStock() + $value
                            ]
                        );
                        $stockFactory->setQuantityInStock($stockFactory->getQuantityInStock() + $value)->save();
                    }

                    $revertQty += $value;
                }
            }

            if ($revertQty) {
                $stockRegistry = $this->_stockRegistry->getStockItem($item['product_id'], "product_id");
                $this->_journalHelper->insertRow(
                    JournalHelper::SOURCE_CANCEL,
                    JournalHelper::ACTION_QTY,
                    "O#$entityId,P#" . $productId,
                    [
                        "from" => $stockRegistry->getQty(),
                        "to" => $stockRegistry->getQty() + $item['qty_ordered']
                    ]
                );

                $stockRegistry->setQty($stockRegistry->getQty() + $revertQty);

                if ($this->_helperCore->getStoreConfig("advancedinventory/settings/auto_update_stock_status")) {
                    $isInStockBefore = $stockRegistry->getIsInStock() ? "In stock" : "Out of stock";

                    $stockRegistry->setIsInStock(1)->save(); // Magento auto convert to out of stock if qty <= 0

                    $isInStockAfter = $stockRegistry->getIsInStock() ? "In stock" : "Out of stock";

                    if ($isInStockBefore != $isInStockAfter) {
                        $this->_journalHelper->insertRow(
                            JournalHelper::SOURCE_CANCEL,
                            JournalHelper::ACTION_IS_IN_STOCK,
                            "O#$entityId,P#" . $productId,
                            ["from" => $isInStockBefore, "to" => $isInStockAfter]
                        );
                    }
                } else {
                    $stockRegistry->save();
                }
            }
        }

        $this->_journalHelper->insertRow(
            JournalHelper::SOURCE_CANCEL,
            JournalHelper::ACTION_ASSIGNATION,
            "O#$entityId",
            ["from" => $order->getAssignedTo(), "to" => 0]
        );

        // update assigned_to
        $order->setAssignedTo(0);

        $connection = $this->salesConnection;
        $tableSog = $connection->getTableName('sales_order_grid');
        $connection->update($tableSog, ["assigned_to" => 0], "entity_id = '" . $entityId . "'");
        return true;
    }

    /**
     * @param $order
     * @return bool|\Magento\Sales\Model\Order\Address
     */
    public function getOrderShippingAddress($order)
    {
        if ($order->getShippingAddress()) {
            try {
                $shippingId = $order->getShippingAddress()->getId();
                /** @var \Magento\Sales\Model\Order\Address $address */
                return $this->_modelAddressFactory->create()->load($shippingId);
            } catch (\Exception $e) {
                $this->_logger->critical($e->getMessage());
            }
        }
        return false;
    }

    /**
     * @param $address
     * @return array
     */
    public function getDestinationByAddress(\Magento\Sales\Model\Order\Address $address)
    {
        $destination = [];
        $destination['country_code'] = $address->getCountryId();

        $regionCode = $address->getData('region_code');

        if ($regionCode === null) {
            $region = $this->_regionFactory->create();

            $regionCode = $region->load($address->getRegionId())->getCode();
        }
        $destination['region_code'] = $regionCode;

        $destination['postcode'] = $address->getPostcode();
        return $destination;
    }

    /**
     * @param \Magento\Sales\Model\Order $order
     * @return array
     */
    protected function groupOrderItems(\Magento\Sales\Model\Order $order)
    {
        $availablePlaces = $this->assignationHelper->getAvailablePlacesByOrder($order);

        $groupItems = $this->groupItemsByAddressAndDeliveryType($order);

        $productIds = [];

        foreach ($groupItems as $key => $groupItem) {
            $groupItems[$key]['places'] = $availablePlaces;

            foreach ($groupItem['items'] as $itemData) {
                $productIds[] = $itemData['product_id'];
            }
        }

        return [
            'product_ids'   =>  $productIds,
            'groups'    =>  $groupItems
        ];
    }

    /**
     * @param \Magento\Sales\Model\Order $order
     * @return array
     */
    public function groupItemsByAddressAndDeliveryType(\Magento\Sales\Model\Order $order)
    {
        $result = [];

        $groupsAddressOrderItem = $this->addressHelper->groupOrderItemsByOrderAddress($order);

        $orderedItems = $this->getAssignationByOrderId($order->getId())->getItems();

        foreach ($groupsAddressOrderItem as $groupAddressOrderItem) {

            /** @var \Magento\Sales\Api\Data\OrderAddressInterface $address */
            $address = $groupAddressOrderItem['address'];
            $orderAddressId = $address->getEntityId();

            foreach ($groupAddressOrderItem['item_ids'] as $orderItemId) {
                if (isset($orderedItems[$orderItemId])) {
                    $deliveryType = $orderedItems[$orderItemId]->getData('delivery_type');

                    $key = $orderAddressId .'-' . $deliveryType;

                    if (!isset($result[$key])) {
                        $result[$key] = [
                            'destination'   =>  [
                                'country_code'    =>  $address->getCountryId(),
                                'region_code'   =>  $address->getData('region_code'),
                                'postcode' =>  $address->getPostcode()
                            ],
                            'delivery_type'    =>  $deliveryType,
                            'items' =>  []
                        ];
                    }

                    $result[$key]['items'][] = $orderedItems[$orderItemId]->getData();
                }
            }
        }

        return $result;
    }

    /**
     * @param $item
     * @return \Magento\Sales\Model\Order\Address|bool
     */
    public function getOrderItemAddress($item)
    {
        return $this->addressHelper->getOrderAddressByOrderItem($item['item_id']);
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
        $this->functionCache->invalidateByCacheTag(['stock_update_qty_' . $productId]);

        /*product stock data for all warehouse*/
        $stockProductLevel = $this->getStockSettingForAllPlaces($productId);

        $availableQtyProductLevel = $stockProductLevel->getQuantityInStock();

        $minQty = $stockProductLevel->getMinQty();

        /*product stock for specific warehouse - placeId*/
        $inventory = $this->getStockSettingForSpecificPlace($productId, $placeId, $itemId);

        $this->log .= "* * * * * Product id ".$productId." - Place id ".$placeId;
        $this->log .= "* * * * * Checking availability, " . $qtyToAssign . " to assign" . ", "
            . (float) $inventory['quantity_in_stock'] . " in stock \r\n";

        $warehouseQty = $inventory->getQuantityInStock();

        $remainingQtyAssign = $qtyToAssign;
        $qtyAssigned = 0;

        $this->log .= "* * * * * * ";

        $status = self::STOCK_STATUS_UNAVAILABLE;

        /*product is not salable*/
        if (!$inventory->getManagedAtStockLevel()) {
            $this->log .= "Qty management disabled!\r\n";

            return $this->generateReturnDataAfterValidateProductStock(
                $status,
                $remainingQtyAssign,
                $qtyAssigned,
                $inventory->getIsQtyDecimal()
            );
        }

        /*product is allowed back order*/
        if ($stockProductLevel->getBackorderableAtProductLevel()) {
            $availableQtyProductLevel += (int) $stockProductLevel->getBackorderLimitInStock();
        }

        /*total product stock is not enough to assign ( < minQty is default Magento logic)*/
        if ($availableQtyProductLevel - $qtyToAssign < $minQty) {
            $this->log .= "Qty is not available!\r\n";
            $status = self::STOCK_STATUS_UNAVAILABLE;

            return $this->generateReturnDataAfterValidateProductStock(
                $status,
                $remainingQtyAssign,
                $qtyAssigned,
                $inventory->getIsQtyDecimal()
            );
        }

        /*current warehouse is allowed back order*/
        if ($inventory->getBackorderableAtStockLevel()) {
            $warehouseQty += (int) $inventory->getBackorderlimitAtStockLevel();
        }

        /*current stock at this warehouse is enough to assign */
        if ($warehouseQty - $qtyToAssign >= 0) {
            $this->log .= "Qty is available!\r\n";
            $remainingQtyAssign = 0;
            $qtyAssigned = $qtyToAssign;
            $status = self::STOCK_STATUS_AVAILABLE;

            return $this->generateReturnDataAfterValidateProductStock(
                $status,
                $remainingQtyAssign,
                $qtyAssigned,
                $inventory->getIsQtyDecimal()
            );
        }

        /*warehouse is in stock, but not enough*/
        if ($warehouseQty > 0) {
            $this->log .= "Qty is partially available!\r\n";

            /*quantity can assigned is the remaining quantity of this warehouse*/
            $qtyAssigned = $warehouseQty;

            /*remaining qty after assigned partial*/
            $remainingQtyAssign = $qtyToAssign - $qtyAssigned;

            $status = self::STOCK_STATUS_AVAILABLE_PARTIAL;

            /*re calculate quantity for case product*/
            try {
                $product = $this->productRepository->getById($productId);

                if ($product->getCaseDisplay() == CaseDisplay::CD_CASE_ONLY) {
                    $unitQty = $product->getUnitQty() ? $product->getUnitQty() : 1;
                    $qtyCase = (int)($qtyAssigned / $unitQty);

                    /*remaining quantity of this warehouse, is not enough for 1 case*/
                    if ($qtyCase <= 0) {
                        $this->log .= "Qty is not available!\r\n";
                        $status = self::STOCK_STATUS_UNAVAILABLE;
                        $remainingQtyAssign = $qtyToAssign;
                        $qtyAssigned = 0;
                    } else {
                        $qtyAssigned = $qtyCase * $unitQty;
                        $remainingQtyAssign = $qtyToAssign - $qtyAssigned;
                    }
                }
            } catch (\Exception $e) {
                $this->log .= $e->getMessage()."\r\n";
            }
        }

        return $this->generateReturnDataAfterValidateProductStock(
            $status,
            $remainingQtyAssign,
            $qtyAssigned,
            $inventory->getIsQtyDecimal()
        );
    }

    /**
     * get stock setting for product
     *
     * @param $productId
     * @return \Magento\Framework\DataObject|mixed
     */
    protected function getStockSettingForAllPlaces($productId)
    {
        /*get all warehouse id*/
        $placeIds = $this->getAssignationHelper()->getPointOfSaleHelper()->getPlaceIds();
        /** @var \Riki\AdvancedInventory\Model\Stock $stockModel */
        $stockModel = $this->_stockFactory->create();

        return $stockModel->getStockSettingsByPlaceIdsAndStoreId($productId, $placeIds, false);
    }

    /**
     * get stock setting for specific place
     *
     * @param $productId
     * @param $placeId
     * @param $item
     * @return \Magento\Framework\DataObject|mixed
     */
    protected function getStockSettingForSpecificPlace($productId, $placeId, $item)
    {
        /** @var \Riki\AdvancedInventory\Model\Stock $stockModel */
        $stockModel = $this->_stockFactory->create();

        return $stockModel->getStockSettingsByPlaceIdAndStoreId($productId, $placeId, [], $item, false);
    }

    /**
     * @param array $itemData order/quote item data
     * @param $placeId
     * @param $requestedQty
     * @return array
     */
    public function getAvailableStockInfo(array $itemData, $placeId, $requestedQty)
    {
        $isOrder = isset($itemData['order_id']);

        return $this->checkAvailability(
            $itemData['product_id'],
            $placeId,
            $requestedQty,
            $isOrder? $itemData['item_id'] : null
        );
    }

    /**
     * Check product is available for quote item
     *
     * @param $productId
     * @param $placeIds
     * @param $qtyToAssign
     * @return array
     */
    public function checkAvailabilityForCartItem($productId, $placeIds, $qtyToAssign)
    {
        /** @var \Riki\AdvancedInventory\Model\Stock $stockModel */
        $stockModel = $this->_stockFactory->create();

        $inventory = $stockModel->getStockSettingsByPlaceIdsAndStoreId($productId, $placeIds, false);

        return $this->validateStockDataForCartItem($inventory, $qtyToAssign);
    }

    /**
     * Validate stock by inventory data
     *
     * @param $inventory
     * @param $qtyToAssign
     * @return array
     */
    private function validateStockDataForCartItem($inventory, $qtyToAssign)
    {
        /*product - quantity in stock*/
        $warehouseQty = $inventory->getQuantityInStock();

        /*quantity after assigned*/
        $qtyAfterAssigned = $warehouseQty - $qtyToAssign;

        /*product min qty*/
        $minQty = $inventory->getMinQty();

        /*quantity can be assigned by this product*/
        $qtyAssigned = 0;
        /*quantity can not be assigned by this product*/
        $remainingQtyAssign = $qtyToAssign;
        /*assign status*/
        $status = 0;

        /*product setting -  Qty Uses Decimals or not*/
        $isQtyDecimal = $inventory->getIsQtyDecimal();

        /*product - stock manage is disabled*/
        if (!$inventory->getManagedAtStockLevel()) {
            return $this->generateReturnDataAfterValidateProductStock(
                $status,
                $remainingQtyAssign,
                $qtyAssigned,
                $isQtyDecimal
            );
        }

        /*product - stock is enough to assign*/
        if ($qtyAfterAssigned >= $minQty) {
            $remainingQtyAssign = 0;
            $qtyAssigned = $qtyToAssign;
            $status = 3;

            return $this->generateReturnDataAfterValidateProductStock(
                $status,
                $remainingQtyAssign,
                $qtyAssigned,
                $isQtyDecimal
            );
        }

        /*product - current stock is not enough but allow back order*/
        if ($inventory->getBackorderableAtStockLevel()) {
            /*back order limit*/
            $qtyAfterAssigned += (int) $inventory->getBackorderLimitInStock();

            /*back order limit is enough to assign*/
            if ($qtyAfterAssigned >= $minQty) {
                $remainingQtyAssign = 0;
                $qtyAssigned = $qtyToAssign;
                $status = 2;

                return $this->generateReturnDataAfterValidateProductStock(
                    $status,
                    $remainingQtyAssign,
                    $qtyAssigned,
                    $isQtyDecimal
                );
            }
        }

        /*product - current stock is not enough*/
        if ($warehouseQty > 0) {
            $remainingQtyAssign = $qtyToAssign - $warehouseQty;
            $qtyAssigned = $warehouseQty;
            $status = 1;

            return $this->generateReturnDataAfterValidateProductStock(
                $status,
                $remainingQtyAssign,
                $qtyAssigned,
                $isQtyDecimal
            );
        }

        $this->generateReturnDataAfterValidateProductStock(
            $status,
            $remainingQtyAssign,
            $qtyAssigned,
            $isQtyDecimal
        );
    }

    /**
     * @param $productId
     * @param $placeId
     * @param $qtyToAssign
     * @param null $deliveryType
     * @param null $regionCode
     * @return array
     */
    public function checkAvailabilityInCart(
        $productId,
        $placeId,
        $qtyToAssign,
        $deliveryType = null,
        $regionCode = null
    ) {

        return $this->checkAvailability($productId, $placeId, $qtyToAssign, null);
    }

    /**
     * get product list by assignation data
     *
     * @param $assignTo
     * @return array
     */
    public function getProductListByAssignationData($assignTo)
    {
        $rs = [];

        if (!empty($assignTo) && !empty($assignTo['items'])) {
            foreach ($assignTo['items'] as $item) {
                array_push($rs, $item['product_id']);
            }
        }

        return $rs;
    }

    /**
     * @param $productId
     * @param $placeId
     * @param $qtyToAssign
     * @param null $deliveryType
     * @param null $regionCode
     * @return array
     */
    public function checkAvailabilityInCartForSimulate(
        $productId,
        $placeId,
        $qtyToAssign,
        $deliveryType = null,
        $regionCode = null
    ) {
        $mutipleAssignation = $this->_helperCore->getStoreConfig(
            "advancedinventory/settings/multiple_assignation_enabled"
        );

        if (!$mutipleAssignation) {
            return $this->checkAvailability($productId, $placeId, $qtyToAssign, null);
        }

        $inventory = $this->_stockFactory->create()->getStockSettings($productId, $placeId, [], null);

        $remainingQtyAssign = 0;
        $qtyAssigned = 0;
        $status = 1;

        return [
            "status" => $status,
            "remaining_qty_to_assign" => $remainingQtyAssign,
            "qty_assigned" => $this->_helperData->qtyFormat($qtyAssigned, $inventory->getIsQtyDecimal())
        ];
    }

    /**
     * @return $this
     */
    public function resetLog()
    {
        $this->log = '';
        return $this;
    }

    /**
     * Generate return data after validate product stock
     *
     * @param $status
     * @param $remainingQtyAssign
     * @param $qtyAssigned
     * @param $isQtyDecimal
     * @return array
     */
    private function generateReturnDataAfterValidateProductStock(
        $status,
        $remainingQtyAssign,
        $qtyAssigned,
        $isQtyDecimal
    ) {
        return [
            "status" => $status,
            "remaining_qty_to_assign" => $remainingQtyAssign,
            "qty_assigned" => $this->_helperData->qtyFormat($qtyAssigned, $isQtyDecimal)
        ];
    }
}
