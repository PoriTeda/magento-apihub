<?php
namespace Riki\ShipLeadTime\Model;
/**
 * Class ImportData
 * @package Riki\ShipLeadTime\Model
 */
class ImportData
{
    /**
     * @var \Riki\ShipLeadTime\Api\LeadtimeRepositoryInterface
     */
    protected $leadTimeRepository;
    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;
    /**
     * @var \Riki\ShipLeadTime\Helper\Csv
     */
    protected $csvHelper;
    /**
     * @var \Magento\Framework\Api\SearchCriteriaBuilder
     */
    protected $criteriaBuilder;
    /**
     * @var LeadtimeFactory
     */
    protected $shipLeadTimeFactory;

    /**
     * ImportData constructor.
     * @param \Riki\ShipLeadTime\Api\LeadtimeRepositoryInterface $leadtimeRepository
     * @param \Psr\Log\LoggerInterface $logger
     * @param \Riki\ShipLeadTime\Helper\Csv $csvHelper
     * @param \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder
     * @param LeadtimeFactory $leadtimeFactory
     */
    public function __construct(
        \Riki\ShipLeadTime\Api\LeadtimeRepositoryInterface $leadtimeRepository,
        \Psr\Log\LoggerInterface $logger,
        \Riki\ShipLeadTime\Helper\Csv $csvHelper,
        \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder,
        \Riki\ShipLeadTime\Model\LeadtimeFactory $leadtimeFactory
    )
    {
        $this->leadTimeRepository = $leadtimeRepository;
        $this->logger = $logger;
        $this->csvHelper = $csvHelper;
        $this->criteriaBuilder = $searchCriteriaBuilder ;
        $this->shipLeadTimeFactory = $leadtimeFactory;
    }

    /**
     *
     */
    public function importData()
    {
        $csvData = $this->csvHelper->getCsvData();
        foreach($csvData as $data)
        {
            if(count($data)>2)
            {
                try{
                    $leadTime = $this->getSingleLeadTime($data);
                    if($leadTime)
                    {
                        $leadTime->setData('shipping_lead_time', $data['shipping_lead_time']);
                        $leadTime->setData('is_active', $data['is_active']);
                        $leadTime->setData('priority', $data['priority']);
                        $this->leadTimeRepository->save($leadTime);
                    }
                    else
                    {
                        $leadTime = $this->shipLeadTimeFactory->create();
                        $leadTime->setData('pref_id', $data['pref_id']);
                        $leadTime->setData('warehouse_id', $data['warehouse_id']);
                        $leadTime->setData('shipping_lead_time', $data['shipping_lead_time']);
                        $leadTime->setData('delivery_type_code', $data['delivery_type_code']);
                        $leadTime->setData('is_active', $data['is_active']);
                        $leadTime->setData('priority', $data['priority']);
                        $leadTime->save();
                    }
                }catch(\Exception $e){
                    $this->logger->info('Could not save shipping lead time: '.$e->getMessage());
                }
            }
        }
    }

    /**
     * @param array $dataRow
     * @return bool
     */
    public function getSingleLeadTime(array $dataRow)
    {
        $preId = $dataRow['pref_id'];
        $shipLeadTime = $dataRow['shipping_lead_time'];
        $warehouseId = $dataRow['warehouse_id'];
        $deliveryType = $dataRow['delivery_type_code'];
        $criterial = $this->criteriaBuilder->addFilter('pref_id', $preId)
                    ->addFilter('warehouse_id',$warehouseId)
                    ->addFilter('delivery_type_code', $deliveryType)
                    ->create();
        $collection = $this->leadTimeRepository->getList($criterial);
        if($collection->getTotalCount())
        {
            $items = $collection->getItems();
            foreach($items as $item)
            {
                return $item;
            }
        }
        return false;
    }
}