<?php

namespace Riki\ShipLeadTime\Model;

class Leadtime extends \Magento\Framework\Model\AbstractModel implements \Riki\ShipLeadTime\Api\Data\LeadtimeInterface
{
    protected $_pointOfSaleCollection;
    protected $_shipLeadTimeHelper;

    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Riki\ShipLeadTime\Model\ResourceModel\Leadtime $resource,
        \Wyomind\PointOfSale\Model\ResourceModel\PointOfSale\CollectionFactory $collectionFactory,
        \Riki\ShipLeadTime\Helper\Data $shipLeadTimeHelper,
        \Riki\ShipLeadTime\Model\ResourceModel\Leadtime\Collection $resourceCollection
    )
    {
        $this->_pointOfSaleCollection = $collectionFactory;
        $this->_shipLeadTimeHelper = $shipLeadTimeHelper;
        parent::__construct($context, $registry, $resource, $resourceCollection);
    }

    /**
     * @return mixed
     */
    public function getIsActive()
    {
        return $this->_getData(self::IS_ACTIVE);
    }

    /**
     * @param $isActive
     * @return $this
     */
    public function setIsActive($isActive)
    {
        return $this->setData(self::IS_ACTIVE, $isActive);
    }

    /**
     * @return mixed
     */
    public function getDeliveryTypeCode()
    {
        return $this->_getData(self::DELIVERY_TYPE_CODE);
    }

    /**
     * @return mixed
     */
    public function getShippingLeadTime()
    {
        return $this->_getData(self::SHIPPING_LEAD_TIME);
    }

    public function getWareHouseOptions()
    {
        $result = array();
        $wareHouseCollection =  $this->_pointOfSaleCollection->create();
        foreach ($wareHouseCollection as $wareHouse) {
            $result[$wareHouse->getStoreCode()] = $wareHouse->getName();
        }
        return $result;
    }

    public function getAllJapanPrefecture()
    {
        $resultArray = $this->_shipLeadTimeHelper->getRegionArr();
        return $resultArray;
    }

    public function getDeliveryType()
    {
        return $this->_shipLeadTimeHelper->getAllDeliveryCollection();
    }
}