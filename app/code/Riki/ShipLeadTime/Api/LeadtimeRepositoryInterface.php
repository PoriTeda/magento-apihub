<?php

namespace Riki\ShipLeadTime\Api;

interface LeadtimeRepositoryInterface
{
    public function save(\Riki\ShipLeadTime\Api\Data\LeadtimeInterface $leadTime);

    /**
     * Get info about lead time
     *
     * @param int|null $id
     *
     * @return \Riki\ShipLeadTime\Api\Data\LeadtimeInterface
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function get($id);

    /**
     * @param \Magento\Framework\Api\SearchCriteriaInterface $searchCriteria
     * @param bool $forEdit
     * @return \Magento\Framework\Api\SearchResultsInterface
     */
    public function getList(\Magento\Framework\Api\SearchCriteriaInterface $searchCriteria, $forEdit = false);

    public function delete(\Riki\ShipLeadTime\Api\Data\LeadtimeInterface $leadTime);

    public function deleteById($id);

    public function checkWarehouseIsValid($posCode, $prefecture,$deliveryType);
}
