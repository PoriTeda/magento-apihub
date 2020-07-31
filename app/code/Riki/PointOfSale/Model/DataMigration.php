<?php
namespace Riki\PointOfSale\Model;

class DataMigration
{
    /**
     * @var \Riki\PointOfSale\Helper\Data
     */
    protected $dataHelper;
    /**
     * @var PointOfSaleRepository
     */
    protected $pointOfSaleRepository;

    protected $pointOfSaleFactory;

    protected $searchCriteria;

    protected $logger;
    /**
     * DataMigration constructor.
     * @param \Riki\PointOfSale\Helper\Data $dataHelper
     * @param PointOfSaleRepository $pointOfSaleRepository
     */
    public function __construct(
        \Riki\PointOfSale\Helper\Data $dataHelper,
        \Riki\PointOfSale\Model\PointOfSaleRepository $pointOfSaleRepository,
        \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder,
        \Wyomind\PointOfSale\Model\PointOfSaleFactory $pointOfSaleFactory,
        \Psr\Log\LoggerInterface $logger
    ) {
    
        $this->dataHelper = $dataHelper;
        $this->pointOfSaleRepository = $pointOfSaleRepository;
        $this->searchCriteria = $searchCriteriaBuilder;
        $this->pointOfSaleFactory = $pointOfSaleFactory;
        $this->logger = $logger;
    }

    /**
     * import data
     */
    public function importData()
    {
        $data = $this->dataHelper->getCsvData();
        foreach ($data as $row) {
            $warehouse = $this->getWarehouseByCode($row['store_code']);
            if (!$warehouse) {
                $newWareHouse = $this->pointOfSaleFactory->create();
                foreach ($row as $key => $value) {
                    if ($key!='place_id') {
                        $newWareHouse->setData($key, $value);
                    }
                }
                try {
                    $newWareHouse->save();
                } catch (\Exception $e) {
                    $this->logger->info('Can not create new Warehouse');
                    $this->logger->info($e->getMessage());
                }
            }
        }
    }

    /**
     * @param $warehouseCode
     * @return bool
     */
    public function getWarehouseByCode($warehouseCode)
    {
        $criteria  = $this->searchCriteria->addFilter('store_code', $warehouseCode)->create();
        $collection = $this->pointOfSaleRepository->getList($criteria);
        if ($collection->getTotalCount()) {
            $items = $collection->getItems();
            foreach ($items as $item) {
                return $item;
            }
        }
        return false;
    }

    /**
     * @param $posId
     * @return bool|mixed
     */
    public function getWarehouseById($posId)
    {
        try {
            return $this->pointOfSaleRepository->get($posId);
        } catch (\Exception $e) {
            $this->logger->info('Can not get warehouse data with id: '. $posId);
            $this->logger->info($e->getMessage());
            return false;
        }
    }
}
