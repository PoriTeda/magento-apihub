<?php

namespace Riki\AdvancedInventory\Cron;

use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Exception\LocalizedException;
use Riki\AdvancedInventory\Exception\WarehouseOutOfStockException;
use Riki\AdvancedInventory\Model\Config\Source\ReAssignation\Status;
use Riki\AdvancedInventory\Model\ReAssignation as ReAssignationModel;
use Riki\Framework\Helper\Cron;
use Riki\Shipment\Model\ResourceModel\Status\Options\Shipment as ShipmentStatus;
use Riki\AdvancedInventory\Model\ResourceModel\ReAssignation\CollectionFactory as ReAssignationCollectionFactory;

class ReAssignation
{
    const MAX_PROCESS_ITEM = 1000;
    const IS_REASSIGNATION_CRON_NAME = 'is_reassignation_cron';

    /**
     * @var \Riki\AdvancedInventory\Model\ResourceModel\ReAssignation\CollectionFactory
     */
    protected $reAssignationCollectionFactory;

    /**
     * @var \Riki\ShipLeadTime\Api\LeadtimeRepositoryInterface
     */
    protected $leadTimeRepository;

    /**
     * @var \Riki\PointOfSale\Helper\Data
     */
    protected $posHelper;

    /**
     * @var \Magento\Framework\Api\SearchCriteriaBuilder
     */
    protected $searchCriteriaBuilder;

    /**
     * @var \Riki\AdvancedInventory\Model\Assignation
     */
    protected $assignationModel;

    /**
     * @var array
     */
    protected $activeWarehouses = [];

    /**
     * @var array
     */
    protected $warehouses;

    /**
     * @var \Magento\Framework\Filesystem
     */
    protected $filesystem;

    /**
     * @var \Magento\Framework\ObjectManagerInterface
     */
    protected $objectManager;

    /**
     * @var \Magento\Framework\Registry
     */
    private $registry;

    /**
     * @var $this
     */
    protected $cronHelper;

    /**
     * ReAssignation constructor.
     * @param ReAssignationCollectionFactory $reAssignationCollectionFactory
     * @param \Riki\ShipLeadTime\Api\LeadtimeRepositoryInterface $leadTimeRepository
     * @param \Riki\PointOfSale\Helper\Data $posHelper
     * @param \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder
     * @param \Riki\AdvancedInventory\Model\Assignation $assignationModel
     * @param \Magento\Framework\Filesystem $filesystem
     * @param \Magento\Framework\Registry $registry
     * @param Cron $cronHelper
     * @param \Magento\Framework\ObjectManagerInterface $objectManager
     */
    public function __construct(
        ReAssignationCollectionFactory $reAssignationCollectionFactory,
        \Riki\ShipLeadTime\Api\LeadtimeRepositoryInterface $leadTimeRepository,
        \Riki\PointOfSale\Helper\Data $posHelper,
        \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder,
        \Riki\AdvancedInventory\Model\Assignation $assignationModel,
        \Magento\Framework\Filesystem $filesystem,
        \Magento\Framework\Registry $registry,
        \Riki\Framework\Helper\Cron $cronHelper,
        \Magento\Framework\ObjectManagerInterface $objectManager
    ) {
        $this->reAssignationCollectionFactory = $reAssignationCollectionFactory;
        $this->leadTimeRepository = $leadTimeRepository;
        $this->posHelper = $posHelper;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->assignationModel = $assignationModel;
        $this->filesystem = $filesystem;
        $this->objectManager = $objectManager;
        $this->registry = $registry;
        $this->cronHelper = $cronHelper->setLockFileName('re_assignation.lock');
        $this->warehouses = $this->getWarehouses();
    }

    /**
     *
     */
    public function execute()
    {
        if ($this->cronHelper->isLocked()) {
            throw new LocalizedException($this->cronHelper->getLockMessage());
        }

        $this->cronHelper->lockProcess();

        $logger = $this->getLogger();

        $this->registry->unregister(self::IS_REASSIGNATION_CRON_NAME);
        $this->registry->register(self::IS_REASSIGNATION_CRON_NAME, true);

        $items = $this->getReAssignationItems();

        /** @var \Riki\AdvancedInventory\Model\ReAssignation $item */
        foreach ($items as $item) {
            $logger->info(__('Start to process for item #%1', $item->getId()));

            try {
                $item->load($item->getId());

                $this->validate($item);

                $data = $this->generateAssignationRequestData($item->getOrder(), $item->getData('warehouse_code'));

                $data['updated_by'] = 'Re-assignation tool';

                $this->assignationModel->update($item->getOrder()->getId(), $data);

                $item->setData('status', Status::STATUS_SUCCESS);

                $logger->info(__('Updated successfully for order #%1', $item->getData('order_increment_id')));
            } catch (WarehouseOutOfStockException $e) {
                $item->setData('status', Status::STATUS_FAILURE);
                $item->setData('message', __('Due to out of stock, we cannot assign this order to new warehouse'));

                $logger->error(__($e->getMessage()));
            } catch (\Exception $e) {
                $item->setData('status', Status::STATUS_FAILURE);
                $item->setData('message', $e->getMessage());

                $logger->critical($e);
            }

            try {
                $item->save();
            } catch (\Exception $e) {
                $logger->critical($e);
            }

            $logger->info(__('Process finish for item #%1', $item->getId()));
        }

        $this->registry->unregister(self::IS_REASSIGNATION_CRON_NAME);

        $this->cronHelper->unLockProcess();
    }

