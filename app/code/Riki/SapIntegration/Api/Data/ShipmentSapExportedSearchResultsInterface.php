<?php

namespace Riki\SapIntegration\Api\Data;

interface ShipmentSapExportedSearchResultsInterface extends \Magento\Framework\Api\SearchResultsInterface
{
    /**
     * Get Shipment Sap Exported list
     *
     * @return \Riki\SapIntegration\Api\Data\ShipmentSapExportedInterface[]
     */
    public function getItems();

    /**
     * Set Shipment Sap Exported list
     *
     * @param \Riki\SapIntegration\Api\Data\ShipmentSapExportedInterface[] $items
     * @return $this
     */
    public function setItems(array $items);
}
