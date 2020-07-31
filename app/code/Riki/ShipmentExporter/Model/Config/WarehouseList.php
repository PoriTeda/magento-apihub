<?php
namespace Riki\ShipmentExporter\Model\Config;

use Magento\Framework\Data\OptionSourceInterface;

/**
 * Class WarehouseList
 * @package Riki\ShipmentExporter\Model\Config
 */
class WarehouseList extends \Magento\Framework\DataObject implements OptionSourceInterface
{
    /**
     * @var
     */
    protected $warehouseCollectionFactory;

    /**
     * WarehouseList constructor.
     * @param \Wyomind\PointOfSale\Model\ResourceModel\PointOfSale\CollectionFactory $collectionFactory
     * @param array $data
     */
    public function __construct(
        \Wyomind\PointOfSale\Model\ResourceModel\PointOfSale\CollectionFactory $collectionFactory,
        array $data = []
    ) {
        $this->warehouseCollectionFactory = $collectionFactory;
        parent::__construct($data);
    }
    /**
     * {@inheritdoc}
     */
    public function toOptionArray()
    {
        $warehouseCollection = $this->warehouseCollectionFactory->create();
        $options = [];
        if ($warehouseCollection->getItems()) {
            foreach ($warehouseCollection->getItems() as $wh) {
                $options[] = ['value' => $wh->getStoreCode(), 'label' => $wh->getName()];
            }
        }
        return $options;
    }
}