    /**
     * @param ReAssignationModel $reAssignation
     * @return $this
     * @throws LocalizedException
     */
    public function validate(ReAssignationModel $reAssignation)
    {
        /** @var \Magento\Sales\Model\Order $order */
        $order = $reAssignation->getOrder();

        if (!$order || !$order->getId()) {
            throw new LocalizedException(
                __('Order Increment Id %1 is not existed', $reAssignation->getData('order_increment_id'))
            );
        }

        if (!$this->assignationModel->getAssignationHelper()->canAssignOrder($order)) {
            throw new LocalizedException(__('Order is not allowed to assign to warehouses.'));
        }

        $shippedShipmentCount = $order->getShipmentsCollection()
            ->addFieldToFilter('shipment_status', ['in' => [
                ShipmentStatus::SHIPMENT_STATUS_SHIPPED_OUT,
                ShipmentStatus::SHIPMENT_STATUS_DELIVERY_COMPLETED,
                ShipmentStatus::SHIPMENT_STATUS_SHIPPED_OUT_PARTIAL,
                ShipmentStatus::SHIPMENT_STATUS_DELIVERY_COMPLETED_PARTIAL
            ]])
            ->getPageSize();

        if ($shippedShipmentCount) {
            throw new LocalizedException(__('Order cannot change warehouse as the shipment already sent to customer'));
        }

        $this->validateWarehouse($reAssignation->getData('warehouse_code'));

        return $this;
    }

    /**
     * @param \Magento\Sales\Model\Order $order
     * @param mixed $warehouseCode
     * @return array
     * @throws LocalizedException
     */
    protected function generateAssignationRequestData(\Magento\Sales\Model\Order $order, $warehouseCode)
    {
        $result = [];

        /** @var \Magento\Sales\Model\Order\Item $item */
        foreach ($order->getItemsCollection() as $item) {
            if (!$item->getHasChildren()) {
                $pos = [];

                foreach ($this->warehouses as $placeCode => $placeId) {
                    $pos[$placeId]['qty_assigned'] = $placeCode == $warehouseCode ? $item->getQtyOrdered() : 0;
                }

                $result[$item->getId()] = [
                    'product_id'    => $item->getProductId(),
                    'qty_to_assign' => $item->getQtyOrdered(),
                    'pos'   => $pos
                ];
            }
        }

        return ['inventory' => ['items'    => $result]];
    }

    /**
     * @return \Magento\Framework\DataObject[]
     */
    public function getReAssignationItems()
    {
        /** @var \Riki\AdvancedInventory\Model\ResourceModel\ReAssignation\Collection $collection */
        $collection = $this->reAssignationCollectionFactory->create();

        $collection->addFieldToFilter('status', Status::STATUS_WAITING)
            ->setOrder('created_at', \Magento\Framework\Data\Collection::SORT_ORDER_ASC)
            ->setPageSize(self::MAX_PROCESS_ITEM);

        return $collection->getItems();
    }

    /**
     * @param mixed $warehouseCode
     * @return $this
     * @throws LocalizedException
     */
    public function validateWarehouse($warehouseCode)
    {
        if (empty($warehouseCode)) { // empty warehouse code <=> clear assignation
            return $this;
        }

        if (!isset($this->activeWarehouses[$warehouseCode])) {
            $leadTimeCount = $this->leadTimeRepository->getList(
                $this->searchCriteriaBuilder->addFilter('warehouse_id', $warehouseCode)
                    ->addFilter('is_active', 1)
                    ->addFilter('priority', true, 'notnull')
                    ->create()
            )->getTotalCount();

            $this->activeWarehouses[$warehouseCode] = $leadTimeCount > 0;
        }

        if (!$this->activeWarehouses[$warehouseCode]) {
            throw new LocalizedException(
                __('Warehouse code for %1 is not available on database or inactive.', $warehouseCode)
            );
        }

        return $this;
    }

    /**
     * @return array
     */
    public function getWarehouses()
    {
        $result = [];

        $warehouses = $this->posHelper->getPlaces();

        foreach ($warehouses as $id => $warehouse) {
            $result[$warehouse->getStoreCode()] = $id;
        }

        return $result;
    }

    /**
     * @return \Monolog\Logger
     */
    protected function getLogger()
    {
        /** @var \Magento\Framework\Logger\Handler\Base $infoHandle */
        $handle = $this->objectManager->create(
            \Magento\Framework\Logger\Handler\Base::class,
            [
                'filePath' => $this->filesystem->getDirectoryWrite(DirectoryList::LOG)->getAbsolutePath(
                    'Riki_ReAssignation/' . date('Y-m-d') . '/' . date('His') . '.log'
                )
            ]
        );

        return $this->objectManager->create(\Riki\AdvancedInventory\Logger\LoggerReAssign::class, [
            'name' => 'Riki_ReAssignation',
            'handlers' => [$handle]
        ]);
    }
}
