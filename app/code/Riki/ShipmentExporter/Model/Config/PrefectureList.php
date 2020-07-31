<?php

namespace Riki\ShipmentExporter\Model\Config;
use Magento\Framework\Data\OptionSourceInterface;

class PrefectureList extends \Magento\Framework\DataObject implements OptionSourceInterface
{
    /**
     * @var
     */
    protected $_regionCollectionFactory;

    /**
     * PrefectureList constructor.
     * @param \Magento\Directory\Model\ResourceModel\Region\CollectionFactory $regionCollectionFactory
     * @param array $data
     */
    public function __construct(
        \Magento\Directory\Model\ResourceModel\Region\CollectionFactory $regionCollectionFactory,
        array $data = []

    ) {
        $this->_regionCollectionFactory = $regionCollectionFactory;
        parent::__construct($data);
    }



    /**
     * {@inheritdoc}
     */
    public function toOptionArray()
    {
        $regionsCollection = $this->_regionCollectionFactory->create();
        $regionsCollection->addFieldToFilter('main_table.country_id','JP');
        return $regionsCollection->load()->toOptionArray();
    }

}
