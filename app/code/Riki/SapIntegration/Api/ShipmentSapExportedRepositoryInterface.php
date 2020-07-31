<?php
namespace Riki\SapIntegration\Api;

use Riki\SapIntegration\Api\Data\ShipmentSapExportedInterface;
use Magento\Framework\Api\SearchCriteriaInterface;

interface ShipmentSapExportedRepositoryInterface
{
    /**
     * @param \Riki\SapIntegration\Api\Data\ShipmentSapExportedInterface $object
     * @return \Riki\SapIntegration\Api\Data\ShipmentSapExportedInterface
     */
    public function save(ShipmentSapExportedInterface $object);

    /**
     * @param $id
     * @return \Riki\SapIntegration\Api\Data\ShipmentSapExportedInterface
     */
    public function getById($id);

    /**
     * @param SearchCriteriaInterface $criteria
     * @return \Riki\SapIntegration\Api\Data\ShipmentSapExportedSearchResultsInterface
     */
    public function getList(SearchCriteriaInterface $criteria);

    /**
     * @param \Riki\SapIntegration\Api\Data\ShipmentSapExportedInterface $object
     * @return bool
     */
    public function delete(ShipmentSapExportedInterface $object);

    /**
     * @param $id
     * @return bool
     */
    public function deleteById($id);
}
