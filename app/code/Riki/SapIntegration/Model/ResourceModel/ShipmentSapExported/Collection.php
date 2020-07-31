<?php

namespace Riki\SapIntegration\Model\ResourceModel\ShipmentSapExported;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use Riki\SapIntegration\Api\Data\ShipmentSapExportedSearchResultsInterface;

class Collection extends AbstractCollection implements ShipmentSapExportedSearchResultsInterface
{
    /**
     * @var string
     */
    protected $_idFieldName = 'shipment_entity_id';

    /**
     * @var \Magento\Framework\Api\SearchCriteriaInterface
     */
    protected $searchCriteria;

    /**
     * Initialization collection
     */
    protected function _construct()
    {
        $this->_init(
            \Riki\SapIntegration\Model\ShipmentSapExported::class,
            \Riki\SapIntegration\Model\ResourceModel\ShipmentSapExported::class
        );
    }

    /**
     * Get search criteria.
     *
     * @return \Magento\Framework\Api\SearchCriteriaInterface|null
     */
    public function getSearchCriteria()
    {
        return $this->searchCriteria;
    }

    /**
     * Set search criteria.
     *
     * @param \Magento\Framework\Api\SearchCriteriaInterface $searchCriteria
     * @return $this
     */
    public function setSearchCriteria(\Magento\Framework\Api\SearchCriteriaInterface $searchCriteria = null)
    {
        $this->searchCriteria = $searchCriteria;
        return $this;
    }

    /**
     * Get total count.
     *
     * @return int
     */
    public function getTotalCount()
    {
        return $this->getSize();
    }

    /**
     * Set total count.
     *
     * @param int $totalCount
     * @return $this
     */
    public function setTotalCount($totalCount)
    {
        return $this;
    }

    /**
     * Set items list.
     *
     * @param \Magento\Framework\Api\ExtensibleDataInterface[] $items
     * @return $this
     * @throws \Exception
     */
    public function setItems(array $items = null)
    {
        if (!$items) {
            return $this;
        }
        foreach ($items as $item) {
            $this->addItem($item);
        }
        return $this;
    }
}
